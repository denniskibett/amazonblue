<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use App\Support\Emails;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One collection notice from an approved batch (owner, 23 Oct 2026): the branded email and, when the batch asks
 * for it, the SMS. Stored in the borrower's notification centre too. Sent by Jobs\SendBatchItem, which records
 * the result on the batch item.
 */
class CollectionNotice extends Notification
{
    public function __construct(public string $kind, public array $email, public string $sms, public array $channels, public ?string $url = null)
    {
    }

    public function via(object $notifiable): array
    {
        return array_values(array_filter([
            'database',
            in_array('email', $this->channels, true) && ! empty($notifiable->email) ? 'mail' : null,
            in_array('sms', $this->channels, true) && ! empty($notifiable->phone) ? SmsChannel::class : null,
        ]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return Emails::mail($this->email);
    }

    public function toSms(object $notifiable): string
    {
        return $this->sms;
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => 'collection_' . $this->kind, 'title' => $this->email['subject'], 'body' => implode(' ', $this->email['paragraphs'] ?? []), 'url' => $this->url];
    }
}
