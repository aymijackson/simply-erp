<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User;
use App\Models\LocationStore;
use App\Models\Supplier;
use Modules\CRM\Models\Customer;

class StockReturn extends Model
{
    protected $fillable = [
        'return_type','return_no','request_uuid',
        'store_id','supplier_id','customer_id',
        'reference_id','reference_type',
        'reason','status','posted_at','posted_by'
    ];

    /* core rels */
    public function lines()    { return $this->hasMany(StockReturnLine::class); }
    public function store()    { return $this->belongsTo(LocationStore::class, 'store_id'); }
    public function postedBy() { return $this->belongsTo(User::class, 'posted_by'); }

    /* ✅ Party rels (use these in UI) */
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }

    /* ✅ Origin document: StockIssue or StockEntry */
    public function origin(): MorphTo
    {
        // origin = stock issue (supplier return) OR stock entry (customer return)
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    /* scopes */
    public function scopeSupplier($q) { return $q->where('return_type','supplier'); }
    public function scopeCustomer($q) { return $q->where('return_type','customer'); }

    /* ✅ Small helpers for table display */
    public function getPartyNameAttribute(): string
    {
        return $this->return_type === 'supplier'
            ? ($this->supplier?->name ?? '—')
            : ($this->customer?->name ?? '—');
    }

    public function getOriginNoAttribute(): string
    {
        // if your StockEntry has entry_no, StockIssue has issue_no
        if (!$this->relationLoaded('origin')) $this->load('origin');

        return match ($this->reference_type) {
            \Modules\Inventory\Models\StockIssue::class => $this->origin?->issue_no ?? '—',
            \Modules\Inventory\Models\StockEntry::class => $this->origin?->reference ?? ($this->origin?->entry_no ?? '—'),
            default => '—',
        };
    }
    
    /*
    |--------------------------------------------------------------------------
    | Polymorphic reference
    |--------------------------------------------------------------------------
    | reference_type + reference_id live on stock_returns table
    */
    public function reference(): MorphTo
    {
        // explicit keys to avoid Laravel guessing wrongly
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Convenience relation: StockIssue (no where on stock_issues!)
    |--------------------------------------------------------------------------
    | This is safe because the FK is reference_id on stock_returns.
    | You should only use this when reference_type === StockIssue::class.
    */
    public function issue()
    {
        return $this->belongsTo(StockIssue::class, 'reference_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Safe helper: returns StockIssue or null (recommended)
    |--------------------------------------------------------------------------
    */
    public function getStockIssueAttribute()
    {
        if ($this->reference_type !== StockIssue::class) {
            return null;
        }

        // uses the belongsTo relation cache if already loaded
        return $this->relationLoaded('issue') ? $this->getRelation('issue') : $this->issue()->first();
    }


    public function getEffectiveStatusAttribute(): string
    {
        // Prefer origin doc status if present; else stock_return status
        if (!$this->relationLoaded('origin')) $this->load('origin');

        return $this->origin?->status ?? ($this->status ?? 'draft');
    }
}
