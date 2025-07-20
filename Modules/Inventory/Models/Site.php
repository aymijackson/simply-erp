<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\SiteFactory;

class Site extends Model
{
    use HasFactory;

    protected $fillable = ['site_code', 'site_name'];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}
