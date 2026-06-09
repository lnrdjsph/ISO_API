<?php

namespace App\Console\Commands;

use App\Services\OrderProcessingService;
use Illuminate\Console\Command;

class OrderCheckSlaCommand extends Command
{
    protected $signature = 'order:check-sla';

    protected $description = 'Check for order SLA breaches (orders awaiting approval > threshold)';

    public function __construct(
        protected OrderProcessingService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking for SLA breaches...');

        $results = $this->service->checkSlaBreaches();

        if ($results['status'] === 'disabled') {
            $this->warn('SLA checking is disabled in config');
            return 0;
        }

        $totalBreaches = $results['total_breaches'] ?? 0;

        if ($totalBreaches === 0) {
            $this->info('✓ No SLA breaches detected');
            return 0;
        }

        $this->warn("⚠ Found {$totalBreaches} SLA breach(es):");
        $this->newLine();

        $headers = ['Order ID', 'SOF ID', 'Hours Waiting', 'SLA Hours', 'Overdue By'];
        $rows = [];

        foreach ($results['orders'] as $breach) {
            $rows[] = [
                $breach['order_id'],
                $breach['sof_id'],
                $breach['hours_waiting'],
                $breach['sla_hours'],
                $breach['overdue_by'] . 'h',
            ];
        }

        $this->table($headers, $rows);

        return 0;
    }
}
