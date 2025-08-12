<?php
namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Production\Models\BillOfMaterial;
use Modules\Production\Models\RawMaterial;      
use Modules\Inventory\Models\Product\ProductVariant;

class BomItem extends Model
{
    protected $fillable = ['bom_header_id','product_variant_id','qty_per_parent'];

    public function product_variant()
    {
        return $this->belongsTo(ProductVariant::class,'product_variant_id');
    }

}
