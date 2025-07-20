<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierContact extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'notes',
        'supplier_id',
    ];

    /**
     * Get the supplier that owns the contact.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
