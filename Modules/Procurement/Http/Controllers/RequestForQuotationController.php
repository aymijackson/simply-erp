<?php

namespace Modules\Procurement\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RequestForQuotationController extends Controller
{
    public function index()
    {
        return view('procurement.rfqs.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $base = DB::table('proc_request_for_quotations as r')
            ->leftJoin('proc_purchase_requisitions as pr', 'pr.id', '=', 'r.requisition_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.created_by')
            ->where('r.company_id', $companyId)
            ->whereNull('r.deleted_at');

        $q = clone $base;

        if ($request->filled('status')) {
            $q->where('r.status', $request->status);
        }

        if ($request->filled('date_from')) {
            $q->where('r.rfq_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->where('r.rfq_date', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $term = trim((string)$request->q);
            $q->where(function ($x) use ($term) {
                $x->where('r.rfq_no', 'like', "%{$term}%")
                    ->orWhere('r.reference', 'like', "%{$term}%")
                    ->orWhere('r.notes', 'like', "%{$term}%")
                    ->orWhere('pr.requisition_no', 'like', "%{$term}%")
                    ->orWhere('u.name', 'like', "%{$term}%");
            });
        }

        $recordsTotal = (clone $base)->count('r.id');
        $recordsFiltered = (clone $q)->count('r.id');

        $start = (int)($request->start ?? 0);
        $length = (int)($request->length ?? 10);
        $draw = (int)($request->draw ?? 1);

        $columns = [
            0 => 'r.id',
            1 => 'r.rfq_no',
            2 => 'r.rfq_date',
            3 => 'r.closing_date',
            4 => 'r.status',
            5 => 'pr.requisition_no',
            6 => 'r.total_amount',
        ];

        $orderColIndex = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'desc');

        $q->select([
            'r.id',
            'r.rfq_no',
            'r.rfq_date',
            'r.closing_date',
            'r.status',
            'r.reference',
            'r.total_amount',
            'pr.requisition_no',
            'u.name as created_by_name',
        ]);

        if ($orderColIndex !== null && isset($columns[(int)$orderColIndex])) {
            $q->orderBy($columns[(int)$orderColIndex], $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $q->orderBy('r.id', 'desc');
        }

        $rows = $q->offset($start)->limit($length)->get();

        $data = $rows->map(function ($r) {
            $supplierCount = DB::table('proc_request_for_quotation_suppliers')
                ->where('rfq_id', $r->id)
                ->count();

            $json = [
                'id' => $r->id,
                'rfq_no' => $r->rfq_no,
                'rfq_date' => $r->rfq_date,
                'closing_date' => $r->closing_date,
                'status' => $r->status,
                'reference' => $r->reference,
            ];

            return [
                'id' => $r->id,
                'rfq_no' => e($r->rfq_no ?: ('RFQ-'.$r->id)),
                'rfq_date' => e($r->rfq_date),
                'closing_date' => e($r->closing_date ?? '—'),
                'status' => $this->statusBadge($r->status),
                'requisition_no' => e($r->requisition_no ?? '—'),
                'supplier_count' => (int)$supplierCount,
                'total_amount' => number_format((float)$r->total_amount, 2),
                'actions' => view('procurement.rfqs.partials.actions', ['json' => $json])->render(),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function createFromRequisition($requisitionId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $req = DB::table('proc_purchase_requisitions')
            ->where('company_id', $companyId)
            ->where('id', (int)$requisitionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$req) {
            return response()->json(['message' => 'Approved requisition not found.'], 404);
        }

        if ($req->status !== 'approved') {
            return response()->json(['message' => 'Only approved requisitions can be converted to RFQ.'], 422);
        }

        $lines = DB::table('proc_purchase_requisition_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.purchase_requisition_id', $req->id)
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
                    'requisition_line_id'   => $x->id,
                    'product_id'            => $x->product_id,
                    'product_label'         => $x->product_id ? trim(($x->product_code ? $x->product_code.' - ' : '').($x->product_name ?? '')) : null,
                    'description'           => $x->description,
                    'unit_id'               => $x->unit_id,
                    'unit_label'            => $x->unit_id ? trim(($x->unit_name ?? '').($x->unit_symbol ? ' ('.$x->unit_symbol.')' : '')) : null,
                    'qty'                   => (float)$x->qty,
                    'estimated_unit_cost'   => (float)$x->estimated_unit_cost,
                    'tax_code_id'           => $x->tax_code_id,
                    'tax_code_label'        => $x->tax_code_id ? trim(($x->tax_code_code ?? '').' - '.($x->tax_code_name ?? '')) : null,
                    'tax_rate_id'           => $x->tax_rate_id,
                    'tax_rate'              => $x->tax_rate !== null ? (float)$x->tax_rate : null,
                    'tax_amount'            => (float)$x->tax_amount,
                    'line_total'            => (float)$x->line_total,
                    'location_id'           => $x->location_id,
                    'store_id'              => $x->store_id,
                    'memo'                  => $x->memo,
                ];
            })->values();

        return response()->json([
            'header' => [
                'requisition_id' => $req->id,
                'requisition_no' => $req->requisition_no,
                'rfq_date' => date('Y-m-d'),
                'closing_date' => null,
                'reference' => $req->reference,
                'notes' => $req->notes,
            ],
            'lines' => $lines,
        ]);
    }

    public function show($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $rfq = DB::table('proc_request_for_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$rfq) {
            return response()->json(['message' => 'RFQ not found.'], 404);
        }

        $lines = DB::table('proc_request_for_quotation_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.rfq_id', $rfq->id)
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
                    'id' => $x->id,
                    'requisition_line_id' => $x->requisition_line_id,
                    'product_id' => $x->product_id,
                    'product_label' => $x->product_id ? trim(($x->product_code ? $x->product_code.' - ' : '').($x->product_name ?? '')) : null,
                    'description' => $x->description,
                    'unit_id' => $x->unit_id,
                    'unit_label' => $x->unit_id ? trim(($x->unit_name ?? '').($x->unit_symbol ? ' ('.$x->unit_symbol.')' : '')) : null,
                    'qty' => (float)$x->qty,
                    'estimated_unit_cost' => (float)$x->estimated_unit_cost,
                    'tax_code_id' => $x->tax_code_id,
                    'tax_code_label' => $x->tax_code_id ? trim(($x->tax_code_code ?? '').' - '.($x->tax_code_name ?? '')) : null,
                    'tax_rate_id' => $x->tax_rate_id,
                    'tax_rate' => $x->tax_rate !== null ? (float)$x->tax_rate : null,
                    'tax_amount' => (float)$x->tax_amount,
                    'line_total' => (float)$x->line_total,
                    'location_id' => $x->location_id,
                    'store_id' => $x->store_id,
                    'memo' => $x->memo,
                ];
            })->values();

        $suppliers = DB::table('proc_request_for_quotation_suppliers as s')
            ->leftJoin('suppliers as sp', 'sp.id', '=', 's.supplier_id')
            ->leftJoin('supplier_contacts as sc', 'sc.id', '=', 's.supplier_contact_id')
            ->where('s.rfq_id', $rfq->id)
            ->orderBy('sp.name')
            ->get([
                's.*',
                'sp.name as supplier_name',
                'sc.name as supplier_contact_name',
                'sc.email as supplier_contact_email',
                'sc.phone as supplier_contact_phone',
            ])
            ->map(function ($x) {
                return [
                    'id' => $x->id,
                    'supplier_id' => $x->supplier_id,
                    'supplier_label' => $x->supplier_name,
                    'supplier_contact_id' => $x->supplier_contact_id,
                    'supplier_contact_label' => $x->supplier_contact_name,
                    'response_status' => $x->response_status,
                    'contact_name' => $x->contact_name,
                    'contact_email' => $x->contact_email,
                    'contact_phone' => $x->contact_phone,
                    'notes' => $x->notes,
                ];
            })->values();

        return response()->json([
            'rfq' => [
                'id' => $rfq->id,
                'requisition_id' => $rfq->requisition_id,
                'rfq_no' => $rfq->rfq_no,
                'rfq_date' => $rfq->rfq_date,
                'closing_date' => $rfq->closing_date,
                'currency_code' => $rfq->currency_code,
                'fx_rate' => $rfq->fx_rate,
                'status' => $rfq->status,
                'reference' => $rfq->reference,
                'notes' => $rfq->notes,
                'subtotal' => (float)$rfq->subtotal,
                'tax_total' => (float)$rfq->tax_total,
                'total_amount' => (float)$rfq->total_amount,
            ],
            'lines' => $lines,
            'suppliers' => $suppliers,
        ]);
    }

