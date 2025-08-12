<?php

// app/Exports/StockEntryExport.php
namespace Modules\Inventory\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel; 


class StockEntryExport
{
    protected Collection $entries;

    public function __construct($entries) { $this->entries = $entries; }

    public function collection()
    {
        return $this->entries->map(function ($e) {
            return [
                'Reference'   => $e->reference,
                'Date'        => $e->entry_date->format('Y-m-d'),
                'Store'       => $e->store->name,
                'Type'        => $e->entry_type === 'cust_return' ? 'Return' : 'Normal',
                'Supplier'    => optional($e->supplier)->name,
                'Customer'    => optional($e->customer)->name,
                'Status'      => ucfirst($e->status),
                'Line count'  => $e->lines->count(),
                'Total Qty'   => $e->lines->sum('qty'),
                'Total Value' => $e->lines->sum(fn ($l) => $l->qty * $l->unit_cost),
            ];
        });
    }

    public function headings(): array
    {
        return ['Ref', 'Date', 'Store', 'Type', 'Supplier', 'Customer',
                'Status', 'Lines', 'Qty', 'Value'];
    }
}
