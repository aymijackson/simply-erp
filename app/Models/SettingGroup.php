<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingGroup extends Model
{
    protected $fillable = [
        'code','name','module','description','sort_order','is_active'
    ];

    public function settings()
    {
        return $this->hasMany(Setting::class, 'setting_group_id');
    }
}
