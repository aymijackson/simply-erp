<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Document\Traits\HasDocuments;

class Supplier extends Model
{
    use HasDocuments;
    protected $table = 'suppliers';

    protected $fillable = [
        'name','status','default_currency','payment_terms','lead_time_days','rating'
    ];

    public function addresses()
    {
        return $this->hasMany(SupplierAddress::class, 'supplier_id');
    }

    public function contacts()
    {
        return $this->hasMany(SupplierContact::class, 'supplier_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_products', 'supplier_id', 'product_id')
            ->withPivot(['unit_cost','min_order_qty','lead_time_days','last_cost_change']);
    }
}
