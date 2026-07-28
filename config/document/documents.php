<?php

$models = [
    \App\Models\Supplier::class,
    \Modules\HRM\Models\Employee::class,

    \Modules\Finance\Models\PettyCashTransaction::class,
    
    \Modules\Procurement\Models\PurchaseOrder::class,
    \Modules\Procurement\Models\GoodsReceipt::class,
    \Modules\Procurement\Models\SupplierQuotation::class,
    \Modules\Procurement\Models\RequestForQuotation::class,

    \Modules\Sales\Models\SalesInvoice::class,
    \Modules\Sales\Models\SalesOrder::class,
    \Modules\Sales\Models\Delivery::class,

    \Modules\CRM\Models\Customer::class,
    \Modules\CRM\Models\Lead::class,
    \Modules\CRM\Models\Opportunity::class,
    \Modules\CRM\Models\SupportTicket::class,

    \Modules\Inventory\Models\Product\Product::class,
    \Modules\Production\Models\WorkOrder::class,
    \Modules\Production\Models\BillOfMaterial::class,
];

return [
    'linkable_models' => array_values(array_filter($models, function ($class) {
        return class_exists($class);
    })),
];