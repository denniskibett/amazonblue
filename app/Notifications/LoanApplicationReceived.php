<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the borrower: their self-service application reached us.
 */
class LoanApplicationReceived extends Notification
{
    public function __construct(public Loan $loan)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $number = str_pad((string) $this->loan->id, 5, '0', STR_PAD_LEFT);

        return (new MailMessage)
            ->subject('Application received: loan #' . $number)
            ->greeting('Hi ' . strtok((string) $notifiable->name, ' ') . ',')
            ->line('We have your application for KES ' . number_format((float) $this->loan->amount, 2) . ' (loan #' . $number . ').')
            ->line('Our team will review it shortly. Once approved, the money is sent to your M-Pesa and we will let you know.')
            ->action('View my loan', route('account.loan', $this->loan))
            ->salutation('Aimzone Ventures');
    }
}
