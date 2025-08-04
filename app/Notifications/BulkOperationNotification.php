<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BulkOperationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $type;
    protected $performedBy;
    protected $count;
    protected $details;

    public function __construct($type, $performedBy, $count, $details)
    {
        $this->type = $type;
        $this->performedBy = $performedBy;
        $this->count = $count;
        $this->details = $details;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Bulk Operation Notification')
            ->line("Operation: {$this->type}")
            ->line("Performed By: {$this->performedBy->name}")
            ->line("Total Affected Products: {$this->count}")
            ->line("Details: {$this->details}");
    }

    public function toArray($notifiable)
    {
        return [
            'type' => $this->type,
            'performed_by' => $this->performedBy->only(['id', 'name', 'email']),
            'count' => $this->count,
            'details' => $this->details,
        ];
    }
}
