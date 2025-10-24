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
            '4002'  =>  'F2 - Metro Wholesalemart Colon',
            '2010'  =>  'S10 - Metro Maasin',
            '2017'  =>  'S17 - Metro Tacloban',
            '2019'   =>  'S19 - Metro Bay-Bay',
            '3018'   =>  'F18 - Metro Alang-Alang',
            '3019'   =>  'F19 - Metro Hilongos',
            '2008'    =>  'S8 - Metro Toledo',
            '6012'    =>  'H8 - Super Metro Antipolo',
            '6009'    =>  'H9 - Super Metro Carcar',
            '6010'   =>  'H10 - Super Metro Bogo',
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
