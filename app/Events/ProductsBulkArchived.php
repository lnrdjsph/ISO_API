<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ProductsBulkArchived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $productIds;
    public User $user;
    public ?string $reason;

    public function __construct(array $productIds, User $user, ?string $reason = null)
    {
        $this->productIds = $productIds;
        $this->user = $user;
        $this->reason = $reason;
    }
}
