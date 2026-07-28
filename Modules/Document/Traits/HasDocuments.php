<?php

namespace Modules\Document\Traits;

use Modules\Document\Models\DocumentLink;

trait HasDocuments
{
    public function documentLinks()
    {
        return $this->morphMany(DocumentLink::class, 'linkable');
    }

    public function documents()
    {
        return $this->morphToMany(
            \Modules\Document\Models\Document::class,
            'linkable',
            'document_links',
            'linkable_id',
            'document_id'
        )->withPivot(['id', 'relation_type', 'remarks', 'created_by', 'created_at', 'updated_at']);
    }
}