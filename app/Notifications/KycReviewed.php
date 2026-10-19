<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the borrower: their verification was approved (they can borrow) or needs changes (with the reviewer's note).
 */
class KycReviewed extends Notification
{
    public function __construct(public bool $approved, public ?string $note = null)
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
            return $mail->subject('You are verified')
                ->line('Good news: your details are verified. You can now apply for a loan from your account.')
                ->action('Borrow now', route('account.borrow'));
        }

        return $mail->subject('Your details need a quick update')
            ->line('We could not verify your details yet. Here is what our team asked for:')
            ->line('"' . $this->note . '"')
            ->line('Update them in your account and send them again; we will review them straight away.')
            ->action('Update my details', route('kyc.review'));
    }
}
