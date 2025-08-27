<?php

// app/Models/Production/WorkOrderCostType.php
namespace Modules\Production\Models;
use Modules\Inventory\Models\Product\Unit;

use Illuminate\Database\Eloquent\Model;

class WorkOrderCostType extends Model
{
    protected $table = 'work_order_cost_types';
    protected $fillable = ['code','name','category','default_unit_id','is_active'];

    public function unit() { return $this->belongsTo(Unit::class, 'default_unit_id'); }
}

// app/Models/Production/WorkOrderCostLine.php
namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WorkOrderCostLine extends Model
{
    protected $table = 'work_order_cost_lines';
    protected $fillable = [
        'work_order_id','work_order_cost_type_id','unit_id',
        'qty','rate','amount','occurred_at','employee_id','machine_id','vendor_id','description'
    ];

    public function workOrder()  { return $this->belongsTo(WorkOrder::class); }
    public function type()       { return $this->belongsTo(WorkOrderCostType::class,'work_order_cost_type_id'); }
    public function unit()       { return $this->belongsTo(Unit::class,'unit_id'); }

    // If DB triggers aren’t available, keep totals in sync here:
    protected static function booted()
    {
        $retotal = fn ($m) => DB::statement('CALL sp_work_order_retotal(?)', [$m->work_order_id]);
        static::created($retotal);
        static::updated($retotal);
        static::deleted($retotal);
    }
}
