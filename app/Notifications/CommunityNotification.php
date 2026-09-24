<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommunityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $message, public string $url, public string $kind)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        $channels = [];
        if (config('folkscript.broadcast_notifications', false)) { $channels[] = 'broadcast'; }
        if (config('folkscript.mail_notifications', false) && $notifiable->newsletter_enabled && $notifiable->hasVerifiedEmail()) { $channels[] = 'mail'; }
        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => $this->message, 'title' => $this->message, 'url' => $this->url, 'type' => $this->kind];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($notifiable->suspended_at) { return false; }
        return $channel !== 'mail' || ($notifiable->newsletter_enabled && $notifiable->hasVerifiedEmail() && config('folkscript.mail_notifications', false));
    }

    public function toDatabase(object $notifiable): array { return $this->toArray($notifiable); }
    public function toBroadcast(object $notifiable): BroadcastMessage { return new BroadcastMessage($this->toArray($notifiable)); }
    public function broadcastType(): string { return 'folkscript.community'; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('A new connection on Folkscript')->greeting('Hello '.$notifiable->name.',')->line($this->message)->action('Join the conversation', url($this->url))->line('Manage email preferences in your Folkscript account settings.');
    }
}
