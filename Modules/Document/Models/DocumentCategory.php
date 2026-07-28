<?php

namespace Modules\Document\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function types()
    {
        return $this->hasMany(DocumentType::class, 'category_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'category_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

}