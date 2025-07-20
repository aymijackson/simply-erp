<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\BrandFactory;
use Modules\Inventory\Models\BrandManufacturer;

class ProductModel extends Model
{
    use HasFactory;

    protected $table = 'models';
    protected $fillable = ['manufacturer_id', 'brand_code', 'brand_name'];

    public function manufacturer()
    {
        return $this->belongsTo(BrandManufacturer::class, 'manufacturer_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productInstances()
    {
        return $this->hasMany(ProductInstance::class);
    }
}
