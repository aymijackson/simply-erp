<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\ProductPriceRecordFactory;

class ProductPriceRecord extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'from_date', 'product_price'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
