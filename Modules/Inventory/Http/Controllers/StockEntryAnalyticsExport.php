<?php

namespace Modules\Inventory\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;

class StockEntryAnalyticsExport implements WithMultipleSheets
{
    public function __construct(public array $data) {}

    public function sheets(): array
    {
        return [
            new ArraySheet('KPIs', [
                ['Metric','Value'],
                ['Total Entries', $this->data['kpis']['total']],
                ['Draft', $this->data['kpis']['draft']],
                ['Approved', $this->data['kpis']['approved']],
                ['Posted', $this->data['kpis']['posted']],
                ['Total Value', $this->data['kpis']['value']],
                ['From', $this->data['filters']['from']],
                ['To', $this->data['filters']['to']],
            ]),
            new ArraySheet('Trend', collect($this->data['trend'])->map(fn($r)=>[$r['d'],$r['c']])->prepend(['Date','Count'])->toArray()),
            new ArraySheet('Top Stores', collect($this->data['topStores'])->map(fn($r)=>[$r['label'],$r['value']])->prepend(['Store','Count'])->toArray()),
        ];
    }
}

class ArraySheet implements FromArray
{
    public function __construct(public string $title, public array $rows) {}
    public function array(): array { return $this->rows; }
    public function title(): string { return $this->title; }
}
