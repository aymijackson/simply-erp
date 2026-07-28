<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\PurchaseOrderHeaderFactory;

class PurchaseRequisitionHeader extends Model
{
    use HasFactory;

    protected $fillable = ['req_no','requested_by','cost_center_id','needed_by_date',
        'purpose','status','total_est_cost','approved_by','approved_at'];
    public function lines()   { return $this->hasMany(PurchaseRequisitionLine::class); }
    public function purchase_order()      { return $this->hasOne(PurchaseOrder::class); }
    public function approver(){ return $this->belongsTo(User::class,'approved_by'); }
}
