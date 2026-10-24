<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Jobs\SendBatchItem;
use App\Models\Loan;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Messaging\CollectionNotices;
use App\Support\Emails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Email and SMS manager (owner, 23 Oct 2026). Reminders and collection notices go out in batches a person checks
 * and sends: pick the kind, untick anyone who shouldn't get it, read the exact email, then send. Every batch and
 * every recipient's result is kept. Also shows whether email, SMS and the queue are set up to deliver.
 */
class MessagesController extends Controller
{
    private const ROLES = ['super-admin', 'admin', 'manager'];

    private function guard(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(self::ROLES), 403, 'Messages are for managers and admins.');
    }

    public function index(Request $request, CollectionNotices $notices)
    {
        $this->guard($request);
        $counts = Cache::remember('messages:counts:' . now()->format('YmdH'), 600, fn () => collect(CollectionNotices::KINDS)
            ->map(fn ($k, $kind) => $notices->candidates($kind)->reject(fn ($r) => $r->skip)->count())->all());
        $batches = DB::table('message_batches as b')->leftJoin('users as c', 'c.id', '=', 'b.created_by')->leftJoin('users as s', 's.id', '=', 'b.sent_by')
            ->orderByDesc('b.id')->limit(200)->get(['b.*', 'c.name as created_by_name', 's.name as sent_by_name']);

        return view('staff.messages.index', ['counts' => $counts, 'batches' => $batches, 'health' => $this->health()]);
    }

    /** The recipients a new batch would go to, ticked, with the reason anyone is left out. */
    public function create(Request $request, CollectionNotices $notices)
    {
        $this->guard($request);
        $kind = (string) $request->query('kind', 'overdue');
        abort_unless(isset(CollectionNotices::KINDS[$kind]), 404);
        $days = max(0, min(14, (int) $request->query('days', 3)));
        $forbearance = $request->boolean('forbearance');
        $rows = $notices->candidates($kind, $days, $forbearance);

        return view('staff.messages.create', compact('kind', 'days', 'forbearance', 'rows') + ['health' => $this->health()]);
    }

    /** The exact email one borrower would get, for the preview frame. */
    public function preview(Request $request, CollectionNotices $notices)
    {
        $this->guard($request);
        $kind = (string) $request->query('kind');
        abort_unless(isset(CollectionNotices::KINDS[$kind]), 404);
        if (in_array($kind, CollectionNotices::PERSON_KINDS, true)) {
            $user = User::with('borrower')->findOrFail((int) $request->query('loan'));
            $missing = $user->borrower?->missing() ?: ['income type', 'net income', 'employer or business', 'pay schedule'];
            $sms = $notices->personSms($kind, $user, $missing);

            return $request->boolean('sms') ? response()->json(['sms' => $sms, 'length' => mb_strlen($sms)])
                : response((string) Emails::mail($notices->personEmail($kind, $user, $missing))->render())->header('Content-Type', 'text/html; charset=utf-8')->header('X-Frame-Options', 'SAMEORIGIN');
        }
        $loan = Loan::with(['user', 'loanType:id,name'])->findOrFail((int) $request->query('loan'));
        $row = $notices->current($kind, $loan, 30) ?? (object) ['owed' => 0.0, 'days_late' => 0, 'due_date' => now()];
        $email = $notices->email($kind, $loan->user, $loan, $row->owed, $row->days_late, $row->due_date);
        $sms = $notices->sms($kind, $loan, $row->owed, $row->days_late, $row->due_date);
        $html = (string) Emails::mail($email)->render();

        return $request->boolean('sms') ? response()->json(['sms' => $sms, 'length' => mb_strlen($sms)])
            : response($html)->header('Content-Type', 'text/html; charset=utf-8')->header('X-Frame-Options', 'SAMEORIGIN');
    }

    /** Saves a draft batch from the ticked loans. Nothing is sent yet. */
    public function store(Request $request, CollectionNotices $notices)
    {
        $this->guard($request);
        $data = $request->validate([
            'kind' => 'required|in:' . implode(',', array_keys(CollectionNotices::KINDS)),
            'loans' => 'required|array|min:1|max:2000',
            'loans.*' => 'integer',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,sms',
            'note' => 'nullable|string|max:500',
        ]);
        $person = in_array($data['kind'], CollectionNotices::PERSON_KINDS, true);
        $loans = $person ? collect() : Loan::with(['user:id,name,email,phone', 'loanType:id,name'])->whereIn('id', $data['loans'])->get();
        $people = $person ? $notices->people($data['kind'], 0)->whereIn('key', array_map('intval', $data['loans'])) : collect();
        $id = DB::transaction(function () use ($data, $loans, $people, $notices, $request) {
            $id = DB::table('message_batches')->insertGetId([
                'kind' => $data['kind'], 'channels' => implode(',', array_unique($data['channels'])), 'status' => 'draft',
                'subject' => CollectionNotices::subject($data['kind']), 'note' => $data['note'] ?? null,
                'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $n = 0;
            foreach ($people as $p) {
                DB::table('message_batch_items')->insert(['batch_id' => $id, 'user_id' => $p->user->id, 'loan_id' => null, 'owed' => 0, 'days_late' => 0,
                    'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
                $n++;
            }
            foreach ($loans as $loan) {
                $row = $notices->current($data['kind'], $loan, 30);
                if (! $row) {
                    continue;
                }
                DB::table('message_batch_items')->insert(['batch_id' => $id, 'user_id' => $loan->user_id, 'loan_id' => $loan->id,
                    'owed' => $row->owed, 'days_late' => $row->days_late, 'due_date' => $row->due_date->toDateString(),
                    'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
                $n++;
            }
            DB::table('message_batches')->where('id', $id)->update(['recipients' => $n]);

            return $id;
        });
        AuditTrail::record('message_batches', $id, 'create', null, ['kind' => $data['kind'], 'recipients' => count($data['loans'])]);

        return redirect()->route('staff.messages.show', $id)->with('success', 'Draft saved. Read the email, then send it.');
    }

    public function show(Request $request, int $batch)
    {
        $this->guard($request);
        $b = DB::table('message_batches as b')->leftJoin('users as c', 'c.id', '=', 'b.created_by')->leftJoin('users as s', 's.id', '=', 'b.sent_by')
            ->where('b.id', $batch)->first(['b.*', 'c.name as created_by_name', 's.name as sent_by_name']);
        abort_unless($b, 404);
        $items = DB::table('message_batch_items as i')->join('users as u', 'u.id', '=', 'i.user_id')->where('i.batch_id', $batch)
            ->orderBy('i.id')->get(['i.*', 'u.name', 'u.email', 'u.phone']);

        return view('staff.messages.show', ['b' => $b, 'items' => $items, 'health' => $this->health()]);
    }

    /** The person pressing Send is the approval: each recipient is queued and sent with today's balance. */
    public function send(Request $request, int $batch)
    {
        $this->guard($request);
        $request->validate(['confirm' => 'accepted']);
        $b = DB::table('message_batches')->find($batch);
        abort_unless($b && $b->status === 'draft', 409, 'Only a draft can be sent.');
        if (str_contains($b->channels, 'sms')) {
            $hour = (int) now('Africa/Nairobi')->format('G');
            if ($hour >= 20 || $hour < 8) {
                return back()->with('error', 'SMS are not sent between 20:00 and 08:00. Send the email-only part now, or send this batch in the morning.');
            }
        }
        DB::table('message_batches')->where('id', $batch)->update(['status' => 'sending', 'sent_by' => $request->user()->id, 'sent_at' => now(), 'updated_at' => now()]);
        AuditTrail::record('message_batches', $batch, 'update', ['status' => 'draft'], ['status' => 'sending', 'sent_by' => $request->user()->id]);
        DB::table('message_batch_items')->where('batch_id', $batch)->where('status', 'pending')->pluck('id')
            ->each(fn ($id) => SendBatchItem::dispatch((int) $id));

        return redirect()->route('staff.messages.show', $batch)->with('success', 'Sending. Results fill in below as each message goes out.');
    }

    public function cancel(Request $request, int $batch)
    {
        $this->guard($request);
        $b = DB::table('message_batches')->find($batch);
        abort_unless($b && in_array($b->status, ['draft', 'sending'], true), 409);
        DB::table('message_batches')->where('id', $batch)->update(['status' => 'cancelled', 'updated_at' => now()]);
        DB::table('message_batch_items')->where('batch_id', $batch)->where('status', 'pending')->update(['status' => 'skipped', 'error' => 'batch cancelled', 'updated_at' => now()]);
        AuditTrail::record('message_batches', $batch, 'update', ['status' => $b->status], ['status' => 'cancelled']);

        return redirect()->route('staff.messages.index')->with('success', 'Batch cancelled. Nothing more will be sent from it.');
    }

    /** Is email, SMS and the queue set up to deliver? Settings names and plain values only, never keys. */
    private function health(): array
    {
        $mailer = (string) config('mail.default');
        $sms = (string) config('lending.notifications.sms.driver', 'log');
        $queue = (string) config('queue.default');

        return [
            'mail' => ['ok' => ! in_array($mailer, ['log', 'array'], true), 'value' => $mailer, 'from' => config('mail.from.address'),
                'note' => in_array($mailer, ['log', 'array'], true) ? 'Emails are written to the log, not sent. Set MAIL_MAILER=smtp with the mail account.' : 'Sending through ' . strtoupper($mailer) . '. SPF and DKIM must pass or Gmail files them as spam.'],
            'sms' => ['ok' => $sms !== 'log', 'value' => $sms,
                'note' => $sms === 'log' ? "SMS are written to the log only. Set SMS_DRIVER=kenyasms with the KenyaSMS account." : ($sms === 'kenyasms' ? 'Sending through KenyaSMS.' : "Sending through Africa's Talking" . (config('lending.notifications.sms.africastalking.username') === 'sandbox' ? ' sandbox (not delivered to phones).' : '.'))],
            'queue' => ['ok' => $queue !== 'sync' || app()->environment('local', 'testing'), 'value' => $queue,
                'pending' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
                'failed' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
                'note' => $queue === 'sync' ? 'Messages send while you wait.' : 'The scheduler sends queued messages every minute (needs the cron line).'],
            'auto' => ['ok' => (bool) config('lending.notifications.enabled'), 'value' => config('lending.notifications.enabled') ? 'on' : 'off',
                'note' => config('lending.notifications.enabled') ? 'Approved, money sent, payment received and due reminders go out by themselves.' : 'Automatic notices (approved, money sent, payment received, due reminders) are off: NOTIFICATIONS_ENABLED.'],
            'recent' => Schema::hasTable('notifications') ? DB::table('notifications as n')->leftJoin('users as u', 'u.id', '=', 'n.notifiable_id')
                ->orderByDesc('n.created_at')->limit(15)->get(['n.data', 'n.created_at', 'u.name', 'u.id as user_id']) : collect(),
        ];
    }
}
