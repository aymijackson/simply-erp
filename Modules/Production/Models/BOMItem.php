<?php
namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Production\Models\BillOfMaterial;
use Modules\Production\Models\RawMaterial;      

class BOMItem extends Model
{
    protected $fillable = [
        'bill_of_material_id', 'raw_material_id', 'quantity'
    ];

    protected $table = 'bom_items';
    public function bom()
    {
        return $this->belongsTo(BillOfMaterial::class, 'bill_of_material_id');
    }

    public function raw_material()
    {
        return $this->belongsTo(RawMaterial::class);
    }

}
