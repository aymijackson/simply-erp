<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\FinanceAccount;

class Company extends Model
{
    protected $fillable = [
        'name',
        'email',
        'address',
        'website',
    ];
    
    public function financeAccountMapping()
    {
        return $this->hasOne(\Modules\Finance\Models\AccountMapping::class, 'company_id', 'id');
    }
    
    public function financeAccounts()
    {
        return $this->hasMany(\Modules\Finance\Models\FinanceAccount::class, 'company_id', 'id');
    }

}
