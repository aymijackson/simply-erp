<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'status',
        'default_currency',
        'payment_terms',
        'lead_time_days',
        'rating',
    ];

}
