<?php
// Modules/Sales/Models/SalesDeliveryLine.php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDeliveryLine extends Model
{
    use HasFactory;

    protected $table = 'sales_delivery_lines';

    protected $fillable = [
        'sales_delivery_id',
        'product_variant_id',
        'qty',
        'unit_cost',       // pulled from inventory layer for COGS
        'invoice_line_id', // back-link after invoicing (nullable)
        'stock_tx_id',     // ledger row created when posting ISSUE
    ];

    protected $casts = [
        'qty'       => 'float',
        'unit_cost' => 'float',
    ];

    /* ─────────────── Relationships ─────────────── */

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(SalesDelivery::class, 'sales_delivery_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product\ProductVariant::class, 'product_variant_id');
    }

    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceLine::class, 'invoice_line_id');
    }

    public function stockTx(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\StockTransaction::class, 'stock_tx_id');
    }
}
