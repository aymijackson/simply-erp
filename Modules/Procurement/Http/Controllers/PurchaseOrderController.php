<?php

namespace Modules\Procurement\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('procurement.purchase_orders.index');
    }

    /**
     * Temporary diagnostic: shows the raw stored status and the exact
     * actions HTML the datatable would generate for one PO, so this can be
     * checked by visiting a URL instead of digging through DevTools.
     * Remove once the "some action buttons missing" report is resolved.
     */
    public function debugActions($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_orders')
            ->where('company_id', $companyId)
            ->where('id', (int) $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        $json = ['id' => $row->id, 'po_no' => $row->po_no, 'status' => $row->status];

        return response()->json([
            'id' => $row->id,
            'po_no' => $row->po_no,
            'raw_status' => $row->status,
            'raw_status_length' => strlen((string) $row->status),
            'raw_status_bytes' => array_map('ord', str_split((string) $row->status)),
            'status_badge_html' => $this->statusBadge($row->status),
            'actions_html' => view('procurement.purchase_orders.partials.actions', compact('json'))->render(),
        ]);
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $base = DB::table('proc_purchase_orders as po')
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->leftJoin('supplier_contacts as sc', 'sc.id', '=', 'po.supplier_contact_id')
            ->leftJoin('proc_purchase_requisitions as pr', 'pr.id', '=', 'po.purchase_requisition_id')
            ->leftJoin('proc_request_for_quotations as rfq', 'rfq.id', '=', 'po.rfq_id')
            ->leftJoin('proc_supplier_quotations as sq', 'sq.id', '=', 'po.supplier_quotation_id')
            ->where('po.company_id', $companyId)
            ->whereNull('po.deleted_at');

        $q = clone $base;

        if ($request->filled('status')) {
            $q->where('po.status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $q->where('po.supplier_id', (int)$request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $q->where('po.po_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->where('po.po_date', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $term = trim((string)$request->q);
            $q->where(function ($x) use ($term) {
                $x->where('po.po_no', 'like', "%{$term}%")
                    ->orWhere('po.supplier_po_ref', 'like', "%{$term}%")
                    ->orWhere('po.reference', 'like', "%{$term}%")
                    ->orWhere('s.name', 'like', "%{$term}%")
                    ->orWhere('sc.name', 'like', "%{$term}%")
                    ->orWhere('pr.requisition_no', 'like', "%{$term}%")
                    ->orWhere('rfq.rfq_no', 'like', "%{$term}%")
                    ->orWhere('sq.quotation_no', 'like', "%{$term}%");
            });
        }

        $recordsTotal = (clone $base)->count('po.id');
        $recordsFiltered = (clone $q)->count('po.id');

        $start = (int)($request->start ?? 0);
        $length = (int)($request->length ?? 10);
        $draw = (int)($request->draw ?? 1);

        $columns = [
            0 => 'po.id',
            1 => 'po.po_no',
            2 => 'po.po_date',
            3 => 'po.expected_delivery_date',
            4 => 's.name',
            5 => 'po.status',
            6 => 'po.total_amount',
        ];

        $orderColIndex = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'desc');

        $q->select([
            'po.id',
            'po.po_no',
            'po.supplier_po_ref',
            'po.po_date',
            'po.expected_delivery_date',
            'po.currency_code',
            'po.total_amount',
            'po.status',
            'po.reference',
            's.name as supplier_name',
            'sc.name as contact_name',
            'pr.requisition_no',
            'rfq.rfq_no',
            'sq.quotation_no',
        ]);

        if ($orderColIndex !== null && isset($columns[(int)$orderColIndex])) {
            $q->orderBy($columns[(int)$orderColIndex], $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $q->orderBy('po.id', 'desc');
        }

        $rows = $q->offset($start)->limit($length)->get();

        $data = $rows->map(function ($r) {
            $json = [
                'id' => $r->id,
                'po_no' => $r->po_no,
                'status' => $r->status,
            ];

            return [
                'id' => $r->id,
                'po_no' => e($r->po_no),
                'po_date' => e($r->po_date),
                'expected_delivery_date' => e($r->expected_delivery_date ?? '—'),
                'supplier' => e($r->supplier_name ?? '—'),
                'contact' => e($r->contact_name ?? '—'),
                'status' => $this->statusBadge($r->status),
                'total_amount' => number_format((float)$r->total_amount, 2),
                'actions' => view('procurement.purchase_orders.partials.actions', compact('json'))->render(),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function createFromQuotation($quotationId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $sq = DB::table('proc_supplier_quotations as sq')
            ->leftJoin('proc_request_for_quotations as rfq', 'rfq.id', '=', 'sq.rfq_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'sq.supplier_id')
            ->where('sq.company_id', $companyId)
            ->where('sq.id', (int)$quotationId)
            ->whereNull('sq.deleted_at')
            ->first([
                'sq.*',
                'rfq.requisition_id',
                'rfq.rfq_no',
                's.name as supplier_name',
                's.payment_terms',
            ]);

        if (!$sq) {
            return response()->json(['message' => 'Supplier quotation not found.'], 404);
        }

        if (!in_array($sq->status, ['accepted', 'reviewed', 'submitted', 'draft'])) {
            return response()->json(['message' => 'This quotation cannot be converted to a purchase order.'], 422);
        }

        $lines = DB::table('proc_supplier_quotation_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.supplier_quotation_id', $sq->id)
            ->orderBy('l.id')
            ->get([
                'l.*',
                'p.product_code',
                'p.product_name',
                'u.name as unit_name',
                'u.symbol as unit_symbol',
                'tc.code as tax_code_code',
                'tc.name as tax_code_name',
            ])
            ->map(function ($x) {
                return [
                    'supplier_quotation_line_id' => $x->id,
                    'rfq_line_id' => $x->rfq_line_id,
                    'product_id' => $x->product_id,
                    'product_label' => $x->product_id ? trim(($x->product_code ? $x->product_code.' - ' : '').($x->product_name ?? '')) : null,
                    'description' => $x->description,
                    'unit_id' => $x->unit_id,
                    'unit_label' => $x->unit_id ? trim(($x->unit_name ?? '').($x->unit_symbol ? ' ('.$x->unit_symbol.')' : '')) : null,
                    'qty' => (float)$x->qty,
                    'unit_price' => (float)$x->unit_price,
                    'discount_percent' => $x->discount_percent !== null ? (float)$x->discount_percent : null,
                    'discount_amount' => (float)$x->discount_amount,
                    'tax_code_id' => $x->tax_code_id,
                    'tax_code_label' => $x->tax_code_id ? trim(($x->tax_code_code ?? '').' - '.($x->tax_code_name ?? '')) : null,
                    'tax_rate_id' => $x->tax_rate_id,
                    'tax_rate' => $x->tax_rate !== null ? (float)$x->tax_rate : null,
                    'tax_amount' => (float)$x->tax_amount,
                    'shipping_amount' => (float)($x->shipping_amount ?? 0),
                    'other_charges_amount' => (float)($x->other_charges_amount ?? 0),
                    'line_total' => (float)$x->line_total,
                    'lead_time_days' => $x->lead_time_days,
                    'expected_delivery_date' => null,
                    'remarks' => $x->remarks,
                ];
            })->values();

        return response()->json([
            'header' => [
                'requisition_id' => $sq->requisition_id,
                'rfq_id' => $sq->rfq_id,
                'supplier_quotation_id' => $sq->id,
                'supplier_id' => $sq->supplier_id,
                'supplier_label' => $sq->supplier_name,
                'supplier_contact_id' => null,
                'supplier_po_ref' => null,
                'po_date' => date('Y-m-d'),
                'expected_delivery_date' => null,
                'currency_code' => $sq->currency_code,
                'fx_rate' => $sq->fx_rate,
                'payment_terms' => $sq->payment_terms,
                'incoterms' => null,
                'reference' => $sq->reference,
                'notes' => $sq->notes,
                'internal_notes' => null,
                'delivery_location_id' => null,
                'delivery_store_id' => null,
                'bill_to_location_id' => null,
            ],
            'lines' => $lines,
        ]);
    }

    public function show($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_orders')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        $lines = DB::table('proc_purchase_order_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.purchase_order_id', $row->id)
            ->orderBy('l.id')
            ->get([
                'l.*',
                'p.product_name as product_name',
                'p.product_code as product_code',
                'u.name as unit_name',
                'u.symbol as unit_symbol',
                'tc.name as tax_code_name',
                'tc.code as tax_code_code',
            ])
            ->map(function ($x) {
                return [
                    'id' => $x->id,
                    'purchase_requisition_line_id' => $x->purchase_requisition_line_id,
                    'rfq_line_id' => $x->rfq_line_id,
                    'supplier_quotation_line_id' => $x->supplier_quotation_line_id,
                    'product_id' => $x->product_id,
                    'product_label' => $x->product_id ? trim(($x->product_code ? $x->product_code.' - ' : '').($x->product_name ?? '')) : null,
                    'description' => $x->description,
                    'unit_id' => $x->unit_id,
                    'unit_label' => $x->unit_id ? trim(($x->unit_name ?? '').($x->unit_symbol ? ' ('.$x->unit_symbol.')' : '')) : null,
                    'location_id' => $x->location_id,
                    'store_id' => $x->store_id,
                    'qty' => (float)$x->qty,
                    'unit_price' => (float)$x->unit_price,
                    'discount_percent' => $x->discount_percent !== null ? (float)$x->discount_percent : null,
                    'discount_amount' => (float)$x->discount_amount,
                    'tax_code_id' => $x->tax_code_id,
                    'tax_code_label' => $x->tax_code_id ? trim(($x->tax_code_code ?? '').' - '.($x->tax_code_name ?? '')) : null,
                    'tax_rate_id' => $x->tax_rate_id,
                    'tax_rate' => $x->tax_rate !== null ? (float)$x->tax_rate : null,
                    'tax_amount' => (float)$x->tax_amount,
                    'shipping_amount' => (float)$x->shipping_amount,
                    'other_charges_amount' => (float)$x->other_charges_amount,
                    'line_total' => (float)$x->line_total,
                    'lead_time_days' => $x->lead_time_days,
                    'expected_delivery_date' => $x->expected_delivery_date,
                    'received_qty' => (float)$x->received_qty,
                    'billed_qty' => (float)$x->billed_qty,
                    'is_closed' => (int)$x->is_closed,
                    'remarks' => $x->remarks,
                ];
            })->values();

        return response()->json([
            'purchase_order' => [
                'id' => $row->id,
                'purchase_requisition_id' => $row->purchase_requisition_id,
                'rfq_id' => $row->rfq_id,
                'supplier_quotation_id' => $row->supplier_quotation_id,
                'supplier_id' => $row->supplier_id,
                'supplier_contact_id' => $row->supplier_contact_id,
                'po_no' => $row->po_no,
                'supplier_po_ref' => $row->supplier_po_ref,
                'po_date' => $row->po_date,
                'expected_delivery_date' => $row->expected_delivery_date,
                'currency_code' => $row->currency_code,
                'fx_rate' => $row->fx_rate,
                'delivery_location_id' => $row->delivery_location_id,
                'delivery_store_id' => $row->delivery_store_id,
                'bill_to_location_id' => $row->bill_to_location_id,
                'payment_terms' => $row->payment_terms,
                'incoterms' => $row->incoterms,
                'reference' => $row->reference,
                'notes' => $row->notes,
                'internal_notes' => $row->internal_notes,
                'status' => $row->status,
                'subtotal' => (float)$row->subtotal,
                'discount_total' => (float)$row->discount_total,
                'tax_total' => (float)$row->tax_total,
                'shipping_total' => (float)$row->shipping_total,
                'other_charges_total' => (float)$row->other_charges_total,
                'total_amount' => (float)$row->total_amount,
                'received_amount' => (float)$row->received_amount,
                'billed_amount' => (float)$row->billed_amount,
            ],
            'lines' => $lines,
        ]);
    }

    public function details($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_orders as po')
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->leftJoin('supplier_contacts as sc', 'sc.id', '=', 'po.supplier_contact_id')
            ->leftJoin('proc_purchase_requisitions as pr', 'pr.id', '=', 'po.purchase_requisition_id')
            ->leftJoin('proc_request_for_quotations as rfq', 'rfq.id', '=', 'po.rfq_id')
            ->leftJoin('proc_supplier_quotations as sq', 'sq.id', '=', 'po.supplier_quotation_id')
            ->leftJoin('locations as dl', 'dl.id', '=', 'po.delivery_location_id')
            ->leftJoin('location_stores as ds', 'ds.id', '=', 'po.delivery_store_id')
            ->leftJoin('locations as bl', 'bl.id', '=', 'po.bill_to_location_id')
            ->leftJoin('users as u1', 'u1.id', '=', 'po.approved_by')
            ->leftJoin('users as u2', 'u2.id', '=', 'po.issued_by')
            ->leftJoin('users as u3', 'u3.id', '=', 'po.closed_by')
            ->leftJoin('users as u4', 'u4.id', '=', 'po.cancelled_by')
            ->where('po.company_id', $companyId)
            ->where('po.id', (int)$id)
            ->whereNull('po.deleted_at')
            ->first([
                'po.*',
                's.name as supplier_name',
                'sc.name as contact_name',
                'sc.email as contact_email',
                'sc.phone as contact_phone',
                'pr.requisition_no',
                'rfq.rfq_no',
                'sq.quotation_no',
                'dl.name as delivery_location_name',
                'ds.name as delivery_store_name',
                'bl.name as bill_to_location_name',
                'u1.name as approved_by_name',
                'u2.name as issued_by_name',
                'u3.name as closed_by_name',
                'u4.name as cancelled_by_name',
            ]);

        if (!$row) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        $lines = DB::table('proc_purchase_order_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.purchase_order_id', $row->id)
            ->orderBy('l.id')
            ->get([
                'l.*',
                'p.product_name as product_name',
                'p.product_code as product_code',
                'u.name as unit_name',
                'u.symbol as unit_symbol',
                'tc.name as tax_code_name',
                'tc.code as tax_code_code',
            ]);

        return response()->json([
            'header' => [
                'id' => $row->id,
                'po_no' => $row->po_no,
                'supplier_po_ref' => $row->supplier_po_ref,
                'po_date' => $row->po_date,
                'expected_delivery_date' => $row->expected_delivery_date,
                'status' => $row->status,
                'supplier' => $row->supplier_name,
                'contact_name' => $row->contact_name,
                'contact_email' => $row->contact_email,
                'contact_phone' => $row->contact_phone,
                'purchase_requisition_no' => $row->requisition_no,
                'rfq_no' => $row->rfq_no,
                'quotation_no' => $row->quotation_no,
                'currency_code' => $row->currency_code,
                'fx_rate' => $row->fx_rate !== null ? (float)$row->fx_rate : null,
                'payment_terms' => $row->payment_terms,
                'incoterms' => $row->incoterms,
                'reference' => $row->reference,
                'notes' => $row->notes,
                'internal_notes' => $row->internal_notes,
                'delivery_location' => $row->delivery_location_name,
                'delivery_store' => $row->delivery_store_name,
                'bill_to_location' => $row->bill_to_location_name,
                'subtotal' => (float)$row->subtotal,
                'discount_total' => (float)$row->discount_total,
                'tax_total' => (float)$row->tax_total,
                'shipping_total' => (float)$row->shipping_total,
                'other_charges_total' => (float)$row->other_charges_total,
                'total_amount' => (float)$row->total_amount,
                'approved_at' => $row->approved_at,
                'approved_by' => $row->approved_by_name,
                'issued_at' => $row->issued_at,
                'issued_by' => $row->issued_by_name,
                'closed_at' => $row->closed_at,
                'closed_by' => $row->closed_by_name,
                'cancelled_at' => $row->cancelled_at,
                'cancelled_by' => $row->cancelled_by_name,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ],
            'lines' => $lines,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validatePurchaseOrder($request);

        return DB::transaction(function () use ($companyId, $data) {
            $id = DB::table('proc_purchase_orders')->insertGetId([
                'company_id' => $companyId,
                'purchase_requisition_id' => $data['header']['purchase_requisition_id'],
                'rfq_id' => $data['header']['rfq_id'],
                'supplier_quotation_id' => $data['header']['supplier_quotation_id'],
                'supplier_id' => $data['header']['supplier_id'],
                'supplier_contact_id' => $data['header']['supplier_contact_id'],
                'po_no' => $this->generatePoNo($companyId),
                'supplier_po_ref' => $data['header']['supplier_po_ref'],
                'po_date' => $data['header']['po_date'],
                'expected_delivery_date' => $data['header']['expected_delivery_date'],
                'currency_code' => $data['header']['currency_code'],
                'fx_rate' => $data['header']['fx_rate'],
                'delivery_location_id' => $data['header']['delivery_location_id'],
                'delivery_store_id' => $data['header']['delivery_store_id'],
                'bill_to_location_id' => $data['header']['bill_to_location_id'],
                'payment_terms' => $data['header']['payment_terms'],
                'incoterms' => $data['header']['incoterms'],
                'reference' => $data['header']['reference'],
                'notes' => $data['header']['notes'],
                'internal_notes' => $data['header']['internal_notes'],
                'status' => 'draft',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'shipping_total' => 0,
                'other_charges_total' => 0,
                'total_amount' => 0,
                'received_amount' => 0,
                'billed_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($data['lines'])) {
                $rows = array_map(function ($ln) use ($id) {
                    $ln['purchase_order_id'] = $id;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);

                DB::table('proc_purchase_order_lines')->insert($rows);
            }

            $this->recalcTotals($id);

            $this->auditLog(
                'procurement.purchase_orders',
                'create',
                'PurchaseOrder',
                $id,
                'Purchase Order created'
            );

            return response()->json([
                'message' => 'Purchase Order created.',
                'id' => $id,
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_orders')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        if (!in_array($row->status, ['draft', 'approved'])) {
            return response()->json(['message' => 'Only draft or approved purchase orders can be edited.'], 422);
        }

        $data = $this->validatePurchaseOrder($request);

        return DB::transaction(function () use ($id, $data) {
            DB::table('proc_purchase_orders')
                ->where('id', (int)$id)
                ->update([
                    'purchase_requisition_id' => $data['header']['purchase_requisition_id'],
                    'rfq_id' => $data['header']['rfq_id'],
                    'supplier_quotation_id' => $data['header']['supplier_quotation_id'],
                    'supplier_id' => $data['header']['supplier_id'],
                    'supplier_contact_id' => $data['header']['supplier_contact_id'],
                    'supplier_po_ref' => $data['header']['supplier_po_ref'],
                    'po_date' => $data['header']['po_date'],
                    'expected_delivery_date' => $data['header']['expected_delivery_date'],
                    'currency_code' => $data['header']['currency_code'],
                    'fx_rate' => $data['header']['fx_rate'],
                    'delivery_location_id' => $data['header']['delivery_location_id'],
                    'delivery_store_id' => $data['header']['delivery_store_id'],
                    'bill_to_location_id' => $data['header']['bill_to_location_id'],
                    'payment_terms' => $data['header']['payment_terms'],
                    'incoterms' => $data['header']['incoterms'],
                    'reference' => $data['header']['reference'],
                    'notes' => $data['header']['notes'],
                    'internal_notes' => $data['header']['internal_notes'],
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            DB::table('proc_purchase_order_lines')
                ->where('purchase_order_id', (int)$id)
                ->delete();

            if (!empty($data['lines'])) {
                $rows = array_map(function ($ln) use ($id) {
                    $ln['purchase_order_id'] = $id;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);

                DB::table('proc_purchase_order_lines')->insert($rows);
            }

            $this->recalcTotals((int)$id);

            $this->auditLog(
                'procurement.purchase_orders',
                'update',
                'PurchaseOrder',
                (int)$id,
                'Purchase Order updated'
            );

            return response()->json(['message' => 'Purchase Order updated.']);
        });
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_orders')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        if ($row->status !== 'draft') {
            return response()->json(['message' => 'Only draft purchase orders can be deleted.'], 422);
        }

        DB::table('proc_purchase_orders')
            ->where('id', (int)$id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        $this->auditLog(
            'procurement.purchase_orders',
            'delete',
            'PurchaseOrder',
            (int)$id,
            'Purchase Order deleted'
        );

        return response()->json(['message' => 'Purchase Order deleted.']);
    }

    public function approve($id)
    {
        return $this->changeStatus($id, ['draft'], 'approved', 'approved_at', 'approved_by', 'Purchase Order approved');
    }

    public function issue($id)
    {
        return $this->changeStatus($id, ['approved'], 'issued', 'issued_at', 'issued_by', 'Purchase Order issued');
    }

    public function close($id)
    {
        return $this->changeStatus($id, ['issued', 'partially_rcv', 'fully_rcv', 'partially_billed', 'billed'], 'closed', 'closed_at', 'closed_by', 'Purchase Order closed');
    }

    public function cancel($id)
    {
        return $this->changeStatus($id, ['draft', 'approved'], 'cancelled', 'cancelled_at', 'cancelled_by', 'Purchase Order cancelled');
    }

    protected function changeStatus($id, array $from, string $to, string $stampField, string $userField, string $description)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_orders')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        if (!in_array($row->status, $from, true)) {
            return response()->json(['message' => 'This purchase order cannot move to the requested status.'], 422);
        }

        DB::table('proc_purchase_orders')
            ->where('id', (int)$id)
            ->update([
                'status' => $to,
                $stampField => now(),
                $userField => auth()->id(),
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        $this->auditLog(
            'procurement.purchase_orders',
            $to,
            'PurchaseOrder',
            (int)$id,
            $description
        );

        return response()->json(['message' => ucfirst($to).' successfully.']);
    }

    public function pdf($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_orders as po')
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->leftJoin('supplier_contacts as sc', 'sc.id', '=', 'po.supplier_contact_id')
            ->leftJoin('proc_purchase_requisitions as pr', 'pr.id', '=', 'po.purchase_requisition_id')
            ->leftJoin('proc_request_for_quotations as rfq', 'rfq.id', '=', 'po.rfq_id')
            ->leftJoin('proc_supplier_quotations as sq', 'sq.id', '=', 'po.supplier_quotation_id')
            ->leftJoin('locations as dl', 'dl.id', '=', 'po.delivery_location_id')
            ->leftJoin('location_stores as ds', 'ds.id', '=', 'po.delivery_store_id')
            ->leftJoin('locations as bl', 'bl.id', '=', 'po.bill_to_location_id')
            ->where('po.company_id', $companyId)
            ->where('po.id', (int)$id)
            ->whereNull('po.deleted_at')
            ->first([
                'po.*',
                's.name as supplier_name',
                'sc.name as contact_name',
                'sc.email as contact_email',
                'sc.phone as contact_phone',
                'pr.requisition_no',
                'rfq.rfq_no',
                'sq.quotation_no',
                'dl.name as delivery_location_name',
                'ds.name as delivery_store_name',
                'bl.name as bill_to_location_name',
            ]);

        abort_if(!$row, 404, 'Purchase order not found.');

        $lines = DB::table('proc_purchase_order_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.purchase_order_id', $row->id)
            ->orderBy('l.id')
            ->get([
                'l.*',
                'p.product_name as product_name',
                'p.product_code as product_code',
                'u.name as unit_name',
                'u.symbol as unit_symbol',
                'tc.name as tax_code_name',
                'tc.code as tax_code_code',
            ]);

        $pdf = Pdf::loadView('procurement.purchase_orders.pdf', [
            'purchaseOrder' => $row,
            'lines' => $lines,
        ])->setPaper('a4', 'portrait');

        $this->auditLog(
            'procurement.purchase_orders',
            'download_pdf',
            'PurchaseOrder',
            (int)$row->id,
            'Purchase Order PDF downloaded'
        );

        return $pdf->download(($row->po_no ?: 'purchase-order-'.$row->id).'.pdf');
    }

    public function select2Suppliers(Request $request)
    {
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('suppliers')
            ->when($q !== '', function ($x) use ($q) {
                $x->where('name', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'default_currency', 'payment_terms']);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => $r->name,
                'default_currency' => $r->default_currency ?? null,
                'payment_terms' => $r->payment_terms ?? null,
            ])->values(),
        ]);
    }

    public function select2SupplierContacts(Request $request)
    {
        $supplierId = (int)$request->get('supplier_id');
        $q = trim((string)$request->get('q', ''));

        if (!$supplierId) {
            return response()->json(['results' => []]);
        }

        $rows = DB::table('supplier_contacts')
            ->where('supplier_id', $supplierId)
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => $r->name,
                'email' => $r->email,
                'phone' => $r->phone,
            ])->values(),
        ]);
    }

    public function select2Quotations(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('proc_supplier_quotations as sq')
            ->leftJoin('suppliers as s', 's.id', '=', 'sq.supplier_id')
            ->where('sq.company_id', $companyId)
            ->whereNull('sq.deleted_at')
            ->whereIn('sq.status', ['accepted', 'reviewed'])
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('sq.quotation_no', 'like', "%{$q}%")
                      ->orWhere('s.name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('sq.id')
            ->limit(30)
            ->get([
                'sq.id',
                'sq.quotation_no',
                'sq.supplier_id',
                's.name as supplier_name',
            ]);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => trim($r->quotation_no.' - '.($r->supplier_name ?? '')),
                'supplier_id' => $r->supplier_id,
                'supplier_name' => $r->supplier_name,
            ])->values(),
        ]);
    }

    public function select2Locations(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('locations')
            ->where('company_id', $companyId)
            ->when($q !== '', fn($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => $r->name,
            ])->values(),
        ]);
    }

    public function select2Stores(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('location_stores')
                ->join('locations', 'locations.id', '=', 'location_stores.location_id')
                ->where('locations.company_id', $companyId)
                ->when($q !== '', function ($x) use ($q) {
                    $x->where('location_stores.name', 'like', "%{$q}%");
                })
                ->orderBy('location_stores.name')
                ->limit(30)
                ->get([
                    'location_stores.id',
                    'location_stores.name'
                ]);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => $r->name,
            ])->values(),
        ]);
    }

    public function select2Products(Request $request)
    {
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('products')
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('product_name', 'like', "%{$q}%")
                      ->orWhere('product_code', 'like', "%{$q}%");
                });
            })
            ->orderBy('product_name')
            ->limit(30)
            ->get(['id', 'product_code', 'product_name']);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => trim(($r->product_code ? $r->product_code.' - ' : '').$r->product_name),
            ])->values(),
        ]);
    }

    public function select2Units(Request $request)
    {
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('units')
            ->when($q !== '', fn($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'symbol']);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => trim($r->name.($r->symbol ? ' ('.$r->symbol.')' : '')),
            ])->values(),
        ]);
    }

    public function select2TaxCodes(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('finance_tax_codes as tc')
            ->leftJoin('finance_tax_rates as tr', 'tr.id', '=', 'tc.rate_id')
            ->where('tc.company_id', $companyId)
            ->where('tc.is_active', 1)
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('tc.name', 'like', "%{$q}%")
                      ->orWhere('tc.code', 'like', "%{$q}%");
                });
            })
            ->orderBy('tc.name')
            ->limit(30)
            ->get([
                'tc.id',
                'tc.name',
                'tc.code',
                'tc.rate_id',
                'tc.is_exempt',
                'tc.is_out_of_scope',
                'tr.rate',
            ]);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => trim(($r->code ?? '').' - '.($r->name ?? '')),
                'rate_id' => $r->rate_id,
                'rate' => $r->rate ?? 0,
                'is_exempt' => (int)$r->is_exempt,
                'is_out_of_scope' => (int)$r->is_out_of_scope,
            ])->values(),
        ]);
    }

    private function validatePurchaseOrder(Request $request): array
    {
        $v = Validator::make($request->all(), [
            'purchase_requisition_id' => ['nullable', 'integer'],
            'rfq_id' => ['nullable', 'integer'],
            'supplier_quotation_id' => ['nullable', 'integer'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'supplier_contact_id' => ['nullable', 'integer', 'exists:supplier_contacts,id'],
            'supplier_po_ref' => ['nullable', 'string', 'max:100'],
            'po_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'fx_rate' => ['nullable', 'numeric'],
            'delivery_location_id' => ['nullable', 'integer'],
            'delivery_store_id' => ['nullable', 'integer'],
            'bill_to_location_id' => ['nullable', 'integer'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'incoterms' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_requisition_line_id' => ['nullable', 'integer'],
            'lines.*.rfq_line_id' => ['nullable', 'integer'],
            'lines.*.supplier_quotation_line_id' => ['nullable', 'integer'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.unit_id' => ['nullable', 'integer'],
            'lines.*.location_id' => ['nullable', 'integer'],
            'lines.*.store_id' => ['nullable', 'integer'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'lines.*.shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.other_charges_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.lead_time_days' => ['nullable', 'integer', 'min:0'],
            'lines.*.expected_delivery_date' => ['nullable', 'date'],
            'lines.*.remarks' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $header = [
            'purchase_requisition_id' => !empty($v['purchase_requisition_id']) ? (int)$v['purchase_requisition_id'] : null,
            'rfq_id' => !empty($v['rfq_id']) ? (int)$v['rfq_id'] : null,
            'supplier_quotation_id' => !empty($v['supplier_quotation_id']) ? (int)$v['supplier_quotation_id'] : null,
            'supplier_id' => (int)$v['supplier_id'],
            'supplier_contact_id' => !empty($v['supplier_contact_id']) ? (int)$v['supplier_contact_id'] : null,
            'supplier_po_ref' => $v['supplier_po_ref'] ?? null,
            'po_date' => $v['po_date'],
            'expected_delivery_date' => $v['expected_delivery_date'] ?? null,
            'currency_code' => !empty($v['currency_code']) ? strtoupper(trim($v['currency_code'])) : null,
            'fx_rate' => $v['fx_rate'] ?? null,
            'delivery_location_id' => !empty($v['delivery_location_id']) ? (int)$v['delivery_location_id'] : null,
            'delivery_store_id' => !empty($v['delivery_store_id']) ? (int)$v['delivery_store_id'] : null,
            'bill_to_location_id' => !empty($v['bill_to_location_id']) ? (int)$v['bill_to_location_id'] : null,
            'payment_terms' => $v['payment_terms'] ?? null,
            'incoterms' => $v['incoterms'] ?? null,
            'reference' => $v['reference'] ?? null,
            'notes' => $v['notes'] ?? null,
            'internal_notes' => $v['internal_notes'] ?? null,
        ];

        $lines = [];
        foreach ($v['lines'] as $ln) {
            $qty = (float)$ln['qty'];
            $unitPrice = (float)$ln['unit_price'];
            $discountPercent = isset($ln['discount_percent']) && $ln['discount_percent'] !== '' ? (float)$ln['discount_percent'] : 0.0;
            $rate = isset($ln['tax_rate']) && $ln['tax_rate'] !== '' ? (float)$ln['tax_rate'] : 0.0;
            $shippingAmount = isset($ln['shipping_amount']) && $ln['shipping_amount'] !== '' ? (float)$ln['shipping_amount'] : 0.0;
            $otherChargesAmount = isset($ln['other_charges_amount']) && $ln['other_charges_amount'] !== '' ? (float)$ln['other_charges_amount'] : 0.0;

            $gross = $qty * $unitPrice;
            $discountAmount = $discountPercent > 0 ? round($gross * $discountPercent / 100, 2) : 0.0;
            $taxBase = $gross - $discountAmount;
            $taxAmount = $rate > 0 ? round($taxBase * $rate / 100, 2) : 0.0;
            $lineTotal = round($taxBase + $taxAmount + $shippingAmount + $otherChargesAmount, 2);

            $taxRateId = null;
            if (!empty($ln['tax_code_id'])) {
                $taxCode = DB::table('finance_tax_codes')->where('id', (int)$ln['tax_code_id'])->first(['rate_id']);
                $taxRateId = $taxCode?->rate_id;
            }

            $lines[] = [
                'purchase_requisition_line_id' => !empty($ln['purchase_requisition_line_id']) ? (int)$ln['purchase_requisition_line_id'] : null,
                'rfq_line_id' => !empty($ln['rfq_line_id']) ? (int)$ln['rfq_line_id'] : null,
                'supplier_quotation_line_id' => !empty($ln['supplier_quotation_line_id']) ? (int)$ln['supplier_quotation_line_id'] : null,
                'product_id' => !empty($ln['product_id']) ? (int)$ln['product_id'] : null,
                'product_variant_id' => null,
                'description' => $ln['description'] ?? null,
                'unit_id' => !empty($ln['unit_id']) ? (int)$ln['unit_id'] : null,
                'location_id' => !empty($ln['location_id']) ? (int)$ln['location_id'] : null,
                'store_id' => !empty($ln['store_id']) ? (int)$ln['store_id'] : null,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent > 0 ? $discountPercent : null,
                'discount_amount' => $discountAmount,
                'tax_code_id' => !empty($ln['tax_code_id']) ? (int)$ln['tax_code_id'] : null,
                'tax_rate_id' => $taxRateId,
                'tax_rate' => $rate > 0 ? $rate : null,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'other_charges_amount' => $otherChargesAmount,
                'line_total' => $lineTotal,
                'lead_time_days' => !empty($ln['lead_time_days']) ? (int)$ln['lead_time_days'] : null,
                'expected_delivery_date' => $ln['expected_delivery_date'] ?? null,
                'received_qty' => 0,
                'billed_qty' => 0,
                'is_closed' => 0,
                'remarks' => $ln['remarks'] ?? null,
            ];
        }

        return compact('header', 'lines');
    }

    private function recalcTotals(int $id): void
    {
        $rows = DB::table('proc_purchase_order_lines')
            ->where('purchase_order_id', $id)
            ->get([
                'qty',
                'unit_price',
                'discount_amount',
                'tax_amount',
                'shipping_amount',
                'other_charges_amount',
                'line_total',
                'received_qty',
                'billed_qty',
            ]);

        $subtotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;
        $shippingTotal = 0.0;
        $otherChargesTotal = 0.0;
        $grand = 0.0;
        $receivedAmount = 0.0;
        $billedAmount = 0.0;

        foreach ($rows as $r) {
            $subtotal += ((float)$r->qty * (float)$r->unit_price);
            $discountTotal += (float)$r->discount_amount;
            $taxTotal += (float)$r->tax_amount;
            $shippingTotal += (float)$r->shipping_amount;
            $otherChargesTotal += (float)$r->other_charges_amount;
            $grand += (float)$r->line_total;

            $lineQty = (float)$r->qty;
            $lineTotal = (float)$r->line_total;

            if ($lineQty > 0) {
                $unitFullValue = $lineTotal / $lineQty;
                $receivedAmount += ((float)$r->received_qty * $unitFullValue);
                $billedAmount += ((float)$r->billed_qty * $unitFullValue);
            }
        }

        DB::table('proc_purchase_orders')
            ->where('id', $id)
            ->update([
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discountTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'shipping_total' => round($shippingTotal, 2),
                'other_charges_total' => round($otherChargesTotal, 2),
                'total_amount' => round($grand, 2),
                'received_amount' => round($receivedAmount, 2),
                'billed_amount' => round($billedAmount, 2),
                'updated_at' => now(),
            ]);
    }

    private function generatePoNo(int $companyId): string
    {
        $prefix = 'PO-'.date('Ym').'-';

        $last = DB::table('proc_purchase_orders')
            ->where('company_id', $companyId)
            ->where('po_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('po_no');

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $next = ((int)$m[1]) + 1;
        }

        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    private function statusBadge(?string $status): string
    {
        return match (strtolower((string)$status)) {
            'approved' => '<span class="badge bg-success">APPROVED</span>',
            'issued' => '<span class="badge bg-primary">ISSUED</span>',
            'partially_rcv' => '<span class="badge bg-info">PARTIALLY RECEIVED</span>',
            'fully_rcv' => '<span class="badge bg-success">FULLY RECEIVED</span>',
            'partially_billed' => '<span class="badge bg-warning text-dark">PARTIALLY BILLED</span>',
            'billed' => '<span class="badge bg-dark">BILLED</span>',
            'closed' => '<span class="badge bg-secondary">CLOSED</span>',
            'cancelled' => '<span class="badge bg-danger">CANCELLED</span>',
            default => '<span class="badge bg-warning text-dark">DRAFT</span>',
        };
    }

    private function auditLog(
        string $module,
        string $action,
        ?string $subjectType = null,
        $subjectId = null,
        ?string $description = null,
        array $meta = []
    ): void {
        DB::table('audit_logs')->insert([
            'user_id' => auth()->id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'route' => request()->route() ? request()->route()->getName() : null,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => !empty($meta) ? json_encode($meta) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}