    public function details($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $rfq = DB::table('proc_request_for_quotations as r')
            ->leftJoin('proc_purchase_requisitions as pr', 'pr.id', '=', 'r.requisition_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.created_by')
            ->where('r.company_id', $companyId)
            ->where('r.id', (int)$id)
            ->whereNull('r.deleted_at')
            ->first([
                'r.*',
                'pr.requisition_no',
                'u.name as created_by_name',
            ]);

        if (!$rfq) {
            return response()->json(['message' => 'RFQ not found.'], 404);
        }

        $lines = DB::table('proc_request_for_quotation_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.rfq_id', $rfq->id)
            ->orderBy('l.id')
            ->get([
                'l.*',
                'p.product_code',
                'p.product_name',
                'u.name as unit_name',
                'u.symbol as unit_symbol',
                'tc.code as tax_code_code',
                'tc.name as tax_code_name',
            ]);

        $suppliers = DB::table('proc_request_for_quotation_suppliers as rs')
            ->leftJoin('suppliers as s', 's.id', '=', 'rs.supplier_id')
            ->leftJoin('supplier_contacts as sc', 'sc.id', '=', 'rs.supplier_contact_id')
            ->where('rs.rfq_id', $rfq->id)
            ->orderBy('s.name')
            ->get([
                'rs.*',
                's.name as supplier_name',
                'sc.name as supplier_contact_name',
            ]);

        $this->auditLog(
            'procurement.rfqs',
            'view',
            'RequestForQuotation',
            (int)$rfq->id,
            'RFQ viewed',
            ['rfq_no' => $rfq->rfq_no]
        );

        return response()->json([
            'header' => [
                'id' => $rfq->id,
                'rfq_no' => $rfq->rfq_no,
                'rfq_date' => $rfq->rfq_date,
                'closing_date' => $rfq->closing_date,
                'status' => $rfq->status,
                'reference' => $rfq->reference,
                'notes' => $rfq->notes,
                'requisition_no' => $rfq->requisition_no,
                'created_by' => $rfq->created_by_name,
                'subtotal' => (float)$rfq->subtotal,
                'tax_total' => (float)$rfq->tax_total,
                'total_amount' => (float)$rfq->total_amount,
            ],
            'lines' => $lines,
            'suppliers' => $suppliers,
        ]);
    }

