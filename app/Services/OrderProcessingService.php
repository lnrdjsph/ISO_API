<?php

namespace App\Services;

use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use App\Models\ISO_B2B\OrderNote;
use App\Models\User;
use App\Support\LocationConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProcessingService
{
    protected const LOG_CHANNEL = 'order_agent';

    // Statuses the agent monitors
    protected const PENDING_STATUSES = ['new order', 'for approval'];

    /**
     * Process pending orders: auto-approve low-risk, escalate high-risk to approver.
     */
    public function processPendingOrders(int $limit = null, bool $dryRun = false): array
    {
        if (!config('agents.order_processor.enabled')) {
            Log::channel(self::LOG_CHANNEL)->info('Order processor disabled in config');
            return ['status' => 'disabled', 'processed' => 0];
        }

        $query = Order::whereIn('order_status', self::PENDING_STATUSES)
            ->orderBy('created_at', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        $orders = $query->get();

        $results = [
            'total'         => $orders->count(),
            'auto_approved' => 0,
            'escalated'     => 0,
            'skipped'       => 0,
            'failed'        => 0,
            'details'       => [],
        ];

        foreach ($orders as $order) {
            try {
                $result = $this->processOrder($order, $dryRun);
                $results['details'][] = $result;
                $results[$result['action']]++ ;
            } catch (\Exception $e) {
                Log::channel(self::LOG_CHANNEL)->error('Error processing order', [
                    'order_id' => $order->id,
                    'sof_id'   => $order->sof_id,
                    'error'    => $e->getMessage(),
                ]);
                $results['failed']++;
                $results['details'][] = [
                    'order_id' => $order->id,
                    'sof_id'   => $order->sof_id,
                    'action'   => 'failed',
                    'reason'   => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Process a single order: calculate risk, make approval decision.
     */
    public function processOrder(Order $order, bool $dryRun = false): array
    {
        // Skip orders already handled by another run (race-condition guard)
        if (!in_array($order->order_status, self::PENDING_STATUSES)) {
            return $this->result($order, 'skipped', 'Status changed since query');
        }

        // Validate stock before anything else
        if (!$this->hasStockAvailable($order)) {
            Log::channel(self::LOG_CHANNEL)->info('Order skipped — insufficient stock', [
                'order_id' => $order->id,
            ]);
            return $this->result($order, 'skipped', 'Insufficient WMS stock; awaiting restock');
        }

        $riskScore = $this->calculateRiskScore($order);

        return $this->shouldAutoApprove($order, $riskScore)
            ? $this->approve($order, $riskScore, $dryRun)
            : $this->escalate($order, $riskScore, $dryRun);
    }

    // ── Risk Scoring ─────────────────────────────────────────────────────────

    /**
     * Calculate composite risk score (0–100).
     * Weights: amount 40%, customer history 30%, store history 20%, sku 10%.
     */
    protected function calculateRiskScore(Order $order): float
    {
        $weights = config('agents.order_processor.risk_weights');

        $amountScore   = $this->amountScore($order);
        $customerScore = $this->customerScore($order);
        $storeScore    = $this->storeScore($order->requesting_store);
        $skuScore      = $this->skuScore($order);

        $score = ($amountScore   * $weights['amount'])
               + ($customerScore * $weights['customer'])
               + ($storeScore    * $weights['store'])
               + ($skuScore      * $weights['sku']);

        Log::channel(self::LOG_CHANNEL)->debug('Risk score', [
            'order_id'       => $order->id,
            'amount_score'   => round($amountScore, 2),
            'customer_score' => round($customerScore, 2),
            'store_score'    => round($storeScore, 2),
            'sku_score'      => round($skuScore, 2),
            'total'          => round($score, 2),
        ]);

        return $score;
    }

    /**
     * Amount score: normalized against 50K max → 0–100.
     * Uses order_items.amount sum (actual line-item totals).
     */
    protected function amountScore(Order $order): float
    {
        $total = $order->items()->where('item_type', 'MAIN')->sum('amount');
        return min(($total / 50000) * 100, 100);
    }

    /**
     * Customer score: based on order history for this store.
     */
    protected function customerScore(Order $order): float
    {
        $tiers = config('agents.order_processor.customer_risk_scores');

        $recentOrders = Order::where('requesting_store', $order->requesting_store)
            ->where('created_at', '>', now()->subMonths(3))
            ->where('order_status', 'completed')
            ->count();

        if ($recentOrders > 10) return $tiers['Gold']     ?? 10;
        if ($recentOrders > 5)  return $tiers['Silver']   ?? 20;
        if ($recentOrders > 0)  return $tiers['Bronze']   ?? 30;
        return                         $tiers['New']      ?? 50;
    }

    /**
     * Store score: based on rejection history.
     * Uses LocationConfig to confirm store is active.
     */
    protected function storeScore(string $storeCode): float
    {
        $tiers = config('agents.order_processor.customer_risk_scores');

        // Store not found in LocationConfig means it's inactive/unknown
        $name = LocationConfig::storeName($storeCode);
        if (!$name || $name === $storeCode) {
            return 50;
        }

        $rejectedLast3m = Order::where('requesting_store', $storeCode)
            ->where('order_status', 'rejected')
            ->where('created_at', '>', now()->subMonths(3))
            ->count();

        if ($rejectedLast3m > 2) return $tiers['Bronze'] ?? 30;
        if ($rejectedLast3m > 0) return $tiers['Silver'] ?? 20;
        return                          $tiers['Gold']   ?? 10;
    }

    /**
     * SKU score: baseline 0; extend to flag new/discontinued SKUs if needed.
     */
    protected function skuScore(Order $order): float
    {
        return 0;
    }

    // ── Auto-Approval Decision ────────────────────────────────────────────────

    protected function shouldAutoApprove(Order $order, float $riskScore): bool
    {
        $config    = config('agents.order_processor');
        $threshold = $config['auto_approve_threshold'];

        if ($riskScore >= $threshold) {
            return false;
        }

        // Region-specific amount threshold (uses actual main-item totals)
        $regionKey = LocationConfig::regionForStore($order->requesting_store);
        if ($regionKey) {
            $limits = $config['amount_thresholds'];
            $limit  = $limits[$regionKey] ?? $limits['default'];
            $total  = $order->items()->where('item_type', 'MAIN')->sum('amount');
            if ($total > $limit) {
                return false;
            }
        }

        // Require at least "Bronze" customer history (score ≤ 30)
        if ($this->customerScore($order) > 30) {
            return false;
        }

        return true;
    }

    // ── Approval & Escalation ─────────────────────────────────────────────────

    /**
     * Auto-approve: update order_status, deduct both allocation tiers, add note.
     *
     * Two-tier deduction (mirrors OrderController::deductAllocationStock logic):
     *   1. products_{storeCode}.allocation_per_case  — cases, MAIN items only
     *   2. product_wms_allocations.wms_virtual_allocation — pieces, ALL items
     */
    protected function approve(Order $order, float $riskScore, bool $dryRun = false): array
    {
        $config = config('agents.order_processor');

        if (!$dryRun) {
            DB::transaction(function () use ($order, $riskScore, $config) {
                $order->update(['order_status' => 'approved']);

                if ($config['enable_auto_deduction']) {
                    $this->deductStock($order);
                }

                OrderNote::create([
                    'order_id' => $order->id,
                    'user_id'  => null,
                    'status'   => 'approved',
                    'note'     => "Auto-approved by Order Processing Agent. Risk score: " . round($riskScore, 1) . "/100.",
                ]);
            });

            if ($config['notify_warehouse']) {
                $this->notifyWarehouse($order);
            }

            Log::channel(self::LOG_CHANNEL)->info('Order auto-approved', [
                'order_id'   => $order->id,
                'sof_id'     => $order->sof_id,
                'risk_score' => round($riskScore, 2),
            ]);
        }

        return $this->result($order, 'auto_approved', 'Low-risk order; stock available', $riskScore);
    }

    /**
     * Escalate: set status to 'for approval', route to regional approver, add note.
     * Approver resolved via LocationConfig → regionForStore → regionApproverUserId.
     */
    protected function escalate(Order $order, float $riskScore, bool $dryRun = false): array
    {
        $config    = config('agents.order_processor');
        $regionKey = LocationConfig::regionForStore($order->requesting_store);
        $approverId = $regionKey ? LocationConfig::regionApproverUserId($regionKey) : null;
        $approver   = $approverId ? User::find($approverId) : null;

        if (!$dryRun) {
            DB::transaction(function () use ($order, $riskScore, $regionKey, $approverId) {
                $order->update(['order_status' => 'for approval']);

                OrderNote::create([
                    'order_id' => $order->id,
                    'user_id'  => null,
                    'status'   => 'for approval',
                    'note'     => "Escalated by Order Processing Agent. Risk score: " . round($riskScore, 1) . "/100. Region: {$regionKey}. Routed to approver ID: {$approverId}.",
                ]);
            });

            if ($config['notification_email'] && $approver) {
                $this->notifyApprover($order, $approver, $riskScore);
            }

            Log::channel(self::LOG_CHANNEL)->info('Order escalated', [
                'order_id'      => $order->id,
                'sof_id'        => $order->sof_id,
                'risk_score'    => round($riskScore, 2),
                'region'        => $regionKey,
                'approver_id'   => $approverId,
                'approver_name' => $approver?->name,
            ]);
        }

        return $this->result($order, 'escalated',
            "Risk {$riskScore} exceeds threshold; routed to {$approver?->name}",
            $riskScore,
            ['approver_id' => $approverId, 'approver_name' => $approver?->name]
        );
    }

    // ── Stock Operations ──────────────────────────────────────────────────────

    /**
     * Check both allocation tiers are sufficient for all items.
     */
    protected function hasStockAvailable(Order $order): bool
    {
        $storeCode = $order->requesting_store;
        $warehouse = $order->warehouse;

        $items = $order->items()->where('item_type', 'MAIN')->get();

        foreach ($items as $item) {
            // Tier 1: store-level cases
            $storeAlloc = DB::table("products_{$storeCode}")
                ->where('sku', $item->sku)
                ->whereNull('archived_at')
                ->value('allocation_per_case') ?? 0;

            if ($storeAlloc < $item->qty_per_cs) {
                return false;
            }

            // Tier 2: warehouse-level pieces
            $wmsAlloc = DB::table('product_wms_allocations')
                ->where('sku', $item->sku)
                ->where('warehouse_code', $warehouse)
                ->value('wms_virtual_allocation') ?? 0;

            $requiredPieces = $item->total_qty * ($item->qty_per_pc ?: 1);
            if ($wmsAlloc < $requiredPieces) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deduct stock from both allocation tiers (mirrors OrderController::deductAllocationStock).
     *
     * Tier 1 — products_{storeCode}.allocation_per_case:
     *   - MAIN items only
     *   - Deduct by qty_per_cs (cases ordered)
     *
     * Tier 2 — product_wms_allocations.wms_virtual_allocation:
     *   - ALL items (MAIN + FREEBIE)
     *   - Deduct by total_qty × qty_per_pc (total pieces)
     */
    protected function deductStock(Order $order): void
    {
        $storeCode = $order->requesting_store;
        $warehouse = $order->warehouse;

        $items = $order->items()->get();

        foreach ($items as $item) {
            // Tier 1: cases, MAIN only
            if ($item->item_type === 'MAIN') {
                DB::table("products_{$storeCode}")
                    ->where('sku', $item->sku)
                    ->update([
                        'allocation_per_case' => DB::raw("GREATEST(0, allocation_per_case - {$item->qty_per_cs})"),
                    ]);
            }

            // Tier 2: pieces, all item types
            $pieces = $item->total_qty * ($item->qty_per_pc ?: 1);
            DB::table('product_wms_allocations')
                ->where('sku', $item->sku)
                ->where('warehouse_code', $warehouse)
                ->update([
                    'wms_virtual_allocation' => DB::raw("GREATEST(0, wms_virtual_allocation - {$pieces})"),
                ]);
        }
    }

    // ── SLA Monitoring ────────────────────────────────────────────────────────

    /**
     * Flag orders stuck in 'for approval' beyond the SLA window.
     */
    public function checkSlaBreaches(): array
    {
        if (!config('agents.order_processor.check_sla_enabled')) {
            return ['status' => 'disabled'];
        }

        $slaHours   = config('agents.order_processor.sla_hours');
        $cutoffTime = now()->subHours($slaHours);

        $breaches = Order::where('order_status', 'for approval')
            ->where('created_at', '<', $cutoffTime)
            ->get();

        $results = ['total_breaches' => $breaches->count(), 'orders' => []];

        foreach ($breaches as $order) {
            $hoursWaiting = $order->created_at->diffInHours(now());

            $results['orders'][] = [
                'order_id'     => $order->id,
                'sof_id'       => $order->sof_id,
                'store'        => $order->requesting_store,
                'hours_waiting' => $hoursWaiting,
                'sla_hours'    => $slaHours,
                'overdue_by'   => $hoursWaiting - $slaHours,
            ];

            Log::channel(self::LOG_CHANNEL)->warning('Order SLA breach', [
                'order_id'     => $order->id,
                'sof_id'       => $order->sof_id,
                'hours_waiting' => $hoursWaiting,
            ]);
        }

        return $results;
    }

    // ── Notifications ─────────────────────────────────────────────────────────

    protected function notifyApprover(Order $order, User $approver, float $riskScore): void
    {
        try {
            Log::channel(self::LOG_CHANNEL)->info('Approver notification queued', [
                'to'         => $approver->email,
                'order_id'   => $order->id,
                'risk_score' => round($riskScore, 1),
            ]);

            // Uncomment once OrderApprovalRequestMail is confirmed to work:
            // Mail::to($approver->email)->send(new \App\Mail\OrderApprovalRequestMail($order));
        } catch (\Exception $e) {
            Log::channel(self::LOG_CHANNEL)->warning('Approver notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function notifyWarehouse(Order $order): void
    {
        try {
            $emailAddr = config('agents.order_processor.notification_email_address');
            Log::channel(self::LOG_CHANNEL)->info('Warehouse notification queued', [
                'to'       => $emailAddr,
                'order_id' => $order->id,
                'warehouse' => $order->warehouse,
            ]);

            // Uncomment once mail template is ready:
            // Mail::raw("Order {$order->sof_id} approved for warehouse {$order->warehouse}.",
            //     fn($m) => $m->to($emailAddr)->subject("Order Ready: {$order->sof_id}"));
        } catch (\Exception $e) {
            Log::channel(self::LOG_CHANNEL)->warning('Warehouse notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function result(Order $order, string $action, string $reason, float $riskScore = 0, array $extra = []): array
    {
        return array_merge([
            'order_id'     => $order->id,
            'sof_id'       => $order->sof_id,
            'store'        => $order->requesting_store,
            'action'       => $action,
            'risk_score'   => round($riskScore, 2),
            'reason'       => $reason,
            'processed_at' => now()->toIso8601String(),
        ], $extra);
    }
}
