<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SupplierCreditsController extends Controller
{
    public function index()
    {
        return view('finance.supplier_credits.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $q = DB::table('finance_supplier_credits as sc')
            ->leftJoin('suppliers as s', 's.id', '=', 'sc.supplier_id')
            ->where('sc.company_id', $companyId)
            ->whereNull('sc.deleted_at')
            ->select([
                'sc.id',
                'sc.credit_no',
                'sc.credit_date',
                'sc.total_amount',
                'sc.unapplied_amount',
                'sc.status',
                'sc.currency_code',
                'sc.reference',
                'sc.memo',
                'sc.supplier_id',
                'sc.ap_control_account_id',
                'sc.fx_rate',
                's.name as supplier_name',
            ]);

        if ($request->filled('status')) {
            $q->where('sc.status', $request->status);
        }
        if ($request->filled('date_from')) {
            $q->where('sc.credit_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->where('sc.credit_date', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $q->where(function ($x) use ($term) {
                $x->where('sc.credit_no', 'like', "%{$term}%")
                  ->orWhere('sc.reference', 'like', "%{$term}%")
                  ->orWhere('sc.memo', 'like', "%{$term}%")
                  ->orWhere('s.name', 'like', "%{$term}%");
            });
        }

        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);

        $recordsTotal = (clone $q)->count();
        $rows = $q->orderByDesc('sc.id')->offset($start)->limit($length)->get();

        $data = $rows->map(function ($r) {
            $statusBadge = match ($r->status) {
                'posted' => '<span class="badge bg-success">POSTED</span>',
                'voided' => '<span class="badge bg-dark">VOIDED</span>',
                default  => '<span class="badge bg-secondary">DRAFT</span>',
            };

            $json = [
                'id' => $r->id,
                'credit_no' => $r->credit_no,
                'credit_date' => $r->credit_date,
                'supplier_id' => $r->supplier_id,
                'supplier_label' => $r->supplier_name ?: ('Supplier #' . $r->supplier_id),
                'currency_code' => $r->currency_code,
                'reference' => $r->reference,
                'memo' => $r->memo,
                'status' => $r->status,
                'ap_control_account_id' => $r->ap_control_account_id,
                'fx_rate' => $r->fx_rate,
            ];

            $actions = view('finance.supplier_credits.partials.actions', [
                'cr' => (object) ['id' => $r->id, 'status' => $r->status],
                'json' => $json,
            ])->render();

            return [
                'id' => $r->id,
                'credit_no' => e($r->credit_no ?? ('CR-' . $r->id)),
                'credit_date' => e($r->credit_date ?? ''),
                'supplier' => e($r->supplier_name ?? '—'),
                'currency' => e($r->currency_code ?? ''),
                'total' => number_format((float) ($r->total_amount ?? 0), 2),
                'unapplied' => number_format((float) ($r->unapplied_amount ?? 0), 2),
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

    public function lines($creditId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $cr = DB::table('finance_supplier_credits')
            ->where('company_id', $companyId)
            ->where('id', (int) $creditId)
            ->whereNull('deleted_at')
            ->first();

        if (!$cr) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $dist = DB::table('finance_supplier_credit_lines as l')
            ->leftJoin('finance_accounts as a', 'a.id', '=', 'l.gl_account_id')
            ->leftJoin('finance_tax_codes as tc', 'tc.id', '=', 'l.tax_code_id')
            ->leftJoin('finance_tax_rates as tr', 'tr.id', '=', 'l.tax_rate_id')
            ->where('l.supplier_credit_id', (int) $creditId)
            ->orderBy('l.id')
            ->get([
                'l.id',
                'l.description',
                'l.gl_account_id',
                'l.qty',
                'l.unit_cost',
                'l.tax_code_id',
                'l.tax_rate_id',
                'l.tax_rate',
                'l.tax_amount',
                'l.line_total',
                'l.memo',
                'a.code as gl_code',
                'a.name as gl_name',
                'tc.code as tax_code_code',
                'tc.name as tax_code_name',
                'tr.code as tax_rate_code',
                'tr.name as tax_rate_name',
                'tr.rate as selected_rate',
            ])
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'description' => $r->description,
                    'gl_account_id' => $r->gl_account_id,
                    'gl_account_label' => $r->gl_account_id ? trim(($r->gl_code ?? '') . ' - ' . ($r->gl_name ?? '')) : null,
                    'qty' => (float) $r->qty,
                    'unit_cost' => (float) $r->unit_cost,
                    'tax_code_id' => $r->tax_code_id,
                    'tax_code_label' => $r->tax_code_id ? trim(($r->tax_code_code ?? '') . ' - ' . ($r->tax_code_name ?? '')) : null,
                    'tax_rate_id' => $r->tax_rate_id,
                    'tax_rate_label' => $r->tax_rate_id
                        ? trim(($r->tax_rate_code ?? '') . ' - ' . ($r->tax_rate_name ?? '') . ' (' . number_format((float) ($r->selected_rate ?? $r->tax_rate ?? 0), 2) . '%)')
                        : null,
                    'tax_rate' => $r->tax_rate !== null ? (float) $r->tax_rate : null,
                    'tax_amount' => (float) $r->tax_amount,
                    'line_total' => (float) $r->line_total,
                    'memo' => $r->memo,
                ];
            })->values();

        $apps = DB::table('finance_supplier_credit_applications as a')
            ->join('finance_supplier_bills as b', 'b.id', '=', 'a.bill_id')
            ->where('a.supplier_credit_id', (int) $creditId)
            ->orderBy('a.id')
            ->get([
                'a.id',
                'a.bill_id',
                'a.amount_applied',
                'b.bill_no',
                'b.due_date',
                'b.balance_due',
                'b.currency_code',
            ])
            ->map(function ($r) {
                $label = trim(
                    ($r->bill_no ?? ('BILL-' . $r->bill_id))
                    . ' | Due ' . $r->due_date
                    . ' | Bal ' . number_format((float) $r->balance_due, 2)
                    . ' ' . $r->currency_code
                );

                return [
                    'id' => $r->id,
                    'bill_id' => $r->bill_id,
                    'bill_label' => $label,
                    'amount_applied' => (float) $r->amount_applied,
                ];
            })->values();

        return response()->json([
            'distribution' => $dist,
            'applications' => $apps,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateCredit($request);

        return DB::transaction(function () use ($companyId, $data) {
            $totals = $this->computeTotals($data['distribution'] ?? []);

            $id = DB::table('finance_supplier_credits')->insertGetId([
                'company_id' => $companyId,
                'credit_no' => $data['credit_no'] ?? null,
                'credit_date' => $data['credit_date'],
                'supplier_id' => (int) $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'goods_receipt_id' => $data['goods_receipt_id'] ?? null,
                'supplier_bill_id' => $data['supplier_bill_id'] ?? null,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'ap_control_account_id' => $data['ap_control_account_id'] ?? null,
                'currency_code' => $data['currency_code'] ?? null,
                'fx_rate' => $data['fx_rate'] ?? null,
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'total_amount' => $totals['total_amount'],
                'unapplied_amount' => $totals['total_amount'],
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncDistribution($id, $data['distribution'] ?? []);
            $this->syncApplications($id, $data['applications'] ?? [], (int) $data['supplier_id'], $companyId);

            $applied = $this->computeApplied($data['applications'] ?? []);

            DB::table('finance_supplier_credits')
                ->where('id', $id)
                ->update([
                    'unapplied_amount' => round($totals['total_amount'] - $applied, 2),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'message' => 'Supplier credit created.',
                'id' => $id,
            ]);
        });
    }

    public function update(Request $request, $creditId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $cr = DB::table('finance_supplier_credits')
            ->where('company_id', $companyId)
            ->where('id', (int) $creditId)
            ->whereNull('deleted_at')
            ->first();

        if (!$cr) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (($cr->status ?? 'draft') !== 'draft') {
            return response()->json(['message' => 'Only draft credits can be edited.'], 422);
        }

        $data = $this->validateCredit($request);
        $totals = $this->computeTotals($data['distribution'] ?? []);
        $applied = $this->computeApplied($data['applications'] ?? []);

        if ($applied > $totals['total_amount'] + 0.0001) {
            return response()->json(['message' => 'Applied total cannot exceed credit total.'], 422);
        }

        return DB::transaction(function () use ($creditId, $data, $totals, $applied, $companyId) {
            DB::table('finance_supplier_credits')
                ->where('id', (int) $creditId)
                ->update([
                    'credit_no' => $data['credit_no'] ?? null,
                    'credit_date' => $data['credit_date'],
                    'supplier_id' => (int) $data['supplier_id'],
                    'purchase_order_id' => $data['purchase_order_id'] ?? null,
                    'goods_receipt_id' => $data['goods_receipt_id'] ?? null,
                    'supplier_bill_id' => $data['supplier_bill_id'] ?? null,
                    'source_type' => $data['source_type'] ?? null,
                    'source_id' => $data['source_id'] ?? null,
                    'ap_control_account_id' => $data['ap_control_account_id'] ?? null,
                    'currency_code' => $data['currency_code'] ?? null,
                    'fx_rate' => $data['fx_rate'] ?? null,
                    'reference' => $data['reference'] ?? null,
                    'memo' => $data['memo'] ?? null,
                    'subtotal' => $totals['subtotal'],
                    'tax_total' => $totals['tax_total'],
                    'total_amount' => $totals['total_amount'],
                    'unapplied_amount' => round($totals['total_amount'] - $applied, 2),
                    'updated_at' => now(),
                ]);

            $this->syncDistribution((int) $creditId, $data['distribution'] ?? []);
            $this->syncApplications((int) $creditId, $data['applications'] ?? [], (int) $data['supplier_id'], $companyId);

            return response()->json(['message' => 'Supplier credit updated.']);
        });
    }

    public function destroy($creditId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $cr = DB::table('finance_supplier_credits')
            ->where('company_id', $companyId)
            ->where('id', (int) $creditId)
            ->whereNull('deleted_at')
            ->first();

        if (!$cr) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (($cr->status ?? 'draft') !== 'draft') {
            return response()->json(['message' => 'Only draft credits can be deleted.'], 422);
        }

        DB::transaction(function () use ($creditId) {
            DB::table('finance_supplier_credits')->where('id', (int) $creditId)->update(['deleted_at' => now()]);
            DB::table('finance_supplier_credit_lines')->where('supplier_credit_id', (int) $creditId)->delete();
            DB::table('finance_supplier_credit_applications')->where('supplier_credit_id', (int) $creditId)->delete();
        });

        return response()->json(['message' => 'Deleted.']);
    }

    public function post($creditId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $cr = DB::table('finance_supplier_credits')
            ->where('company_id', $companyId)
            ->where('id', (int) $creditId)
            ->whereNull('deleted_at')
            ->first();

        if (!$cr) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (($cr->status ?? 'draft') !== 'draft') {
            return response()->json(['message' => 'Only draft credits can be posted.'], 422);
        }

        if ((float) ($cr->total_amount ?? 0) <= 0) {
            return response()->json(['message' => 'Credit total must be > 0.'], 422);
        }

        return DB::transaction(function () use ($companyId, $creditId) {
            if (!class_exists(\Modules\Finance\Services\Posting\SupplierCreditPostingService::class)) {
                throw new \RuntimeException('SupplierCreditPostingService not found. Please create posting service.');
            }

            $jeId = \Modules\Finance\Services\Posting\SupplierCreditPostingService::post($companyId, (int) $creditId);

            DB::table('finance_supplier_credits')
                ->where('id', (int) $creditId)
                ->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'posted_by' => auth()->id(),
                    'journal_entry_id' => $jeId,
                    'updated_at' => now(),
                ]);

            return response()->json(['message' => 'Supplier credit posted.']);
        });
    }

    public function void($creditId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $cr = DB::table('finance_supplier_credits')
            ->where('company_id', $companyId)
            ->where('id', (int) $creditId)
            ->whereNull('deleted_at')
            ->first();

        if (!$cr) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (($cr->status ?? '') !== 'posted') {
            return response()->json(['message' => 'Only posted credits can be voided.'], 422);
        }

        DB::table('finance_supplier_credits')
            ->where('id', (int) $creditId)
            ->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'Supplier credit voided.']);
    }

    public function suppliers(Request $request)
    {
        $q = trim((string) $request->q);

        $rows = DB::table('suppliers')
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', DB::raw('name as text')]);

        return response()->json(['results' => $rows]);
    }

    public function openBills(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $supplierId = (int) ($request->supplier_id ?? 0);
        $q = trim((string) $request->q);

        $rows = DB::table('finance_supplier_bills as b')
            ->where('b.company_id', $companyId)
            ->whereNull('b.deleted_at')
            ->where('b.status', 'posted')
            ->where('b.balance_due', '>', 0)
            ->when($supplierId > 0, fn ($x) => $x->where('b.supplier_id', $supplierId))
            ->when($q !== '', fn ($x) => $x->where('b.bill_no', 'like', "%{$q}%"))
            ->orderByDesc('b.due_date')
            ->limit(30)
            ->get(['b.id', 'b.bill_no', 'b.due_date', 'b.balance_due', 'b.currency_code'])
            ->map(function ($r) {
                $label = trim(
                    ($r->bill_no ?? ('BILL-' . $r->id))
                    . ' | Due ' . $r->due_date
                    . ' | Bal ' . number_format((float) $r->balance_due, 2)
                    . ' ' . $r->currency_code
                );

                return [
                    'id' => $r->id,
                    'text' => $label,
                    'balance_due' => (float) $r->balance_due,
                ];
            });

        return response()->json(['results' => $rows]);
    }

    public function glAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->q);

        $rows = DB::table('finance_accounts as a')
            ->where('a.company_id', $companyId)
            ->whereNull('a.deleted_at')
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('a.code', 'like', "%{$q}%")
                      ->orWhere('a.name', 'like', "%{$q}%");
                });
            })
            ->orderBy('a.code')
            ->limit(30)
            ->get(['a.id', DB::raw("CONCAT(a.code,' - ',a.name) as text")]);

        return response()->json(['results' => $rows]);
    }

    public function currencies(Request $request)
    {
        $q = trim((string) $request->q);

        $rows = DB::table('currencies')
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->orderBy('code')
            ->limit(30)
            ->get([DB::raw('code as id'), DB::raw("CONCAT(code,' - ',name) as text")]);

        return response()->json(['results' => $rows]);
    }

    public function apControlAccounts(Request $request)
    {
        return $this->glAccounts($request);
    }

    public function taxCodes(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->q);
    
        $rows = DB::table('finance_tax_codes as tc')
            ->leftJoin('finance_tax_rates as tr', 'tr.id', '=', 'tc.rate_id')
            ->where('tc.company_id', $companyId)
            ->whereNull('tc.deleted_at')
            ->where('tc.is_active', 1)
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('tc.code', 'like', "%{$q}%")
                      ->orWhere('tc.name', 'like', "%{$q}%");
                });
            })
            ->orderBy('tc.code')
            ->limit(30)
            ->get([
                'tc.id',
                'tc.code',
                'tc.name',
                'tc.rate_id',
                'tr.code as rate_code',
                'tr.name as rate_name',
                'tr.rate',
            ])
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'text' => trim(($r->code ?? '') . ' - ' . ($r->name ?? '')),
                    'rate_id' => $r->rate_id,
                    'rate' => $r->rate !== null ? (float) $r->rate : null,
                    'rate_text' => $r->rate_id
                        ? trim(($r->rate_code ?? '') . ' - ' . ($r->rate_name ?? '') . ' (' . number_format((float) ($r->rate ?? 0), 2) . '%)')
                        : null,
                ];
            });
    
        return response()->json(['results' => $rows->values()]);
    }

    public function taxRates(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->q);
        $taxType = trim((string) $request->get('tax_type', ''));

        $rows = DB::table('finance_tax_rates')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->when($taxType !== '', fn ($x) => $x->where('tax_type', $taxType))
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->orderBy('code')
            ->limit(30)
            ->get([
                'id',
                'code',
                'name',
                'rate',
                'tax_type',
            ])
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'text' => trim(($r->code ?? '') . ' - ' . ($r->name ?? '') . ' (' . number_format((float) $r->rate, 2) . '%)'),
                    'rate' => (float) $r->rate,
                    'tax_type' => $r->tax_type,
                ];
            });

        return response()->json(['results' => $rows->values()]);
    }

    private function validateCredit(Request $request): array
    {
        $data = Validator::make($request->all(), [
            'credit_no' => ['nullable', 'string', 'max:50'],
            'credit_date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],

            'purchase_order_id' => ['nullable', 'integer'],
            'goods_receipt_id' => ['nullable', 'integer'],
            'supplier_bill_id' => ['nullable', 'integer'],
            'source_type' => ['nullable', 'string', 'max:50'],
            'source_id' => ['nullable', 'integer'],

            'ap_control_account_id' => ['nullable', 'integer', 'exists:finance_accounts,id'],
            'currency_code' => ['nullable', 'string', 'size:3', 'exists:currencies,code'],
            'fx_rate' => ['nullable', 'numeric', 'min:0.000001'],

            'reference' => ['nullable', 'string', 'max:100'],
            'memo' => ['nullable', 'string'],

            'distribution' => ['required', 'array', 'min:1'],
            'distribution.*.description' => ['nullable', 'string', 'max:255'],
            'distribution.*.gl_account_id' => ['required', 'integer', 'exists:finance_accounts,id'],
            'distribution.*.qty' => ['required', 'numeric', 'min:0'],
            'distribution.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'distribution.*.tax_code_id' => ['nullable', 'integer', 'exists:finance_tax_codes,id'],
            'distribution.*.tax_rate_id' => ['nullable', 'integer', 'exists:finance_tax_rates,id'],
            'distribution.*.tax_rate' => ['nullable', 'numeric', 'min:0'],

            'applications' => ['nullable', 'array'],
            'applications.*.bill_id' => ['required', 'integer', 'exists:finance_supplier_bills,id'],
            'applications.*.amount_applied' => ['required', 'numeric', 'min:0.01'],
        ])->validate();

        $data['currency_code'] = !empty($data['currency_code'])
            ? strtoupper(trim($data['currency_code']))
            : null;

        return $data;
    }

    private function computeTotals(array $lines): array
    {
        $subtotal = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($lines as $ln) {
            $qty  = (float) ($ln['qty'] ?? 0);
            $unit = (float) ($ln['unit_cost'] ?? 0);
            $rate = $this->resolveDistributionTaxRate($ln);

            $base = $qty * $unit;
            $t = $base * ($rate / 100);

            $subtotal += $base;
            $tax += $t;
            $total += ($base + $t);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($tax, 2),
            'total_amount' => round($total, 2),
        ];
    }

    private function computeApplied(array $apps): float
    {
        $t = 0.0;
        foreach ($apps as $a) {
            $t += (float) ($a['amount_applied'] ?? 0);
        }
        return round($t, 2);
    }

    private function syncDistribution(int $creditId, array $lines): void
    {
        DB::table('finance_supplier_credit_lines')
            ->where('supplier_credit_id', $creditId)
            ->delete();

        $rows = [];

        foreach ($lines as $ln) {
            $qty  = (float) ($ln['qty'] ?? 0);
            $unit = (float) ($ln['unit_cost'] ?? 0);
            $rate = $this->resolveDistributionTaxRate($ln);

            $base = $qty * $unit;
            $tax  = $base * ($rate / 100);
            $lineTotal = $base + $tax;

            $rows[] = [
                'supplier_credit_id' => $creditId,
                'description' => $ln['description'] ?? null,
                'gl_account_id' => (int) $ln['gl_account_id'],
                'qty' => $qty,
                'unit_cost' => $unit,
                'tax_code_id' => $ln['tax_code_id'] ?? null,
                'tax_rate_id' => $ln['tax_rate_id'] ?? null,
                'tax_rate' => $rate,
                'tax_amount' => round($tax, 2),
                'line_total' => round($lineTotal, 2),
                'memo' => $ln['memo'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            DB::table('finance_supplier_credit_lines')->insert($rows);
        }
    }

    private function syncApplications(int $creditId, array $apps, int $supplierId, int $companyId): void
    {
        foreach ($apps as $a) {
            $bill = DB::table('finance_supplier_bills')
                ->where('company_id', $companyId)
                ->where('supplier_id', $supplierId)
                ->where('id', (int) $a['bill_id'])
                ->whereNull('deleted_at')
                ->first();

            if (!$bill) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'applications' => 'Invalid bill selection (must belong to supplier).',
                ]);
            }
        }

        DB::table('finance_supplier_credit_applications')
            ->where('supplier_credit_id', $creditId)
            ->delete();

        $rows = [];
        foreach ($apps as $a) {
            $rows[] = [
                'supplier_credit_id' => $creditId,
                'bill_id' => (int) $a['bill_id'],
                'amount_applied' => round((float) $a['amount_applied'], 2),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            DB::table('finance_supplier_credit_applications')->insert($rows);
        }
    }

    private function resolveDistributionTaxRate(array $ln): float
    {
        if (!empty($ln['tax_rate_id'])) {
            $rate = DB::table('finance_tax_rates')
                ->where('id', (int) $ln['tax_rate_id'])
                ->value('rate');

            if ($rate !== null) {
                return (float) $rate;
            }
        }

        return (float) ($ln['tax_rate'] ?? 0);
    }
}