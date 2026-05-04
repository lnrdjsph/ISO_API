<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $resetUrl,
        public string $userName,
    ) {}

    public function build()
    {
        $logoCid  = 'metro-logo@metro';
        $logoPath = public_path('images/MarengEms_Logo.png');

        return $this->subject('Reset Password Notification')
            ->view('emails.auth.reset_password')
            ->withSymfonyMessage(function (Email $message) use ($logoPath, $logoCid) {
                $message->addPart(
                    (new DataPart(fopen($logoPath, 'r'), 'logo.png', 'image/png'))
                        ->asInline()
                        ->setContentId($logoCid)
                );
            })
            ->with([
                'resetUrl' => $this->resetUrl,
                'userName' => $this->userName,
                'logoCid'  => 'cid:' . $logoCid,
            ]);
    }
}
