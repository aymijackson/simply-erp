<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HRM\Models\Employee;
use Modules\CRM\Traits\HasNotes;

class Opportunity extends Model
{
    use HasFactory, HasNotes;

    protected $fillable = [
        'title',
        'customer_id',
        'value',
        'stage',
        'probability',
        'close_date',
        'owner_id',
        'notes',
    ];

    protected $casts = [
        'close_date' => 'date',
        'value' => 'decimal:2',
        'probability' => 'integer'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function owner()
    {
        return $this->belongsTo(Employee::class, 'owner_id');
    }
}
