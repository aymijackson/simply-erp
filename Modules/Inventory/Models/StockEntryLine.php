<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\StockEntry;
use Modules\Inventory\Models\Product\Product;   
use Modules\Inventory\Models\Product\ProductVariant;
// use Modules\Inventory\Database\Factories\StockFactory;

class StockEntryLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id', 'stock_entry_id', 'unit_cost', 'qty'];

    public function product_variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function attributeValue()
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }

    public function entry()
    {
        return $this->belongsTo(StockEntry::class, 'stock_entry_id');
    }
    
    #   nomenclature for procurement module
    
    public function stockEntry()
    {
        return $this->belongsTo(StockEntry::class, 'stock_entry_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
