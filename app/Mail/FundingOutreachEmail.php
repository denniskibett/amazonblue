<?php

namespace App\Mail;

use App\Models\Funding\FundingEmail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * A funding cold email (20 Nov 2026): from sales@sharet.africa as "<manager> · Aimzone Ventures", with the AIM-E-
 * reference in the subject so replies to the shared inbox sort to AimZone. One-click opt-out (RFC 8058) in the headers
 * and in the footer. Plain and personal, a tracking image and one tracked link.
 */
class FundingOutreachEmail extends Mailable
{
    public function __construct(public FundingEmail $email, public User $manager)
    {
    }

    public function envelope(): Envelope
    {
        $from = config('funding_crm.from');

        return new Envelope(
            from: new Address($from['address'], $this->manager->name . ' · ' . $from['name']),
            replyTo: [new Address($from['address'], $from['name'])],
            subject: $this->email->subject . ' [' . $this->email->reference . ']',
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<' . route('funding.optout', $this->email->track) . '>, <mailto:' . config('funding_crm.from.address') . '?subject=unsubscribe%20' . $this->email->reference . '>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'X-Aimzone-Ref' => $this->email->reference,
        ]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.funding-outreach', text: 'emails.funding-outreach-text', with: [
            'paragraphs' => preg_split("/\n{2,}/", trim($this->email->body)),
            'applyUrl' => route('funding.click', $this->email->track),
            'pixelUrl' => route('funding.open', $this->email->track),
            'optOutUrl' => route('funding.optout', $this->email->track),
        ]);
    }
}
