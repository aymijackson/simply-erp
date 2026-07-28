<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SalesCreditNote extends Model
{
    protected $table = 'sales_credit_notes';

    protected $fillable = [
        'customer_id','sales_invoice_id','stock_return_id',
        'credit_note_no','credit_note_date','currency_code',
        'subtotal','tax_total','grand_total',
        'reason','remarks','status',
        'posted_at','posted_by','voided_at','voided_by'
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
    ];

    public function customer(){ return $this->belongsTo(\Modules\CRM\Models\Customer::class, 'customer_id'); }
    public function invoice(){ return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); }
    public function stockReturn(){ return $this->belongsTo(\Modules\Inventory\Models\StockReturn::class, 'stock_return_id'); }

    public function lines(){ return $this->hasMany(SalesCreditNoteLine::class, 'sales_credit_note_id'); }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status){
            'posted' => 'success',
            'void'   => 'danger',
            default  => 'secondary', // draft
        };
    }
}
