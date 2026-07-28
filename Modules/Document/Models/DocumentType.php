<?php

namespace Modules\Document\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'code',
        'description',
        'allowed_extensions',
        'max_file_size_mb',
        'requires_expiry_date',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requires_expiry_date' => 'boolean',
        'is_active' => 'boolean',
        'max_file_size_mb' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'type_id');
    }

    public function extensionsArray(): array
    {
        if (!$this->allowed_extensions) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', strtolower($this->allowed_extensions)))));
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}