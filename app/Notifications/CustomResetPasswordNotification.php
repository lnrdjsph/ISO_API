<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;

class CustomResetPasswordNotification extends Notification
{
    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new ResetPasswordMail($resetUrl, $notifiable->name))
            ->to($notifiable->email);
    }
}
