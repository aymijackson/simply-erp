<?php

namespace Modules\CRM\Traits;

use Modules\CRM\Models\Note;

trait HasNotes
{
    /**
     * Get all notes attached to the model.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    /**
     * Attach a note to this model.
     */
    public function addNote(array $data)
    {
        return $this->notes()->create($data);
    }
}