<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WmsMonitor extends Command
{
    protected $signature = 'wms:monitor {warehouse}';
    protected $description = 'Monitor WMS update progress in real-time';

    public function handle()
    {
        $warehouseCode = $this->argument('warehouse');
        
        $this->info("=== Real-Time WMS Monitor: {$warehouseCode} ===");
        $this->info("Press Ctrl+C to stop\n");

        $lastProcessed = 0;
        $lastFailed = 0;
        $stuckCount = 0;

        while (true) {
            $runningCache = Cache::get("wms_update_running_{$warehouseCode}");
            
            if (!$runningCache) {
                $this->warn("[" . now()->format('H:i:s') . "] No update running for warehouse {$warehouseCode}");
                sleep(5);
                continue;
            }

            $processed = (int) Cache::get("wms_processed_{$warehouseCode}", 0);
            $failed = (int) Cache::get("wms_failed_{$warehouseCode}", 0);
            $total = $runningCache['total_skus'] ?? 0;
            $percent = $total > 0 ? round(($processed / $total) * 100, 1) : 0;

            // Check queue status
            $pendingJobs = DB::table('jobs')->where('queue', 'default')->count();
            $failedJobsInQueue = DB::table('failed_jobs')->count();

            // Calculate rate
            $processedDelta = $processed - $lastProcessed;
            $failedDelta = $failed - $lastFailed;

            // Detect if stuck
            if ($processedDelta == 0 && $processed < $total && $pendingJobs > 0) {
                $stuckCount++;
            } else {
                $stuckCount = 0;
            }

            $status = $stuckCount > 3 ? "⚠️  STUCK" : "✓ Running";

            $this->line(sprintf(
                "[%s] %s | Progress: %d/%d (%s%%) | Failed: %d | Rate: +%d/3s | Queue: %d pending, %d failed",
                now()->format('H:i:s'),
                $status,
                $processed,
                $total,
                $percent,
                $failed,
                $processedDelta,
                $pendingJobs,
                $failedJobsInQueue
            ));

            if ($stuckCount > 3) {
                $this->error("⚠️  WARNING: No progress for " . ($stuckCount * 3) . " seconds!");
                $this->warn("   Check:");
                $this->warn("   1. Queue worker running: php artisan queue:work");
                $this->warn("   2. Check logs: tail -f storage/logs/laravel.log");
                $this->warn("   3. Check failed jobs: php artisan queue:failed");
            }

            if ($processed >= $total) {
                $this->info("\n✓ Update completed!");
                break;
            }

            $lastProcessed = $processed;
            $lastFailed = $failed;

            sleep(3);
        }

        return 0;
    }
}