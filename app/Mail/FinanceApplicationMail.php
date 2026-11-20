<?php

namespace App\Mail;

use App\Models\FinanceApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Emails to a business about its finance application: its private link, receipt, a request for more, the decision. */
class FinanceApplicationMail extends Mailable
{
    public function __construct(public FinanceApplication $fa, public string $kind, public string $link)
    {
    }

    /** The branded spec (App\Support\Emails) for this kind of email. */
    public function spec(): array
    {
        $a = $this->fa;
        $first = \App\Support\Emails::firstName($a->contact_name);
        $amount = $a->amount ? $a->currency() . ' ' . number_format((float) $a->amount) : 'Not set yet';

        return match ($this->kind) {
            'submitted' => \App\Support\Emails::financeReceived($first, $a->businessName(), $a->reference, $amount, $this->link),
            'more_info' => \App\Support\Emails::financeMoreInfo($first, $a->reference, (string) $a->staff_note, $this->link),
            'approved' => \App\Support\Emails::financeApproved($first, $a->reference, $amount, (string) $a->staff_note, $this->link),
            'declined' => \App\Support\Emails::financeDeclined($first, $a->reference, (string) $a->staff_note, $this->link),
            default => \App\Support\Emails::financeLink($first, strtolower((string) $a->productConfig('label')), $a->businessName(), $a->reference, $this->link, (int) config('finance_journey.link_days', 30)),
        };
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->spec()['subject']);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.branded', text: 'emails.branded-text', with: ['m' => $this->spec()]);
    }
}
