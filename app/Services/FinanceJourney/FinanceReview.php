<?php

namespace App\Services\FinanceJourney;

use App\Mail\FinanceApplicationMail;
use App\Models\FinanceApplication;
use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Support\Facades\Mail;

/**
 * Staff decisions on a finance application (20 Nov 2026). In review → ask for more (back to the applicant at a step),
 * approve in principle (subject to final terms, security and a signed agreement), or decline with a reason. Each
 * decision is logged on the application and emailed to the applicant with a fresh link.
 */
class FinanceReview
{
    public const DECISIONS = ['in_review' => 'Start review', 'more_info' => 'Ask for more', 'approved' => 'Approve in principle', 'declined' => 'Decline'];

    public function __construct(private Journey $journey)
    {
    }

    public function decide(FinanceApplication $app, string $decision, User $by, ?string $note = null, ?string $backTo = null): void
    {
        $allowed = [
            'in_review' => ['submitted'],
            'more_info' => ['submitted', 'in_review'],
            'approved' => ['submitted', 'in_review'],
            'declined' => ['submitted', 'in_review', 'more_info'],
        ];
        if (! isset($allowed[$decision]) || ! in_array($app->status, $allowed[$decision], true)) {
            throw new \DomainException($app->reference . ' is ' . strtolower($app->statusLabel()) . '; that decision does not apply.');
        }
        if (in_array($decision, ['more_info', 'declined'], true) && blank($note)) {
            throw new \DomainException('Write what the applicant should know.');
        }
        $before = $app->status;
        $app->status = $decision;
        $app->reviewer_id = $by->id;
        $app->reviewed_at = now();
        $app->staff_note = $decision === 'in_review' ? $app->staff_note : $note;
        if ($decision === 'more_info' && $backTo && array_key_exists($backTo, config('finance_journey.steps'))) {
            $app->steps_done = array_values(array_diff($app->steps_done ?? [], [$backTo, 'review']));
        } elseif ($decision === 'more_info') {
            $app->steps_done = array_values(array_diff($app->steps_done ?? [], ['review']));
        }
        $app->log($decision, $note, $by);
        $app->save();
        AuditTrail::record('finance_applications', $app->id, 'update', ['status' => $before], ['status' => $decision, 'by' => $by->id]);

        if ($decision !== 'in_review') {
            $this->tell($app, $decision);
        }
        if ($app->prospect && in_array($decision, ['approved', 'declined'], true)) {
            $app->prospect->setStage($decision === 'approved' ? 'approved' : 'lost', $by, $app->reference . ': ' . $app->statusLabel());
        }
    }

    public function tell(FinanceApplication $app, string $kind): void
    {
        $token = $this->journey->rotate($app);
        Mail::to($app->email)->send(new FinanceApplicationMail($app, $kind, route('finance.show', $token)));
    }
}
