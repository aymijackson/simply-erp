<?php

namespace Modules\Sales\Services;

use Modules\Sales\Models\PriceList;
use Modules\Sales\Models\PricingRule;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\CRM\Models\Customer;

/**
 * PricingService
 *
 * Resolves the best price for a variant given a customer, qty, and context.
 *
 * Price resolution order (highest priority first):
 *  1. Customer-assigned price list (if customer has one assigned)
 *  2. Default sale price list (company-wide)
 *  3. Variant base price (product_variants.price)
 *
 * After base price is resolved, active PricingRules are applied in
 * priority order (lowest priority number wins / stacks).
 */
class PricingService
{
    // ── Main Entry Point ──────────────────────────────────────────────────────

    /**
     * Resolve the unit price for a variant in an order context.
     *
     * @param  int        $variantId
     * @param  float      $qty
     * @param  int|null   $customerId
     * @param  string     $currencyCode
     * @return array{unit_price: float, source: string, price_list_id: int|null, discount_applied: bool}
     */
    public function resolve(
        int $variantId,
        float $qty = 1.0,
        ?int $customerId = null,
        string $currencyCode = 'USD'
    ): array {
        $variant = ProductVariant::find($variantId);

        if (! $variant) {
            return $this->result(0.0, 'not_found', null, false);
        }

        // Step 1: Find the right price list
        [$basePrice, $source, $priceListId] = $this->resolveBasePrice(
            $variant, $qty, $customerId, $currencyCode
        );

        // Step 2: Apply any active pricing rules on top
        [$finalPrice, $discountApplied] = $this->applyRules(
            $basePrice, $variantId, $customerId, $qty
        );

        return $this->result($finalPrice, $source, $priceListId, $discountApplied);
    }

    // ── Base Price Resolution ─────────────────────────────────────────────────

    private function resolveBasePrice(
        ProductVariant $variant,
        float $qty,
        ?int $customerId,
        string $currencyCode
    ): array {
        // 1. Customer-specific price list
        if ($customerId) {
            $customerList = $this->getCustomerPriceList($customerId, $currencyCode);

            if ($customerList) {
                $price = $customerList->priceFor($variant->id, $qty);
                if ($price !== null) {
                    return [$price, 'customer_price_list', $customerList->id];
                }
            }
        }

        // 2. Default company price list
        $defaultList = $this->getDefaultPriceList($currencyCode);

        if ($defaultList) {
            $price = $defaultList->priceFor($variant->id, $qty);
            if ($price !== null) {
                return [$price, 'default_price_list', $defaultList->id];
            }
        }

        // 3. Variant base price
        return [(float) $variant->price, 'variant_base_price', null];
    }

    // ── Rule Application ──────────────────────────────────────────────────────

    private function applyRules(
        float $price,
        int $variantId,
        ?int $customerId,
        float $qty
    ): array {
        $rules = PricingRule::active()
            ->where(function ($q) use ($variantId, $customerId) {
                $q->where('apply_on', 'all')
                  ->orWhere(fn($q2) => $q2->where('apply_on', 'product')
                      ->where('apply_to_id', $variantId))
                  ->orWhere(fn($q2) => $q2->where('apply_on', 'customer')
                      ->where('apply_to_id', $customerId));
            })
            ->where(function ($q) use ($qty) {
                $q->whereNull('min_order_qty')
                  ->orWhere('min_order_qty', '<=', $qty);
            })
            ->orderBy('priority')
            ->get();

        if ($rules->isEmpty()) {
            return [$price, false];
        }

        $finalPrice = $price;
        foreach ($rules as $rule) {
            $finalPrice = $rule->applyTo($finalPrice);
        }

        return [$finalPrice, true];
    }

    // ── Price List Lookups ────────────────────────────────────────────────────

    private function getCustomerPriceList(int $customerId, string $currencyCode): ?PriceList
    {
        return PriceList::active()
            ->forSale()
            ->where('currency_code', $currencyCode)
            ->whereHas('customers', fn($q) => $q->where('customer_id', $customerId))
            ->orderByDesc('is_default')
            ->first();
    }

    private function getDefaultPriceList(string $currencyCode): ?PriceList
    {
        // First try default=true + currency match
        $list = PriceList::active()
            ->forSale()
            ->where('currency_code', $currencyCode)
            ->where('is_default', true)
            ->first();

        if ($list) return $list;

        // Fall back to any active sale list for this currency
        return PriceList::active()
            ->forSale()
            ->where('currency_code', $currencyCode)
            ->first();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function result(float $price, string $source, ?int $priceListId, bool $discountApplied): array
    {
        return [
            'unit_price'       => round($price, 4),
            'source'           => $source,
            'price_list_id'    => $priceListId,
            'discount_applied' => $discountApplied,
        ];
    }

    /**
     * Batch resolve prices for multiple variants at once.
     * Used by the create order form to pre-populate prices via AJAX.
     *
     * @param  array   $variantIds
     * @param  float   $qty
     * @param  int|null $customerId
     * @param  string  $currencyCode
     * @return array   [variantId => resolved result]
     */
    public function resolveBatch(
        array $variantIds,
        float $qty = 1.0,
        ?int $customerId = null,
        string $currencyCode = 'USD'
    ): array {
        $results = [];
        foreach ($variantIds as $variantId) {
            $results[$variantId] = $this->resolve((int) $variantId, $qty, $customerId, $currencyCode);
        }
        return $results;
    }

    /**
     * Get all price lists for a customer (for display in order form).
     */
    public function getCustomerPriceLists(int $customerId): \Illuminate\Support\Collection
    {
        return PriceList::active()
            ->forSale()
            ->whereHas('customers', fn($q) => $q->where('customer_id', $customerId))
            ->get(['id', 'name', 'currency_code', 'is_default']);
    }
}