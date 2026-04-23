<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class SettingsRegionEmail extends Model
{
    protected $table = 'settings_region_emails';

    protected $fillable = [
        'region_key',
        'email',
        'label',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────

    public function region()
    {
        return $this->belongsTo(SettingsRegion::class, 'region_key', 'region_key');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRegion($query, string $regionKey)
    {
        return $query->where('region_key', $regionKey);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Get all active email addresses for a given region key.
     * Returns a plain array of email strings.
     */
    public static function emailsForRegion(string $regionKey): array
    {
        return self::active()
            ->forRegion($regionKey)
            ->pluck('email')
            ->toArray();
    }

    /**
     * Get all active email addresses for a given store code.
     * Resolves the store → region via LocationConfig, then fetches emails.
     */
    public static function emailsForStore(string $storeCode): array
    {
        // Find which region this store belongs to
        $regions = \App\Support\LocationConfig::regions(); // ['lz' => ['6012'], 'vs' => [...], ...]

        $regionKey = null;
        foreach ($regions as $key => $stores) {
            if (in_array($storeCode, $stores, true)) {
                $regionKey = $key;
                break;
            }
        }

        if (!$regionKey) {
            return [];
        }

        return self::emailsForRegion($regionKey);
    }
}
