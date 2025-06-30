<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\RMSCommerceSynchronizationController;

class RMSSync extends Command
{
    protected $signature = 'rms:sync';
    protected $description = 'Run RMS Commerce Synchronization';

    public function handle()
    {
        $this->info('Starting RMS Synchronization...');
        
        try {
            $controller = new RMSCommerceSynchronizationController();
            $result = $controller->synchronize();
            
            $this->info('Synchronization completed successfully');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Synchronization failed: ' . $e->getMessage());
            return 1;
        }
    }
}