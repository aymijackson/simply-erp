<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use \Modules\Inventory\Models\Product\Product;

class BillOfMaterial extends Model
{
    protected $fillable = [
        'product_id', 'version', 'description', 'notes', 'is_active'
    ];

    public function items()
    {
        return $this->hasMany(BOMItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
