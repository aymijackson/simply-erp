<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerContact extends Model
{
    protected $table = 'customer_contacts';

    protected $fillable = [
        'customer_id',
        'first_name',
        'last_name',
        'position',
        'email',
        'phone',
        'is_primary',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active'  => 'boolean',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}