    public function pdf($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $rfq = DB::table('proc_request_for_quotations as r')
            ->leftJoin('proc_purchase_requisitions as pr', 'pr.id', '=', 'r.requisition_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.created_by')
            ->where('r.company_id', $companyId)
            ->where('r.id', (int)$id)
            ->whereNull('r.deleted_at')
            ->first([
                'r.*',
                'pr.requisition_no',
                'u.name as created_by_name',
            ]);

        abort_if(!$rfq, 404, 'RFQ not found.');

        $lines = DB::table('proc_request_for_quotation_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.rfq_id', $rfq->id)
            ->orderBy('l.id')
            ->get([
                'l.*',
                'p.product_code',
                'p.product_name',
                'u.name as unit_name',
                'u.symbol as unit_symbol',
                'tc.code as tax_code_code',
                'tc.name as tax_code_name',
            ]);

        $suppliers = DB::table('proc_request_for_quotation_suppliers as rs')
            ->leftJoin('suppliers as s', 's.id', '=', 'rs.supplier_id')
            ->leftJoin('supplier_contacts as sc', 'sc.id', '=', 'rs.supplier_contact_id')
            ->where('rs.rfq_id', $rfq->id)
            ->orderBy('s.name')
            ->get([
                'rs.*',
                's.name as supplier_name',
                'sc.name as supplier_contact_name',
            ]);

        $pdf = Pdf::loadView('procurement.rfqs.pdf', [
            'rfq' => $rfq,
            'lines' => $lines,
            'suppliers' => $suppliers,
        ])->setPaper('a4', 'portrait');

        $this->auditLog(
            'procurement.rfqs',
            'download_pdf',
            'RequestForQuotation',
            (int)$rfq->id,
            'RFQ PDF downloaded',
            ['rfq_no' => $rfq->rfq_no]
        );

        return $pdf->stream(($rfq->rfq_no ?: 'rfq-'.$rfq->id).'.pdf');
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $data = $this->validateRfq($request);

        return DB::transaction(function () use ($companyId, $data) {
            $id = DB::table('proc_request_for_quotations')->insertGetId([
                'company_id' => $companyId,
                'requisition_id' => $data['header']['requisition_id'],
                'rfq_no' => $this->generateRfqNo($companyId),
                'rfq_date' => $data['header']['rfq_date'],
                'closing_date' => $data['header']['closing_date'],
                'currency_code' => $data['header']['currency_code'],
                'fx_rate' => $data['header']['fx_rate'],
                'status' => 'draft',
                'reference' => $data['header']['reference'],
                'notes' => $data['header']['notes'],
                'created_by' => auth()->id(),
                'subtotal' => 0,
                'tax_total' => 0,
                'total_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($data['lines'])) {
                $rows = array_map(function ($ln) use ($id) {
                    $ln['rfq_id'] = $id;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);
                DB::table('proc_request_for_quotation_lines')->insert($rows);
            }

            if (!empty($data['suppliers'])) {
                $suppliers = array_map(function ($sp) use ($id) {
                    return [
                        'rfq_id' => $id,
                        'supplier_id' => $sp['supplier_id'],
                        'supplier_contact_id' => $sp['supplier_contact_id'] ?? null,
                        'response_status' => 'pending',
                        'contact_name' => $sp['contact_name'] ?? null,
                        'contact_email' => $sp['contact_email'] ?? null,
                        'contact_phone' => $sp['contact_phone'] ?? null,
                        'notes' => $sp['notes'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $data['suppliers']);
                DB::table('proc_request_for_quotation_suppliers')->insert($suppliers);
            }

            $this->recalcTotals($id);

            $this->auditLog(
                'procurement.rfqs',
                'create',
                'RequestForQuotation',
                $id,
                'RFQ created',
                [
                    'line_count' => count($data['lines']),
                    'supplier_count' => count($data['suppliers']),
                ]
            );

            return response()->json([
                'message' => 'RFQ created.',
                'id' => $id,
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_request_for_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'RFQ not found.'], 404);
        }

        if (!in_array($row->status, ['draft'])) {
            return response()->json(['message' => 'Only draft RFQs can be edited.'], 422);
        }

        $data = $this->validateRfq($request);

        return DB::transaction(function () use ($id, $row, $data) {
            DB::table('proc_request_for_quotations')
                ->where('id', (int)$id)
                ->update([
                    'requisition_id' => $data['header']['requisition_id'],
                    'rfq_date' => $data['header']['rfq_date'],
                    'closing_date' => $data['header']['closing_date'],
                    'currency_code' => $data['header']['currency_code'],
                    'fx_rate' => $data['header']['fx_rate'],
                    'reference' => $data['header']['reference'],
                    'notes' => $data['header']['notes'],
                    'updated_at' => now(),
                ]);

            DB::table('proc_request_for_quotation_lines')->where('rfq_id', (int)$id)->delete();
            DB::table('proc_request_for_quotation_suppliers')->where('rfq_id', (int)$id)->delete();

            if (!empty($data['lines'])) {
                $rows = array_map(function ($ln) use ($id) {
                    $ln['rfq_id'] = $id;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);
                DB::table('proc_request_for_quotation_lines')->insert($rows);
            }

            if (!empty($data['suppliers'])) {
                $suppliers = array_map(function ($sp) use ($id) {
                    return [
                        'rfq_id' => $id,
                        'supplier_id' => $sp['supplier_id'],
                        'supplier_contact_id' => $sp['supplier_contact_id'] ?? null,
                        'response_status' => 'pending',
                        'contact_name' => $sp['contact_name'] ?? null,
                        'contact_email' => $sp['contact_email'] ?? null,
                        'contact_phone' => $sp['contact_phone'] ?? null,
                        'notes' => $sp['notes'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $data['suppliers']);
                DB::table('proc_request_for_quotation_suppliers')->insert($suppliers);
            }

            $this->recalcTotals((int)$id);

            $this->auditLog(
                'procurement.rfqs',
                'update',
                'RequestForQuotation',
                (int)$id,
                'RFQ updated',
                [
                    'old_status' => $row->status,
                    'line_count' => count($data['lines']),
                    'supplier_count' => count($data['suppliers']),
                ]
            );

            return response()->json(['message' => 'RFQ updated.']);
        });
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_request_for_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'RFQ not found.'], 404);
        }

        if ($row->status !== 'draft') {
            return response()->json(['message' => 'Only draft RFQs can be deleted.'], 422);
        }

        DB::table('proc_request_for_quotations')
            ->where('id', (int)$id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        $this->auditLog(
            'procurement.rfqs',
            'delete',
            'RequestForQuotation',
            (int)$id,
            'RFQ deleted',
            ['rfq_no' => $row->rfq_no]
        );

        return response()->json(['message' => 'RFQ deleted.']);
    }

    public function send($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $rfq = DB::table('proc_request_for_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$rfq) {
            return response()->json(['message' => 'RFQ not found.'], 404);
        }

        if ($rfq->status !== 'draft') {
            return response()->json(['message' => 'Only draft RFQs can be sent.'], 422);
        }

        $supplierCount = DB::table('proc_request_for_quotation_suppliers')
            ->where('rfq_id', $rfq->id)
            ->count();

        if ($supplierCount < 1) {
            return response()->json(['message' => 'Add at least one supplier before sending RFQ.'], 422);
        }

        DB::transaction(function () use ($rfq) {
            DB::table('proc_request_for_quotations')
                ->where('id', $rfq->id)
                ->update([
                    'status' => 'sent',
                    'updated_at' => now(),
                ]);

            DB::table('proc_request_for_quotation_suppliers')
                ->where('rfq_id', $rfq->id)
                ->whereNull('sent_at')
                ->update([
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        $this->auditLog(
            'procurement.rfqs',
            'send',
            'RequestForQuotation',
            (int)$rfq->id,
            'RFQ sent to suppliers',
            ['rfq_no' => $rfq->rfq_no]
        );

        return response()->json(['message' => 'RFQ sent.']);
    }

    public function close($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $rfq = DB::table('proc_request_for_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$rfq) {
            return response()->json(['message' => 'RFQ not found.'], 404);
        }

        if (!in_array($rfq->status, ['sent'])) {
            return response()->json(['message' => 'Only sent RFQs can be closed.'], 422);
        }

        DB::table('proc_request_for_quotations')
            ->where('id', $rfq->id)
            ->update([
                'status' => 'closed',
                'updated_at' => now(),
            ]);

        $this->auditLog(
            'procurement.rfqs',
            'close',
            'RequestForQuotation',
            (int)$rfq->id,
            'RFQ closed',
            ['rfq_no' => $rfq->rfq_no]
        );

        return response()->json(['message' => 'RFQ closed.']);
    }

    public function award($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $rfq = DB::table('proc_request_for_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$rfq) {
            return response()->json(['message' => 'RFQ not found.'], 404);
        }

        if (!in_array($rfq->status, ['closed', 'sent'])) {
            return response()->json(['message' => 'Only sent or closed RFQs can be awarded.'], 422);
        }

        DB::table('proc_request_for_quotations')
            ->where('id', $rfq->id)
            ->update([
                'status' => 'awarded',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        $this->auditLog(
            'procurement.rfqs',
            'award',
            'RequestForQuotation',
            (int)$rfq->id,
            'RFQ awarded',
            ['rfq_no' => $rfq->rfq_no]
        );

        return response()->json(['message' => 'RFQ awarded.']);
    }

    public function select2Requisitions(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('proc_purchase_requisitions')
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('requisition_no', 'like', "%{$q}%")
                      ->orWhere('reference', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get(['id', 'requisition_no', 'reference']);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => trim(($r->requisition_no ?? 'REQ-'.$r->id).($r->reference ? ' - '.$r->reference : '')),
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
                'tc.tax_type',
                'tc.is_reverse_charge',
                'tc.is_exempt',
                'tc.is_out_of_scope',
                'tc.rate_id',
                'tr.rate',
            ]);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => trim(($r->code ?? '').' - '.($r->name ?? '')),
                'rate_id' => $r->rate_id,
                'rate' => $r->rate ?? 0,
                'tax_type' => $r->tax_type,
                'is_reverse_charge' => (int)$r->is_reverse_charge,
                'is_exempt' => (int)$r->is_exempt,
                'is_out_of_scope' => (int)$r->is_out_of_scope,
            ])->values(),
        ]);
    }

    public function select2Suppliers(Request $request)
    {
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('suppliers')
            ->where('status', 'active')
            ->when($q !== '', function ($x) use ($q) {
                $x->where('name', 'like', "%{$q}%");
            })
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
                      ->orWhere('phone', 'like', "%{$q}%")
                      ->orWhere('role', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'email', 'phone', 'role']);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => trim($r->name . ($r->role ? ' - '.$r->role : '')),
                'name' => $r->name,
                'email' => $r->email,
                'phone' => $r->phone,
                'role' => $r->role,
            ])->values(),
        ]);
    }

    private function validateRfq(Request $request): array
    {
        $v = Validator::make($request->all(), [
            'requisition_id' => ['nullable', 'integer', 'exists:proc_purchase_requisitions,id'],
            'rfq_date' => ['required', 'date'],
            'closing_date' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'fx_rate' => ['nullable', 'numeric'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.requisition_line_id' => ['nullable', 'integer', 'exists:proc_purchase_requisition_lines,id'],
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.estimated_unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:finance_tax_codes,id'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'lines.*.location_id' => ['nullable', 'integer'],
            'lines.*.store_id' => ['nullable', 'integer'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],

            'suppliers' => ['required', 'array', 'min:1'],
            'suppliers.*.supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'suppliers.*.supplier_contact_id' => ['nullable', 'integer', 'exists:supplier_contacts,id'],
            'suppliers.*.contact_name' => ['nullable', 'string', 'max:255'],
            'suppliers.*.contact_email' => ['nullable', 'email', 'max:255'],
            'suppliers.*.contact_phone' => ['nullable', 'string', 'max:255'],
            'suppliers.*.notes' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $header = [
            'requisition_id' => !empty($v['requisition_id']) ? (int)$v['requisition_id'] : null,
            'rfq_date' => $v['rfq_date'],
            'closing_date' => $v['closing_date'] ?? null,
            'currency_code' => !empty($v['currency_code']) ? strtoupper(trim($v['currency_code'])) : null,
            'fx_rate' => $v['fx_rate'] ?? null,
            'reference' => $v['reference'] ?? null,
            'notes' => $v['notes'] ?? null,
        ];

        $lines = [];
        foreach ($v['lines'] as $ln) {
            $qty = (float)$ln['qty'];
            $unit = (float)$ln['estimated_unit_cost'];
            $rate = isset($ln['tax_rate']) && $ln['tax_rate'] !== '' ? (float)$ln['tax_rate'] : 0.0;

            $base = $qty * $unit;
            $tax = $rate > 0 ? round($base * $rate / 100, 2) : 0.0;
            $tot = round($base + $tax, 2);

            $taxRateId = null;
            if (!empty($ln['tax_code_id'])) {
                $taxCode = DB::table('finance_tax_codes')->where('id', (int)$ln['tax_code_id'])->first(['rate_id']);
                $taxRateId = $taxCode?->rate_id;
            }

            $lines[] = [
                'requisition_line_id' => !empty($ln['requisition_line_id']) ? (int)$ln['requisition_line_id'] : null,
                'product_id' => !empty($ln['product_id']) ? (int)$ln['product_id'] : null,
                'product_variant_id' => null,
                'description' => $ln['description'] ?? null,
                'unit_id' => !empty($ln['unit_id']) ? (int)$ln['unit_id'] : null,
                'qty' => $qty,
                'estimated_unit_cost' => $unit,
                'tax_code_id' => !empty($ln['tax_code_id']) ? (int)$ln['tax_code_id'] : null,
                'tax_rate_id' => $taxRateId,
                'tax_rate' => $rate > 0 ? $rate : null,
                'tax_amount' => $tax,
                'line_total' => $tot,
                'location_id' => !empty($ln['location_id']) ? (int)$ln['location_id'] : null,
                'store_id' => !empty($ln['store_id']) ? (int)$ln['store_id'] : null,
                'memo' => $ln['memo'] ?? null,
            ];
        }

        $supplierIds = [];
        $suppliers = [];

        foreach ($v['suppliers'] as $sp) {
            $supplierId = (int)$sp['supplier_id'];

            if (in_array($supplierId, $supplierIds, true)) {
                continue;
            }
            $supplierIds[] = $supplierId;

            $supplierContactId = !empty($sp['supplier_contact_id']) ? (int)$sp['supplier_contact_id'] : null;
            $contactName = trim((string)($sp['contact_name'] ?? ''));
            $contactEmail = trim((string)($sp['contact_email'] ?? ''));
            $contactPhone = trim((string)($sp['contact_phone'] ?? ''));

            if (!$supplierContactId && $contactName !== '') {
                $existingContact = DB::table('supplier_contacts')
                    ->where('supplier_id', $supplierId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($contactName)])
                    ->when($contactEmail !== '', function ($q) use ($contactEmail) {
                        $q->whereRaw('LOWER(email) = ?', [mb_strtolower($contactEmail)]);
                    })
                    ->first();

                if ($existingContact) {
                    $supplierContactId = $existingContact->id;
                } else {
                    $supplierContactId = DB::table('supplier_contacts')->insertGetId([
                        'supplier_id' => $supplierId,
                        'name' => $contactName,
                        'role' => null,
                        'email' => $contactEmail !== '' ? $contactEmail : null,
                        'phone' => $contactPhone !== '' ? $contactPhone : null,
                        'notes' => 'Auto-created from RFQ',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($supplierContactId) {
                $contact = DB::table('supplier_contacts')
                    ->where('id', $supplierContactId)
                    ->where('supplier_id', $supplierId)
                    ->first();

                if ($contact) {
                    $contactName = $contact->name ?? $contactName;
                    $contactEmail = $contact->email ?? $contactEmail;
                    $contactPhone = $contact->phone ?? $contactPhone;
                }
            }

            $suppliers[] = [
                'supplier_id' => $supplierId,
                'supplier_contact_id' => $supplierContactId,
                'contact_name' => $contactName !== '' ? $contactName : null,
                'contact_email' => $contactEmail !== '' ? $contactEmail : null,
                'contact_phone' => $contactPhone !== '' ? $contactPhone : null,
                'notes' => $sp['notes'] ?? null,
            ];
        }

        return compact('header', 'lines', 'suppliers');
    }

    private function recalcTotals(int $id): void
    {
        $rows = DB::table('proc_request_for_quotation_lines')
            ->where('rfq_id', $id)
            ->get(['tax_amount', 'line_total', 'qty', 'estimated_unit_cost']);

        $subtotal = 0.0;
        $taxTotal = 0.0;
        $grand = 0.0;

        foreach ($rows as $r) {
            $subtotal += ((float)$r->qty * (float)$r->estimated_unit_cost);
            $taxTotal += (float)$r->tax_amount;
            $grand += (float)$r->line_total;
        }

        DB::table('proc_request_for_quotations')
            ->where('id', $id)
            ->update([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total_amount' => round($grand, 2),
                'updated_at' => now(),
            ]);
    }

    private function generateRfqNo(int $companyId): string
    {
        $prefix = 'RFQ-'.date('Ym').'-';

        $last = DB::table('proc_request_for_quotations')
            ->where('company_id', $companyId)
            ->where('rfq_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('rfq_no');

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $next = ((int)$m[1]) + 1;
        }

        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    private function statusBadge(?string $status): string
    {
        return match (strtolower((string)$status)) {
            'sent' => '<span class="badge bg-info">SENT</span>',
            'closed' => '<span class="badge bg-secondary">CLOSED</span>',
            'awarded' => '<span class="badge bg-success">AWARDED</span>',
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