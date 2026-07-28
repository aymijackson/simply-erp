<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Document\Models\DocumentLink;
use Modules\Inventory\Models\Product\Category;
use Modules\Inventory\Models\Product\Brand;
use Modules\Inventory\Models\Product\ProductAttribute;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Inventory\Models\Product\Unit;
use Modules\Inventory\Models\Product\ProductImage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'product_code',
        'product_name',
        'product_description',
        'image_path', // legacy single image field, kept for backward compatibility
        'product_price',
        'average_cost',
        'brand_id',
        'unit_id',
        'pack_size',
        'product_stock_quantity',
        'is_active',
    ];

    protected $casts = [
        'product_price'           => 'decimal:2',
        'average_cost'            => 'decimal:2',
        'product_stock_quantity'  => 'integer',
        'is_active'               => 'boolean',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
        'deleted_at'              => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Core Relationships
    |--------------------------------------------------------------------------
    */

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id')
            ->withTimestamps();
    }

    /**
     * Legacy single category relation.
     * Keep only if you still have category_id on products table in some environments.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Product Attribute Structure
    |--------------------------------------------------------------------------
    */

    /**
     * product_attributes rows for this product
     */
    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class, 'product_id');
    }

    /**
     * Direct access to attribute types through product_attributes pivot rows
     */
    public function attributeTypes()
    {
        return $this->belongsToMany(
            ProductAttributeType::class,
            'product_attributes',
            'product_id',
            'attribute_type_id'
        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Variants
    |--------------------------------------------------------------------------
    */

    /**
     * Correct relation: one product has many variants
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Product Images
    |--------------------------------------------------------------------------
    */

    /**
     * Multiple gallery images for product
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')
            ->orderBy('sort_order')
            ->orderByDesc('is_primary')
            ->orderByDesc('id');
    }

    /**
     * Primary image relation
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class, 'product_id')
            ->where('is_primary', 1)
            ->where('is_active', 1)
            ->latestOfMany();
    }

    /**
     * Active images only
     */
    public function activeImages()
    {
        return $this->hasMany(ProductImage::class, 'product_id')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderByDesc('is_primary')
            ->orderByDesc('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Shared Document Management Links
    |--------------------------------------------------------------------------
    */

    /**
     * Polymorphic document links using shared DMS
     */
    public function documentLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'linkable');
    }

    /**
     * Convenience accessor to fetch linked documents
     */
    public function documents()
    {
        return $this->documentLinks()->with('document');
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy / Existing Relations Kept Only If Still Used Elsewhere
    |--------------------------------------------------------------------------
    */

    public function group()
    {
        return $this->belongsTo(ItemGroup::class, 'group_id');
    }

    public function defaultUom()
    {
        return $this->belongsTo(ProductUom::class, 'default_uom');
    }

    public function instances()
    {
        return $this->hasMany(ProductInstance::class);
    }

    public function priceRecords()
    {
        return $this->hasMany(ProductPriceRecord::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Returns best available image URL/path source:
     * 1. primary gallery image
     * 2. first active gallery image
     * 3. legacy image_path
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

        return $this->image_path ?: null;
    }

    public function getHasImagesAttribute(): bool
    {
        if ($this->relationLoaded('images')) {
            return $this->images->count() > 0;
        }

        return $this->images()->exists() || !empty($this->image_path);
    }

    public function getHasDocumentsAttribute(): bool
    {
        if ($this->relationLoaded('documentLinks')) {
            return $this->documentLinks->count() > 0;
        }

        return $this->documentLinks()->exists();
    }

    public function getVariantCountAttribute(): int
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->count();
        }

        return $this->variants()->count();
    }
}