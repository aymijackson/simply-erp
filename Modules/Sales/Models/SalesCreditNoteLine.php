<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SalesCreditNoteLine extends Model
{
    protected $table = 'sales_credit_note_lines';

    protected $fillable = [
        'sales_credit_note_id',
        'sales_invoice_line_id',
        'product_variant_id',
        'description',
        'qty','unit_price',
        'tax_rate','tax_amount',
        'line_total'
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function creditNote(){ return $this->belongsTo(SalesCreditNote::class, 'sales_credit_note_id'); }
    public function invoiceLine(){ return $this->belongsTo(SalesInvoiceLine::class, 'sales_invoice_line_id'); }
    public function variant(){ return $this->belongsTo(\Modules\Inventory\Models\ProductVariant::class, 'product_variant_id'); }
}
