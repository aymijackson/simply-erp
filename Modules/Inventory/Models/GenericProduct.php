<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\GenericProductFactory;

class GenericProduct extends Model
{
    use HasFactory;

    protected $fillable = ['generic_name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'generic_id');
    }
}
