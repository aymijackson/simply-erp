<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\ExchangeRate;

class ExchangeRateService
{
    // -------------------------------------------------------
    // Datatable
    // -------------------------------------------------------

    public function datatable(array $filters = []): Collection
    {
        return ExchangeRate::query()
            ->when($filters['base_currency'] ?? null, fn($q, $v) => $q->where('base_currency', $v))
            ->when($filters['quote_currency'] ?? null, fn($q, $v) => $q->where('quote_currency', $v))
            ->when($filters['is_active'] ?? null, fn($q, $v) => $q->where('is_active', $v))
            ->orderByDesc('rate_date')
            ->orderBy('base_currency')
            ->get();
    }

    // -------------------------------------------------------
    // CRUD
    // -------------------------------------------------------

    public function store(array $data): ExchangeRate
    {
        $data['base_currency']  = strtoupper($data['base_currency']);
        $data['quote_currency'] = strtoupper($data['quote_currency']);
        $data['created_by']     = auth()->id();
        $data['updated_by']     = auth()->id();

        return ExchangeRate::create($data);
    }

    public function update(ExchangeRate $rate, array $data): ExchangeRate
    {
        $data['base_currency']  = strtoupper($data['base_currency']);
        $data['quote_currency'] = strtoupper($data['quote_currency']);
        $data['updated_by']     = auth()->id();

        $rate->update($data);

        return $rate->fresh();
    }

    public function destroy(ExchangeRate $rate): void
    {
        $rate->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return ExchangeRate::whereIn('id', $ids)->delete();
    }

    public function toggleActive(ExchangeRate $rate): ExchangeRate
    {
        $rate->update([
            'is_active'  => ! $rate->is_active,
            'updated_by' => auth()->id(),
        ]);

        return $rate->fresh();
    }

    // -------------------------------------------------------
    // Lookups
    // -------------------------------------------------------

    /**
     * Return the latest active rate for a pair, for use in transaction forms.
     */
    public function latestRate(string $base, string $quote): ?ExchangeRate
    {
        return ExchangeRate::getRate($base, $quote);
    }

    /**
     * Select2 – distinct base currencies in the system.
     */
    public function baseCurrencies(): Collection
    {
        return ExchangeRate::active()
            ->distinct()
            ->orderBy('base_currency')
            ->pluck('base_currency');
    }

    /**
     * Select2 – all active pairs as [{base, quote, rate, rate_date}].
     */
    public function activePairs(): Collection
    {
        return ExchangeRate::active()
            ->orderByDesc('rate_date')
            ->get(['base_currency', 'quote_currency', 'rate', 'rate_date']);
    }
}