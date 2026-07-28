<?php

namespace Modules\Finance\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use SoftDeletes;

    protected $table = 'finance_tax_rates';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'rate',
        'tax_type',
        'effective_from',
        'effective_to',
        'is_compound',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_compound' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function taxCodes()
    {
        return $this->hasMany(TaxCode::class, 'rate_id');
    }
}