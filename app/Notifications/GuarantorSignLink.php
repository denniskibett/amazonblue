<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To a guarantor: read and sign the guarantee for a loan (owner, 21 Nov 2026). Sent to an address, not an account. */
class GuarantorSignLink extends Notification
{
    public function __construct(public string $name, public string $borrower, public float $amount, public string $url, public string $expires)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Please sign as guarantor for ' . $this->borrower)
            ->greeting('Hello ' . $this->name . ',')
            ->line($this->borrower . ' named you as guarantor for a loan of KES ' . number_format($this->amount, 2) . ' from Aimzone Ventures. We pay it out once you sign.')
            ->line('It takes about three minutes on your phone: read the guarantee, add your details and a photo of your ID, and sign on screen.')
            ->action('Read and sign', $this->url)
            ->line('The link works until ' . $this->expires . '. If you do not know ' . $this->borrower . ' or did not agree to this, ignore this email and tell us.');
    }
}
