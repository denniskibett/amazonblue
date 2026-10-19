<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->line)
            ->action($this->actionText, $this->actionUrl)
            ->salutation('Aimzone Ventures system');
    }
}
