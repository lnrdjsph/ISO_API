<?php

namespace App\Mail;

use App\Models\ISO_B2B\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
        $stores = [
            'f2'  => 'F2 - Metro Wholesalemart Colon',
            's10' => 'S10 - Metro Maasin',
            's17' => 'S17 - Metro Tacloban',
            's19' => 'S19 - Metro Bay-Bay',
            'f18' => 'F18 - Metro Alang-Alang',
            'f19' => 'F19 - Metro Hilongos',
            's8'  => 'S8 - Metro Toledo',
            'h8'  => 'H8 - Super Metro Antipolo',
            'h9'  => 'H9 - Super Metro Carcar',
            'h10' => 'H10 - Super Metro Bogo',
        ];

        $storeName = $stores[$this->order->requesting_store] ?? $this->order->requesting_store;

        return $this->subject('Order Approval Request')
            ->view('emails.orders.approval')
            ->with([
                'order' => $this->order,
                'storeName' => $storeName,
            ]);
    }


}
