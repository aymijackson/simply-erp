<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSegmentPreset extends Model
{
    protected $table = 'crm_customer_segment_presets';

    protected $fillable = [
        'name','description',
        'high_value_min','hot_recency_days',
        'engaged_score_min','engaged_recency_days',
        'dormant_days','risk_statuses',
        'is_default','is_active',
        'created_by','updated_by'
    ];

    protected $casts = [
        'risk_statuses' => 'array',
        'is_default'    => 'boolean',
        'is_active'     => 'boolean',
        'high_value_min'=> 'decimal:2',
    ];
}
