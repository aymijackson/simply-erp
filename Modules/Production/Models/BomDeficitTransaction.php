<?php

// Modules/Production/Models/BomDeficitTransaction.php
namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BomDeficitTransaction extends Model
{
    protected $fillable = ['bom_id','product_variant_id','direction','qty','unit_cost','source_bom_id','ref_type','ref_id','note','created_by'];

    public function ref(): MorphTo { 
        return $this->morphTo(); 
    }

    public function bom()        
    { 
        return $this->belongsTo(BomHeader::class, 'bom_id'); 
    }

    public function source_bom()        
    { 
        return $this->belongsTo(BomHeader::class, 'source_bom_id'); 
    }

    public function variant()    
    { 
        return $this->belongsTo(\Modules\Inventory\Models\Product\ProductVariant::class, 'product_variant_id'); 
    }

    public function sourceBom()  
    { 
        return $this->belongsTo(BomHeader::class, 'source_bom_id'); 
    }

    public function creator()    
    { 
        return $this->belongsTo(\App\Models\User::class, 'created_by'); 
    }
}