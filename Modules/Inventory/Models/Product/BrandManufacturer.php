<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\BrandManufacturerFactory;
use Modules\Inventory\Models\Brand;

class BrandManufacturer extends Model
{
    use HasFactory;

    protected $fillable = ['manufacturer_name'];

    public function brands()
    {
        return $this->hasMany(Brand::class, 'manufacturer_id');
    }
}
