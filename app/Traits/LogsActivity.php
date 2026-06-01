<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    /**
     * Log a user activity.
     *
     * @param string      $action       e.g. 'user.created', 'order.approved', 'product.bulk_update'
     * @param string|null $description  Human-readable description
     * @param array       $properties   Extra context (model IDs, before/after, etc.)
     */
    protected function logActivity(string $action, ?string $description = null, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'user_id'     => auth()->id(),
                'action'      => $action,
                'description' => $description ?? $this->defaultDescription($action),
                'properties'  => array_merge($properties, [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'url'        => request()->fullUrl(),
                    'method'     => request()->method(),
                ]),
            ]);
        } catch (\Throwable $e) {
            // Never let logging break the main flow
            Log::error('ActivityLog failed: ' . $e->getMessage(), [
                'action' => $action,
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Log changes to a model (diff-style).
     */
    protected function logModelChange(string $action, $model, array $before = [], array $after = []): void
    {
        $changes = [];
        foreach ($after as $key => $newVal) {
            $oldVal = $before[$key] ?? null;
            if ($oldVal != $newVal) {
                $changes[$key] = ['from' => $oldVal, 'to' => $newVal];
            }
        }

        $this->logActivity($action, null, [
            'model'      => class_basename($model),
            'model_id'   => $model->id ?? null,
            'changes'    => $changes,
        ]);
    }

    private function defaultDescription(string $action): string
    {
        $user = auth()->user();
        $who  = $user ? "{$user->name} ({$user->role})" : 'Guest';
        return "{$who} performed {$action}";
    }
}
