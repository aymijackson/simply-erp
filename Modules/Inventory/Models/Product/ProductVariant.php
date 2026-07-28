<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Modules\Document\Models\DocumentLink;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'item_type',
        'stock_quantity',
        'reorder_point',
    ];

    protected $casts = [
        'product_id'      => 'integer',
        'price'           => 'decimal:2',
        'stock_quantity'  => 'integer',
        'reorder_point'   => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
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

    public function attributeValues()
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_attribute_value_product_variant',
            'product_variant_id',
            'product_attribute_value_id'
        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Variant Images
    |--------------------------------------------------------------------------
    */

    public function images()
    {
        return $this->hasMany(ProductVariantImage::class, 'product_variant_id')
            ->orderBy('sort_order')
            ->orderByDesc('is_primary')
            ->orderByDesc('id');
    }

    public function activeImages()
    {
        return $this->hasMany(ProductVariantImage::class, 'product_variant_id')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderByDesc('is_primary')
            ->orderByDesc('id');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductVariantImage::class, 'product_variant_id')
            ->where('is_primary', 1)
            ->where('is_active', 1)
            ->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | Shared Document Management Links
    |--------------------------------------------------------------------------
    */

    public function documentLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'linkable');
    }

    public function documents()
    {
        return $this->documentLinks()->with('document');
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory / Stock
    |--------------------------------------------------------------------------
    */

    public function stockTransactions()
    {
        return $this->hasMany(\Modules\Inventory\Models\StockTransaction::class, 'product_variant_id');
    }

    public function scopeLowStock($q)
    {
        $sub = DB::table('v_stock_levels')
            ->selectRaw('product_variant_id, SUM(qty_on_hand) AS qty_on_hand')
            ->groupBy('product_variant_id');

        return $q->joinSub($sub, 'v', function ($join) {
                $join->on('v.product_variant_id', '=', 'product_variants.id');
            })
            ->whereColumn('v.qty_on_hand', '<=', 'product_variants.reorder_point')
            ->addSelect('product_variants.*', 'v.qty_on_hand');
    }

    public function getLowStockFlagAttribute(): bool
    {
        $qty = (float) ($this->qty_on_hand ?? $this->stock_quantity ?? 0);
        $reorderPoint = (float) ($this->reorder_point ?? 0);

        return $qty <= $reorderPoint;
    }

    public function getStockStatusAttribute(): string
    {
        $qty = (float) ($this->qty_on_hand ?? $this->stock_quantity ?? 0);
        $reorderPoint = (float) ($this->reorder_point ?? 0);

        if ($qty <= 0) {
            return 'out_of_stock';
        }

        if ($reorderPoint > 0 && $qty <= $reorderPoint) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /*
    |--------------------------------------------------------------------------
    | Production
    |--------------------------------------------------------------------------
    */

    public function routings()
    {
        return $this->hasMany(\Modules\Production\Models\Routing::class, 'product_variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    public function getDisplayImagePathAttribute(): ?string
    {
        if ($this->relationLoaded('primaryImage') && $this->primaryImage) {
            return $this->primaryImage->file_path;
        }

        $primary = $this->primaryImage()->first();
        if ($primary) {
            return $primary->file_path;
        }

        $first = $this->activeImages()->first();
        if ($first) {
            return $first->file_path;
        }

        return null;
    }

    public function getHasImagesAttribute(): bool
    {
        if ($this->relationLoaded('images')) {
            return $this->images->count() > 0;
        }

        return $this->images()->exists();
    }

    public function getHasDocumentsAttribute(): bool
    {
        if ($this->relationLoaded('documentLinks')) {
            return $this->documentLinks->count() > 0;
        }

        return $this->documentLinks()->exists();
    }

    public function getAttributeSummaryAttribute(): string
    {
        if (!$this->relationLoaded('attributeValues')) {
            $this->load('attributeValues.attribute.type');
        }

        return $this->attributeValues
            ->map(function ($value) {
                $typeName = $value->attribute?->type?->name;
                return $typeName ? ($typeName . ': ' . $value->value) : $value->value;
            })
            ->filter()
            ->implode(' | ');
    }
}