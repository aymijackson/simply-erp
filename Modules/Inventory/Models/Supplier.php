<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\SupplierFactory;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['supplier_code', 'supplier_name', 'supplier_type'];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrderHeader::class);
    }
}
