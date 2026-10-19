<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the borrower: their business was approved, or needs changes (with the reviewer's note).
 */
class BusinessReviewed extends Notification
{
    public function __construct(public int $businessId, public string $name, public bool $approved, public ?string $note = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hi ' . strtok((string) $notifiable->name, ' ') . ',')->salutation('Aimzone Ventures');

        if ($this->approved) {
            return $mail->subject($this->name . ' is verified')
                ->line('Good news: ' . $this->name . ' is verified and linked to your account.')
                ->line('Business loans and trade finance will appear in your account as soon as they open.')
                ->action('View my business', route('account.business.show', $this->businessId));
        }

        return $mail->subject($this->name . ': your details need a quick update')
            ->line('We could not verify ' . $this->name . ' yet. Here is what our team asked for:')
            ->line('"' . $this->note . '"')
            ->line('Update the details in your account and send them again; we will review them straight away.')
            ->action('Update my business', route('account.business.review', $this->businessId));
    }
}
