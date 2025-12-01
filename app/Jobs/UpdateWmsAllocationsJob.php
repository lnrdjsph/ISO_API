<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateWmsAllocationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Don't retry on failure

    protected $location;

    public function __construct($location)
    {
        $this->location = $location;
    }

    public function handle()
    {
        $cacheKey = "wms_update_running_{$this->location}";

        try {
            Log::info("Starting WMS allocation update for location: {$this->location}");

            // --- Check Oracle RMS connection ---
            try {
                DB::connection('oracle_rms')->getPdo();
                Log::info("Oracle RMS connection successful.");
            } catch (\Exception $e) {
                Log::error("Oracle RMS connection failed: " . $e->getMessage());
                throw new \Exception("Oracle RMS connection failed: " . $e->getMessage());
            }

            // --- Check Oracle WMS connection ---
            try {
                DB::connection('oracle_wms')->getPdo();
                Log::info("Oracle WMS connection successful.");
            } catch (\Exception $e) {
                Log::error("Oracle WMS connection failed: " . $e->getMessage());
                throw new \Exception("Oracle WMS connection failed: " . $e->getMessage());
            }

            // Run the artisan command if connections are OK
            $exitCode = Artisan::call('products:update-allocations', [
                '--location' => $this->location
            ]);

            if ($exitCode === 0) {
                Log::info("WMS allocation update completed successfully for location: {$this->location}");
            } else {
                Log::error("WMS allocation update failed with exit code {$exitCode} for location: {$this->location}");
            }

        } catch (\Exception $e) {
            Log::error("WMS allocation update error for location {$this->location}: " . $e->getMessage());
            throw $e;
        } finally {
            // Always clear the cache lock when done
            Cache::forget($cacheKey);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        $cacheKey = "wms_update_running_{$this->location}";
        Cache::forget($cacheKey);

        Log::error("WMS allocation job failed for location {$this->location}: " . $exception->getMessage());
    }
}
