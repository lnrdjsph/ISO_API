<?php

namespace App\Console\Commands;

use App\Services\OrderProcessingService;
use Illuminate\Console\Command;

class OrderProcessCommand extends Command
{
    protected $signature = 'order:process
                        {--order-id= : Process specific order}
                        {--limit=50 : Maximum orders to process}
                        {--dry-run : Preview changes without committing}';

    protected $description = 'Process pending orders: auto-approve low-risk, escalate high-risk to approver';

    public function __construct(
        protected OrderProcessingService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $orderId = $this->option('order-id');
        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('Starting order processing...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No changes will be committed');
        }

        // Process single order
        if ($orderId) {
            $order = \App\Models\ISO_B2B\Order::find($orderId);
            if (!$order) {
                $this->error("Order {$orderId} not found");
                return 1;
            }

            $result = $this->service->processOrder($order, $dryRun);
            $this->displayResult($result);
            return 0;
        }

        // Process batch
        $results = $this->service->processPendingOrders($limit, $dryRun);

        $this->displayBatchResults($results);

        return 0;
    }

    protected function displayResult(array $result): void
    {
        $headers = ['Key', 'Value'];
        $rows = [];

        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $rows[] = [$key, $value];
        }

        $this->table($headers, $rows);
    }

    protected function displayBatchResults(array $results): void
    {
        if (($results['status'] ?? null) === 'disabled') {
            $this->warn('Order processing is disabled in config');
            return;
        }

        $this->newLine();
        $this->info('=' . str_repeat('=', 70));
        $this->info('Order Processing Summary');
        $this->info('=' . str_repeat('=', 70));

        $this->line("Total orders processed: <info>{$results['total']}</info>");
        $this->line("Auto-approved:          <fg=green>{$results['auto_approved']}</>");
        $this->line("Escalated:              <fg=yellow>{$results['escalated']}</>");
        $this->line("Failed:                 <fg=red>{$results['failed']}</>");

        if (!empty($results['details'])) {
            $this->newLine();
            $this->info('Details:');

            foreach ($results['details'] as $detail) {
                $action = match ($detail['action']) {
                    'auto_approved' => '<fg=green>✓ AUTO-APPROVED</>',
                    'escalated' => '<fg=yellow>→ ESCALATED</>',
                    'failed' => '<fg=red>✗ FAILED</>',
                    default => $detail['action'],
                };

                $line = "  {$action} | {$detail['sof_id']} | Risk: " . ($detail['risk_score'] ?? 'N/A');
                if (isset($detail['approver_name'])) {
                    $line .= " → {$detail['approver_name']}";
                }
                $this->line($line);
            }
        }

        $this->info('=' . str_repeat('=', 70));
    }
}
