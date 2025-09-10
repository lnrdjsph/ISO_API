<?php

namespace App\Mail;

use App\Models\ISO_B2B\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
        return $this->subject('Your Order Has Been Approved')
                    ->markdown('emails.orders.approved');
    }
}
