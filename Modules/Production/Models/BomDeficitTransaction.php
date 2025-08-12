<?php

// Modules/Production/Models/BomDeficitTransaction.php
namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BomDeficitTransaction extends Model
{
    protected $fillable = ['bom_id','product_variant_id','direction','qty','unit_cost','source_bom_id','ref_type','ref_id','note','created_by'];
    public function ref(): MorphTo { return $this->morphTo(); }
}