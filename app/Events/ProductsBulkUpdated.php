<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ProductsBulkUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $productIds;
    public $updateData;
    public $user;

    public function __construct(array $productIds, array $updateData, User $user)
    {
        $this->productIds = $productIds;
        $this->updateData = $updateData;
        $this->user = $user;
    }
}

class ProductsBulkArchived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $productIds;
    public $user;
    public $reason;

    public function __construct(array $productIds, User $user, ?string $reason = null)
    {
        $this->productIds = $productIds;
        $this->user = $user;
        $this->reason = $reason;
    }
}