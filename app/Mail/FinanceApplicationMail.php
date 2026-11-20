<?php

namespace App\Mail;

use App\Models\FinanceApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Emails to a business about its finance application: its private link, receipt, a request for more, the decision. */
class FinanceApplicationMail extends Mailable
{
    public const SUBJECTS = [
        'link' => 'Your application link',
        'submitted' => 'We have your application',
        'more_info' => 'We need something more for your application',
        'approved' => 'Your application is approved in principle',
        'declined' => 'An update on your application',
    ];

    public function __construct(public FinanceApplication $fa, public string $kind, public string $link)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: (self::SUBJECTS[$this->kind] ?? 'Your application') . ' · ' . $this->fa->reference);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.finance-application-text');
    }
}
