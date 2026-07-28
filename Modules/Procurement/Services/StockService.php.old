<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\{
    StockEntry,
    StockTransaction,
    StockReturn,
    Product\ProductVariant
};

class StockService
{
    /**
     * Post (finalise) a stock-entry document.
     *
     * ─ entry_type = normal      → tx_type ENTRY      (positive)
     * ─ entry_type = cust_return → tx_type RETURN_IN  (positive)
     */
    public static function postEntry(StockEntry $entry): void
    {
        /* Guardrails ---------------------------------------------------- */
        abort_if($entry->status !== 'approved' && $entry->status !== 'posted',        // only approved drafts can post
                 400, 'Entry already posted or not approved');

        /* 100 % atomic update ------------------------------------------ */
        DB::transaction(function () use ($entry) {

            $txType = $entry->entry_type === 'cust_return'
                    ? 'RETURN_IN'
                    : 'ENTRY';
            
            if( $entry->entry_type === 'cust_return')
            {
                StockReturn::create([
                    'return_no'         =>  'CUS: '.rand(),
                    'return_type'       =>  'customer',
                    'store_id'          =>  $entry->store_id,
                    'reference_id'      =>  $entry->customer_id,   // still OK
                    'reference_type'    =>  'Modules/CRM/Customer',
                    'reason'            =>  'Customer Returned',
                    'status'            =>  'posted', // NEW (optional)
                    'posted_at'         =>  date('Y-m-d H:i:s'), //$entry->entry_date,
                    'posted_by'         =>  NULL, //auth()->id(),
                    'status'            =>  'posted',
                ]);
            }

            foreach ($entry->lines as $line) {

                /* ---------- Ledger row ---------- */
                StockTransaction::create([
                    'product_variant_id' => $line->product_variant_id,
                    'location_store_id'  => $entry->store_id,
                    'tx_type'            => $txType,
                    'qty'                =>  $line->qty,               // always positive
                    'unit_cost'          =>  $line->unit_cost,
                    'txable_type'        => get_class($entry),
                    'txable_id'          => $entry->id,
                    'tx_date'            => $entry->entry_date,
                    'supplier_id'        => $entry->supplier_id   ?? null,   // still OK
                    'customer_id'        => $entry->customer_id   ?? null,   // NEW (optional)
                ]);

                /* ---------- Running balance (redundant column) ---------- */
                ProductVariant::whereKey($line->product_variant_id)
                              ->lockForUpdate()
                              ->increment('stock_quantity', $line->qty);
            }

            /* ---------- Stamp the header ---------- */
            $entry->update([
                'status'    => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);
        });
    }
}
