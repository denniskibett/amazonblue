<?php

namespace App\Notifications\Channels;

use App\Services\Sms\SmsSender;
use Illuminate\Notifications\Notification;

/** Laravel notification channel for SMS: the notification provides toSms($notifiable) (Stage 4 Phase 6). */
class SmsChannel
{
    public function __construct(private SmsSender $sender)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        $text = method_exists($notification, 'toSms') ? $notification->toSms($notifiable) : null;
        $phone = $notifiable->routeNotificationFor('sms', $notification) ?? ($notifiable->phone ?? null);
        if ($text && $phone && ! $this->sender->send((string) $phone, $text)) {
            throw new \RuntimeException('SMS not accepted'); // the queue retries it, then it lands in failed_jobs
        }
    }
}
