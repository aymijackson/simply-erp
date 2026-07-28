<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\{ StockEntry, StockReturn, StockTransaction };

class StockService
{
    /* … keep your other methods … */

    /** Remove ledger tx + the linked stock_return (header & lines). */
    public static function unpostEntry(StockEntry $entry): void
    {
        // 1) delete ledger
        StockTransaction::whereMorphedTo('txable', $entry)->delete();

        // 2) delete linked return (header + lines via FK or explicit)
        self::deleteReturnForEntry($entry);

        // 3) recalc stock for affected variants
        $variantIds = $entry->lines()->pluck('product_variant_id')->unique();
        foreach ($variantIds as $vid) {
            self::recalculateVariantOnHand($vid);
        }
    }

    /** Post an entry; when customer return, keep stock_returns in sync. */
    public static function postEntry(StockEntry $entry, bool $force = false): void
    {
        $already = StockTransaction::whereMorphedTo('txable', $entry)->exists();
        if ($already && !$force) {
            abort(422, 'Entry already posted. Pass repost=1 or call repostEntry().');
        }
        if (!in_array($entry->status, ['posted'], true)) {
            abort(422, 'Only entries with posted status can be posted.');
        }

        DB::transaction(function () use ($entry, $already, $force) {
            $entry->loadMissing('lines'); // ensure lines are loaded

            if ($already && $force) {
                self::unpostEntry($entry);
            }

            // --- sync stock_returns if this is a customer return ---
            if ($entry->entry_type === 'cust_return') {
                self::upsertReturnForEntry($entry);   // <- rebuild lines here
            }

            $txType = $entry->entry_type === 'cust_return' ? 'RETURN_IN' : 'ENTRY';

            foreach ($entry->lines as $ln) {
                StockTransaction::create([
                    'product_variant_id' => $ln->product_variant_id,
                    'location_store_id'  => $entry->store_id,
                    'tx_type'            => $txType,
                    'qty'                => $ln->qty,
                    'unit_cost'          => $ln->unit_cost,
                    'txable_type'        => get_class($entry),
                    'txable_id'          => $entry->id,
                    'tx_date'            => $entry->entry_date,
                    'supplier_id'        => $entry->supplier_id ?? null,
                    'customer_id'        => $entry->customer_id ?? null,
                ]);

                self::recalculateVariantOnHand($ln->product_variant_id);
            }

            $entry->forceFill([
                'status'    => 'posted',
                'entry_date' => now(),
                'posted_by' => auth()->id(),
            ])->save();
        });
    }

    /* --------------------- stock_returns helpers --------------------- */

    protected static function returnNoFor(StockEntry $entry): string
    {
        // deterministic key so we can find/update the same return
        return 'RET-ENTRY-'.$entry->id;
    }

    /** Create/update the stock_return header and REPLACE its lines. */
    protected static function upsertReturnForEntry(StockEntry $entry): void
    {
        // 1) header
        $sr = StockReturn::updateOrCreate(
            [
                'return_type' => 'customer',
                'return_no'   => self::returnNoFor($entry),
            ],
            [
                'store_id'       => $entry->store_id,
                'reference_id'   => $entry->customer_id,
                'reference_type' => 'Modules\\CRM\\Models\\Customer',
                'reason'         => $entry->reference ?? 'Customer return',
                'status'         => 'posted',
                'posted_at'      => $entry->posted_at ?? now(),
                'posted_by'      => auth()->id(),
            ]
        );

        // 2) lines: wipe & reinsert using the current entry lines
        DB::table('stock_return_lines')->where('stock_return_id', $sr->id)->delete();

        $now  = now();
        $rows = [];
        foreach ($entry->lines as $ln) {
            $rows[] = [
                'stock_return_id'    => $sr->id,
                'product_variant_id' => $ln->product_variant_id,
                'qty'                => $ln->qty,        // fits DECIMAL(14,4)
                'unit_cost'          => $ln->unit_cost,  // DECIMAL(14,4)
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }
        if ($rows) {
            DB::table('stock_return_lines')->insert($rows);
        }
    }

    /** Delete the return generated from this entry (and its lines). */
    protected static function deleteReturnForEntry(StockEntry $entry): void
    {
        // If your FK has ON DELETE CASCADE this single delete is enough.
        // Otherwise delete lines first.
        $sr = StockReturn::where('return_type', 'customer')
            ->where('return_no', self::returnNoFor($entry))
            ->first();

        if ($sr) {
            DB::table('stock_return_lines')->where('stock_return_id', $sr->id)->delete();
            $sr->delete();
        }
    }

    /* --------------------- on-hand calculator --------------------- */

    public static function recalculateVariantOnHand(int $variantId): void
    {
        $sum = DB::table('stock_transactions')
            ->where('product_variant_id', $variantId)
            ->sum('qty');

        DB::table('product_variants')
            ->where('id', $variantId)
            ->update(['stock_quantity' => $sum]);
    }
}
