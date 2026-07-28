<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Modules\CRM\Models\Customer;
use Modules\Sales\Models\SalesOrder;
use App\Models\Driver;   // adjust if your driver model namespace differs
use App\Models\Vehicle;  // adjust if your vehicle model namespace differs
use App\Models\LocationStore; // adjust if your namespace differs

class SalesDelivery extends Model
{
    protected $table = 'sales_deliveries';

    protected $fillable = [
        'delivery_no',
        'sales_order_id',
        'driver_id',
        'vehicle_id',
        'customer_id',
        'location_store_id',
        'ship_date',
        'delivered_at',
        'status',
        'remarks',
    ];

    protected $casts = [
        'ship_date'     => 'date',
        'delivered_at'  => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function store()
    {
        return $this->belongsTo(LocationStore::class, 'location_store_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesDeliveryLine::class, 'sales_delivery_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'posted'    => 'success',
            'cancelled' => 'danger',
            default     => 'secondary',
        };
    }
}
