<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\StockEntryLine;
use Modules\Inventory\Models\Product\Product;   
use Modules\Inventory\Models\Product\ProductVariant;   
use App\Models\LocationStore;   

// use Modules\Inventory\Database\Factories\StockFactory;

class StockEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id', 'store_id', 'shelf_id', 'reference', 'status', 'entry_date'];

    public function lines()
    {
        return $this->hasMany(StockEntryLine::class);
    }

    /* the product variants that appear on those lines */
    public function product_variants()
    {
        return $this->belongsToMany(        // tables: stock_entries ← stock_entry_lines → product_variants
            ProductVariant::class,          // related model
            'stock_entry_lines',            // pivot table
            'stock_entry_id',               // FK on pivot pointing to THIS model
            'product_variant_id'            // FK on pivot pointing to VARIANT
        )
        ->withPivot(['qty', 'unit_cost'])    // keep the qty & cost columns handy
        ->withTimestamps();                  // if you want created_at/updated_at on pivot
    }

    public function store()
    {
        return $this->belongsTo(LocationStore::class);
    }
}
