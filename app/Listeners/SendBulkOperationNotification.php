<?php

namespace App\Listeners;

use App\Events\ProductsBulkUpdated;
use App\Events\ProductsBulkArchived;
use App\Models\User;
use App\Notifications\BulkOperationNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendBulkOperationNotification
{
    public function handleBulkUpdate(ProductsBulkUpdated $event)
    {
        // Send notification to administrators about bulk update
        $adminUsers = User::where('role', 'super admin')->get();
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new BulkOperationNotification(
                'products_updated',
                $event->user,
                count($event->productIds),
                'Updated ' . implode(', ', array_keys($event->updateData))
            ));
        }
    }

    public function handleBulkArchive(ProductsBulkArchived $event)
    {
        // Send notification about bulk archive
        $adminUsers = User::where('role', 'super admin')->get();
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new BulkOperationNotification(
                'products_archived',
                $event->user,
                count($event->productIds),
                'Reason: ' . ($event->reason ?: 'No reason provided')
            ));
        }
    }
}
