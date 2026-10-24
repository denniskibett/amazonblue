<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To a director: verify yourself for a business's loans (owner, 24 Oct 2026). Sent to an address, not an account. */
class DirectorVerificationLink extends Notification
{
    public function __construct(public string $name, public string $business, public string $url, public string $expires)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Please verify yourself for ' . $this->business)
            ->greeting('Hello ' . $this->name . ',')
            ->line($this->business . ' listed you as a director and is applying to borrow from Aimzone Ventures. We verify every director before a business loan.')
            ->line('It takes about three minutes: confirm your ID number and take photos of your ID and your face.')
            ->action('Verify yourself', $this->url)
            ->line('The link works until ' . $this->expires . '. If you are not a director of ' . $this->business . ', ignore this email.');
    }
}
