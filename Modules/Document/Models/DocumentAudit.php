<?php

namespace Modules\Document\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DocumentAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'performed_by',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}