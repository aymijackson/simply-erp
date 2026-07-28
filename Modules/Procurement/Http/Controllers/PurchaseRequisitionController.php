<?php

namespace Modules\Procurement\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseRequisitionController extends Controller
{
    public function index()
    {
        return view('procurement.purchase_requisitions.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $base = DB::table('proc_purchase_requisitions as pr')
            ->leftJoin('users as u', 'u.id', '=', 'pr.requested_by')
            ->where('pr.company_id', $companyId)
            ->whereNull('pr.deleted_at');

        $q = clone $base;

        if ($request->filled('status')) {
            $q->where('pr.status', $request->status);
        }

        if ($request->filled('priority')) {
            $q->where('pr.priority', $request->priority);
        }

        if ($request->filled('date_from')) {
            $q->where('pr.requisition_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->where('pr.requisition_date', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $q->where(function ($x) use ($term) {
                $x->where('pr.requisition_no', 'like', "%{$term}%")
                    ->orWhere('pr.reference', 'like', "%{$term}%")
                    ->orWhere('pr.notes', 'like', "%{$term}%")
                    ->orWhere('u.name', 'like', "%{$term}%");
            });
        }

        $recordsTotal = (clone $base)->count('pr.id');
        $recordsFiltered = (clone $q)->count('pr.id');

        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);

        $orderColIndex = $request->input('order.0.column');
        $orderDir      = $request->input('order.0.dir', 'desc');

        $columns = [
            0 => 'pr.id',
            1 => 'pr.requisition_no',
            2 => 'pr.requisition_date',
            3 => 'pr.needed_by_date',
            4 => 'pr.priority',
            5 => 'pr.status',
            6 => 'u.name',
            7 => 'pr.total_amount',
        ];

        $q->select([
            'pr.id',
            'pr.requisition_no',
            'pr.requisition_date',
            'pr.needed_by_date',
            'pr.priority',
            'pr.status',
            'pr.reference',
            'pr.total_amount',
            'u.name as requested_by_name',
        ]);

