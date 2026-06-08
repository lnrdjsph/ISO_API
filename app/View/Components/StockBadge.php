<?php

namespace App\View\Components;

use Illuminate\View\Component;

class StockBadge extends Component
{
    public int   $value;
    public string $status;
    public string $badgeClasses;
    public string $statusColor;

    public function __construct(int $value, string $casePack = '')
    {
        $this->value = $value;
        $minCasePack = $this->parseMinCasePack($casePack);

        [$this->status, $this->badgeClasses, $this->statusColor] = $this->resolveStatus($value, $minCasePack);
    }

    private function parseMinCasePack(string $casePack): int
    {
        if (empty($casePack)) {
            return 0;
        }

        $numbers = array_filter(
            array_map('intval', array_map('trim', explode('|', $casePack)))
        );

        return !empty($numbers) ? min($numbers) : 0;
    }

    private function resolveStatus(int $value, int $minCasePack): array
    {
        if ($value === 0) {
            return ['Out of Stock', 'border-red-200/60 bg-red-100/60 text-red-800', 'text-red-300'];
        }

        if ($minCasePack > 0 && $value < $minCasePack) {
            return ['Below case pack', 'border-red-200/60 bg-red-100/60 text-red-800', 'text-red-300'];
        }

        if ($minCasePack > 0 && $value <= $minCasePack * 10) {
            return ['Low WMS Stocks', 'border-orange-200/60 bg-orange-100/60 text-orange-800', 'text-orange-300'];
        }

        return ['In Stock', 'border-green-300/60 bg-green-200/60 text-green-900', 'text-green-300'];
    }

    public function render()
    {
        return view('components.stock-badge');
    }
}
