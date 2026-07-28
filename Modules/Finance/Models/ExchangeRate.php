<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ExchangeRate extends Model
{
    protected $table = 'exchange_rates';

    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'rate_date',
        'source',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rate'      => 'decimal:8',
        'rate_date' => 'date',
        'is_active' => 'boolean',
    ];

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPair(Builder $query, string $base, string $quote): Builder
    {
        return $query->where('base_currency', strtoupper($base))
                     ->where('quote_currency', strtoupper($quote));
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Get the most recent active rate for a currency pair on or before a given date.
     * Falls back to the latest rate if no exact-date match.
     */
    public static function getRate(string $base, string $quote, ?string $asOfDate = null): ?self
    {
        $asOfDate ??= now()->toDateString();

        return static::active()
            ->forPair($base, $quote)
            ->where('rate_date', '<=', $asOfDate)
            ->orderByDesc('rate_date')
            ->first();
    }

    /**
     * Convert an amount from base to quote currency.
     */
    public static function convert(float $amount, string $base, string $quote, ?string $asOfDate = null): ?float
    {
        if ($base === $quote) {
            return $amount;
        }

        $rate = static::getRate($base, $quote, $asOfDate);

        return $rate ? round($amount * (float) $rate->rate, 4) : null;
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}