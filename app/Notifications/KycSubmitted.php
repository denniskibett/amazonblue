<?php

namespace App\Notifications;

use App\Support\Emails;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the borrower: we have their verification details and will review them.
 */
class KycSubmitted extends Notification
{
    public function __construct(public string $reference)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return Emails::mail(Emails::kycSubmitted(Emails::firstName($notifiable->name), $this->reference, now()->format('j M Y')));
    }
}
