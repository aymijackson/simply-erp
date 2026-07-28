<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Modules\CRM\Models\Customer;

class PriceList extends Model
{
    use SoftDeletes;

    protected $table = 'price_lists';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'currency_code',
        'type',
        'is_default',
        'valid_from',
        'valid_to',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
        'valid_from' => 'date',
        'valid_to'   => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function items()
    {
        return $this->hasMany(PriceListItem::class, 'price_list_id');
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_price_lists',
            'price_list_id', 'customer_id')
            ->withTimestamps();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')
                  ->orWhere('valid_to', '>=', now()->toDateString());
            });
    }

    public function scopeForSale(Builder $query): Builder
    {
        return $query->where('type', 'sale');
    }

    public function scopeForPurchase(Builder $query): Builder
    {
        return $query->where('type', 'purchase');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Get price for a specific variant and qty from this list.
     * Returns the item with the highest min_qty that is still <= $qty.
     */
    public function priceFor(int $variantId, float $qty = 1.0): ?float
    {
        $item = $this->items()
            ->where('product_variant_id', $variantId)
            ->where('min_qty', '<=', $qty)
            ->orderByDesc('min_qty')
            ->first();

        return $item ? (float) $item->unit_price : null;
    }

    public function getIsCurrentlyValidAttribute(): bool
    {
        $today = now()->toDateString();
        $fromOk = ! $this->valid_from || $this->valid_from->lte(now());
        $toOk   = ! $this->valid_to   || $this->valid_to->gte(now());
        return $this->is_active && $fromOk && $toOk;
    }
}