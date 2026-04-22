<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class SettingsRegion extends Model
{
    protected $primaryKey  = 'region_key';
    protected $keyType     = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'region_key',
        'label',
        'is_active',
        'created_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function stores()
    {
        return $this->hasMany(SettingsStore::class, 'region_key', 'region_key');
    }
}
