<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UserInvitation extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'auth.set-password.show',
            Carbon::now()->addHours(24),
            ['id' => $notifiable->id]
        );

        return (new MailMessage)
            ->subject(__('You have been invited to :app', ['app' => config('app.name')]))
            ->greeting(__('Hello,'))
            ->line(__('You have been invited to join :app. Click the button below to choose a password and activate your account.', ['app' => config('app.name')]))
            ->action(__('Set password'), $url)
            ->line(__('This link expires in 24 hours. If you did not expect this email, you can ignore it.'));
    }
}
