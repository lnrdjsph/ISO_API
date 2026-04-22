<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class SettingsStore extends Model
{
    protected $primaryKey  = 'store_code';
    protected $keyType     = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'store_code',
        'display_name',
        'short_name',
        'warehouse_code',
        'region_key',
        'status',
        'created_by',
        'updated_by',
    ];

    public function warehouse()
    {
        return $this->belongsTo(SettingsWarehouse::class, 'warehouse_code', 'warehouse_code');
    }

    public function region()
    {
        return $this->belongsTo(SettingsRegion::class, 'region_key', 'region_key');
    }

    public function scopeVisible($query)
    {
        return $query->whereIn('status', ['active', 'pending']);
    }
}
