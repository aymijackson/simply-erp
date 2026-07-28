<?php

namespace Modules\Document\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLink extends Model
{
    protected $fillable = [
        'document_id',
        'linkable_type',
        'linkable_id',
        'relation_type',
        'remarks',
        'created_by',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function linkable()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}