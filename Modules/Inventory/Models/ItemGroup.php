<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\ItemGroupFactory;

class ItemGroup extends Model
{
    protected $fillable = ['group_code', 'group_name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'group_id');
    }
}
