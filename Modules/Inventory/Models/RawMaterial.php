<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\ItemCategory;
// use Modules\Inventory\Database\Factories\RawMaterialFactory;

class RawMaterial extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): RawMaterialFactory
    // {
    //     // return RawMaterialFactory::new();
    // }

    /**
     * Get the category that this raw material belongs to.
     */
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id', 'id');
        // 'category_id' => local key on raw_materials table
        // 'id' => primary key on categories table (default if omitted)
    }
}
