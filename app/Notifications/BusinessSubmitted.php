<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject('We have your business details (' . $this->reference . ')')
            ->greeting('Hi ' . strtok((string) $notifiable->name, ' ') . ',')
            ->line('Thanks for sending the details of ' . $this->name . '. Our team will check the documents and may call you to confirm, usually within two working days.')
            ->line('Your reference is ' . $this->reference . '. We will email you as soon as it is done.')
            ->action('View my business', route('account.business.show', $this->businessId))
            ->salutation('Aimzone Ventures');
    }
}
