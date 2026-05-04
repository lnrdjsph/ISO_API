<?php

namespace App\Mail;

use App\Models\ISO_B2B\Order;
use App\Models\User;
use App\Support\LocationConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class OrderApprovalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        $storeName     = LocationConfig::storeName($this->order->requesting_store);
        $requesterName = User::find($this->order->requested_by)?->name ?? 'Unknown User';
        $logoCid       = 'metro-logo@metro';
        $logoPath      = public_path('images/MarengEms_Logo.png');

        return $this->subject('Order Approval Request')
            ->view('emails.orders.approval')
            ->withSymfonyMessage(function (Email $message) use ($logoPath, $logoCid) {
                $message->addPart(
                    (new DataPart(fopen($logoPath, 'r'), 'logo.png', 'image/png'))
                        ->asInline()
                        ->setContentId($logoCid)
                );
            })
            ->with([
                'order'         => $this->order,
                'storeName'     => $storeName,
                'requesterName' => $requesterName,
                'logoCid'       => 'cid:' . $logoCid,
            ]);
    }
}
