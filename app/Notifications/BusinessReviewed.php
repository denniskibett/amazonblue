<?php

namespace App\Notifications;

use App\Support\Emails;
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
        if ($this->approved) {
            return Emails::mail(Emails::kybApproved($this->name, route('account.business.show', $this->businessId)));
        }

        return Emails::mail(Emails::kycActionNeeded(Emails::firstName($notifiable->name), (string) $this->note,
            route('account.business.review', $this->businessId), 'the details of ' . $this->name));
    }
}
