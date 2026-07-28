<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpensesController extends Controller
{
    public function index()
    {
        return view('finance.expenses.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $q = DB::table('finance_expenses as e')
            ->leftJoin('finance_expense_categories as c', 'c.id', '=', 'e.category_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'e.supplier_id')
            ->leftJoin('finance_bank_accounts as b', 'b.id', '=', 'e.bank_account_id')
            ->where('e.company_id', $companyId)
            ->whereNull('e.deleted_at')
            ->select([
                'e.id','e.expense_no','e.expense_date','e.vendor_name','e.supplier_id',
                'e.reference','e.currency_code','e.payment_mode','e.total_amount','e.status',
                'c.name as category_name',
                's.name as supplier_name',
                'b.name as bank_account_name',
            ]);

        if ($request->filled('status')) $q->where('e.status', $request->status);
        if ($request->filled('payment_mode')) $q->where('e.payment_mode', $request->payment_mode);
        if ($request->filled('date_from')) $q->where('e.expense_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $q->where('e.expense_date', '<=', $request->date_to);

        if ($request->filled('q')) {
            $term = trim((string)$request->q);
            $q->where(function($x) use ($term){
                $x->where('e.expense_no','like',"%{$term}%")
                  ->orWhere('e.reference','like',"%{$term}%")
                  ->orWhere('e.vendor_name','like',"%{$term}%")
                  ->orWhere('c.name','like',"%{$term}%")
                  ->orWhere('s.name','like',"%{$term}%");
            });
        }

        $baseCount = DB::table('finance_expenses as e')
            ->where('e.company_id', $companyId)
            ->whereNull('e.deleted_at')
            ->count();

        $recordsFiltered = (clone $q)->count();

        $start  = (int)($request->start ?? 0);
        $length = (int)($request->length ?? 10);
        $draw   = (int)($request->draw ?? 1);

        $orderColIndex = $request->input('order.0.column');
        $orderDir      = $request->input('order.0.dir', 'desc');

        $columns = [
            0 => 'e.id',
            1 => 'e.expense_date',
            2 => 'e.expense_no',
            3 => 'c.name',
            4 => 'e.vendor_name',
            5 => 'e.currency_code',
            6 => 'e.total_amount',
        ];

        if ($orderColIndex !== null && isset($columns[(int)$orderColIndex])) {
            $q->orderBy($columns[(int)$orderColIndex], $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $q->orderBy('e.id','desc');
        }

        $rows = $q->offset($start)->limit($length)->get();

        $data = $rows->map(function($r){
            $badge = match($r->status){
                'posted' => 'success',
                'voided' => 'secondary',
                default  => 'warning',
            };

            $json = [
                'id' => $r->id,
                'status' => $r->status,
            ];

            return [
                'id' => $r->id,
                'expense_date' => e($r->expense_date),
                'expense_no' => e($r->expense_no ?? ('EXP-'.$r->id)),
                'category' => e($r->category_name ?? '—'),
                'vendor' => e($r->supplier_name ?? $r->vendor_name ?? '—'),
                'currency' => e($r->currency_code ?? ''),
                'total' => number_format((float)$r->total_amount, 2),
                'status' => '<span class="badge bg-'.$badge.'">'.strtoupper(e($r->status)).'</span>',
                'actions' => view('finance.expenses.partials.actions', ['json'=>$json, 'row'=>$r])->render(),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $baseCount,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function show($expenseId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $e = DB::table('finance_expenses')
            ->where('company_id',$companyId)
            ->where('id',(int)$expenseId)
            ->whereNull('deleted_at')
            ->first();

        if (!$e) {
            return response()->json(['message'=>'Not found'], 404);
        }

        $category = DB::table('finance_expense_categories')->where('id',$e->category_id)->first(['id','name']);
        $supplier = $e->supplier_id ? DB::table('suppliers')->where('id',$e->supplier_id)->first(['id','name']) : null;

        $bank = $e->bank_account_id
            ? DB::table('finance_bank_accounts')->where('id',$e->bank_account_id)->first(['id','name'])
            : null;

        $payable = $e->payable_account_id
            ? DB::table('finance_accounts')->where('id',$e->payable_account_id)->first(['id','code','name'])
            : null;

        $lines = DB::table('finance_expense_lines as l')
            ->leftJoin('finance_accounts as a','a.id','=','l.gl_account_id')
            ->leftJoin('finance_tax_codes as tc','tc.id','=','l.tax_code_id')
            ->where('l.expense_id',$e->id)
            ->orderBy('l.id')
            ->get([
                'l.id','l.description','l.gl_account_id','l.qty','l.unit_cost',
                'l.tax_code_id','l.tax_rate_id','l.tax_rate','l.tax_amount','l.line_total','l.memo',
                'a.code as gl_code','a.name as gl_name',
                'tc.code as tax_code_code','tc.name as tax_code_name',
            ])
            ->map(fn($ln)=>[
                'id' => $ln->id,
                'description' => $ln->description,
                'gl_account_id' => $ln->gl_account_id,
                'gl_account_label' => $ln->gl_account_id ? trim(($ln->gl_code ?? '').' - '.($ln->gl_name ?? '')) : null,
                'qty' => (float)$ln->qty,
                'unit_cost' => (float)$ln->unit_cost,
                'tax_code_id' => $ln->tax_code_id,
                'tax_rate_id' => $ln->tax_rate_id,
                'tax_code_label' => $ln->tax_code_id ? trim(($ln->tax_code_code ?? '').' - '.($ln->tax_code_name ?? '')) : null,
                'tax_rate' => $ln->tax_rate !== null ? (float)$ln->tax_rate : null,
                'tax_amount' => (float)$ln->tax_amount,
                'line_total' => (float)$ln->line_total,
                'memo' => $ln->memo,
            ])->values();

        return response()->json([
            'expense' => [
                'id' => $e->id,
                'expense_no' => $e->expense_no,
                'expense_date' => $e->expense_date,
                'category_id' => $e->category_id,
                'category_label' => $category?->name,
                'supplier_id' => $e->supplier_id,
                'supplier_label' => $supplier?->name,
                'vendor_name' => $e->vendor_name,
                'reference' => $e->reference,
                'memo' => $e->memo,
                'currency_code' => $e->currency_code,
                'fx_rate' => $e->fx_rate,
                'payment_mode' => $e->payment_mode,
                'bank_account_id' => $e->bank_account_id,
                'bank_account_label' => $bank?->name,
                'payable_account_id' => $e->payable_account_id,
                'payable_account_label' => $payable ? ($payable->code.' - '.$payable->name) : null,
                'subtotal' => (float)$e->subtotal,
                'tax_total' => (float)$e->tax_total,
                'total_amount' => (float)$e->total_amount,
                'status' => $e->status,
            ],
            'lines' => $lines,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateExpense($request, $companyId);

        return DB::transaction(function() use ($companyId, $data){

            $id = DB::table('finance_expenses')->insertGetId(array_merge($data['header'], [
                'company_id' => $companyId,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            if (!empty($data['lines'])) {
                $lines = array_map(function($ln) use ($id){
                    $ln['expense_id'] = $id;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);

                DB::table('finance_expense_lines')->insert($lines);
            }

            $this->recalcTotals($id);

            return response()->json(['message'=>'Expense created.','id'=>$id]);
        });
    }

    public function update(Request $request, $expenseId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('finance_expenses')
            ->where('company_id',$companyId)
            ->where('id',(int)$expenseId)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) return response()->json(['message'=>'Not found.'], 404);
        if ($row->status !== 'draft') return response()->json(['message'=>'Only draft expenses can be edited.'], 422);

        $data = $this->validateExpense($request, $companyId);

        return DB::transaction(function() use ($expenseId, $data){

            DB::table('finance_expenses')
                ->where('id',(int)$expenseId)
                ->update(array_merge($data['header'], [
                    'updated_at' => now(),
                ]));

            DB::table('finance_expense_lines')->where('expense_id',(int)$expenseId)->delete();

            if (!empty($data['lines'])) {
                $lines = array_map(function($ln) use ($expenseId){
                    $ln['expense_id'] = (int)$expenseId;
                    $ln['created_at'] = now();
                    $ln['updated_at'] = now();
                    return $ln;
                }, $data['lines']);

                DB::table('finance_expense_lines')->insert($lines);
            }

            $this->recalcTotals((int)$expenseId);

            return response()->json(['message'=>'Expense updated.']);
        });
    }

    public function destroy($expenseId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('finance_expenses')
            ->where('company_id',$companyId)
            ->where('id',(int)$expenseId)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) return response()->json(['message'=>'Not found.'], 404);
        if ($row->status !== 'draft') return response()->json(['message'=>'Only draft expenses can be deleted.'], 422);

        DB::transaction(function() use ($expenseId){
            DB::table('finance_expense_lines')->where('expense_id',(int)$expenseId)->delete();
            DB::table('finance_expenses')->where('id',(int)$expenseId)->update([
                'deleted_at'=>now(),
                'updated_at'=>now(),
            ]);
        });

        return response()->json(['message'=>'Deleted.']);
    }

    public function post($expenseId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $e = DB::table('finance_expenses')
            ->where('company_id',$companyId)
            ->where('id',(int)$expenseId)
            ->whereNull('deleted_at')
            ->first();

        if (!$e) return response()->json(['message'=>'Not found.'], 404);
        if ($e->status !== 'draft') return response()->json(['message'=>'Only draft can be posted.'], 422);

        if ($e->payment_mode === 'bank' && empty($e->bank_account_id)) {
            return response()->json(['message'=>'Bank account is required for bank payments.'], 422);
        }

        if ($e->payment_mode === 'credit' && empty($e->payable_account_id)) {
            return response()->json(['message'=>'Payable/Control account is required for credit expenses.'], 422);
        }

        return DB::transaction(function() use ($companyId, $expenseId, $e){

            $jeId = DB::table('finance_journal_entries')->insertGetId([
                'company_id' => $companyId,
                'period_id' => null,
                'entry_no' => null,
                'entry_date' => $e->expense_date,
                'reference' => $e->reference ?? ($e->expense_no ?? ('EXP-'.$e->id)),
                'memo' => $e->memo ?? 'Expense Posting',
                'status' => 'posted',
                'source_type' => 'expense',
                'source_id' => (int)$expenseId,
                'posted_at' => now(),
                'posted_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $creditAccountId = match($e->payment_mode) {
                'bank', 'cash' => $this->resolveBankGLAccountId($companyId, (int)$e->bank_account_id),
                'credit'       => (int)$e->payable_account_id,
                default        => null,
            };

            if (!$creditAccountId) {
                throw new \RuntimeException('Could not resolve credit account.');
            }

            $currency = $e->currency_code ?? null;
            $fx = $e->fx_rate ?? null;

            $expenseLines = DB::table('finance_expense_lines')
                ->where('expense_id',(int)$expenseId)
                ->get();

            if ($expenseLines->count() < 1) {
                throw new \RuntimeException('Expense must have at least one line.');
            }

            $lines = [];
            $debitTotal = 0.0;

            foreach ($expenseLines as $ln) {
                $baseAmt = round(((float)$ln->qty * (float)$ln->unit_cost), 2);

                if ($baseAmt <= 0) {
                    continue;
                }

                $debitTotal += $baseAmt;

                $lines[] = [
                    'journal_entry_id' => $jeId,
                    'account_id' => (int)$ln->gl_account_id,
                    'description' => $ln->description ?? 'Expense line',
                    'debit' => $baseAmt,
                    'credit' => 0,
                    'memo' => $ln->memo,
                    'currency_code' => $currency,
                    'fx_rate' => $fx,
                    'party_type' => null,
                    'party_id' => null,
                    'bank_account_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($debitTotal <= 0) {
                throw new \RuntimeException('Expense total must be > 0.');
            }

            $totalTax = (float) DB::table('finance_expense_lines')
                ->where('expense_id', (int)$expenseId)
                ->sum('tax_amount');

            if ($totalTax > 0) {
                $map = DB::table('finance_account_mappings')
                    ->where('company_id', $companyId)
                    ->first(['vat_input_account_id']);

                if (empty($map?->vat_input_account_id)) {
                    throw new \RuntimeException('VAT input account is not configured in finance account mappings.');
                }

                $debitTotal += $totalTax;

                $lines[] = [
                    'journal_entry_id' => $jeId,
                    'account_id' => (int)$map->vat_input_account_id,
                    'description' => 'Input tax on expense',
                    'debit' => $totalTax,
                    'credit' => 0,
                    'memo' => $e->memo,
                    'currency_code' => $currency,
                    'fx_rate' => $fx,
                    'party_type' => null,
                    'party_id' => null,
                    'bank_account_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $lines[] = [
                'journal_entry_id' => $jeId,
                'account_id' => (int)$creditAccountId,
                'description' => 'Expense payment',
                'debit' => 0,
                'credit' => $debitTotal,
                'memo' => $e->memo,
                'currency_code' => $currency,
                'fx_rate' => $fx,
                'party_type' => $e->supplier_id ? 'supplier' : null,
                'party_id' => $e->supplier_id ? (int)$e->supplier_id : null,
                'bank_account_id' => in_array($e->payment_mode, ['bank','cash']) ? (int)$e->bank_account_id : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('finance_journal_entry_lines')->insert($lines);

            DB::table('finance_expenses')
                ->where('id',(int)$expenseId)
                ->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'posted_by' => auth()->id(),
                    'journal_entry_id' => $jeId,
                    'updated_at' => now(),
                ]);

            return response()->json(['message'=>'Expense posted.']);
        });
    }

    public function void($expenseId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $e = DB::table('finance_expenses')
            ->where('company_id',$companyId)
            ->where('id',(int)$expenseId)
            ->whereNull('deleted_at')
            ->first();

        if (!$e) return response()->json(['message'=>'Not found.'], 404);
        if ($e->status !== 'posted') return response()->json(['message'=>'Only posted expenses can be voided.'], 422);

        return DB::transaction(function() use ($expenseId, $e){

            DB::table('finance_expenses')
                ->where('id',(int)$expenseId)
                ->update([
                    'status' => 'voided',
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            if (!empty($e->journal_entry_id)) {
                DB::table('finance_journal_entries')
                    ->where('id',(int)$e->journal_entry_id)
                    ->update([
                        'status'=>'voided',
                        'updated_at'=>now(),
                    ]);
            }

            return response()->json(['message'=>'Expense voided.']);
        });
    }

    /** ===== Lookups for Select2 ===== */

    public function select2Suppliers(Request $request)
    {
        $q = trim((string)$request->get('q',''));

        $rows = DB::table('suppliers')
            ->when($q !== '', function($x) use ($q){
                $x->where(function($w) use ($q){
                    $w->where('name','like',"%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id','name']);

        return response()->json([
            'results' => $rows->map(fn($r)=>['id'=>$r->id,'text'=>$r->name])->values(),
        ]);
    }

    public function select2Categories(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q',''));

        $rows = DB::table('finance_expense_categories')
            ->where('company_id',$companyId)
            ->where('is_active',1)
            ->when($q !== '', fn($x)=>$x->where('name','like',"%{$q}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id','name']);

        return response()->json([
            'results' => $rows->map(fn($r)=>['id'=>$r->id,'text'=>$r->name])->values(),
        ]);
    }

    public function select2Currencies(Request $request)
    {
        $q = trim((string)$request->get('q',''));

        $rows = DB::table('currencies')
            ->where('is_active',1)
            ->when($q !== '', function($x) use ($q){
                $x->where(function($w) use ($q){
                    $w->where('code','like',"%{$q}%")->orWhere('name','like',"%{$q}%");
                });
            })
            ->orderBy('code')
            ->limit(50)
            ->get(['code','name']);

        return response()->json([
            'results' => $rows->map(fn($r)=>['id'=>$r->code,'text'=>$r->code.' - '.$r->name])->values(),
        ]);
    }

    public function select2BankAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q',''));

        $rows = DB::table('finance_bank_accounts')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->where('is_active',1)
            ->when($q !== '', fn($x)=>$x->where('name','like',"%{$q}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id','name','type','currency_code']);

        return response()->json([
            'results' => $rows->map(fn($r)=>[
                'id'=>$r->id,
                'text'=>$r->name.' ('.$r->type.')'.($r->currency_code?(' - '.$r->currency_code):''),
            ])->values(),
        ]);
    }

    public function select2PayableAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q',''));

        $rows = DB::table('finance_accounts')
            ->where('company_id',$companyId)
            ->where('is_active',1)
            ->when($q !== '', function($x) use ($q){
                $x->where(function($w) use ($q){
                    $w->where('code','like',"%{$q}%")->orWhere('name','like',"%{$q}%");
                });
            })
            ->orderBy('code')
            ->limit(50)
            ->get(['id','code','name']);

        return response()->json([
            'results' => $rows->map(fn($r)=>['id'=>$r->id,'text'=>$r->code.' - '.$r->name])->values(),
        ]);
    }

    public function select2GLAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q',''));

        $rows = DB::table('finance_accounts')
            ->where('company_id',$companyId)
            ->where('is_active',1)
            ->when($q !== '', function($x) use ($q){
                $x->where(function($w) use ($q){
                    $w->where('code','like',"%{$q}%")->orWhere('name','like',"%{$q}%");
                });
            })
            ->orderBy('code')
            ->limit(50)
            ->get(['id','code','name']);

        return response()->json([
            'results' => $rows->map(fn($r)=>['id'=>$r->id,'text'=>$r->code.' - '.$r->name])->values(),
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
            ->whereNull('tc.deleted_at')
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('tc.name', 'like', "%{$q}%")
                      ->orWhere('tc.code', 'like', "%{$q}%");
                });
            })
            ->orderBy('tc.code')
            ->limit(50)
            ->get([
                'tc.id',
                'tc.name',
                'tc.code',
                'tc.rate_id',
                'tc.is_reverse_charge',
                'tc.is_exempt',
                'tc.is_out_of_scope',
                'tr.rate',
                'tr.name as rate_name',
            ]);

        return response()->json([
            'results' => $rows->map(function ($r) {
                $rate = $r->rate !== null ? (float)$r->rate : 0;
                return [
                    'id' => $r->id,
                    'text' => $r->code . ' - ' . $r->name . ' (' . number_format($rate, 2) . '%)',
                    'rate_id' => $r->rate_id,
                    'rate' => $rate,
                    'is_reverse_charge' => (int)$r->is_reverse_charge,
                    'is_exempt' => (int)$r->is_exempt,
                    'is_out_of_scope' => (int)$r->is_out_of_scope,
                ];
            })->values(),
        ]);
    }

    /** ===== Helpers ===== */

    private function validateExpense(Request $request, int $companyId): array
    {
        $v = Validator::make($request->all(), [
            'expense_no' => ['nullable','string','max:50'],
            'expense_date' => ['required','date'],
            'category_id' => ['required','integer','exists:finance_expense_categories,id'],
            'supplier_id' => ['nullable','integer','exists:suppliers,id'],
            'vendor_name' => ['nullable','string','max:255'],
            'reference' => ['nullable','string','max:100'],
            'memo' => ['nullable','string'],
            'currency_code' => ['nullable','string','size:3'],
            'fx_rate' => ['nullable','numeric'],
            'payment_mode' => ['required','in:cash,bank,credit'],
            'bank_account_id' => ['nullable','integer','exists:finance_bank_accounts,id'],
            'payable_account_id' => ['nullable','integer','exists:finance_accounts,id'],

            'lines' => ['required','array','min:1'],
            'lines.*.description' => ['nullable','string','max:255'],
            'lines.*.gl_account_id' => ['required','integer','exists:finance_accounts,id'],
            'lines.*.qty' => ['required','numeric','min:0.0001'],
            'lines.*.unit_cost' => ['required','numeric','min:0'],
            'lines.*.tax_code_id' => ['nullable','integer','exists:finance_tax_codes,id'],
            'lines.*.tax_rate' => ['nullable','numeric','min:0'],
            'lines.*.memo' => ['nullable','string','max:255'],
        ])->validate();

        $supplierId = !empty($v['supplier_id']) ? (int)$v['supplier_id'] : null;
        $vendorName = $v['vendor_name'] ?? null;

        if ($supplierId && empty($vendorName)) {
            $s = DB::table('suppliers')->where('id',$supplierId)->first(['name']);
            $vendorName = $s?->name;
        }

        $header = [
            'expense_no' => $v['expense_no'] ?? null,
            'expense_date' => $v['expense_date'],
            'category_id' => (int)$v['category_id'],
            'supplier_id' => $supplierId,
            'vendor_name' => $vendorName,
            'reference' => $v['reference'] ?? null,
            'memo' => $v['memo'] ?? null,
            'currency_code' => !empty($v['currency_code']) ? strtoupper(trim($v['currency_code'])) : null,
            'fx_rate' => $v['fx_rate'] ?? null,
            'payment_mode' => $v['payment_mode'],
            'bank_account_id' => !empty($v['bank_account_id']) ? (int)$v['bank_account_id'] : null,
            'payable_account_id' => !empty($v['payable_account_id']) ? (int)$v['payable_account_id'] : null,
        ];

        $lines = [];
        foreach (($v['lines'] ?? []) as $ln) {
            $qty = (float)$ln['qty'];
            $unit = (float)$ln['unit_cost'];
            $base = $qty * $unit;

            $taxCodeId = !empty($ln['tax_code_id']) ? (int)$ln['tax_code_id'] : null;
            $taxRateId = null;
            $taxRate = 0.0;

            if ($taxCodeId) {
                $taxCode = DB::table('finance_tax_codes as tc')
                    ->leftJoin('finance_tax_rates as tr', 'tr.id', '=', 'tc.rate_id')
                    ->where('tc.company_id', $companyId)
                    ->where('tc.id', $taxCodeId)
                    ->select([
                        'tc.id',
                        'tc.rate_id',
                        'tc.is_reverse_charge',
                        'tc.is_exempt',
                        'tc.is_out_of_scope',
                        'tr.rate',
                    ])
                    ->first();

                if (!$taxCode) {
                    throw new \RuntimeException('Invalid tax code selected.');
                }

                $taxRateId = $taxCode->rate_id ? (int)$taxCode->rate_id : null;

                if ($taxCode->is_exempt || $taxCode->is_out_of_scope) {
                    $taxRate = 0.0;
                } else {
                    $taxRate = (float)($taxCode->rate ?? 0);
                }
            } else {
                $taxRate = isset($ln['tax_rate']) && $ln['tax_rate'] !== '' ? (float)$ln['tax_rate'] : 0.0;
            }

            $taxAmt = ($taxRate > 0) ? round(($base * $taxRate / 100), 2) : 0.0;
            $total = round(($base + $taxAmt), 2);

            $lines[] = [
                'description' => $ln['description'] ?? null,
                'gl_account_id' => (int)$ln['gl_account_id'],
                'qty' => $qty,
                'unit_cost' => $unit,
                'tax_code_id' => $taxCodeId,
                'tax_rate_id' => $taxRateId,
                'tax_rate' => $taxRate > 0 ? $taxRate : null,
                'tax_amount' => $taxAmt,
                'line_total' => $total,
                'memo' => $ln['memo'] ?? null,
            ];
        }

        return ['header'=>$header, 'lines'=>$lines];
    }

    private function recalcTotals(int $expenseId): void
    {
        $rows = DB::table('finance_expense_lines')->where('expense_id',$expenseId)->get(['qty','unit_cost','tax_amount','line_total']);

        $subtotal = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($rows as $r) {
            $subtotal += round(((float)$r->qty * (float)$r->unit_cost), 2);
            $tax += (float)$r->tax_amount;
            $total += (float)$r->line_total;
        }

        DB::table('finance_expenses')->where('id',$expenseId)->update([
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($tax, 2),
            'total_amount' => round($total, 2),
            'updated_at' => now(),
        ]);
    }

    private function resolveBankGLAccountId(int $companyId, int $bankAccountId): ?int
    {
        $b = DB::table('finance_bank_accounts')
            ->where('company_id',$companyId)
            ->where('id',$bankAccountId)
            ->first(['gl_account_id']);

        if (!empty($b?->gl_account_id)) {
            return (int)$b->gl_account_id;
        }

        $map = DB::table('finance_account_mappings')
            ->where('company_id',$companyId)
            ->first(['default_bank_gl_account_id']);

        return !empty($map?->default_bank_gl_account_id) ? (int)$map->default_bank_gl_account_id : null;
    }
}