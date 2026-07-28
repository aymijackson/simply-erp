<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'setting_group_id','key','label','description',
        'value','value_type',
        'scope','scope_id',
        'is_sensitive','is_required','is_active','sort_order'
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'is_required'  => 'boolean',
        'is_active'    => 'boolean',
        'scope_id'     => 'integer',
        'sort_order'   => 'integer',
    ];

    public function group()
    {
        return $this->belongsTo(SettingGroup::class, 'setting_group_id');
    }
}
