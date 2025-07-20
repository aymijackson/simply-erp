<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use Modules\CRM\Traits\HasNotes;


class Customer extends Model
{
    use HasNotes;
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
