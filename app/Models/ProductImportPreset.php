<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProductImportPreset extends Model
{
    protected $table = 'product_import_presets';

    protected $fillable = [
        'name',
        'store_code',
        'target_month',
        'status',
        'rows',
        'insert_count',
        'update_count',
        'error_count',
        'errors',
        'notes',
        'created_by',
        'applied_by',
        'applied_at',
    ];

    protected $casts = [
        'rows'         => 'array',
        'errors'       => 'array',
        'target_month' => 'date',
        'applied_at'   => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applier()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
