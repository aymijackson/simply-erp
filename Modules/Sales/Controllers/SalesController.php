<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesInvoiceLine;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Inventory\Models\Product\ProductVariant;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\{BomHeader,BomItem};
use Modules\Inventory\Models\Product\Product;


class SalesController extends Controller
{
    public function invoiceLineSelect2(Request $r)
    {
        $rows = SalesInvoiceLine::query()
            ->where('customer_id', $r->customer_id)
            ->where('product_variant_id', $r->variant_id)
            ->selectRaw('id, CONCAT(invoice_no," – ",qty) AS text')
            ->limit(25)->get();
        return response()->json($rows);
    }

    public function deliveryLineSelect2(Request $r)
    {
        $rows = SalesDeliveryLine::query()
            ->where('customer_id', $r->customer_id)
            ->where('product_variant_id', $r->variant_id)
            ->selectRaw('id, CONCAT(delivery_no," – ",qty) AS text')
            ->limit(25)->get();
        return response()->json($rows);
    }
}
