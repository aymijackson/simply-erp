<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Inventory\Models\Product\ProductVariant;

class SalesInvoiceLine extends Model
{
    protected $table = 'sales_invoice_lines';

    // SalesInvoiceLine.php
    protected $fillable = [
      'sales_invoice_id',
      'sales_order_line_id',
      'product_variant_id',
      'line_type',
      'charge_code',
      'description',
      'qty_to_invoice',
      'unit_price',
      'line_total',
      'is_taxable',
      'tax_rate',
      'tax_amount',
    ];
    
    protected $casts = [
      'qty_to_invoice' => 'decimal:4',
      'unit_price'     => 'decimal:4',
      'line_total'     => 'decimal:4',
      'tax_rate'       => 'decimal:4',
      'tax_amount'     => 'decimal:4',
      'is_taxable'     => 'boolean',
    ];


    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class, 'sales_order_line_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
