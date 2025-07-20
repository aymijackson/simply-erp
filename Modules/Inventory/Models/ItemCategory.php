<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\ItemCategoryFactory;

class ItemCategory extends Model
{
    use HasFactory;

    protected $fillable = ['category_code', 'category_name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
