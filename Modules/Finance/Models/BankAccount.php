<?php
// File: Modules/Finance/Models/BankAccount.php
// (If you already have this model elsewhere, keep yours; only add relationship helpers)

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;

    protected $table = 'finance_bank_accounts';

    protected $fillable = [
        'company_id','name','type','currency_code',
        'bank_id','bank_name','account_number','sort_code','iban','swift',
        'opening_balance','opening_balance_date',
        'gl_account_id','is_active','notes',
    ];

    protected $casts = [
        'opening_balance_date' => 'date',
        'is_active' => 'boolean',
    ];
}