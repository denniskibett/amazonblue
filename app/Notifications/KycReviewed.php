<?php

namespace App\Notifications;

use App\Support\Emails;
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
        $first = Emails::firstName($notifiable->name);

        return Emails::mail($this->approved ? Emails::kycApproved($first) : Emails::kycActionNeeded($first, (string) $this->note));
    }
}