        if ($orderColIndex !== null && isset($columns[(int) $orderColIndex])) {
            $q->orderBy($columns[(int) $orderColIndex], $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $q->orderBy('pr.id', 'desc');
        }

        $rows = $q->offset($start)->limit($length)->get();

        $data = $rows->map(function ($r) {
            $json = [
                'id'               => $r->id,
                'requisition_no'   => $r->requisition_no,
                'requisition_date' => $r->requisition_date,
                'needed_by_date'   => $r->needed_by_date,
                'priority'         => $r->priority,
                'status'           => $r->status,
                'reference'        => $r->reference,
            ];

            return [
                'id'               => $r->id,
                'requisition_no'   => e($r->requisition_no ?: ('REQ-' . $r->id)),
                'requisition_date' => e($r->requisition_date),
                'needed_by_date'   => e($r->needed_by_date ?? '—'),
                'priority'         => $this->priorityBadge($r->priority),
                'status'           => $this->statusBadge($r->status),
                'requested_by'     => e($r->requested_by_name ?? '—'),
                'total_amount'     => number_format((float) $r->total_amount, 2),
                'actions'          => view('procurement.purchase_requisitions.partials.actions', ['json' => $json])->render(),
            ];
        })->values();

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function show($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_requisitions')
            ->where('company_id', $companyId)
            ->where('id', (int) $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Requisition not found.'], 404);
        }

        $lines = DB::table('proc_purchase_requisition_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.purchase_requisition_id', $row->id)
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
                    'id'                  => $x->id,
                    'product_id'          => $x->product_id,
                    'product_label'       => $x->product_id ? trim(($x->product_code ? $x->product_code . ' - ' : '') . ($x->product_name ?? ('Product#' . $x->product_id))) : null,
                    'description'         => $x->description,
                    'unit_id'             => $x->unit_id,
                    'unit_label'          => $x->unit_id ? trim(($x->unit_name ?? '') . ($x->unit_symbol ? ' (' . $x->unit_symbol . ')' : '')) : null,
                    'qty'                 => (float) $x->qty,
                    'estimated_unit_cost' => (float) $x->estimated_unit_cost,
                    'tax_code_id'         => $x->tax_code_id,
                    'tax_code_label'      => $x->tax_code_id ? trim(($x->tax_code_code ?? '') . ' - ' . ($x->tax_code_name ?? '')) : null,
                    'tax_rate_id'         => $x->tax_rate_id,
                    'tax_rate'            => $x->tax_rate !== null ? (float) $x->tax_rate : null,
                    'tax_amount'          => (float) $x->tax_amount,
                    'line_total'          => (float) $x->line_total,
                    'location_id'         => $x->location_id,
                    'store_id'            => $x->store_id,
                    'memo'                => $x->memo,
                ];
            })->values();

        return response()->json([
            'requisition' => [
                'id'               => $row->id,
                'requisition_no'   => $row->requisition_no,
                'requisition_date' => $row->requisition_date,
                'needed_by_date'   => $row->needed_by_date,
                'priority'         => $row->priority,
                'status'           => $row->status,
                'reference'        => $row->reference,
                'notes'            => $row->notes,
                'subtotal'         => (float) $row->subtotal,
                'tax_total'        => (float) $row->tax_total,
                'total_amount'     => (float) $row->total_amount,
            ],
            'lines' => $lines,
        ]);
    }

    public function details($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_requisitions as pr')
            ->leftJoin('users as u1', 'u1.id', '=', 'pr.requested_by')
            ->leftJoin('users as u2', 'u2.id', '=', 'pr.approved_by')
            ->where('pr.company_id', $companyId)
            ->where('pr.id', (int) $id)
            ->whereNull('pr.deleted_at')
            ->first([
                'pr.*',
                'u1.name as requested_by_name',
                'u2.name as approved_by_name',
            ]);

        if (!$row) {
            return response()->json(['message' => 'Requisition not found.'], 404);
        }

        $lines = DB::table('proc_purchase_requisition_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.purchase_requisition_id', $row->id)
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

        $this->auditLog(
            'procurement.requisitions',
            'view',
            'PurchaseRequisition',
            (int) $row->id,
            'Purchase Requisition viewed',
            ['requisition_no' => $row->requisition_no]
        );

        return response()->json([
            'header' => [
                'id'               => $row->id,
                'requisition_no'   => $row->requisition_no,
                'requisition_date' => $row->requisition_date,
                'needed_by_date'   => $row->needed_by_date,
                'priority'         => $row->priority,
                'status'           => $row->status,
                'reference'        => $row->reference,
                'notes'            => $row->notes,
                'subtotal'         => (float) $row->subtotal,
                'tax_total'        => (float) $row->tax_total,
                'total_amount'     => (float) $row->total_amount,
                'requested_by'     => $row->requested_by_name,
                'approved_by'      => $row->approved_by_name,
                'approved_at'      => $row->approved_at,
                'created_at'       => $row->created_at,
            ],
            'lines' => $lines,
        ]);
    }

    public function pdf($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_requisitions as pr')
            ->leftJoin('users as u1', 'u1.id', '=', 'pr.requested_by')
            ->leftJoin('users as u2', 'u2.id', '=', 'pr.approved_by')
            ->where('pr.company_id', $companyId)
            ->where('pr.id', (int) $id)
            ->whereNull('pr.deleted_at')
            ->first([
                'pr.*',
                'u1.name as requested_by_name',
                'u2.name as approved_by_name',
            ]);

        abort_if(!$row, 404, 'Requisition not found.');

        $lines = DB::table('proc_purchase_requisition_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.purchase_requisition_id', $row->id)
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

        $pdf = Pdf::loadView('procurement.purchase_requisitions.pdf', [
            'requisition' => $row,
            'lines'       => $lines,
        ])->setPaper('a4', 'portrait');

        $this->auditLog(
            'procurement.requisitions',
            'download_pdf',
            'PurchaseRequisition',
            (int) $row->id,
            'Purchase Requisition PDF downloaded',
            ['requisition_no' => $row->requisition_no]
        );

        return $pdf->stream(($row->requisition_no ?: 'requisition-' . $row->id) . '.pdf');
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $userId    = auth()->id();

        $data = $this->validateRequisition($request);

        return DB::transaction(function () use ($companyId, $userId, $data) {
            $id = DB::table('proc_purchase_requisitions')->insertGetId([
                'company_id'       => $companyId,
                'requisition_no'   => $this->generateReqNo($companyId),
                'requisition_date' => $data['header']['requisition_date'],
                'needed_by_date'   => $data['header']['needed_by_date'],
                'department_id'    => null,
                'requested_by'     => $userId,
                'priority'         => $data['header']['priority'],
                'status'           => 'draft',
                'reference'        => $data['header']['reference'],
                'notes'            => $data['header']['notes'],
                'subtotal'         => 0,
                'tax_total'        => 0,
                'total_amount'     => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            if (!empty($data['lines'])) {
                $rows = array_map(function ($ln) use ($id) {
                    $ln['purchase_requisition_id'] = $id;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);

                DB::table('proc_purchase_requisition_lines')->insert($rows);
            }

            $this->recalcTotals($id);

            $this->auditLog(
                'procurement.requisitions',
                'create',
                'PurchaseRequisition',
                $id,
                'Purchase Requisition created',
                ['line_count' => count($data['lines'])]
            );

            return response()->json([
                'message' => 'Purchase Requisition created.',
                'id'      => $id,
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_requisitions')
            ->where('company_id', $companyId)
            ->where('id', (int) $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Requisition not found.'], 404);
        }

        if (!in_array($row->status, ['draft', 'rejected'])) {
            return response()->json(['message' => 'Only draft or rejected requisitions can be edited.'], 422);
        }

        $data = $this->validateRequisition($request);

        return DB::transaction(function () use ($id, $row, $data) {
            DB::table('proc_purchase_requisitions')
                ->where('id', (int) $id)
                ->update([
                    'requisition_date' => $data['header']['requisition_date'],
                    'needed_by_date'   => $data['header']['needed_by_date'],
                    'priority'         => $data['header']['priority'],
                    'reference'        => $data['header']['reference'],
                    'notes'            => $data['header']['notes'],
                    'updated_at'       => now(),
                ]);

            DB::table('proc_purchase_requisition_lines')
                ->where('purchase_requisition_id', (int) $id)
                ->delete();

            if (!empty($data['lines'])) {
                $rows = array_map(function ($ln) use ($id) {
                    $ln['purchase_requisition_id'] = $id;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);

                DB::table('proc_purchase_requisition_lines')->insert($rows);
            }

            $this->recalcTotals((int) $id);

            $this->auditLog(
                'procurement.requisitions',
                'update',
                'PurchaseRequisition',
                (int) $id,
                'Purchase Requisition updated',
                [
                    'old_status' => $row->status,
                    'line_count' => count($data['lines']),
                ]
            );

            return response()->json(['message' => 'Purchase Requisition updated.']);
        });
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_requisitions')
            ->where('company_id', $companyId)
            ->where('id', (int) $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Requisition not found.'], 404);
        }

        if ($row->status !== 'draft') {
            return response()->json(['message' => 'Only draft requisitions can be deleted.'], 422);
        }

        DB::table('proc_purchase_requisitions')
            ->where('id', (int) $id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        $this->auditLog(
            'procurement.requisitions',
            'delete',
            'PurchaseRequisition',
            (int) $id,
            'Purchase Requisition deleted',
            ['requisition_no' => $row->requisition_no]
        );

        return response()->json(['message' => 'Purchase Requisition deleted.']);
    }

    public function submit($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_requisitions')
            ->where('company_id', $companyId)
            ->where('id', (int) $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Requisition not found.'], 404);
        }

        if ($row->status !== 'draft') {
            return response()->json(['message' => 'Only draft requisitions can be submitted.'], 422);
        }

        DB::table('proc_purchase_requisitions')
            ->where('id', (int) $id)
            ->update([
                'status'     => 'submitted',
                'updated_at' => now(),
            ]);

        $this->auditLog(
            'procurement.requisitions',
            'submit',
            'PurchaseRequisition',
            (int) $id,
            'Purchase Requisition submitted',
            ['old_status' => 'draft', 'new_status' => 'submitted']
        );

        return response()->json(['message' => 'Purchase Requisition submitted.']);
    }

    public function approve($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_requisitions')
            ->where('company_id', $companyId)
            ->where('id', (int) $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Requisition not found.'], 404);
        }

        if ($row->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted requisitions can be approved.'], 422);
        }

        DB::table('proc_purchase_requisitions')
            ->where('id', (int) $id)
            ->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'updated_at'  => now(),
            ]);

        $this->auditLog(
            'procurement.requisitions',
            'approve',
            'PurchaseRequisition',
            (int) $id,
            'Purchase Requisition approved',
            ['old_status' => 'submitted', 'new_status' => 'approved']
        );

        return response()->json(['message' => 'Purchase Requisition approved.']);
    }

    public function reject($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_purchase_requisitions')
            ->where('company_id', $companyId)
            ->where('id', (int) $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Requisition not found.'], 404);
        }

        if ($row->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted requisitions can be rejected.'], 422);
        }

        DB::table('proc_purchase_requisitions')
            ->where('id', (int) $id)
            ->update([
                'status'      => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'updated_at'  => now(),
            ]);

        $this->auditLog(
            'procurement.requisitions',
            'reject',
            'PurchaseRequisition',
            (int) $id,
            'Purchase Requisition rejected',
            ['old_status' => 'submitted', 'new_status' => 'rejected']
        );

        return response()->json(['message' => 'Purchase Requisition rejected.']);
    }

    public function select2Products(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

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
            'results' => $rows->map(fn ($r) => [
                'id'   => $r->id,
                'text' => trim(($r->product_code ? $r->product_code . ' - ' : '') . $r->product_name),
            ])->values(),
        ]);
    }

    public function select2Units(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('units')
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'symbol']);

        return response()->json([
            'results' => $rows->map(fn ($r) => [
                'id'   => $r->id,
                'text' => trim($r->name . ($r->symbol ? ' (' . $r->symbol . ')' : '')),
            ])->values(),
        ]);
    }

    public function select2TaxCodes(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));

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
            'results' => $rows->map(fn ($r) => [
                'id'                => $r->id,
                'text'              => trim(($r->code ?? '') . ' - ' . ($r->name ?? '')),
                'rate_id'           => $r->rate_id,
                'rate'              => $r->rate ?? 0,
                'tax_type'          => $r->tax_type,
                'is_reverse_charge' => (int) $r->is_reverse_charge,
                'is_exempt'         => (int) $r->is_exempt,
                'is_out_of_scope'   => (int) $r->is_out_of_scope,
            ])->values(),
        ]);
    }

    public function select2Locations(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('locations')
            ->where('company_id', $companyId)
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']);

        return response()->json([
            'results' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'text' => $r->name,
            ])->values(),
        ]);
    }

    public function select2Stores(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('location_stores')
            ->where('company_id', $companyId)
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']);

        return response()->json([
            'results' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'text' => $r->name,
            ])->values(),
        ]);
    }

    private function validateRequisition(Request $request): array
    {
        $v = Validator::make($request->all(), [
            'requisition_date' => ['required', 'date'],
            'needed_by_date'   => ['nullable', 'date'],
            'priority'         => ['required', 'in:low,normal,high,urgent'],
            'reference'        => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string'],

            'lines'                        => ['required', 'array', 'min:1'],
            'lines.*.product_id'          => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description'         => ['nullable', 'string', 'max:255'],
            'lines.*.unit_id'             => ['nullable', 'integer', 'exists:units,id'],
            'lines.*.qty'                 => ['required', 'numeric', 'min:0.0001'],
            'lines.*.estimated_unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id'         => ['nullable', 'integer', 'exists:finance_tax_codes,id'],
            'lines.*.tax_rate'            => ['nullable', 'numeric', 'min:0'],
            'lines.*.location_id'         => ['nullable', 'integer', 'exists:locations,id'],
            'lines.*.store_id'            => ['nullable', 'integer', 'exists:location_stores,id'],
            'lines.*.memo'                => ['nullable', 'string', 'max:255'],
        ])->validate();

        $header = [
            'requisition_date' => $v['requisition_date'],
            'needed_by_date'   => $v['needed_by_date'] ?? null,
            'priority'         => $v['priority'],
            'reference'        => $v['reference'] ?? null,
            'notes'            => $v['notes'] ?? null,
        ];

        $lines = [];
        foreach ($v['lines'] as $ln) {
            $qty  = (float) $ln['qty'];
            $unit = (float) $ln['estimated_unit_cost'];
            $rate = isset($ln['tax_rate']) && $ln['tax_rate'] !== '' ? (float) $ln['tax_rate'] : 0.0;

            $base = $qty * $unit;
            $tax  = $rate > 0 ? round($base * $rate / 100, 2) : 0.0;
            $tot  = round($base + $tax, 2);

            $taxRateId = null;
            if (!empty($ln['tax_code_id'])) {
                $taxCode = DB::table('finance_tax_codes')
                    ->where('id', (int) $ln['tax_code_id'])
                    ->first(['rate_id']);
                $taxRateId = $taxCode?->rate_id;
            }

            $lines[] = [
                'product_id'           => !empty($ln['product_id']) ? (int) $ln['product_id'] : null,
                'product_variant_id'   => null,
                'description'          => $ln['description'] ?? null,
                'unit_id'              => !empty($ln['unit_id']) ? (int) $ln['unit_id'] : null,
                'qty'                  => $qty,
                'estimated_unit_cost'  => $unit,
                'tax_code_id'          => !empty($ln['tax_code_id']) ? (int) $ln['tax_code_id'] : null,
                'tax_rate_id'          => $taxRateId,
                'tax_rate'             => $rate > 0 ? $rate : null,
                'tax_amount'           => $tax,
                'line_total'           => $tot,
                'location_id'          => !empty($ln['location_id']) ? (int) $ln['location_id'] : null,
                'store_id'             => !empty($ln['store_id']) ? (int) $ln['store_id'] : null,
                'memo'                 => $ln['memo'] ?? null,
            ];
        }

        return compact('header', 'lines');
    }

    private function recalcTotals(int $id): void
    {
        $rows = DB::table('proc_purchase_requisition_lines')
            ->where('purchase_requisition_id', $id)
            ->get(['qty', 'estimated_unit_cost', 'tax_amount', 'line_total']);

        $subtotal = 0.0;
        $taxTotal = 0.0;
        $grand    = 0.0;

        foreach ($rows as $r) {
            $subtotal += ((float) $r->qty * (float) $r->estimated_unit_cost);
            $taxTotal += (float) $r->tax_amount;
            $grand    += (float) $r->line_total;
        }

        DB::table('proc_purchase_requisitions')
            ->where('id', $id)
            ->update([
                'subtotal'     => round($subtotal, 2),
                'tax_total'    => round($taxTotal, 2),
                'total_amount' => round($grand, 2),
                'updated_at'   => now(),
            ]);
    }

    private function generateReqNo(int $companyId): string
    {
        $prefix = 'REQ-' . date('Ym') . '-';

        $last = DB::table('proc_purchase_requisitions')
            ->where('company_id', $companyId)
            ->where('requisition_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('requisition_no');

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function statusBadge(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'approved'  => '<span class="badge bg-success">APPROVED</span>',
            'submitted' => '<span class="badge bg-info">SUBMITTED</span>',
            'rejected'  => '<span class="badge bg-danger">REJECTED</span>',
            'cancelled' => '<span class="badge bg-secondary">CANCELLED</span>',
            'converted' => '<span class="badge bg-primary">CONVERTED</span>',
            default     => '<span class="badge bg-warning">DRAFT</span>',
        };
    }

    private function priorityBadge(?string $priority): string
    {
        $priority = strtolower((string) $priority);

        return match ($priority) {
            'urgent' => '<span class="badge bg-danger">URGENT</span>',
            'high'   => '<span class="badge bg-warning text-dark">HIGH</span>',
            'low'    => '<span class="badge bg-secondary">LOW</span>',
            default  => '<span class="badge bg-info text-dark">NORMAL</span>',
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
            'user_id'      => auth()->id(),
            'module'       => $module,
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'route'        => request()->route() ? request()->route()->getName() : null,
            'url'          => request()->fullUrl(),
            'method'       => request()->method(),
            'ip'           => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'meta'         => !empty($meta) ? json_encode($meta) : null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}