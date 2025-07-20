<?php
namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\Company;
use Modules\Inventory\Models\Department;
use Modules\HRM\Models\Employee;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'date', 'clock_in', 'clock_out', 'note'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
