<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPaymentAllocation extends Model
{
    protected $table = 'sales_payment_allocations';

    protected $fillable = ['sales_payment_id','sales_invoice_id','amount_applied'];

    public function payment()
    {
        return $this->belongsTo(SalesPayment::class, 'sales_payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
