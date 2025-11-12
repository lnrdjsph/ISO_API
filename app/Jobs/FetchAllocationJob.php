<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FetchAllocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sku;

    public function __construct($sku)
    {
        $this->sku = $sku;
    }

    public function handle()
    {
        $allocation = DB::connection('oracle_wms')->selectOne("
            SELECT SUM(ci.unit_qty) AS total_unit_qty
            FROM rwms.container c
            JOIN rwms.container_item ci ON c.facility_id = ci.facility_id AND c.container_id = ci.container_id
            WHERE c.container_status NOT IN ('X', 'T')
            AND ci.item_id = ?
        ", [$this->sku]);

        // Save result in cache or DB
        Cache::put("allocation_{$this->sku}", $allocation->total_unit_qty ?? 0, now()->addMinutes(30));
    }
}