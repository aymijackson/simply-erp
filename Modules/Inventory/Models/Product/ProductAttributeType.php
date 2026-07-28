<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductAttributeType extends Model
{
    use HasFactory;

    protected $table = 'product_attribute_types';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Core Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * All product_attribute rows using this type
     */
    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class, 'attribute_type_id');
    }

    /**
     * Get all attribute values THROUGH product_attributes
     */
    public function values()
    {
        return $this->hasManyThrough(
            ProductAttributeValue::class,
            ProductAttribute::class,
            'attribute_type_id',        // FK on product_attributes
            'product_attribute_id',     // FK on product_attribute_values
            'id',                       // PK on product_attribute_types
            'id'                        // PK on product_attributes
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    public function getValuesListAttribute(): array
    {
        if (!$this->relationLoaded('values')) {
            $this->load('values');
        }

        return $this->values
            ->pluck('value')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    public function getValuesTextAttribute(): string
    {
        return implode(', ', $this->values_list);
    }

    public function getAttributeCountAttribute(): int
    {
        if ($this->relationLoaded('attributes')) {
            return $this->attributes->count();
        }

        return $this->attributes()->count();
    }
}