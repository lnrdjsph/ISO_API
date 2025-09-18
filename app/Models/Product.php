<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'description',
        'department',
        'case_pack',
        'srp',
        'allocation_per_case',
        'cash_bank_card_scheme',
        'po15_scheme',
        'discount_scheme',
        'freebie_sku',
        'freebie_description',
        'archived_at',
        'archived_by',
        'archive_reason',
        'updated_by'
    ];

    protected $casts = [
        'srp' => 'decimal:2',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get only non-archived products
     */
    public function scopeActive(Builder $query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope to get only archived products
     */
    public function scopeArchived(Builder $query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Check if product is archived
     */
    public function isArchived()
    {
        return !is_null($this->archived_at);
    }

    /**
     * Archive the product
     */
    public function archive()
    {
        $this->update(['archived_at' => now()]);
    }

    /**
     * Restore the product
     */
    public function restore()
    {
        $this->update(['archived_at' => null]);
    }

    /**
     * Bulk archive multiple products
     */
    public static function bulkArchive(array $ids)
    {
        return static::whereIn('id', $ids)
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);
    }

    /**
     * Bulk restore multiple products
     */
    public static function bulkRestore(array $ids)
    {
        return static::whereIn('id', $ids)
            ->whereNotNull('archived_at')
            ->update(['archived_at' => null]);
    }

    /**
     * Bulk update multiple products
     */
    public static function bulkUpdateFields(array $ids, array $data)
    {
        $data['updated_at'] = now();
        
        return static::whereIn('id', $ids)->update($data);
    }

        /**
     * Relationship with the user who archived the product
     */
    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Relationship with the user who last updated the product
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}