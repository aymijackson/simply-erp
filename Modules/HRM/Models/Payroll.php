<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HRM\Models\Employee;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'pay_date',
        'basic_salary',
        'total_allowances',
        'total_deductions',
        'net_salary',
        'payment_status',
        'is_paid',
        'remarks',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowances()
    {
        return $this->hasMany(PayrollAllowance::class);
    }

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }
}
