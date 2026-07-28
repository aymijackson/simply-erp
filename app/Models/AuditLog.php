<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
  protected $fillable = [
    'user_id','module','action','description',
    'subject_type','subject_id',
    'route','url','method','ip','user_agent','meta',
  ];

  protected $casts = [
    'meta' => 'array',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function subject(): MorphTo
  {
    return $this->morphTo();
  }
}
