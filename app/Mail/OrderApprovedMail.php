<?php

namespace App\Mail;

use App\Models\ISO_B2B\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class OrderApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        $requesterName = User::find($this->order->requested_by)?->name ?? 'Unknown User';
        $logoCid       = 'metro-logo@metro';
        $logoPath      = public_path('images/MarengEms_Logo.png');

        return $this->subject('Your Order Has Been Approved')
            ->view('emails.orders.approved')
            ->withSymfonyMessage(function (Email $message) use ($logoPath, $logoCid) {
                $message->addPart(
                    (new DataPart(fopen($logoPath, 'r'), 'logo.png', 'image/png'))
                        ->asInline()
                        ->setContentId($logoCid)
                );
            })
            ->with([
                'order'         => $this->order,
                'requesterName' => $requesterName,
                'logoCid'       => 'cid:' . $logoCid,
            ]);
    }
}
