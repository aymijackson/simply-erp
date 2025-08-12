<?php
// Modules/Sales/Models/SalesInvoiceLine.php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasOne};

class SalesInvoiceLine extends Model
{
    use HasFactory;

    protected $table = 'sales_invoice_lines';

    protected $fillable = [
        'sales_invoice_id',
        'product_variant_id',
        'description',
        'qty',
        'unit_price',
        'tax_rate',
        'line_total',           // qty × unit_price (+tax)
        'delivery_line_id',     // optional back-link to fulfilment
    ];

    protected $casts = [
        'qty'         => 'float',
        'unit_price'  => 'float',
        'tax_rate'    => 'float',
        'line_total'  => 'float',
    ];

    /* ─────────────── Relationships ─────────────── */

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product\ProductVariant::class, 'product_variant_id');
    }

    public function deliveryLine(): BelongsTo
    {
        return $this->belongsTo(SalesDeliveryLine::class, 'delivery_line_id');
    }
}
