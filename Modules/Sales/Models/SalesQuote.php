<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\Models\Customer;

class SalesQuote extends Model
{
    use SoftDeletes;

    protected $table = 'sales_quotes';

    protected $fillable = [
        'company_id',
        'customer_id',
        'quote_no',
        'quote_date',
        'valid_until',
        'currency_code',
        'reference',
        'notes',
        'subtotal',
        'tax_total',
        'discount_total',
        'total_amount',
        'status',
        'sent_at',
        'sent_by',
        'won_at',
        'won_by',
        'rejected_at',
        'rejected_by',
        'expired_at',
        'reviewed_at',
        'reviewed_by',
        'review_comments',
        'converted_at',
        'converted_by',
        'sales_order_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quote_date'   => 'date',
        'valid_until'  => 'date',
        'sent_at'      => 'datetime',
        'won_at'       => 'datetime',
        'rejected_at'  => 'datetime',
        'expired_at'   => 'datetime',
        'reviewed_at'  => 'datetime',
        'converted_at' => 'datetime',
        'subtotal'       => 'decimal:2',
        'tax_total'      => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total_amount'   => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function lines()
    {
        return $this->hasMany(SalesQuoteLine::class, 'sales_quote_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }
}
