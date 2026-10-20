<?php

namespace App\Notifications;

use App\Models\Loan;
use App\Support\Emails;
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

        return Emails::mail(Emails::applicationReceived(Emails::firstName($notifiable->name),
            'KES ' . number_format((float) $this->loan->amount, 2), 'Loan #' . $number, route('account.loan', $this->loan)));
    }
}
