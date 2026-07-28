<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SupplierBillsController extends Controller
{
    public function index()
    {
        return view('finance.supplier_bills.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $q = DB::table('finance_supplier_bills as b')
            ->leftJoin('suppliers as s', 's.id', '=', 'b.supplier_id')
            ->where('b.company_id', $companyId)
            ->whereNull('b.deleted_at')
            ->select([
                'b.id',
                'b.bill_no',
                'b.bill_date',
                'b.due_date',
                'b.supplier_id',
                'b.vendor_name',
                'b.currency_code',
                'b.fx_rate',
                'b.total_amount',
                'b.balance_due',
                'b.status',
                'b.reference',
                'b.memo',
                'b.journal_entry_id',
                'b.source_type',
                'b.source_id',
                'b.payable_account_id',
                's.name as supplier_name',
            ]);

        if ($request->filled('status')) {
            $q->where('b.status', $request->status);
        }

        if ($request->filled('date_from')) {
            $q->where('b.bill_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->where('b.bill_date', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $q->where(function ($x) use ($term) {
                $x->where('b.bill_no', 'like', "%{$term}%")
                    ->orWhere('b.vendor_name', 'like', "%{$term}%")
                    ->orWhere('b.reference', 'like', "%{$term}%")
                    ->orWhere('b.memo', 'like', "%{$term}%");
            });
        }

        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);

        $recordsTotal = (clone $q)->count();

        $rows = $q->orderByDesc('b.id')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($r) {
            $statusBadge = match ($r->status) {
                'posted'    => '<span class="badge bg-success">POSTED</span>',
                'part_paid' => '<span class="badge bg-warning">PART PAID</span>',
                'paid'      => '<span class="badge bg-primary">PAID</span>',
                'voided'    => '<span class="badge bg-dark">VOIDED</span>',
                default     => '<span class="badge bg-secondary">DRAFT</span>',
            };

            $vendorDisplay = $r->supplier_name ?: ($r->vendor_name ?: '—');

            $json = [
                'id' => $r->id,
                'bill_no' => $r->bill_no,
                'bill_date' => $r->bill_date,
                'due_date' => $r->due_date,
                'supplier_id' => $r->supplier_id,
                'supplier_label' => $r->supplier_name,
                'vendor_name' => $r->vendor_name,
                'currency_code' => $r->currency_code,
                'fx_rate' => $r->fx_rate,
                'reference' => $r->reference,
                'memo' => $r->memo,
                'status' => $r->status,
                'source_type' => $r->source_type,
                'source_id' => $r->source_id,
                'source_label' => $this->resolveSourceLabel($r->source_type, $r->source_id),
                'ap_control_account_id' => $r->payable_account_id,
                'ap_control_account_label' => $this->resolveAccountLabel($r->payable_account_id),
            ];

            $actions = view('finance.supplier_bills.partials.actions', [
                'bill' => (object) ['id' => $r->id, 'status' => $r->status],
                'json' => $json,
            ])->render();

            return [
                'id' => $r->id,
                'bill_no' => e($r->bill_no ?? ('BILL-' . $r->id)),
                'bill_date' => e($r->bill_date ?? ''),
                'due_date' => e($r->due_date ?? ''),
                'vendor' => e($vendorDisplay),
                'currency' => e($r->currency_code ?? ''),
                'total' => number_format((float) ($r->total_amount ?? 0), 2),
                'balance' => number_format((float) ($r->balance_due ?? 0), 2),
                'status' => $statusBadge,
                'actions' => $actions,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function lines($billId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $bill = DB::table('finance_supplier_bills')
            ->where('company_id', $companyId)
            ->where('id', (int) $billId)
            ->whereNull('deleted_at')
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $lines = DB::table('finance_supplier_bill_lines as l')
            ->leftJoin('finance_accounts as a', 'a.id', '=', 'l.gl_account_id')
            ->where('l.bill_id', (int) $billId)
            ->orderBy('l.id')
            ->get([
                'l.id',
                'l.purchase_requisition_line_id',
                'l.rfq_line_id',
                'l.supplier_quotation_line_id',
                'l.purchase_order_line_id',
                'l.goods_receipt_line_id',
                'l.description',
                'l.gl_account_id',
                'l.qty',
                'l.unit_cost',
                'l.tax_rate',
                'l.tax_amount',
                'l.line_total',
                'l.memo',
                'a.code as gl_code',
                'a.name as gl_name',
            ])->map(function ($r) {
                return [
                    'id' => $r->id,
                    'purchase_requisition_line_id' => $r->purchase_requisition_line_id,
                    'rfq_line_id' => $r->rfq_line_id,
                    'supplier_quotation_line_id' => $r->supplier_quotation_line_id,
                    'purchase_order_line_id' => $r->purchase_order_line_id,
                    'goods_receipt_line_id' => $r->goods_receipt_line_id,
                    'description' => $r->description,
                    'gl_account_id' => $r->gl_account_id,
                    'gl_account_label' => $r->gl_account_id ? trim(($r->gl_code ?? '') . ' - ' . ($r->gl_name ?? '')) : null,
                    'qty' => (float) $r->qty,
                    'unit_cost' => (float) $r->unit_cost,
                    'tax_rate' => $r->tax_rate !== null ? (float) $r->tax_rate : null,
                    'tax_amount' => (float) $r->tax_amount,
                    'line_total' => (float) $r->line_total,
                    'memo' => $r->memo,
                ];
            })->values();

        return response()->json(['lines' => $lines]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateBill($request);

        if (empty($data['vendor_name']) && !empty($data['supplier_id'])) {
            $data['vendor_name'] = DB::table('suppliers')
                ->where('id', $data['supplier_id'])
                ->value('name');
        }

        return DB::transaction(function () use ($companyId, $data, $request) {
            $totals = $this->computeTotals($data['lines']);

            $id = DB::table('finance_supplier_bills')->insertGetId([
                'company_id' => $companyId,
                'bill_no' => $data['bill_no'] ?? null,
                'bill_date' => $data['bill_date'],
                'due_date' => $data['due_date'] ?? null,

                'supplier_id' => $data['supplier_id'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? null,

                'purchase_requisition_id' => $request->source_type === 'purchase_requisition' ? $request->source_id : null,
                'rfq_id' => $request->source_type === 'rfq' ? $request->source_id : null,
                'supplier_quotation_id' => $request->source_type === 'supplier_quotation' ? $request->source_id : null,
                'purchase_order_id' => $request->source_type === 'purchase_order' ? $request->source_id : null,
                'goods_receipt_id' => $request->source_type === 'goods_receipt' ? $request->source_id : null,

                'source_type' => $request->source_type ?? null,
                'source_id' => $request->source_id ?? null,

                'currency_code' => $data['currency_code'] ?? null,
                'fx_rate' => $data['fx_rate'] ?? null,

                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,

                'payable_account_id' => $request->ap_control_account_id ?: null,

                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'total_amount' => $totals['total_amount'],
                'amount_paid' => 0,
                'balance_due' => $totals['total_amount'],

                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncLines($id, $data['lines']);

            return response()->json([
                'message' => 'Supplier bill created.',
                'id' => $id,
            ]);
        });
    }

    public function update(Request $request, $billId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $bill = DB::table('finance_supplier_bills')
            ->where('company_id', $companyId)
            ->where('id', (int) $billId)
            ->whereNull('deleted_at')
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (($bill->status ?? 'draft') !== 'draft') {
            return response()->json(['message' => 'Only draft bills can be edited.'], 422);
        }

        $data = $this->validateBill($request);

        if (empty($data['vendor_name']) && !empty($data['supplier_id'])) {
            $data['vendor_name'] = DB::table('suppliers')
                ->where('id', $data['supplier_id'])
                ->value('name');
        }

        $totals = $this->computeTotals($data['lines']);

        return DB::transaction(function () use ($billId, $data, $request, $totals) {
            DB::table('finance_supplier_bills')
                ->where('id', (int) $billId)
                ->update([
                    'bill_no' => $data['bill_no'] ?? null,
                    'bill_date' => $data['bill_date'],
                    'due_date' => $data['due_date'] ?? null,

                    'supplier_id' => $data['supplier_id'] ?? null,
                    'vendor_name' => $data['vendor_name'] ?? null,

                    'purchase_requisition_id' => $request->source_type === 'purchase_requisition' ? $request->source_id : null,
                    'rfq_id' => $request->source_type === 'rfq' ? $request->source_id : null,
                    'supplier_quotation_id' => $request->source_type === 'supplier_quotation' ? $request->source_id : null,
                    'purchase_order_id' => $request->source_type === 'purchase_order' ? $request->source_id : null,
                    'goods_receipt_id' => $request->source_type === 'goods_receipt' ? $request->source_id : null,

                    'source_type' => $request->source_type ?? null,
                    'source_id' => $request->source_id ?? null,

                    'currency_code' => $data['currency_code'] ?? null,
                    'fx_rate' => $data['fx_rate'] ?? null,

                    'reference' => $data['reference'] ?? null,
                    'memo' => $data['memo'] ?? null,

                    'payable_account_id' => $request->ap_control_account_id ?: null,

                    'subtotal' => $totals['subtotal'],
                    'tax_total' => $totals['tax_total'],
                    'total_amount' => $totals['total_amount'],
                    'balance_due' => $totals['total_amount'],
                    'updated_at' => now(),
                ]);

            $this->syncLines((int) $billId, $data['lines']);

            return response()->json(['message' => 'Supplier bill updated.']);
        });
    }

    public function destroy($billId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $bill = DB::table('finance_supplier_bills')
            ->where('company_id', $companyId)
            ->where('id', (int) $billId)
            ->whereNull('deleted_at')
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (($bill->status ?? 'draft') !== 'draft') {
            return response()->json(['message' => 'Only draft bills can be deleted.'], 422);
        }

        DB::transaction(function () use ($billId) {
            DB::table('finance_supplier_bills')
                ->where('id', (int) $billId)
                ->update(['deleted_at' => now()]);

            DB::table('finance_supplier_bill_lines')
                ->where('bill_id', (int) $billId)
                ->delete();
        });

        return response()->json(['message' => 'Deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $ids = collect($request->ids ?? [])->filter()->map(fn ($x) => (int) $x)->values();

        if ($ids->isEmpty()) {
            return response()->json(['message' => 'No records selected.'], 422);
        }

        $rows = DB::table('finance_supplier_bills')
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get(['id', 'status']);

        foreach ($rows as $row) {
            if (($row->status ?? 'draft') !== 'draft') {
                return response()->json(['message' => 'Only draft bills can be bulk deleted.'], 422);
            }
        }

        DB::transaction(function () use ($ids) {
            DB::table('finance_supplier_bills')
                ->whereIn('id', $ids)
                ->update(['deleted_at' => now()]);

            DB::table('finance_supplier_bill_lines')
                ->whereIn('bill_id', $ids)
                ->delete();
        });

        return response()->json(['message' => 'Selected bills deleted.']);
    }

    public function post($billId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $bill = DB::table('finance_supplier_bills')
            ->where('company_id', $companyId)
            ->where('id', (int) $billId)
            ->whereNull('deleted_at')
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (($bill->status ?? 'draft') !== 'draft') {
            return response()->json(['message' => 'Only draft bills can be posted.'], 422);
        }

        if ((float) ($bill->total_amount ?? 0) <= 0) {
            return response()->json(['message' => 'Bill total must be greater than zero.'], 422);
        }

        return DB::transaction(function () use ($companyId, $billId) {
            if (!class_exists(\Modules\Finance\Services\Posting\SupplierBillPostingService::class)) {
                throw new \RuntimeException('SupplierBillPostingService not found. Please create posting service.');
            }

            $jeId = \Modules\Finance\Services\Posting\SupplierBillPostingService::post($companyId, (int) $billId);

            DB::table('finance_supplier_bills')
                ->where('id', (int) $billId)
                ->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'posted_by' => auth()->id(),
                    'journal_entry_id' => $jeId,
                    'updated_at' => now(),
                ]);

            return response()->json(['message' => 'Bill posted.']);
        });
    }

    public function void($billId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $bill = DB::table('finance_supplier_bills')
            ->where('company_id', $companyId)
            ->where('id', (int) $billId)
            ->whereNull('deleted_at')
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (($bill->status ?? '') !== 'posted' && ($bill->status ?? '') !== 'part_paid' && ($bill->status ?? '') !== 'paid') {
            return response()->json(['message' => 'Only posted, part-paid, or paid bills can be voided.'], 422);
        }

        DB::table('finance_supplier_bills')
            ->where('id', (int) $billId)
            ->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'Bill voided.']);
    }

    public function lookupSourceRecords(Request $request)
    {
        $type = trim((string) $request->get('source_type', ''));
        $q    = trim((string) $request->get('q', ''));
        $companyId = auth()->user()->company_id ?? 1;

        if ($type === '') {
            return response()->json(['results' => []]);
        }

        switch ($type) {
            case 'purchase_requisition':
                $rows = DB::table('proc_purchase_requisitions as pr')
                    ->where('pr.company_id', $companyId)
                    ->when($q !== '', function ($query) use ($q) {
                        $query->where(function ($x) use ($q) {
                            if (Schema::hasColumn('proc_purchase_requisitions', 'pr_no')) {
                                $x->where('pr.pr_no', 'like', "%{$q}%");
                            }
                            if (Schema::hasColumn('proc_purchase_requisitions', 'reference')) {
                                $x->orWhere('pr.reference', 'like', "%{$q}%");
                            }
                        });
                    })
                    ->orderByDesc('pr.id')
                    ->limit(30)
                    ->get([
                        'pr.id',
                        DB::raw((Schema::hasColumn('proc_purchase_requisitions', 'pr_no') ? 'pr.pr_no' : 'NULL') . ' as doc_no'),
                    ]);

                $results = $rows->map(fn ($r) => [
                    'id' => $r->id,
                    'text' => ($r->doc_no ?: ('PR #' . $r->id)),
                ]);
                break;

            case 'rfq':
                $rows = DB::table('proc_request_for_quotations as r')
                    ->where('r.company_id', $companyId)
                    ->when($q !== '', function ($query) use ($q) {
                        $query->where(function ($x) use ($q) {
                            if (Schema::hasColumn('proc_request_for_quotations', 'rfq_no')) {
                                $x->where('r.rfq_no', 'like', "%{$q}%");
                            }
                            if (Schema::hasColumn('proc_request_for_quotations', 'reference')) {
                                $x->orWhere('r.reference', 'like', "%{$q}%");
                            }
                        });
                    })
                    ->orderByDesc('r.id')
                    ->limit(30)
                    ->get([
                        'r.id',
                        DB::raw((Schema::hasColumn('proc_request_for_quotations', 'rfq_no') ? 'r.rfq_no' : 'NULL') . ' as doc_no'),
                    ]);

                $results = $rows->map(fn ($r) => [
                    'id' => $r->id,
                    'text' => ($r->doc_no ?: ('RFQ #' . $r->id)),
                ]);
                break;

            case 'supplier_quotation':
                $rows = DB::table('proc_supplier_quotations as sq')
                    ->leftJoin('suppliers as s', 's.id', '=', 'sq.supplier_id')
                    ->where('sq.company_id', $companyId)
                    ->when($q !== '', function ($query) use ($q) {
                        $query->where(function ($x) use ($q) {
                            if (Schema::hasColumn('proc_supplier_quotations', 'quotation_no')) {
                                $x->where('sq.quotation_no', 'like', "%{$q}%");
                            }
                            if (Schema::hasColumn('proc_supplier_quotations', 'quote_no')) {
                                $x->orWhere('sq.quote_no', 'like', "%{$q}%");
                            }
                            $x->orWhere('s.name', 'like', "%{$q}%");
                        });
                    })
                    ->orderByDesc('sq.id')
                    ->limit(30)
                    ->get([
                        'sq.id',
                        DB::raw(
                            (Schema::hasColumn('proc_supplier_quotations', 'quotation_no')
                                ? 'sq.quotation_no'
                                : (Schema::hasColumn('proc_supplier_quotations', 'quote_no') ? 'sq.quote_no' : 'NULL')
                            ) . ' as doc_no'
                        ),
                        's.name as supplier_name',
                    ]);

                $results = $rows->map(fn ($r) => [
                    'id' => $r->id,
                    'text' => ($r->doc_no ?: ('SQ #' . $r->id)) . ' - ' . ($r->supplier_name ?: 'No Supplier'),
                ]);
                break;

            case 'purchase_order':
                $rows = DB::table('proc_purchase_orders as p')
                    ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
                    ->where('p.company_id', $companyId)
                    ->when($q !== '', function ($query) use ($q) {
                        $query->where(function ($x) use ($q) {
                            if (Schema::hasColumn('proc_purchase_orders', 'po_no')) {
                                $x->where('p.po_no', 'like', "%{$q}%");
                            }
                            if (Schema::hasColumn('proc_purchase_orders', 'po_number')) {
                                $x->orWhere('p.po_number', 'like', "%{$q}%");
                            }
                            $x->orWhere('s.name', 'like', "%{$q}%");
                        });
                    })
                    ->orderByDesc('p.id')
                    ->limit(30)
                    ->get([
                        'p.id',
                        DB::raw(
                            (Schema::hasColumn('proc_purchase_orders', 'po_no')
                                ? 'p.po_no'
                                : (Schema::hasColumn('proc_purchase_orders', 'po_number') ? 'p.po_number' : 'NULL')
                            ) . ' as doc_no'
                        ),
                        's.name as supplier_name',
                    ]);

                $results = $rows->map(fn ($r) => [
                    'id' => $r->id,
                    'text' => ($r->doc_no ?: ('PO #' . $r->id)) . ' - ' . ($r->supplier_name ?: 'No Supplier'),
                ]);
                break;

            case 'goods_receipt':
                $rows = DB::table('proc_goods_receipts as g')
                    ->leftJoin('suppliers as s', 's.id', '=', 'g.supplier_id')
                    ->where('g.company_id', $companyId)
                    ->whereNull('g.deleted_at')
                    ->when($q !== '', function ($query) use ($q) {
                        $query->where(function ($x) use ($q) {
                            $x->where('g.grn_no', 'like', "%{$q}%")
                              ->orWhere('s.name', 'like', "%{$q}%");
                        });
                    })
                    ->orderByDesc('g.id')
                    ->limit(30)
                    ->get([
                        'g.id',
                        'g.grn_no as doc_no',
                        's.name as supplier_name',
                    ]);

                $results = $rows->map(fn ($r) => [
                    'id' => $r->id,
                    'text' => ($r->doc_no ?: ('GRN #' . $r->id)) . ' - ' . ($r->supplier_name ?: 'No Supplier'),
                ]);
                break;

            default:
                $results = collect([]);
                break;
        }

        return response()->json(['results' => $results->values()]);
    }

    public function loadSource(Request $request)
    {
        $type = trim((string) $request->get('source_type', ''));
        $id   = (int) $request->get('source_id');
        $companyId = auth()->user()->company_id ?? 1;

        if ($type === '' || !$id) {
            return response()->json(['message' => 'Source type and source record are required.'], 422);
        }

        switch ($type) {
            case 'purchase_requisition':
                $pr = DB::table('proc_purchase_requisitions')
                    ->where('company_id', $companyId)
                    ->where('id', $id)
                    ->first();

                if (!$pr) {
                    return response()->json(['message' => 'Purchase requisition not found.'], 404);
                }

                $lines = DB::table('proc_purchase_requisition_lines')
                    ->where('purchase_requisition_id', $id)
                    ->orderBy('id')
                    ->get();

                return response()->json([
                    'header' => [
                        'supplier_id' => null,
                        'supplier_label' => null,
                        'vendor_name' => null,
                        'bill_date' => now()->format('Y-m-d'),
                        'due_date' => now()->addDays(30)->format('Y-m-d'),
                        'reference' => $pr->pr_no ?? ('PR #' . $pr->id),
                        'memo' => 'Loaded from Purchase Requisition',
                        'currency_code' => $pr->currency_code ?? null,
                        'fx_rate' => $pr->fx_rate ?? 1,
                        'ap_control_account_id' => null,
                        'ap_control_account_label' => null,
                    ],
                    'lines' => $lines->map(function ($l) {
                        $qty = (float) ($l->qty ?? 1);
                        $unit = (float) ($l->unit_price ?? 0);

                        return [
                            'purchase_requisition_line_id' => $l->id,
                            'description' => $l->description ?? null,
                            'qty' => $qty,
                            'unit_cost' => $unit,
                            'tax_rate' => (float) ($l->tax_rate ?? 0),
                            'line_total' => $qty * $unit,
                            'gl_account_id' => null,
                            'gl_account_label' => null,
                        ];
                    })->values(),
                ]);

        case 'rfq':
            $rfq = DB::table('proc_request_for_quotations')
                ->where('company_id', $companyId)
                ->where('id', $id)
                ->first();
        
            if (!$rfq) {
                return response()->json(['message' => 'RFQ not found.'], 404);
            }
        
            // Correct table name based on your schema
            $lines = DB::table('proc_request_for_quotation_lines')
                ->where('rfq_id', $id)
                ->orderBy('id')
                ->get();
    
                return response()->json([
                    'header' => [
                        'supplier_id' => null,
                        'supplier_label' => null,
                        'vendor_name' => null,
                        'bill_date' => now()->format('Y-m-d'),
                        'due_date' => now()->addDays(30)->format('Y-m-d'),
                        'reference' => $rfq->rfq_no ?? ('RFQ #' . $rfq->id),
                        'memo' => 'Loaded from RFQ',
                        'currency_code' => $rfq->currency_code ?? null,
                        'fx_rate' => $rfq->fx_rate ?? 1,
                        'ap_control_account_id' => null,
                        'ap_control_account_label' => null,
                    ],
            
                    'lines' => $lines->map(function ($l) {
                        $qty = (float) ($l->qty ?? 1);
                        $unit = (float) ($l->estimated_unit_cost ?? 0); // matches your DB column
            
                        return [
                            'rfq_line_id' => $l->id,
                            'description' => $l->description,
                            'qty' => $qty,
                            'unit_cost' => $unit,
                            'tax_rate' => (float) ($l->tax_rate ?? 0),
                            'line_total' => $qty * $unit,
                            'gl_account_id' => null,
                            'gl_account_label' => null,
                        ];
                    })->values(),
                ]);
            case 'supplier_quotation':
                $sq = DB::table('proc_supplier_quotations as sq')
                    ->leftJoin('suppliers as s', 's.id', '=', 'sq.supplier_id')
                    ->where('sq.company_id', $companyId)
                    ->where('sq.id', $id)
                    ->select('sq.*', 's.name as supplier_name')
                    ->first();

                if (!$sq) {
                    return response()->json(['message' => 'Supplier quotation not found.'], 404);
                }

                $lines = DB::table('proc_supplier_quotation_lines')
                    ->where('supplier_quotation_id', $id)
                    ->orderBy('id')
                    ->get();

                return response()->json([
                    'header' => [
                        'supplier_id' => $sq->supplier_id ?? null,
                        'supplier_label' => $sq->supplier_name ?? null,
                        'vendor_name' => $sq->supplier_name ?? null,
                        'bill_date' => now()->format('Y-m-d'),
                        'due_date' => now()->addDays(30)->format('Y-m-d'),
                        'reference' => $sq->quotation_no ?? $sq->quote_no ?? ('SQ #' . $sq->id),
                        'memo' => 'Loaded from Supplier Quotation',
                        'currency_code' => $sq->currency_code ?? null,
                        'fx_rate' => $sq->fx_rate ?? 1,
                        'ap_control_account_id' => null,
                        'ap_control_account_label' => null,
                    ],
                    'lines' => $lines->map(function ($l) {
                        $qty = (float) ($l->qty ?? 1);
                        $unit = (float) ($l->unit_price ?? 0);

                        return [
                            'supplier_quotation_line_id' => $l->id,
                            'description' => $l->description ?? null,
                            'qty' => $qty,
                            'unit_cost' => $unit,
                            'tax_rate' => (float) ($l->tax_rate ?? 0),
                            'line_total' => $qty * $unit,
                            'gl_account_id' => null,
                            'gl_account_label' => null,
                        ];
                    })->values(),
                ]);

            case 'purchase_order':
                $po = DB::table('proc_purchase_orders as p')
                    ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
                    ->where('p.company_id', $companyId)
                    ->where('p.id', $id)
                    ->select('p.*', 's.name as supplier_name')
                    ->first();

                if (!$po) {
                    return response()->json(['message' => 'Purchase order not found.'], 404);
                }

                $lines = DB::table('proc_purchase_order_lines')
                    ->where('purchase_order_id', $id)
                    ->orderBy('id')
                    ->get();

                return response()->json([
                    'header' => [
                        'supplier_id' => $po->supplier_id ?? null,
                        'supplier_label' => $po->supplier_name ?? null,
                        'vendor_name' => $po->supplier_name ?? null,
                        'bill_date' => now()->format('Y-m-d'),
                        'due_date' => now()->addDays(30)->format('Y-m-d'),
                        'reference' => $po->po_no ?? $po->po_number ?? ('PO #' . $po->id),
                        'memo' => 'Loaded from Purchase Order',
                        'currency_code' => $po->currency_code ?? null,
                        'fx_rate' => $po->fx_rate ?? 1,
                        'ap_control_account_id' => null,
                        'ap_control_account_label' => null,
                    ],
                    'lines' => $lines->map(function ($l) {
                        $qty = (float) ($l->qty ?? 1);
                        $unit = (float) ($l->unit_price ?? 0);

                        return [
                            'purchase_order_line_id' => $l->id,
                            'description' => $l->description ?? null,
                            'qty' => $qty,
                            'unit_cost' => $unit,
                            'tax_rate' => (float) ($l->tax_rate ?? 0),
                            'line_total' => $qty * $unit,
                            'gl_account_id' => null,
                            'gl_account_label' => null,
                        ];
                    })->values(),
                ]);

            case 'goods_receipt':
                $grn = DB::table('proc_goods_receipts as g')
                    ->leftJoin('suppliers as s', 's.id', '=', 'g.supplier_id')
                    ->where('g.company_id', $companyId)
                    ->where('g.id', $id)
                    ->whereNull('g.deleted_at')
                    ->select('g.*', 's.name as supplier_name')
                    ->first();

                if (!$grn) {
                    return response()->json(['message' => 'Goods receipt not found.'], 404);
                }

                $lines = DB::table('proc_goods_receipt_lines')
                    ->where('goods_receipt_id', $id)
                    ->orderBy('id')
                    ->get();

                return response()->json([
                    'header' => [
                        'supplier_id' => $grn->supplier_id ?? null,
                        'supplier_label' => $grn->supplier_name ?? null,
                        'vendor_name' => $grn->supplier_name ?? null,
                        'bill_date' => now()->format('Y-m-d'),
                        'due_date' => now()->addDays(30)->format('Y-m-d'),
                        'reference' => $grn->grn_no ?? ('GRN #' . $grn->id),
                        'memo' => 'Loaded from Goods Receipt',
                        'currency_code' => 'NGN',
                        'fx_rate' => 1,
                        'ap_control_account_id' => null,
                        'ap_control_account_label' => null,
                    ],
                    'lines' => $lines->map(function ($l) {
                        $qty = (float) ($l->accepted_qty ?? $l->received_qty ?? 0);
                        $unit = (float) ($l->unit_cost ?? 0);

                        return [
                            'goods_receipt_line_id' => $l->id,
                            'description' => $l->description ?? null,
                            'qty' => $qty,
                            'unit_cost' => $unit,
                            'tax_rate' => 0,
                            'line_total' => $qty * $unit,
                            'gl_account_id' => null,
                            'gl_account_label' => null,
                        ];
                    })->values(),
                ]);

            default:
                return response()->json(['message' => 'Source type not supported.'], 422);
        }
    }

    public function suppliers(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $q = DB::table('suppliers')
            ->select(['id', 'name'])
            ->orderBy('name');

        if ($term !== '') {
            $q->where('name', 'like', "%{$term}%");
        }

        $items = $q->limit(25)->get()->map(fn ($s) => [
            'id' => $s->id,
            'text' => $s->name,
        ]);

        return response()->json(['results' => $items]);
    }

    public function glAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $term = trim((string) $request->get('q', ''));

        $q = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->select(['id', 'code', 'name'])
            ->orderBy('code');

        if ($term !== '') {
            $q->where(function ($x) use ($term) {
                $x->where('code', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            });
        }

        $items = $q->limit(50)->get()->map(fn ($a) => [
            'id' => $a->id,
            'text' => trim(($a->code ?? '') . ' - ' . ($a->name ?? '')),
        ]);

        return response()->json(['results' => $items]);
    }

    public function currencies(Request $request)
    {
        $term = strtoupper(trim((string) $request->get('q', '')));

        $q = DB::table('currencies')->select(['code', 'name'])->orderBy('code');

        if ($term !== '') {
            $q->where(function ($x) use ($term) {
                $x->where('code', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            });
        }

        $items = $q->limit(25)->get()->map(fn ($c) => [
            'id' => $c->code,
            'text' => $c->code . ' - ' . $c->name,
        ]);

        return response()->json(['results' => $items]);
    }

    public function apControlAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('finance_accounts as a')
            ->where('a.company_id', $companyId)
            ->when(Schema::hasColumn('finance_accounts', 'deleted_at'), function ($x) {
                $x->whereNull('a.deleted_at');
            })
            ->when(Schema::hasColumn('finance_accounts', 'account_type'), function ($x) {
                $x->whereIn('a.account_type', ['liability', 'LIABILITY']);
            })
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('a.code', 'like', "%{$q}%")
                      ->orWhere('a.name', 'like', "%{$q}%");
                });
            })
            ->orderBy('a.code')
            ->limit(30)
            ->get(['a.id', 'a.code', 'a.name']);

        return response()->json([
            'results' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'text' => trim(($r->code ?? '') . ' - ' . ($r->name ?? '')),
            ])->values(),
        ]);
    }

    private function validateBill(Request $request): array
    {
        $data = Validator::make($request->all(), [
            'bill_no'        => ['nullable', 'string', 'max:50'],
            'bill_date'      => ['required', 'date'],
            'due_date'       => ['nullable', 'date'],

            'supplier_id'    => ['nullable', 'integer', 'exists:suppliers,id'],
            'vendor_name'    => ['nullable', 'string', 'max:255'],

            'currency_code'  => ['nullable', 'string', 'size:3', 'exists:currencies,code'],
            'fx_rate'        => ['nullable', 'numeric', 'min:0.000001'],

            'reference'      => ['nullable', 'string', 'max:100'],
            'memo'           => ['nullable', 'string'],

            'source_type'    => ['nullable', 'string', 'max:50'],
            'source_id'      => ['nullable', 'integer'],

            'lines'                                   => ['required', 'array', 'min:1'],
            'lines.*.purchase_requisition_line_id'    => ['nullable', 'integer'],
            'lines.*.rfq_line_id'                     => ['nullable', 'integer'],
            'lines.*.supplier_quotation_line_id'      => ['nullable', 'integer'],
            'lines.*.purchase_order_line_id'          => ['nullable', 'integer'],
            'lines.*.goods_receipt_line_id'           => ['nullable', 'integer'],

            'lines.*.description'   => ['nullable', 'string', 'max:255'],
            'lines.*.gl_account_id' => ['required', 'integer', 'exists:finance_accounts,id'],
            'lines.*.qty'           => ['required', 'numeric', 'min:0'],
            'lines.*.unit_cost'     => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate'      => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo'          => ['nullable', 'string', 'max:255'],
        ])->validate();

        $data['currency_code'] = !empty($data['currency_code'])
            ? strtoupper(trim($data['currency_code']))
            : null;

        if (empty($data['supplier_id']) && empty($data['vendor_name'])) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Supplier or Vendor Name is required.',
            ]);
        }

        return $data;
    }

    private function computeTotals(array $lines): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $total = 0.0;

        foreach ($lines as $ln) {
            $qty  = (float) ($ln['qty'] ?? 0);
            $unit = (float) ($ln['unit_cost'] ?? 0);
            $rate = (float) ($ln['tax_rate'] ?? 0);

            $base = $qty * $unit;
            $tax  = $base * ($rate / 100);

            $subtotal += $base;
            $taxTotal += $tax;
            $total += ($base + $tax);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'total_amount' => round($total, 2),
        ];
    }

    private function syncLines(int $billId, array $lines): void
    {
        DB::table('finance_supplier_bill_lines')
            ->where('bill_id', $billId)
            ->delete();

        $rows = [];

        foreach ($lines as $ln) {
            $qty  = (float) ($ln['qty'] ?? 0);
            $unit = (float) ($ln['unit_cost'] ?? 0);
            $rate = (float) ($ln['tax_rate'] ?? 0);

            $base = $qty * $unit;
            $tax = $base * ($rate / 100);
            $lineTotal = $base + $tax;

            $rows[] = [
                'bill_id' => $billId,
                'purchase_requisition_line_id' => $ln['purchase_requisition_line_id'] ?? null,
                'rfq_line_id' => $ln['rfq_line_id'] ?? null,
                'supplier_quotation_line_id' => $ln['supplier_quotation_line_id'] ?? null,
                'purchase_order_line_id' => $ln['purchase_order_line_id'] ?? null,
                'goods_receipt_line_id' => $ln['goods_receipt_line_id'] ?? null,

                'description' => $ln['description'] ?? null,
                'gl_account_id' => (int) ($ln['gl_account_id'] ?? 0),
                'qty' => $qty,
                'unit_cost' => $unit,
                'tax_rate' => array_key_exists('tax_rate', $ln) && $ln['tax_rate'] !== '' ? (float) $ln['tax_rate'] : null,
                'tax_amount' => round($tax, 2),
                'line_total' => round($lineTotal, 2),
                'memo' => $ln['memo'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            DB::table('finance_supplier_bill_lines')->insert($rows);
        }
    }

    private function resolveSourceLabel(?string $sourceType, $sourceId): ?string
    {
        if (!$sourceType || !$sourceId) {
            return null;
        }

        return match ($sourceType) {
            'purchase_order' => DB::table('proc_purchase_orders')
                ->where('id', $sourceId)
                ->value(Schema::hasColumn('proc_purchase_orders', 'po_no') ? 'po_no' : 'po_number'),

            'goods_receipt' => DB::table('proc_goods_receipts')
                ->where('id', $sourceId)
                ->value('grn_no'),

            'purchase_requisition' => DB::table('proc_purchase_requisitions')
                ->where('id', $sourceId)
                ->value('pr_no'),

            'rfq' => DB::table('proc_request_for_quotations')
                ->where('id', $sourceId)
                ->value('rfq_no'),

            'supplier_quotation' => DB::table('proc_supplier_quotations')
                ->where('id', $sourceId)
                ->value(Schema::hasColumn('proc_supplier_quotations', 'quotation_no') ? 'quotation_no' : 'quote_no'),

            default => null,
        };
    }

    private function resolveAccountLabel($accountId): ?string
    {
        if (!$accountId) {
            return null;
        }

        $acc = DB::table('finance_accounts')
            ->where('id', $accountId)
            ->first(['code', 'name']);

        if (!$acc) {
            return null;
        }

        return trim(($acc->code ?? '') . ' - ' . ($acc->name ?? ''));
    }
    
    public function show($billId)
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $bill = DB::table('finance_supplier_bills as b')
            ->leftJoin('suppliers as s', 's.id', '=', 'b.supplier_id')
            ->leftJoin('finance_accounts as a', 'a.id', '=', 'b.payable_account_id')
            ->where('b.company_id', $companyId)
            ->where('b.id', (int) $billId)
            ->whereNull('b.deleted_at')
            ->select([
                'b.*',
                's.name as supplier_name',
                'a.code as payable_code',
                'a.name as payable_name',
            ])
            ->first();
    
        if (!$bill) {
            return response()->json(['message' => 'Supplier bill not found.'], 404);
        }
    
        $lines = DB::table('finance_supplier_bill_lines as l')
            ->leftJoin('finance_accounts as fa', 'fa.id', '=', 'l.gl_account_id')
            ->where('l.bill_id', (int) $billId)
            ->orderBy('l.id')
            ->get([
                'l.*',
                'fa.code as gl_code',
                'fa.name as gl_name',
            ])
            ->map(function ($l) {
                return [
                    'id'          => $l->id,
                    'description' => $l->description,
                    'gl_account'  => trim(($l->gl_code ?? '') . ' - ' . ($l->gl_name ?? '')),
                    'qty'         => (float) $l->qty,
                    'unit_cost'   => (float) $l->unit_cost,
                    'tax_rate'    => $l->tax_rate !== null ? (float) $l->tax_rate : null,
                    'tax_amount'  => (float) $l->tax_amount,
                    'line_total'  => (float) $l->line_total,
                    'memo'        => $l->memo,
                ];
            })
            ->values();
    
        return response()->json([
            'bill' => [
                'id'                       => $bill->id,
                'bill_no'                  => $bill->bill_no,
                'bill_date'                => $bill->bill_date,
                'due_date'                 => $bill->due_date,
                'supplier_id'              => $bill->supplier_id,
                'supplier_name'            => $bill->supplier_name,
                'vendor_name'              => $bill->vendor_name,
                'currency_code'            => $bill->currency_code,
                'fx_rate'                  => $bill->fx_rate,
                'reference'                => $bill->reference,
                'memo'                     => $bill->memo,
                'status'                   => $bill->status,
                'subtotal'                 => (float) $bill->subtotal,
                'tax_total'                => (float) $bill->tax_total,
                'total_amount'             => (float) $bill->total_amount,
                'amount_paid'              => (float) $bill->amount_paid,
                'balance_due'              => (float) $bill->balance_due,
                'source_type'              => $bill->source_type,
                'source_id'                => $bill->source_id,
                'payable_account_label'    => trim(($bill->payable_code ?? '') . ' - ' . ($bill->payable_name ?? '')),
                'journal_entry_id'         => $bill->journal_entry_id,
                'posted_at'                => $bill->posted_at,
                'voided_at'                => $bill->voided_at,
                'created_at'               => $bill->created_at,
            ],
            'lines' => $lines,
        ]);
    }
    
    public function pdf($billId)
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $bill = DB::table('finance_supplier_bills as b')
            ->leftJoin('suppliers as s', 's.id', '=', 'b.supplier_id')
            ->leftJoin('finance_accounts as a', 'a.id', '=', 'b.payable_account_id')
            ->where('b.company_id', $companyId)
            ->where('b.id', (int) $billId)
            ->whereNull('b.deleted_at')
            ->select([
                'b.*',
                's.name as supplier_name',
                'a.code as payable_code',
                'a.name as payable_name',
            ])
            ->first();
    
        abort_unless($bill, 404, 'Supplier bill not found.');
    
        $lines = DB::table('finance_supplier_bill_lines as l')
            ->leftJoin('finance_accounts as fa', 'fa.id', '=', 'l.gl_account_id')
            ->where('l.bill_id', (int) $billId)
            ->orderBy('l.id')
            ->get([
                'l.*',
                'fa.code as gl_code',
                'fa.name as gl_name',
            ]);
    
        return view('finance.supplier_bills.pdf', compact('bill', 'lines'));
    }
}