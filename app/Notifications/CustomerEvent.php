<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One customer event (Stage 4 Phase 6): stored for the notification centre, emailed, and sent by SMS.
 * The copy comes from Services\CustomerEvents. Queued; failed SMS are retried, then kept in failed_jobs.
 */
class CustomerEvent extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public string $event, public string $title, public string $body, public string $sms, public ?string $url = null)
    {
    }

    public function via(object $notifiable): array
    {
        return array_values(array_filter([
            'database',
            ! empty($notifiable->email) ? 'mail' : null,
            ! empty($notifiable->phone) ? SmsChannel::class : null,
        ]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = (new MailMessage)->subject($this->title)->greeting('Hello ' . strtok((string) ($notifiable->name ?? ''), ' ') . ',')->line($this->body);

        return $this->url ? $m->action('Open your account', $this->url) : $m;
    }

    public function toSms(object $notifiable): string
    {
        return $this->sms;
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => $this->event, 'title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
