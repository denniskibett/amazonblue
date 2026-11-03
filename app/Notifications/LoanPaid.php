<?php

namespace App\Notifications;

use App\Support\Emails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $firstName,
        private readonly string $reference,
        private readonly string $total,
        private readonly string $date,
        private readonly bool $onTime,
        private readonly string $url,
    ) {
    }

    public function via(object $notifiable): array
    {
        return empty($notifiable->email) ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return Emails::mail(Emails::facilityClosed(
            $this->firstName,
            $this->reference,
            $this->total,
            $this->date,
            $this->onTime,
            $this->url,
        ));
    }
}