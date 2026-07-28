<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\{
    StockEntry, StockTransaction, Product\ProductVariant
};

class CustomerReturnService
{
    /** Create + lines in one go (draft) */
    public function create(array $hdr, array $lines): StockEntry
    {
        return DB::transaction(function () use ($hdr, $lines) {

            $hdr['entry_type'] = 'cust_return';
            $hdr['status']     = 'draft';
            $hdr['id']   = $hdr['id'] ?? $this->nextNo();

            $entry = StockEntry::create($hdr);
            $entry->lines()->createMany($lines);

            return $entry->load(['store','customer','lines.product_variant']);
        });
    }

    /** Draft → approved */
    public function approve(StockEntry $entry): void
    {
        abort_if($entry->entry_type !== 'cust_return', 400);
        abort_if($entry->status !== 'draft',           400,'Already approved');

        $entry->update(['status'=>'approved','approved_by'=>auth()->id()]);
    }

    /** Approved → posted (creates stock_transactions) */
    public function post(StockEntry $entry): void
    {
        abort_if($entry->entry_type !== 'cust_return', 400);
        abort_if($entry->status !== 'approved',        400,'Not approved');

        DB::transaction(function () use ($entry) {

            foreach ($entry->lines as $ln) {
                /* ledger row (positive qty because stock comes **in**) */
                StockTransaction::create([
                    'product_variant_id'=> $ln->product_variant_id,
                    'location_store_id' => $entry->store_id,
                    'tx_type'           => 'CUST_RETURN',
                    'qty'               =>  $ln->qty,
                    'unit_cost'         =>  $ln->unit_cost ?? 0,
                    'customer_id'       =>  $entry->customer_id,
                    'txable_type'       =>  get_class($entry),
                    'txable_id'         =>  $entry->id,
                    'tx_date'           =>  $entry->entry_date,
                    'posted_at'         =>  now(),
                ]);

                /* running balance on master table */
                ProductVariant::whereKey($ln->product_variant_id)
                              ->lockForUpdate()
                              ->increment('stock_quantity', $ln->qty);
            }

            $entry->update([
                'status'    => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);
        });
    }

    /* ------------ helpers ------------ */
    protected function nextNo(): string
    {
        $seq = (int) StockEntry::where('entry_type','cust_return')->max('id') + 1;
        return 'RET-'.date('Y').'-'.sprintf('%05d',$seq);
    }
}
