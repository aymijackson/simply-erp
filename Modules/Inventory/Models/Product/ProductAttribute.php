<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $table = 'product_attributes';

    protected $fillable = [
        'product_id',
        'attribute_type_id',
    ];

    protected $casts = [
        'product_id'         => 'integer',
        'attribute_type_id'  => 'integer',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Core Relationships
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function type()
    {
        return $this->belongsTo(ProductAttributeType::class, 'attribute_type_id');
    }

    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_attribute_id')
            ->orderBy('value');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForAttributeType($query, int $attributeTypeId)
    {
        return $query->where('attribute_type_id', $attributeTypeId);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    public function getTypeNameAttribute(): ?string
    {
        if (!$this->relationLoaded('type')) {
            $this->load('type');
        }

        return $this->type?->name;
    }

    public function getProductNameAttribute(): ?string
    {
        if (!$this->relationLoaded('product')) {
            $this->load('product');
        }

        return $this->product?->product_name;
    }

    public function getValuesListAttribute(): array
    {
        if (!$this->relationLoaded('values')) {
            $this->load('values');
        }

        return $this->values
            ->pluck('value')
            ->filter()
            ->values()
            ->toArray();
    }

    public function getValuesTextAttribute(): string
    {
        return implode(', ', $this->values_list);
    }
}