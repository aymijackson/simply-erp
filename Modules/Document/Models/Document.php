<?php

namespace Modules\Document\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'uuid',
        'document_no',
        'parent_document_id',
        'version_no',
        'is_latest',
        'category_id',
        'type_id',
        'title',
        'description',
        'notes',
        'original_file_name',
        'file_name',
        'file_path',
        'file_disk',
        'mime_type',
        'file_extension',
        'file_size',
        'checksum',
        'status',
        'confidentiality_level',
        'effective_date',
        'expiry_date',
        'uploaded_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
        'version_no' => 'integer',
        'file_size' => 'integer',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($document) {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'type_id');
    }

    public function parent()
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    public function versions()
    {
        return $this->hasMany(Document::class, 'parent_document_id')->orderBy('version_no');
    }

    public function links()
    {
        return $this->hasMany(DocumentLink::class, 'document_id');
    }

    public function audits()
    {
        return $this->hasMany(DocumentAudit::class, 'document_id')->latest('created_at');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getHumanFileSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'text/plain',
        ], true);
    }

    public function latestRoot(): self
    {
        return $this->parent_document_id ? ($this->parent ?: $this) : $this;
    }
}