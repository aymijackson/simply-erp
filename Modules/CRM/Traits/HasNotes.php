<?php

namespace Modules\CRM\Traits;

use App\Models\Note;

trait HasNotes
{
    /**
     * Get all notes attached to the model.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    /**
     * Attach a note to this model.
     */
    public function addNote(array $data)
    {
        return $this->notes()->create($data);
    }
}