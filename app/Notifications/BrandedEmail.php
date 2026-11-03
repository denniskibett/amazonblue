<?php

namespace App\Notifications;

use App\Support\Emails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** One branded email built from a Support\Emails template array (money sent, payment received). Queued. */
class BrandedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public array $message)
    {
    }

    public function via(object $notifiable): array
    {
        return empty($notifiable->email) ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return Emails::mail($this->message);
    }
}
