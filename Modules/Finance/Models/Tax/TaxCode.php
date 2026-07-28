<?php

namespace Modules\Finance\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxCode extends Model
{
    use SoftDeletes;

    protected $table = 'finance_tax_codes';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'tax_type',
        'rate_id',
        'is_reverse_charge',
        'is_exempt',
        'is_out_of_scope',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'rate_id' => 'integer',
        'is_reverse_charge' => 'boolean',
        'is_exempt' => 'boolean',
        'is_out_of_scope' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function rate()
    {
        return $this->belongsTo(TaxRate::class, 'rate_id');
    }
}