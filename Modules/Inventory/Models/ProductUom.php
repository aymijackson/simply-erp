<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\ProductUomFactory;

class ProductUom extends Model
{
    use HasFactory;

    protected $fillable = ['uom_name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'default_uom');
    }

    public function conversionsFrom()
    {
        return $this->hasMany(ProductUomConversion::class, 'from_uom_id');
    }

    public function conversionsTo()
    {
        return $this->hasMany(ProductUomConversion::class, 'to_uom_id');
    }
}
