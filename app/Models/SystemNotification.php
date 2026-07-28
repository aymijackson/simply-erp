<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'reference_type',
        'reference_id',
        'is_read'
    ];
}