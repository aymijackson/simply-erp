<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierAddress extends Model
{
    protected $table = 'supplier_addresses';

    protected $fillable = [
        'supplier_id',
        'type',
        'line1',
        'line2',
        'country_id',
        'state_id',
        'city_id',
        'postal_code',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
    
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}
