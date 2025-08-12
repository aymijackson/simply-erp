<?php

// Modules/Inventory/Models/StockReturn.php
namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class StockReturn extends Model
{
    protected $fillable = [
        'return_type','return_no','store_id',
        'reference_id','reference_type','reason','status', 'posted_at', 'posted_by'
    ];

    /* ------ rels ------ */
    public function lines()         { return $this->hasMany(StockReturnLine::class); }
    public function store()         { return $this->belongsTo(LocationStore::class); }
    public function postedBy()      { return $this->belongsTo(User::class,'posted_by'); }

    /* ------ scopes ------ */
    public function scopeCustomer($q) { $q->where('return_type','customer'); }
    public function scopeSupplier($q) { $q->where('return_type','supplier'); }
}
