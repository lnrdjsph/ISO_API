<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\RMSCommerceSynchronizationController;
use Illuminate\Support\Facades\Log;

class RMSSynchronizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            Log::info('Starting queued RMS synchronization');
            
            $controller = new RMSCommerceSynchronizationController();
            $controller->synchronize();
            
            Log::info('Queued RMS synchronization completed');
            
        } catch (\Exception $e) {
            Log::error('Queued RMS synchronization failed: ' . $e->getMessage());
            throw $e;
        }
    }
}