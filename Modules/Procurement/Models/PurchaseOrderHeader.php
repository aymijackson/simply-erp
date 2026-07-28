<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\PurchaseOrderHeaderFactory;

class PurchaseOrderHeader extends Model
{
    use HasFactory;

    protected $fillable = ['supplier_id', 'purchase_date', 'total_amount'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }
}
