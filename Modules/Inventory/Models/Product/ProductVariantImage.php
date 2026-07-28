<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariantImage extends Model
{
    use SoftDeletes;

    protected $table = 'product_variant_images';

    protected $fillable = [
        'product_variant_id',
        'title',
        'caption',
        'file_name',
        'original_file_name',
        'file_path',
        'file_disk',
        'mime_type',
        'file_extension',
        'file_size',
        'sort_order',
        'is_primary',
        'is_active',
        'uploaded_by',
    ];

    protected $casts = [
        'product_variant_id' => 'integer',
        'file_size'          => 'integer',
        'sort_order'         => 'integer',
        'is_primary'         => 'boolean',
        'is_active'          => 'boolean',
        'uploaded_by'        => 'integer',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $image) {
            if (is_null($image->sort_order)) {
                $max = static::where('product_variant_id', $image->product_variant_id)->max('sort_order');
                $image->sort_order = ((int) $max) + 1;
            }
        });

        static::saved(function (self $image) {
            if ($image->is_primary) {
                static::where('product_variant_id', $image->product_variant_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_primary' => 0]);
            }

            static::ensurePrimaryExists($image->product_variant_id);
        });

        static::deleted(function (self $image) {
            static::ensurePrimaryExists($image->product_variant_id);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')
            ->orderByDesc('is_primary')
            ->orderByDesc('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function ensurePrimaryExists(int $variantId): void
    {
        $hasPrimary = static::where('product_variant_id', $variantId)
            ->whereNull('deleted_at')
            ->where('is_primary', 1)
            ->exists();

        if ($hasPrimary) {
            return;
        }

        $first = static::where('product_variant_id', $variantId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($first) {
            $first->is_primary = 1;
            $first->saveQuietly();
        }
    }

    public function markAsPrimary(): void
    {
        static::where('product_variant_id', $this->product_variant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => 0]);

        $this->is_primary = 1;
        $this->save();
    }

    public function getHumanFileSizeAttribute(): string
    {
        $bytes = (int) ($this->file_size ?? 0);

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    public function getFileUrlAttribute(): string
    {
        return route('admin.inventory.products.variants.images.view', $this->id);
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title
            ?: $this->original_file_name
            ?: ('Variant Image #' . $this->id);
    }

    public function getStorageAbsolutePathAttribute(): string
    {
        return storage_path('app/public/' . ltrim($this->file_path, '/'));
    }

    public function getExistsOnDiskAttribute(): bool
    {
        return file_exists($this->storage_absolute_path);
    }
}