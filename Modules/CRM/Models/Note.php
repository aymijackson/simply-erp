<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Note extends Model
{
    use HasFactory;

    /* -------------------------------------------------
     | Mass-assignable fields
     |-------------------------------------------------- */
    protected $fillable = [
        'subject',
        'content',
        'author_id',
        'notable_id',
        'notable_type',
    ];

    /* -------------------------------------------------
     | Casts
     |-------------------------------------------------- */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* -------------------------------------------------
     | Relationships
     |-------------------------------------------------- */

    /**
     * Polymorphic link: the entity (Lead, Opportunity, Customer, etc.)
     * this note belongs to.
     */
    public function notable()
    {
        return $this->morphTo();
    }

    /**
     * Author of the note (Employee or User).
     * Adjust the model path if your employee model lives elsewhere.
     */
    public function author()
    {
        return $this->belongsTo(\Modules\HRM\Models\Employee::class, 'author_id');
    }
}