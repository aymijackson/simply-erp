<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\{
    StockIssue, StockTransaction, Product\ProductVariant
};
use Modules\Production\Models\BomItem;
use Modules\Sales\Models\SalesDeliveryLine;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Modules\Production\Services\BomDeficitService;

class StockIssueService
{
        public function post(StockIssue $hdr): void
    {
        // better guard message (optional):
        abort_if($hdr->status === 'posted', 400, 'Issue already posted');
        abort_if($hdr->status !== 'approved', 422, 'Only approved issues can be posted');

        DB::transaction(function () use ($hdr) {
            // 0) Create/link bom_items & sales_delivery_lines (and update BOM qty if needed)
            $this->ensureLinks($hdr);

            // 0b) Reconcile BOM deficits now (only if posting to a BOM)
            if ((int) $hdr->bom_header_id > 0) {
                $svc = app(BomDeficitService::class);
                $hdr->loadMissing('lines');
                foreach ($hdr->lines as $ln) {
                    $svc->reconcileOnIssue(
                        (int) $hdr->bom_header_id,
                        (int) $ln->product_variant_id,
                        (float) $ln->qty,
                        (float) $ln->unit_cost,
                        $hdr
                    );
                }
            }

            // 1) Availability check
            $this->guardAvailability($hdr);

            // 2) Post each line (transactions + decrement on-hand)
            foreach ($hdr->lines as $ln) {
                $cost = $this->currentUnitCost($ln->product_variant_id, $hdr->from_store_id);
                $ln->update(['unit_cost' => $cost]);

                StockTransaction::create([
                    'product_variant_id' => $ln->product_variant_id,
                    'location_store_id'  => $hdr->from_store_id,
                    'tx_type'            => 'ISSUE',
                    'qty'                => $ln->qty,
                    'unit_cost'          => $cost,
                    'txable_type'        => StockIssue::class,
                    'txable_id'          => $hdr->id,
                    'tx_date'            => now(),
                    'posted_at'          => now(),
                ]);

                Log::info('Stock issued', [
                    'issue_id'   => $hdr->id,
                    'line_id'    => $ln->id,
                    'variant_id' => $ln->product_variant_id,
                    'qty'        => $ln->qty,
                    'unit_cost'  => $cost,
                ]);

                ProductVariant::whereKey($ln->product_variant_id)
                    ->lockForUpdate()
                    ->decrement('stock_quantity', $ln->qty);
            }

            // 3) Mark posted
            $hdr->update([
                'status'    => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);
        });
    }

    /* ------------------------------------------------------------------ */
    /* create/attach bom_items or sales_delivery_lines  (POST ONLY)       */
    /* ------------------------------------------------------------------ */
    private function ensureLinks(StockIssue $hdr): void
    {
        $hdr->loadMissing('lines');

        $isBom   = (int) $hdr->bom_header_id     > 0;
        $isSales = (int) $hdr->sales_delivery_id > 0;

        Log::info('ensureLinks (POST) start', [
            'issue_id'   => $hdr->id,
            'bom_header' => $hdr->bom_header_id,
            'delivery'   => $hdr->sales_delivery_id,
            'lines_cnt'  => $hdr->lines->count(),
        ]);

        if ($hdr->lines->isEmpty() || (!$isBom && !$isSales)) {
            Log::warning('ensureLinks: skipped (no lines or no header links).');
            return;
        }

        $linesTable   = $hdr->lines()->getModel()->getTable();
        $hasBomFk     = Schema::hasColumn($linesTable, 'bom_item_id');
        $hasDelLineFk = Schema::hasColumn($linesTable, 'sales_delivery_line_id');

        foreach ($hdr->lines as $ln) {
            // If this issue is to a BOM, auto-repay deficits for that BOM/variant
            if ((int)$hdr->bom_header_id > 0) {
                app(BomDeficitService::class)->repayIfOutstanding(
                    bomId:     (int)$hdr->bom_header_id,
                    variantId: (int)$ln->product_variant_id,
                    qty:       (float)$ln->qty,
                    unitCost:  (float)$ln->cost,
                    refType:   \Modules\Inventory\Models\StockIssue::class, // or 'stock_issue'
                    refId:     (int)$hdr->id,
                    note:      'Auto-repay via Stock Issue #'.$hdr->issue_no
                );
            }

            // --- BOM linking & update recipe qty (increment) ---
            if ($isBom) {
                $item = BomItem::firstOrCreate(
                    [
                        'bom_header_id'      => (int) $hdr->bom_header_id,
                        'product_variant_id' => (int) $ln->product_variant_id,
                    ],
                    ['qty_per_parent' => 0]
                );

                $issueQty = (float) $ln->qty;

                // Default behavior: INCREMENT the recipe by issue qty
                if ($item->wasRecentlyCreated) {
                    $item->qty_per_parent = $issueQty;
                    $item->save();
                } else {
                    $item->increment('qty_per_parent', $issueQty);
                    $item->refresh();
                }

                // (If you prefer REPLACE instead of increment, use:)
                // $item->update(['qty_per_parent' => $issueQty]);

                Log::info('BOM item ensured/updated (POST)', [
                    'issue_id'    => $hdr->id,
                    'line_id'     => $ln->id,
                    'bom_item_id' => $item->id,
                    'variant_id'  => $ln->product_variant_id,
                    'qty_now'     => $item->qty_per_parent,
                ]);

                if ($hasBomFk && (int)$ln->bom_item_id !== (int)$item->id) {
                    $ln->bom_item_id = $item->id;
                    $ln->save();
                }
            }

            // --- Sales Delivery linking ---
            if ($isSales) {
                $delLine = SalesDeliveryLine::firstOrCreate(
                    [
                        'sales_delivery_id'  => (int) $hdr->sales_delivery_id,
                        'product_variant_id' => (int) $ln->product_variant_id,
                    ],
                    [
                        'qty_delivered' => 0,
                        'unit_cost'     => (float) ($ln->unit_cost ?? 0),
                    ]
                );

                Log::info('Delivery line ensured (POST)', [
                    'issue_id'         => $hdr->id,
                    'line_id'          => $ln->id,
                    'delivery_line_id' => $delLine->id,
                    'variant_id'       => $ln->product_variant_id,
                ]);

                if ($hasDelLineFk && (int)$ln->sales_delivery_line_id !== (int)$delLine->id) {
                    $ln->sales_delivery_line_id = $delLine->id;
                    $ln->save();
                }
            }
        }
    }
        
    /* ------------------------------------------------------------------ */
    /* availability / cost helpers (unchanged from original)              */
    /* ------------------------------------------------------------------ */
    private function guardAvailability(StockIssue $hdr): void
    {
        $short = collect($hdr->lines)
            ->groupBy('product_variant_id')
            ->map(function ($grp, $variantId) use ($hdr) {
                $need = $grp->sum('qty');
                $have = $this->onHand($variantId, $hdr->from_store_id);
                return $have < $need ? [
                    'sku'  => $grp->first()->variant->sku,
                    'need' => $need,
                    'have' => $have,
                ] : null;
            })->filter();

        if ($short->isNotEmpty()) {

            Log::warning('Stock shortage detected', [
                'issue_id'  => $hdr->id,
                'shortages' => $short->values(),
            ]);

            $msg = $short->map(fn ($s) =>
                "{$s['sku']} (have {$s['have']}, need {$s['need']})"
            )->implode(', ');

            throw ValidationException::withMessages([
                'qty' => "Insufficient stock for: $msg"
            ]);
        }
    }

    protected function currentUnitCost(int $variantId, int $storeId): float
    {
        if (DB::selectOne("SHOW FULL TABLES LIKE 'v_stock_layers'")) {
            return DB::table('v_stock_layers')
                ->where('product_variant_id', $variantId)
                ->where('location_store_id', $storeId)
                ->orderBy('created_at')
                ->value('unit_cost') ?? 0;
        }
        return DB::table('stock_transactions')
            ->where('product_variant_id', $variantId)
            ->where('location_store_id', $storeId)
            ->whereIn('tx_type', ['ENTRY','TRANSFER_IN','ADJUST_POS'])
            ->latest('created_at')
            ->value('unit_cost') ?? 0;
    }

    protected function onHand(int $variantId, int $storeId): float
    {
        return DB::table('v_stock_levels')
            ->where('product_variant_id', $variantId)
            ->where('location_store_id', $storeId)
            ->value('qty_on_hand') ?? 0;
    }
}
