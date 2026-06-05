<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Settings\SettingsStore;
use App\Models\Settings\SettingsWarehouse;
use App\Models\Settings\SettingsRegion;
use App\Models\Settings\SettingsAuditLog;
use App\Models\Settings\SettingsRegionEmail;

class SettingsController extends Controller
{
    const CACHE_KEY = 'location_settings';

    public function index()
    {
        abort_unless(auth()->user()->role === 'super admin', 403);

        $warehouses   = SettingsWarehouse::orderBy('name')->get();
        $regions      = SettingsRegion::orderBy('region_key')->get();
        $stores       = SettingsStore::with(['warehouse', 'region'])->orderBy('store_code')->get();
        $auditLogs    = SettingsAuditLog::with('user')->orderByDesc('created_at')->limit(50)->get();
        // Approver sentinel rows: email='__approver__', label=user_id string.
        $regionApprovers = SettingsRegionEmail::where('email', '__approver__')
            ->get()->keyBy('region_key');

        $managerUsers = \App\Models\User::orderBy('name')->get(['id', 'name', 'email']);

        return view('settings.index', compact(
            'stores',
            'warehouses',
            'regions',
            'auditLogs',
            'regionApprovers',
            'managerUsers'
        ));
    }

    // ── STORES ───────────────────────────────────────────────────────────

    public function enrollStore(Request $request)
    {
        abort_unless(auth()->user()->role === 'super admin', 403);

        $data = $request->validate([
            'store_code'     => 'required|string|max:10|unique:settings_stores,store_code',
            'display_name'   => 'required|string|max:255',
            'short_name'     => 'required|string|max:255',
            'warehouse_code' => 'required|string|exists:settings_warehouses,warehouse_code',
            'region_code'    => 'required|string|exists:settings_regions,region_key',
            'go_live_status' => 'required|in:active,pending',
        ]);

        $store = SettingsStore::create([
            'store_code'     => $data['store_code'],
            'display_name'   => $data['display_name'],
            'short_name'     => $data['short_name'],
            'warehouse_code' => $data['warehouse_code'],
            'region_key'     => $data['region_code'],
            'status'         => $data['go_live_status'],
            'created_by'     => auth()->id(),
            'updated_by'     => auth()->id(),
        ]);

        $tableName    = "products_{$store->store_code}";
        $tableCreated = false;

        if (!Schema::connection('mysql')->hasTable($tableName)) {
            Schema::connection('mysql')->create($tableName, function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('sku')->unique();
                $t->string('description');
                $t->string('department_code')->nullable();
                $t->string('department')->nullable();
                $t->string('case_pack')->nullable();
                $t->decimal('srp', 10, 2);
                $t->integer('allocation_per_case')->nullable();
                $t->integer('initial_allocation_per_case')->nullable();
                $t->string('cash_bank_card_scheme', 10)->nullable();
                $t->string('po15_scheme', 10)->nullable();
                $t->string('discount_scheme', 10)->nullable();
                $t->string('freebie_sku')->nullable();
                $t->timestamp('archived_at')->nullable();
                $t->unsignedBigInteger('archived_by')->nullable();
                $t->string('archive_reason')->nullable();
                $t->index('archived_at');
                $t->timestamps();
            });
            $tableCreated = true;
        }

        SettingsAuditLog::record('store', $store->store_code, 'created', [], array_merge(
            $store->toArray(),
            ['products_table_created' => $tableCreated]
        ));
        $this->flushCache();

        $msg  = "Store {$store->display_name} ({$store->store_code}) enrolled.";
        $msg .= $tableCreated ? " Table `{$tableName}` created." : " Table `{$tableName}` already existed — skipped.";

