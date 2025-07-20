<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\WarehouseFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['site_id', 'warehouse_code', 'warehouse_name'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function stock()
    {
        return $this->hasMany(Stock::class);
    }
}
