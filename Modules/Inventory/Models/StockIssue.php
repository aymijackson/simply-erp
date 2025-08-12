<?php

// app/Models/StockLevel.php
namespace Modules\Inventory\Models;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;

use Illuminate\Database\Eloquent\Model;

class StockIssue extends Model
{
    protected $fillable = ['issue_no','from_store_id','bom_header_id', 'sales_delivery_id', 'reference','reason','status', 'issue_type', 'requested_by', 'posted_by','posted_at'];
    
    public function lines()      
    { 
        return $this->hasMany(StockIssueLine::class, 'stock_issue_id'); 
    }
    
    public function fromStore()  
    { 
        return $this->belongsTo(LocationStore::class,'from_store_id'); 
    }

    public function bomHeader()  
    { 
        return $this->belongsTo(\Modules\Production\Models\BomHeader::class,'bom_header_id');                           
    }

    public function salesDelivery() 
    { 
        return $this->belongsTo(\Modules\Sales\Models\SalesDelivery::class,'sales_delivery_id'); 
    }
}
