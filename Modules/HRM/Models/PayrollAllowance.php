<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Payroll;

class PayrollAllowance extends Model
{
    use HasFactory;

    protected $fillable = ['payroll_id', 'type', 'amount'];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
