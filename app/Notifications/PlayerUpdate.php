<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Generic player notification — stored in-app and (optionally) emailed. */
class PlayerUpdate extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $kind = 'info',   // info | success | warning | danger
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (Setting::get('email_notifications') === '1' && config('mail.default') !== null) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message)
            ->line('Open the '.Setting::get('app_name').' app to see full details.');
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message, 'kind' => $this->kind];
    }
}
