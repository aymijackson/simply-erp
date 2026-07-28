<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierContact extends Model
{
    protected $table = 'supplier_contacts';

    protected $fillable = [
        'supplier_id',
        'name',
        'role',
        'email',
        'phone',
        'notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
