<?php

namespace App\Services\Funding;

use App\Mail\FundingOutreachEmail;
use App\Models\Funding\FundingCampaign;
use App\Models\Funding\FundingEmail;
use App\Models\Funding\FundingProspect;
use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Sends one funding cold email from sales@sharet.africa, signed by the account manager, with an AIM-E- reference in
 * the subject (the sender is shared with scharge and Breve). Logs it on the prospect, books the follow-up call and
 * moves New → Contacted. Never sends to a suppressed or opted-out address.
 */
class Outreach
{
    public function send(FundingProspect $prospect, User $sender, string $subject, string $body, ?FundingCampaign $campaign = null): FundingEmail
    {
        $to = strtolower(trim((string) $prospect->email));
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException($prospect->name . ' has no valid email address.');
        }
        if ($prospect->suppressed()) {
            throw new \DomainException($to . ' asked not to be emailed (or bounced). It is on the suppression list.');
        }
        $manager = $prospect->owner ?? $sender;
        $subject = trim(preg_replace('/\s*\[AIM-E-\d+\]\s*$/', '', $prospect->merge($subject, $manager)));
        $body = $prospect->merge($body, $manager);

        $email = FundingEmail::create(['prospect_id' => $prospect->id, 'campaign_id' => $campaign?->id, 'reference' => 'tmp-' . Str::random(16),
            'track' => Str::random(32), 'to' => $to, 'subject' => $subject, 'body' => $body, 'sent_by' => $sender->id]);
        $email->update(['reference' => 'AIM-E-' . str_pad((string) $email->id, 6, '0', STR_PAD_LEFT)]);

        try {
            $sent = Mail::to($to)->send(new FundingOutreachEmail($email, $manager));
        } catch (\Throwable $e) {
            $email->delete();
            throw $e;
        }
        $email->update(['sent_at' => now(), 'message_id' => $sent?->getMessageId()]);

        if (! $prospect->owner_id) {
            $prospect->owner_id = $sender->id;
        }
        $prospect->last_emailed_at = now();
        $prospect->save();
        $prospect->activities()->create(['user_id' => $campaign ? null : $sender->id, 'type' => 'email', 'title' => 'Cold email ' . $email->reference . ': ' . $subject,
            'body' => $body, 'done_at' => now()]);
        $prospect->activities()->create(['user_id' => $manager->id, 'type' => 'follow_up', 'title' => 'Call ' . $prospect->name . ' about the cold email',
            'body' => 'Emailed ' . $to . ' on ' . now()->format('j M') . ($campaign ? ' (' . $campaign->reference . ')' : '') . '. Check if they opened it first.',
            'due_at' => now()->addWeekdays((int) config('funding_crm.follow_up_days', 3))->setTime(10, 0)]);
        if ($prospect->stage === 'new') {
            $prospect->setStage('contacted', $campaign ? null : $sender, $campaign ? $campaign->reference : null);
        }
        AuditTrail::record('funding_emails', $email->id, 'create', null, ['reference' => $email->reference, 'prospect' => $prospect->reference, 'by' => $sender->id]);

        return $email;
    }

    /** Prospects a campaign may email: a valid email, not suppressed or opted out, never emailed, still New. */
    public static function eligible(?string $segment = null): Builder
    {
        return FundingProspect::query()
            ->where('stage', 'new')->whereNull('opted_out_at')->whereNull('last_emailed_at')
            ->whereNotNull('email')->where('email', 'like', '%_@_%._%')
            ->whereNotIn('email', fn ($q) => $q->select('email')->from('funding_suppressions'))
            ->when($segment, fn ($q) => $q->where('segment', $segment));
    }

    /** @return array{sent: int, failed: array<string, string>} */
    public function runCampaign(FundingCampaign $campaign, User $by, ?int $limit = null): array
    {
        $limit = min($limit ?? (int) config('funding_crm.batch_limit', 40), (int) config('funding_crm.batch_limit', 40));
        $campaign->update(['status' => 'sending']);
        $sent = 0;
        $failed = [];
        foreach (self::eligible($campaign->segment)->with('owner')->orderBy('id')->limit($limit)->get() as $p) {
            try {
                $this->send($p, $by, $campaign->subject, $campaign->body, $campaign);
                $sent++;
            } catch (\Throwable $e) {
                $failed[$p->reference] = $e->getMessage();
            }
        }
        $campaign->update(['sent_count' => $campaign->sent_count + $sent, 'status' => self::eligible($campaign->segment)->exists() ? 'sending' : 'done']);

        return ['sent' => $sent, 'failed' => $failed];
    }
}
