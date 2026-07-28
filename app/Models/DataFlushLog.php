<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataFlushLog extends Model
{
    protected $fillable = [
        'module','scope','payload','performed_by','ip','user_agent','deleted_count'
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
