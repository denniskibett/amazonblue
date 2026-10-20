<?php

namespace App\Notifications;

use App\Support\Emails;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Short heads-up to admins (new KYC to review, new application) with a link to act on it.
 */
class StaffAlert extends Notification
{
    public function __construct(public string $subject, public string $line, public string $actionText, public string $actionUrl)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return Emails::mail(Emails::staffAlert($this->subject, $this->line, $this->actionText, $this->actionUrl));
    }
}
