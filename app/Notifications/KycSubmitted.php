<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject('We have your details (' . $this->reference . ')')
            ->greeting('Hi ' . strtok((string) $notifiable->name, ' ') . ',')
            ->line('Thanks for sending your verification details. Our team will check them and call your next of kin and guarantor, usually within one working day.')
            ->line('Your reference is ' . $this->reference . '. We will email you as soon as it is done.')
            ->action('View my account', route('account.home'))
            ->salutation('Aimzone Ventures');
    }
}
