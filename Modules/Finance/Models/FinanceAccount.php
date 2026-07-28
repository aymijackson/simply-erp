<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_accounts';

    protected $fillable = [
        'company_id',
        'account_type_id',
        'code',
        'name',
        'parent_id',
        'is_control',
        'allow_manual_posting',
        'is_active',
        'description',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'account_type_id' => 'integer',
        'parent_id' => 'integer',
        'is_control' => 'boolean',
        'allow_manual_posting' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountType::class, 'account_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildrenOnly($query)
    {
        return $query->whereNotNull('parent_id');
    }
}