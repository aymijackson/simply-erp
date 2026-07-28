<?php

namespace Modules\Procurement\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SupplierQuotationController extends Controller
{
    public function index()
    {
        return view('procurement.supplier_quotations.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $base = DB::table('proc_supplier_quotations as sq')
            ->leftJoin('proc_request_for_quotations as rfq', 'rfq.id', '=', 'sq.rfq_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'sq.supplier_id')
            ->where('sq.company_id', $companyId)
            ->whereNull('sq.deleted_at');

        $q = clone $base;

        if ($request->filled('status')) {
            $q->where('sq.status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $q->where('sq.supplier_id', (int)$request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $q->where('sq.quotation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->where('sq.quotation_date', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $term = trim((string)$request->q);
            $q->where(function ($x) use ($term) {
                $x->where('sq.quotation_no', 'like', "%{$term}%")
                  ->orWhere('sq.supplier_quote_no', 'like', "%{$term}%")
                  ->orWhere('sq.reference', 'like', "%{$term}%")
                  ->orWhere('rfq.rfq_no', 'like', "%{$term}%")
                  ->orWhere('s.name', 'like', "%{$term}%");
            });
        }

        $recordsTotal = (clone $base)->count('sq.id');
        $recordsFiltered = (clone $q)->count('sq.id');

        $start = (int)($request->start ?? 0);
        $length = (int)($request->length ?? 10);
        $draw = (int)($request->draw ?? 1);

        $columns = [
            0 => 'sq.id',
            1 => 'sq.quotation_no',
            2 => 'sq.quotation_date',
            3 => 'rfq.rfq_no',
            4 => 's.name',
            5 => 'sq.status',
            6 => 'sq.total_amount',
        ];

        $orderColIndex = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'desc');

        $q->select([
            'sq.id',
            'sq.quotation_no',
            'sq.supplier_quote_no',
            'sq.quotation_date',
            'sq.valid_until',
            'sq.status',
            'sq.total_amount',
            'sq.reference',
            'rfq.rfq_no',
            's.name as supplier_name',
        ]);

        if ($orderColIndex !== null && isset($columns[(int)$orderColIndex])) {
            $q->orderBy($columns[(int)$orderColIndex], $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $q->orderBy('sq.id', 'desc');
        }

        $rows = $q->offset($start)->limit($length)->get();

        $data = $rows->map(function ($r) {
            $json = [
                'id' => $r->id,
                'quotation_no' => $r->quotation_no,
                'status' => $r->status,
            ];

            return [
                'id' => $r->id,
                'quotation_no' => e($r->quotation_no),
                'quotation_date' => e(date('d-m-Y', strtotime($r->quotation_date))),
                'rfq_no' => e($r->rfq_no ?? '—'),
                'supplier' => e($r->supplier_name ?? '—'),
                'valid_until' => e(date('d-m-Y', strtotime($r->valid_until ?? '—'))),
                'status' => $this->statusBadge($r->status),
                'total_amount' => number_format((float)$r->total_amount, 2),
                'actions' => view('procurement.supplier_quotations.partials.actions', compact('json'))->render(),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function createFromRfq($rfqId, Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $supplierId = (int)$request->get('supplier_id');

        $rfq = DB::table('proc_request_for_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$rfqId)
            ->whereNull('deleted_at')
            ->first();

        if (!$rfq) {
            return response()->json(['message' => 'RFQ not found.'], 404);
        }

        if (!$supplierId) {
            return response()->json(['message' => 'Supplier is required.'], 422);
        }

        $rfqSupplier = DB::table('proc_request_for_quotation_suppliers')
            ->where('rfq_id', $rfq->id)
            ->where('supplier_id', $supplierId)
            ->first();

        if (!$rfqSupplier) {
            return response()->json(['message' => 'Selected supplier is not on this RFQ.'], 422);
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
                    'rfq_line_id' => $x->id,
                    'product_id' => $x->product_id,
                    'product_label' => $x->product_id ? trim(($x->product_code ? $x->product_code.' - ' : '').($x->product_name ?? '')) : null,
                    'description' => $x->description,
                    'unit_id' => $x->unit_id,
                    'unit_label' => $x->unit_id ? trim(($x->unit_name ?? '').($x->unit_symbol ? ' ('.$x->unit_symbol.')' : '')) : null,
                    'qty' => (float)$x->qty,
                    'unit_price' => (float)$x->estimated_unit_cost,
                    'tax_code_id' => $x->tax_code_id,
                    'tax_code_label' => $x->tax_code_id ? trim(($x->tax_code_code ?? '').' - '.($x->tax_code_name ?? '')) : null,
                    'tax_rate_id' => $x->tax_rate_id,
                    'tax_rate' => $x->tax_rate !== null ? (float)$x->tax_rate : null,
                    'discount_percent' => null,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => 0,
                    'lead_time_days' => null,
                    'remarks' => null,
                ];
            })->values();

        return response()->json([
            'header' => [
                'rfq_id' => $rfq->id,
                'rfq_no' => $rfq->rfq_no,
                'rfq_supplier_id' => $rfqSupplier->id,
                'supplier_id' => $supplierId,
                'quotation_date' => date('d-m-Y'),
                'valid_until' => null,
                'currency_code' => $rfq->currency_code,
                'fx_rate' => $rfq->fx_rate,
                'reference' => $rfq->reference,
                'notes' => $rfq->notes,
            ],
            'lines' => $lines,
        ]);
    }

    public function show($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_supplier_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Supplier quotation not found.'], 404);
        }

        $lines = DB::table('proc_supplier_quotation_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.supplier_quotation_id', $row->id)
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
                    'line_total' => (float)$x->line_total,
                    'lead_time_days' => $x->lead_time_days,
                    'remarks' => $x->remarks,
                ];
            })->values();

        return response()->json([
            'quotation' => [
                'id' => $row->id,
                'rfq_id' => $row->rfq_id,
                'rfq_supplier_id' => $row->rfq_supplier_id,
                'supplier_id' => $row->supplier_id,
                'quotation_no' => $row->quotation_no,
                'supplier_quote_no' => $row->supplier_quote_no,
                'quotation_date' => $row->quotation_date,
                'valid_until' => $row->valid_until,
                'currency_code' => $row->currency_code,
                'fx_rate' => $row->fx_rate,
                'reference' => $row->reference,
                'notes' => $row->notes,
                'status' => $row->status,
                'subtotal' => (float)$row->subtotal,
                'tax_total' => (float)$row->tax_total,
                'discount_total' => (float)$row->discount_total,
                'total_amount' => (float)$row->total_amount,
            ],
            'lines' => $lines,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateQuotation($request);

        return DB::transaction(function () use ($companyId, $data) {
            $id = DB::table('proc_supplier_quotations')->insertGetId([
                'company_id' => $companyId,
                'rfq_id' => $data['header']['rfq_id'],
                'rfq_supplier_id' => $data['header']['rfq_supplier_id'],
                'supplier_id' => $data['header']['supplier_id'],
                'quotation_no' => $this->generateQuotationNo($companyId),
                'supplier_quote_no' => $data['header']['supplier_quote_no'],
                'quotation_date' => $data['header']['quotation_date'],
                'valid_until' => $data['header']['valid_until'],
                'currency_code' => $data['header']['currency_code'],
                'fx_rate' => $data['header']['fx_rate'],
                'reference' => $data['header']['reference'],
                'notes' => $data['header']['notes'],
                'status' => 'draft',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'total_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($data['lines'])) {
                $rows = array_map(function ($ln) use ($id) {
                    $ln['supplier_quotation_id'] = $id;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);

                DB::table('proc_supplier_quotation_lines')->insert($rows);
            }

            $this->recalcTotals($id);
            $this->auditLog('procurement.supplier_quotations', 'create', 'SupplierQuotation', $id, 'Supplier quotation created');

            return response()->json([
                'message' => 'Supplier quotation created.',
                'id' => $id,
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_supplier_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Supplier quotation not found.'], 404);
        }

        if (!in_array($row->status, ['draft', 'rejected'])) {
            return response()->json(['message' => 'Only draft or rejected quotations can be edited.'], 422);
        }

        $data = $this->validateQuotation($request);

        return DB::transaction(function () use ($id, $data) {
            DB::table('proc_supplier_quotations')
                ->where('id', (int)$id)
                ->update([
                    'rfq_id' => $data['header']['rfq_id'],
                    'rfq_supplier_id' => $data['header']['rfq_supplier_id'],
                    'supplier_id' => $data['header']['supplier_id'],
                    'supplier_quote_no' => $data['header']['supplier_quote_no'],
                    'quotation_date' => $data['header']['quotation_date'],
                    'valid_until' => $data['header']['valid_until'],
                    'currency_code' => $data['header']['currency_code'],
                    'fx_rate' => $data['header']['fx_rate'],
                    'reference' => $data['header']['reference'],
                    'notes' => $data['header']['notes'],
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            DB::table('proc_supplier_quotation_lines')
                ->where('supplier_quotation_id', (int)$id)
                ->delete();

            if (!empty($data['lines'])) {
                $rows = array_map(function ($ln) use ($id) {
                    $ln['supplier_quotation_id'] = $id;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);

                DB::table('proc_supplier_quotation_lines')->insert($rows);
            }

            $this->recalcTotals((int)$id);
            $this->auditLog('procurement.supplier_quotations', 'update', 'SupplierQuotation', (int)$id, 'Supplier quotation updated');

            return response()->json(['message' => 'Supplier quotation updated.']);
        });
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_supplier_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Supplier quotation not found.'], 404);
        }

        if ($row->status !== 'draft') {
            return response()->json(['message' => 'Only draft quotations can be deleted.'], 422);
        }

        DB::table('proc_supplier_quotations')
            ->where('id', (int)$id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        $this->auditLog('procurement.supplier_quotations', 'delete', 'SupplierQuotation', (int)$id, 'Supplier quotation deleted');

        return response()->json(['message' => 'Supplier quotation deleted.']);
    }

    public function submit($id)
    {
        return $this->changeStatus($id, 'draft', 'submitted', 'submitted_at', 'submitted_by', 'Supplier quotation submitted');
    }

    public function review($id)
    {
        return $this->changeStatus($id, 'submitted', 'reviewed', 'reviewed_at', 'reviewed_by', 'Supplier quotation reviewed');
    }

    public function accept($id)
    {
        return $this->changeStatus($id, 'reviewed', 'accepted', 'accepted_at', 'accepted_by', 'Supplier quotation accepted');
    }

    public function reject($id)
    {
        return $this->changeStatus($id, 'reviewed', 'rejected', 'rejected_at', 'rejected_by', 'Supplier quotation rejected');
    }

    protected function changeStatus($id, $from, $to, $stampField, $userField, $desc)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('proc_supplier_quotations')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Supplier quotation not found.'], 404);
        }

        if ($row->status !== $from) {
            return response()->json(['message' => "Only {$from} quotations can be moved to {$to}."], 422);
        }

        DB::table('proc_supplier_quotations')
            ->where('id', (int)$id)
            ->update([
                'status' => $to,
                $stampField => now(),
                $userField => auth()->id(),
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        $this->auditLog('procurement.supplier_quotations', $to, 'SupplierQuotation', (int)$id, $desc);

        return response()->json(['message' => ucfirst($to).' successfully.']);
    }

    public function select2Rfqs(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q', ''));
    
        $allowAwarded = $this->getBoolSetting(
            'procurement.rfq_allow_awarded_for_supplier_quotation',
            false
        );
    
        $allowCancelled = $this->getBoolSetting(
            'procurement.rfq_allow_cancelled_for_supplier_quotation',
            false
        );
    
        $allowedStatuses = ['draft', 'sent', 'closed'];
    
        if ($allowAwarded) {
            $allowedStatuses[] = 'awarded';
        }
    
        if ($allowCancelled) {
            $allowedStatuses[] = 'cancelled';
        }
    
        $rows = DB::table('proc_request_for_quotations')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereIn('status', $allowedStatuses)
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('rfq_no', 'like', "%{$q}%")
                      ->orWhere('reference', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get(['id', 'rfq_no', 'reference', 'status']);
    
        return response()->json([
            'results' => $rows->map(function ($r) {
                return [
                    'id' => $r->id,
                    'text' => trim(
                        ($r->rfq_no ?? 'RFQ-'.$r->id)
                        . ($r->reference ? ' - '.$r->reference : '')
                        . ($r->status ? ' ['.strtoupper($r->status).']' : '')
                    ),
                ];
            })->values(),
        ]);
    }
    
    public function select2RfqSuppliers(Request $request)
    {
        $rfqId = (int)$request->get('rfq_id');
        $q = trim((string)$request->get('q', ''));

        if (!$rfqId) {
            return response()->json(['results' => []]);
        }

        $rows = DB::table('proc_request_for_quotation_suppliers as rs')
            ->join('suppliers as s', 's.id', '=', 'rs.supplier_id')
            ->where('rs.rfq_id', $rfqId)
            ->when($q !== '', function ($x) use ($q) {
                $x->where('s.name', 'like', "%{$q}%");
            })
            ->orderBy('s.name')
            ->limit(30)
            ->get([
                'rs.id',
                'rs.supplier_id',
                's.name as supplier_name',
            ]);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => $r->supplier_name,
                'supplier_id' => $r->supplier_id,
                'supplier_name' => $r->supplier_name,
            ])->values()
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

    private function validateQuotation(Request $request): array
    {
        $v = Validator::make($request->all(), [
            'rfq_id' => ['required','integer','exists:proc_request_for_quotations,id'],
            'rfq_supplier_id' => ['required','integer','exists:proc_request_for_quotation_suppliers,id'],
            'supplier_id' => ['required','integer','exists:suppliers,id'],
            'supplier_quote_no' => ['nullable','string','max:100'],
            'quotation_date' => ['required','date'],
            'valid_until' => ['nullable','date'],
            'currency_code' => ['nullable','string','size:3'],
            'fx_rate' => ['nullable','numeric'],
            'reference' => ['nullable','string','max:100'],
            'notes' => ['nullable','string'],

            'lines' => ['required','array','min:1'],
            'lines.*.rfq_line_id' => ['nullable','integer','exists:proc_request_for_quotation_lines,id'],
            'lines.*.product_id' => ['nullable','integer'],
            'lines.*.description' => ['nullable','string','max:255'],
            'lines.*.unit_id' => ['nullable','integer'],
            'lines.*.qty' => ['required','numeric','min:0.0001'],
            'lines.*.unit_price' => ['required','numeric','min:0'],
            'lines.*.discount_percent' => ['nullable','numeric','min:0'],
            'lines.*.tax_code_id' => ['nullable','integer'],
            'lines.*.tax_rate' => ['nullable','numeric','min:0'],
            'lines.*.lead_time_days' => ['nullable','integer','min:0'],
            'lines.*.remarks' => ['nullable','string','max:255'],
        ])->validate();

        $header = [
            'rfq_id' => (int)$v['rfq_id'],
            'rfq_supplier_id' => (int)$v['rfq_supplier_id'],
            'supplier_id' => (int)$v['supplier_id'],
            'supplier_quote_no' => $v['supplier_quote_no'] ?? null,
            'quotation_date' => $v['quotation_date'],
            'valid_until' => $v['valid_until'] ?? null,
            'currency_code' => !empty($v['currency_code']) ? strtoupper(trim($v['currency_code'])) : null,
            'fx_rate' => $v['fx_rate'] ?? null,
            'reference' => $v['reference'] ?? null,
            'notes' => $v['notes'] ?? null,
        ];

        $lines = [];
        foreach ($v['lines'] as $ln) {
            $qty = (float)$ln['qty'];
            $unitPrice = (float)$ln['unit_price'];
            $discountPercent = isset($ln['discount_percent']) && $ln['discount_percent'] !== '' ? (float)$ln['discount_percent'] : 0.0;
            $rate = isset($ln['tax_rate']) && $ln['tax_rate'] !== '' ? (float)$ln['tax_rate'] : 0.0;

            $gross = $qty * $unitPrice;
            $discountAmount = $discountPercent > 0 ? round($gross * $discountPercent / 100, 2) : 0.0;
            $taxBase = $gross - $discountAmount;
            $taxAmount = $rate > 0 ? round($taxBase * $rate / 100, 2) : 0.0;
            $lineTotal = round($taxBase + $taxAmount, 2);

            $taxRateId = null;
            if (!empty($ln['tax_code_id'])) {
                $taxCode = DB::table('finance_tax_codes')->where('id', (int)$ln['tax_code_id'])->first(['rate_id']);
                $taxRateId = $taxCode?->rate_id;
            }

            $lines[] = [
                'rfq_line_id' => !empty($ln['rfq_line_id']) ? (int)$ln['rfq_line_id'] : null,
                'product_id' => !empty($ln['product_id']) ? (int)$ln['product_id'] : null,
                'product_variant_id' => null,
                'description' => $ln['description'] ?? null,
                'unit_id' => !empty($ln['unit_id']) ? (int)$ln['unit_id'] : null,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent > 0 ? $discountPercent : null,
                'discount_amount' => $discountAmount,
                'tax_code_id' => !empty($ln['tax_code_id']) ? (int)$ln['tax_code_id'] : null,
                'tax_rate_id' => $taxRateId,
                'tax_rate' => $rate > 0 ? $rate : null,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'lead_time_days' => !empty($ln['lead_time_days']) ? (int)$ln['lead_time_days'] : null,
                'remarks' => $ln['remarks'] ?? null,
            ];
        }

        return compact('header', 'lines');
    }

    private function recalcTotals(int $id): void
    {
        $rows = DB::table('proc_supplier_quotation_lines')
            ->where('supplier_quotation_id', $id)
            ->get(['qty','unit_price','discount_amount','tax_amount','line_total']);

        $subtotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;
        $grand = 0.0;

        foreach ($rows as $r) {
            $subtotal += ((float)$r->qty * (float)$r->unit_price);
            $discountTotal += (float)$r->discount_amount;
            $taxTotal += (float)$r->tax_amount;
            $grand += (float)$r->line_total;
        }

        DB::table('proc_supplier_quotations')
            ->where('id', $id)
            ->update([
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discountTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total_amount' => round($grand, 2),
                'updated_at' => now(),
            ]);
    }

    private function generateQuotationNo(int $companyId): string
    {
        $prefix = 'SQ-'.date('Ym').'-';

        $last = DB::table('proc_supplier_quotations')
            ->where('company_id', $companyId)
            ->where('quotation_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('quotation_no');

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $next = ((int)$m[1]) + 1;
        }

        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    private function statusBadge(?string $status): string
    {
        return match (strtolower((string)$status)) {
            'submitted' => '<span class="badge bg-info">SUBMITTED</span>',
            'reviewed' => '<span class="badge bg-primary">REVIEWED</span>',
            'accepted' => '<span class="badge bg-success">ACCEPTED</span>',
            'rejected' => '<span class="badge bg-danger">REJECTED</span>',
            'cancelled' => '<span class="badge bg-secondary">CANCELLED</span>',
            default => '<span class="badge bg-warning text-dark">DRAFT</span>',
        };
    }

    private function auditLog(string $module, string $action, ?string $subjectType = null, $subjectId = null, ?string $description = null): void
    {
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
            'meta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    private function getBoolSetting(string $key, bool $default = false): bool
    {
        $row = DB::table('settings')
            ->where('key', $key)
            ->where('is_active', 1)
            ->first(['value', 'value_type']);
    
        if (!$row) {
            return $default;
        }
    
        $value = is_string($row->value) ? strtolower(trim($row->value)) : $row->value;
    
        return in_array((string)$value, ['1', 'true', 'yes', 'on'], true);
    }
    
    public function details($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $row = DB::table('proc_supplier_quotations as sq')
            ->leftJoin('proc_request_for_quotations as rfq', 'rfq.id', '=', 'sq.rfq_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'sq.supplier_id')
            ->leftJoin('users as u1', 'u1.id', '=', 'sq.submitted_by')
            ->leftJoin('users as u2', 'u2.id', '=', 'sq.reviewed_by')
            ->leftJoin('users as u3', 'u3.id', '=', 'sq.accepted_by')
            ->leftJoin('users as u4', 'u4.id', '=', 'sq.rejected_by')
            ->where('sq.company_id', $companyId)
            ->where('sq.id', (int)$id)
            ->whereNull('sq.deleted_at')
            ->first([
                'sq.*',
                'rfq.rfq_no',
                's.name as supplier_name',
                'u1.name as submitted_by_name',
                'u2.name as reviewed_by_name',
                'u3.name as accepted_by_name',
                'u4.name as rejected_by_name',
            ]);
    
        if (!$row) {
            return response()->json(['message' => 'Supplier quotation not found.'], 404);
        }
    
        $lines = DB::table('proc_supplier_quotation_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.supplier_quotation_id', $row->id)
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
                'id'                => $row->id,
                'quotation_no'      => $row->quotation_no,
                'supplier_quote_no' => $row->supplier_quote_no,
                'quotation_date'    => $row->quotation_date,
                'valid_until'       => $row->valid_until,
                'rfq_id'            => $row->rfq_id,
                'rfq_no'            => $row->rfq_no,
                'supplier_id'       => $row->supplier_id,
                'supplier'          => $row->supplier_name,
                'currency_code'     => $row->currency_code,
                'fx_rate'           => $row->fx_rate !== null ? (float)$row->fx_rate : null,
                'reference'         => $row->reference,
                'notes'             => $row->notes,
                'status'            => $row->status,
                'subtotal'          => (float)$row->subtotal,
                'discount_total'    => (float)$row->discount_total,
                'tax_total'         => (float)$row->tax_total,
                'total_amount'      => (float)$row->total_amount,
                'submitted_at'      => $row->submitted_at,
                'submitted_by'      => $row->submitted_by_name,
                'reviewed_at'       => $row->reviewed_at,
                'reviewed_by'       => $row->reviewed_by_name,
                'accepted_at'       => $row->accepted_at,
                'accepted_by'       => $row->accepted_by_name,
                'rejected_at'       => $row->rejected_at,
                'rejected_by'       => $row->rejected_by_name,
                'created_at'        => $row->created_at,
                'updated_at'        => $row->updated_at,
            ],
            'lines' => $lines,
        ]);
    }
    
    public function pdf($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $row = DB::table('proc_supplier_quotations as sq')
            ->leftJoin('proc_request_for_quotations as rfq', 'rfq.id', '=', 'sq.rfq_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'sq.supplier_id')
            ->leftJoin('users as u1', 'u1.id', '=', 'sq.submitted_by')
            ->leftJoin('users as u2', 'u2.id', '=', 'sq.reviewed_by')
            ->leftJoin('users as u3', 'u3.id', '=', 'sq.accepted_by')
            ->leftJoin('users as u4', 'u4.id', '=', 'sq.rejected_by')
            ->where('sq.company_id', $companyId)
            ->where('sq.id', (int)$id)
            ->whereNull('sq.deleted_at')
            ->first([
                'sq.*',
                'rfq.rfq_no',
                's.name as supplier_name',
                'u1.name as submitted_by_name',
                'u2.name as reviewed_by_name',
                'u3.name as accepted_by_name',
                'u4.name as rejected_by_name',
            ]);
    
        abort_if(!$row, 404, 'Supplier quotation not found.');
    
        $lines = DB::table('proc_supplier_quotation_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->where('l.supplier_quotation_id', $row->id)
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
    
        $pdf = Pdf::loadView('procurement.supplier_quotations.pdf', [
            'quotation' => $row,
            'lines'     => $lines,
        ])->setPaper('a4', 'portrait');
    
        $this->auditLog(
            'procurement.supplier_quotations',
            'download_pdf',
            'SupplierQuotation',
            (int)$row->id,
            'Supplier quotation PDF downloaded'
        );
    
        return $pdf->download(($row->quotation_no ?: 'supplier-quotation-'.$row->id).'.pdf');
    }
}