<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnsureQueueWorker extends Command
{
    protected $signature = 'queue:ensure-running 
                            {--restart : Restart the queue worker if already running}';
    
    protected $description = 'Ensure queue worker is running, start if not';

    public function handle()
    {
        if ($this->isQueueWorkerRunning()) {
            if ($this->option('restart')) {
                $this->info('Queue worker is running. Restarting...');
                $this->stopQueueWorker();
                sleep(2);
                $this->startQueueWorker();
            } else {
                $this->info('✓ Queue worker is already running');
                return Command::SUCCESS;
            }
        } else {
            $this->warn('⚠ Queue worker not running. Starting...');
            $this->startQueueWorker();
        }

        // Verify it started
        sleep(2);
        if ($this->isQueueWorkerRunning()) {
            $this->info('✓ Queue worker started successfully');
            return Command::SUCCESS;
        } else {
            $this->error('✗ Failed to start queue worker');
            return Command::FAILURE;
        }
    }

    protected function isQueueWorkerRunning(): bool
    {
        if ($this->isWindows()) {
            $output = shell_exec('tasklist /FI "IMAGENAME eq php.exe" 2>nul');
            return $output && (stripos($output, 'queue:work') !== false || stripos($output, 'queue:listen') !== false);
        } else {
            $output = shell_exec('ps aux | grep "queue:work\|queue:listen" | grep -v grep');
            return !empty($output);
        }
    }

    protected function startQueueWorker(): void
    {
        $basePath = base_path();
        $phpBinary = PHP_BINARY;
        
        if ($this->isWindows()) {
            // Windows
            $command = "start /B {$phpBinary} {$basePath}/artisan queue:work --queue=default --tries=3 --timeout=300 > NUL 2>&1";
            pclose(popen($command, 'r'));
        } else {
            // Linux/Unix
            $logPath = storage_path('logs/queue-worker.log');
            $command = "nohup {$phpBinary} {$basePath}/artisan queue:work --queue=default --tries=3 --timeout=300 > {$logPath} 2>&1 &";
            exec($command);
        }

        Log::info('Queue worker start command executed');
    }

    protected function stopQueueWorker(): void
    {
        if ($this->isWindows()) {
            // Windows - kill php processes running queue:work
            $output = shell_exec('wmic process where "commandline like \'%queue:work%\'" get processid 2>nul');
            if ($output) {
                preg_match_all('/(\d+)/', $output, $matches);
                foreach ($matches[1] as $pid) {
                    shell_exec("taskkill /PID {$pid} /F 2>nul");
                }
            }
        } else {
            // Linux/Unix
            shell_exec("pkill -f 'queue:work'");
        }

        $this->info('Stopped existing queue workers');
        Log::info('Queue worker stopped');
    }

    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}