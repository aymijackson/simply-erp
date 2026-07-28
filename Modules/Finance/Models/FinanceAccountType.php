<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceAccountType extends Model
{
    use HasFactory;

    protected $table = 'finance_account_types';

    protected $fillable = [
        'code',
        'name',
        'category',
        'normal_balance',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(FinanceAccount::class, 'account_type_id', 'id');
    }
}