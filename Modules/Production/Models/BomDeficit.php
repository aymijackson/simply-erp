<?php
// Modules/Production/Models/BomDeficit.php
namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BomDeficit extends Model
{
    protected $fillable = ['bom_id','product_variant_id','qty_borrowed_total','qty_repaid_total','qty_outstanding','last_txn_at','last_txn_id'];

    public function transactions(): HasMany 
    { 
        return $this->hasMany(BomDeficitTransaction::class, 'bom_id', 'bom_id')->where('product_variant_id', $this->product_variant_id); 
    }

    public function bom()     
    { 
        return $this->belongsTo(BomHeader::class, 'bom_id'); 
    }

    public function variant() 
    { 
        return $this->belongsTo(\Modules\Inventory\Models\Product\ProductVariant::class, 'product_variant_id'); 
    }

    public function lastTxn() 
    { 
        return $this->belongsTo(BomDeficitTransaction::class, 'last_txn_id'); 
    }
}
