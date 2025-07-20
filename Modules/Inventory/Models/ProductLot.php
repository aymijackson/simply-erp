<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\ProductLotFactory;

class ProductLot extends Model
{
    use HasFactory;

    protected $fillable = ['lot_code', 'date_manufactured', 'date_expiry', 'product_attribute_value_id'];

    public function attributeValue()
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }
}
