<?php

// app/Models/StockLevel.php
namespace Modules\Inventory\Models;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;

use Illuminate\Database\Eloquent\Model;

class StockIssue extends Model
{
    protected $fillable = ['issue_no','from_store_id','reference','reason','status','requested_by', 'posted_by','posted_at'];
    
    public function lines()      
    { 
        return $this->hasMany(StockIssueLine::class, 'stock_issue_id'); 
    }
    
    public function fromStore()  
    { 
        return $this->belongsTo(LocationStore::class,'from_store_id'); 
    }
}
