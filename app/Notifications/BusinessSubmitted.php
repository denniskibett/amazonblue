<?php

namespace App\Notifications;

use App\Support\Emails;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the borrower: we have their business details and will review them.
 */
class BusinessSubmitted extends Notification
{
    public function __construct(public int $businessId, public string $name, public string $reference)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return Emails::mail(Emails::kybSubmitted($this->name, $this->reference, route('account.business.show', $this->businessId)));
    }
}
