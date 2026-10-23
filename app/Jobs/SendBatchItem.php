<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Models\User;
use App\Notifications\CollectionNotice;
use App\Services\Messaging\CollectionNotices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sends one recipient of an approved message batch and records the result (owner, 23 Oct 2026). The wording is
 * built when it sends, so the amount is today's balance; a loan cleared since the batch was made is skipped.
 */
class SendBatchItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(public int $itemId)
    {
    }

    public function handle(CollectionNotices $notices): void
    {
        $item = DB::table('message_batch_items')->find($this->itemId);
        if (! $item || $item->status !== 'pending') {
            return;
        }
        $batch = DB::table('message_batches')->find($item->batch_id);
        $user = User::find($item->user_id);
        $loan = $item->loan_id ? Loan::with('loanType:id,name')->find($item->loan_id) : null;
        $mark = function (string $status, ?string $error = null) use ($item, $batch) {
            DB::table('message_batch_items')->where('id', $item->id)->update(['status' => $status, 'error' => $error ? mb_substr($error, 0, 250) : null,
                'sent_at' => $status === 'sent' ? now() : null, 'updated_at' => now()]);
            if ($batch && in_array($status, ['sent', 'failed'], true)) {
                DB::table('message_batches')->where('id', $batch->id)->increment($status);
            }
            if ($batch) {
                $this->closeIfDone($batch->id);
            }
        };
        // a notice about the person, not a loan (complete your profile)
        if ($batch && $user && in_array($batch->kind, CollectionNotices::PERSON_KINDS, true) && $batch->status !== 'cancelled') {
            $missing = $user->borrower?->missing() ?? ['income type', 'net income', 'employer or business', 'pay schedule'];
            if (! $missing) {
                $mark('skipped', 'profile already complete');

                return;
            }
            try {
                $user->notifyNow(new CollectionNotice($batch->kind, $notices->personEmail($batch->kind, $user, $missing),
                    $notices->personSms($batch->kind, $user, $missing), explode(',', $batch->channels), route('account.details')));
                $mark('sent');
            } catch (\Throwable $e) {
                report($e);
                $mark('failed', $e->getMessage());
            }

            return;
        }
        if (! $batch || $batch->status === 'cancelled' || ! $user || ! $loan) {
            $mark('skipped', 'batch cancelled, or the person or loan is gone');

            return;
        }

        // today's balance, not the one when the batch was drafted
        $row = $notices->current($batch->kind, $loan);
        if (! $row) {
            $mark('skipped', 'no longer owing for this notice');

            return;
        }
        try {
            $email = $notices->email($batch->kind, $user, $loan, $row->owed, $row->days_late, $row->due_date);
            $sms = $notices->sms($batch->kind, $loan, $row->owed, $row->days_late, $row->due_date);
            $user->notifyNow(new CollectionNotice($batch->kind, $email, $sms, explode(',', $batch->channels), route('account.loan', $loan)));
            DB::table('message_batch_items')->where('id', $item->id)->update(['owed' => $row->owed, 'days_late' => $row->days_late]);
            $mark('sent');
        } catch (\Throwable $e) {
            report($e);
            $mark('failed', $e->getMessage());
        }
    }

    private function closeIfDone(int $batchId): void
    {
        if (! DB::table('message_batch_items')->where('batch_id', $batchId)->where('status', 'pending')->exists()) {
            DB::table('message_batches')->where('id', $batchId)->where('status', 'sending')->update(['status' => 'sent', 'updated_at' => now()]);
        }
    }
}
