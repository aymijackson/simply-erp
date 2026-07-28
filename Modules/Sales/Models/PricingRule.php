<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $table = 'pricing_rules';

    protected $fillable = [
        'company_id',
        'name',
        'apply_on',
        'apply_to_id',
        'discount_type',
        'discount_value',
        'min_order_qty',
        'min_order_amount',
        'valid_from',
        'valid_to',
        'priority',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_qty' => 'decimal:4',
        'min_order_amount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];
}
