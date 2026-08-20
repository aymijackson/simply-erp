<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Product\ProductVariant;

class SalesQuoteLine extends Model
{
    protected $table = 'sales_quote_lines';

    protected $fillable = [
        'sales_quote_id',
        'product_variant_id',
        'description',
        'qty',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'line_total',
    ];

    protected $casts = [
        'qty'              => 'decimal:4',
        'unit_price'       => 'decimal:4',
        'discount_percent' => 'decimal:4',
        'discount_amount'  => 'decimal:2',
        'tax_rate'         => 'decimal:4',
        'tax_amount'       => 'decimal:2',
        'line_total'       => 'decimal:2',
    ];

    public function quote()
    {
        return $this->belongsTo(SalesQuote::class, 'sales_quote_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