        return redirect()->route('settings.index')->with('success', $msg);
    }

    public function updateStore(Request $request, string $code)
    {
        abort_unless(auth()->user()->role === 'super admin', 403);

        $store  = SettingsStore::findOrFail($code);
        $before = $store->toArray();

        $data = $request->validate([
            'display_name'   => 'required|string|max:255',
            'short_name'     => 'sometimes|string|max:255',
            'warehouse_code' => 'required|string|exists:settings_warehouses,warehouse_code',
            'region_code'    => 'required|string|exists:settings_regions,region_key',
            'go_live_status' => 'sometimes|in:active,pending,inactive',
        ]);

        $store->update([
            'display_name'   => $data['display_name'],
            'short_name'     => $data['short_name']     ?? $store->short_name,
            'warehouse_code' => $data['warehouse_code'],
            'region_key'     => $data['region_code'],
            'status'         => $data['go_live_status'] ?? $store->status,
            'updated_by'     => auth()->id(),
        ]);

        SettingsAuditLog::record('store', $code, 'updated', $before, $store->fresh()->toArray());
        $this->flushCache();

        return redirect()->route('settings.index')->with('success', "Store {$code} updated.");
    }

    public function deactivateStore(string $code)
    {
        abort_unless(auth()->user()->role === 'super admin', 403);

        $store  = SettingsStore::findOrFail($code);
        $before = $store->toArray();

        $store->update(['status' => 'inactive', 'updated_by' => auth()->id()]);

        SettingsAuditLog::record('store', $code, 'deactivated', $before, $store->fresh()->toArray());
        $this->flushCache();

        if (request()->wantsJson()) {
            return response()->json(['message' => "Store {$code} ({$store->display_name}) deactivated."]);
        }

        return redirect()->route('settings.index')->with('success', "Store {$code} deactivated.");
    }

    // ── WAREHOUSES ───────────────────────────────────────────────────────

    public function storeWarehouse(Request $request)
    {
        abort_unless(auth()->user()->role === 'super admin', 403);

        $data = $request->validate([
            'warehouse_code' => 'required|string|max:10|unique:settings_warehouses,warehouse_code',
            'warehouse_name' => 'required|string|max:255',
            'facility_id'    => 'required|string|max:5',
        ]);

        $wh = SettingsWarehouse::create([
            'warehouse_code' => $data['warehouse_code'],
            'name'           => $data['warehouse_name'],
            'facility_id'    => strtoupper($data['facility_id']),
            'created_by'     => auth()->id(),
        ]);

        SettingsAuditLog::record('warehouse', $wh->warehouse_code, 'created', [], $wh->toArray());
        $this->flushCache();

        return redirect()->route('settings.index')->with('success', "Warehouse {$wh->name} added.");
    }

    public function updateWarehouse(Request $request, string $code)
    {
        abort_unless(auth()->user()->role === 'super admin', 403);

        $wh     = SettingsWarehouse::findOrFail($code);
        $before = $wh->toArray();

        $data = $request->validate([
            'warehouse_name' => 'required|string|max:255',
            'facility_id'    => 'required|string|max:5',
            'is_active'      => 'sometimes|boolean',
        ]);

        $wh->update([
            'name'        => $data['warehouse_name'],
            'facility_id' => strtoupper($data['facility_id']),
            'is_active'   => $data['is_active'] ?? $wh->is_active,
        ]);

        SettingsAuditLog::record('warehouse', $code, 'updated', $before, $wh->fresh()->toArray());
        $this->flushCache();

        return redirect()->route('settings.index')->with('success', "Warehouse {$code} updated.");
    }

    // ── REGIONS ──────────────────────────────────────────────────────────

    public function storeRegion(Request $request)
    {
        abort_unless(auth()->user()->role === 'super admin', 403);

        $data = $request->validate([
            'region_key'    => 'required|string|max:10|unique:settings_regions,region_key',
            'region_label'  => 'required|string|max:100',
            'store_codes'   => 'nullable|array',
            'store_codes.*' => 'string|exists:settings_stores,store_code',
        ]);

        $region = SettingsRegion::create([
            'region_key' => $data['region_key'],
            'label'      => $data['region_label'],
            'created_by' => auth()->id(),
        ]);

        if (!empty($data['store_codes'])) {
            SettingsStore::whereIn('store_code', $data['store_codes'])
                ->update(['region_key' => $region->region_key, 'updated_by' => auth()->id()]);
        }

        SettingsAuditLog::record('region', $region->region_key, 'created', [], [
            'region'      => $region->toArray(),
            'store_codes' => $data['store_codes'] ?? [],
        ]);
        $this->flushCache();

        return redirect()->route('settings.index')->with('success', "Region '{$region->label}' added.");
    }

    public function updateRegion(Request $request, string $key)
    {
        abort_unless(auth()->user()->role === 'super admin', 403);

        $region = SettingsRegion::with('stores')->findOrFail($key);
        $before = [
            'region'      => $region->toArray(),
            'store_codes' => $region->stores->pluck('store_code')->toArray(),
        ];

        $data = $request->validate([
            'region_label'  => 'sometimes|string|max:100',
            'store_codes'   => 'nullable|array',
            'store_codes.*' => 'string|exists:settings_stores,store_code',
        ]);

        if (isset($data['region_label'])) {
            $region->update(['label' => $data['region_label']]);
        }

        SettingsStore::where('region_key', $key)
            ->update(['region_key' => null, 'updated_by' => auth()->id()]);

        if (!empty($data['store_codes'])) {
            SettingsStore::whereIn('store_code', $data['store_codes'])
                ->update(['region_key' => $key, 'updated_by' => auth()->id()]);
        }

        SettingsAuditLog::record('region', $key, 'updated', $before, [
            'region'      => $region->fresh()->toArray(),
            'store_codes' => $data['store_codes'] ?? [],
        ]);
        $this->flushCache();

        return redirect()->route('settings.index')->with('success', "Region '{$key}' updated.");
    }

    // ── REGION EMAILS ────────────────────────────────────────────────────


    // ── REGION APPROVER ──────────────────────────────────────────────────

    public function updateRegionApprover(Request $request, string $key)
    {
        abort_unless(auth()->user()->role === 'super admin', 403);

        $data = $request->validate([
            'approver_user_id' => 'nullable|exists:users,id',
        ]);

        SettingsRegion::where('region_key', $key)->firstOrFail();

        $userId = $data['approver_user_id'] ?: null;
        $before = SettingsRegionEmail::where('region_key', $key)
            ->where('email', '__approver__')->value('label');

        // Remove all existing non-sentinel rows so the approver's email
        // becomes the sole notification recipient for this region.
        SettingsRegionEmail::where('region_key', $key)
            ->where('email', '!=', '__approver__')
            ->delete();

        if ($userId) {
            $user = \App\Models\User::findOrFail($userId);

            // Sentinel row: tracks the approver user ID for document generation.
            SettingsRegionEmail::updateOrCreate(
                ['region_key' => $key, 'email' => '__approver__'],
                ['label' => (string) $userId, 'is_active' => false, 'created_by' => auth()->id()]
            );

            // Real email row: used by emailsForRegion() for notification sending.
            SettingsRegionEmail::updateOrCreate(
                ['region_key' => $key, 'email' => $user->email],
                ['label' => $user->name, 'is_active' => true, 'created_by' => auth()->id()]
            );
        } else {
            SettingsRegionEmail::where('region_key', $key)
                ->where('email', '__approver__')->delete();
        }

        SettingsAuditLog::record('region', $key, 'approver_updated',
            ['approver_user_id' => $before],
            ['approver_user_id' => $userId]
        );

        $this->flushCache();

        $approverName = $userId ? \App\Models\User::find($userId)?->name : null;

        return response()->json([
            'ok'            => true,
            'message'       => 'Approver updated.',
            'approver_name' => $approverName,
        ]);
    }

    // ── CACHE ────────────────────────────────────────────────────────────

    private function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
