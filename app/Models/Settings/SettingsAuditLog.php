<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class SettingsAuditLog extends Model
{
    public    $timestamps = false;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'before',
        'after',
    ];

    protected $casts = [
        'before'     => 'array',
        'after'      => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public static function record(
        string $entityType,
        string $entityId,
        string $action,
        array  $before = [],
        array  $after  = []
    ): void {
        static::create([
            'user_id'     => auth()->id(),
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action,
            'before'      => $before ?: null,
            'after'       => $after  ?: null,
        ]);
    }
}
