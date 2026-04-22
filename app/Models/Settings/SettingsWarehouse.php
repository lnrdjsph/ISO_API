<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class SettingsWarehouse extends Model
{
    protected $primaryKey  = 'warehouse_code';
    protected $keyType     = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'warehouse_code',
        'name',
        'facility_id',
        'is_active',
        'created_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function stores()
    {
        return $this->hasMany(SettingsStore::class, 'warehouse_code', 'warehouse_code');
    }
}
