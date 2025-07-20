<?php

namespace Modules\Inventory\Services;
use Modules\Inventory\Models\StockEntry;
use Modules\Inventory\Models\StockEntryLine;    
use Modules\Inventory\Models\StockTransaction;

class StockService
{
    public function handle()
    {
        //
    }

    public static function postEntry(StockEntry $entry)
    {
        foreach ($entry->lines as $line) {
            StockTransaction::create([
                'product_variant_id' => $line->product_variant_id,
                'location_store_id'  => $entry->store_id,
                'tx_type'            => 'ENTRY',
                'qty'                => $line->qty,
                'unit_cost'          => $line->unit_cost,
                'txable_id'          => $entry->id,
                'txable_type'        => StockEntry::class,
                'tx_date'            => $entry->entry_date,
            ]);
        }
    }

}
