<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use Modules\CRM\Traits\HasNotes;
use Modules\Document\Traits\HasDocuments;

class Customer extends Model
{
    use HasNotes;
    use HasDocuments;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'position',      // BUG FIX: was missing from fillable, existed in DB
        'address',
        'tax_id',
        'credit_limit',
        'credit_terms_days',
        'currency_code',
        'website',
        'notes',
        'status',
        // NOTE: 'city' removed — no city column in DB schema
    ];

    protected $casts = [
        'credit_limit'      => 'decimal:2',
        'credit_terms_days' => 'integer',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function priceLists()
    {
        return $this->belongsToMany(\Modules\Sales\Models\PriceList::class,
            'customer_price_lists', 'customer_id', 'price_list_id')
            ->withTimestamps();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contacts()
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function primaryContact()
    {
        return $this->hasOne(CustomerContact::class)->where('is_primary', true);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    public function billingAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('type', 'billing')->where('is_default', true);
    }

    public function shippingAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('type', 'shipping')->where('is_default', true);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    // BUG FIX: original had getFullNameAttribute but Customer has a single 'name' field
    // Keeping it for backward compatibility but returning 'name'
    public function getFullNameAttribute(): string
    {
        return $this->name ?? '';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'   => '<span class="badge bg-success">Active</span>',
            'inactive' => '<span class="badge bg-secondary">Inactive</span>',
            default    => '<span class="badge bg-light text-dark">'.ucfirst($this->status ?? '-').'</span>',
        };
    }

    public function getCreditTermsLabelAttribute(): string
    {
        if (! $this->credit_terms_days) {
            return 'Immediate';
        }
        return "Net {$this->credit_terms_days}";
    }
}