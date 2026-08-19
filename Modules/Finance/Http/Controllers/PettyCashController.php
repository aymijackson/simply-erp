<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Modules\Finance\Models\PettyCashAccount;
use Modules\Finance\Models\PettyCashTransaction;
use Modules\Finance\Models\PettyCashReconciliation;
use Modules\Finance\Models\PettyCashAudit;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceJournalEntryLine;
use App\Models\Supplier;

class PettyCashController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('finance.petty_cash.view');

        if ($request->ajax()) {
            $query = PettyCashTransaction::with([
                'account',
                'expenseAccount',
                'employeePayee',
                'supplierPayee',
                'customerPayee',
            ])->when(Auth::user()->company_id ?? 1, fn($q) => $q->where('company_id', Auth::user()->company_id ?? 1));

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('petty_cash_account_id')) {
                $query->where('petty_cash_account_id', $request->petty_cash_account_id);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('transaction_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('transaction_date', '<=', $request->to_date);
            }

            if ($request->filled('q')) {
                $q = trim($request->q);
                $query->where(function ($sub) use ($q) {
                    $sub->where('transaction_no', 'like', "%{$q}%")
                        ->orWhere('voucher_no', 'like', "%{$q}%")
                        ->orWhere('reference_no', 'like', "%{$q}%")
                        ->orWhere('payee', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            }

            return DataTables::of($query)
                ->addColumn('account_name', fn($row) => $row->account->name ?? '-')
                ->addColumn('expense_account_name', fn($row) => $row->expenseAccount->name ?? '-')
                ->addColumn('payee_name', fn($row) => $row->payee_display ?? '-')
                ->addColumn('balance_hint', function ($row) {
                    $acc = $row->account;
                    if (!$acc) {
                        return '-';
                    }

                    if ((float) $acc->current_balance <= (float) $acc->minimum_balance) {
                        return '<span class="badge bg-warning text-dark">Low Balance</span>';
                    }

                    return '<span class="badge bg-success">OK</span>';
                })
                ->addColumn('actions', function ($row) {
                    return view('finance.petty_cash.partials.actions', compact('row'))->render();
                })
                ->editColumn('amount', fn($row) => number_format($row->amount, 2))
                ->editColumn('transaction_date', fn($row) => optional($row->transaction_date)->format('Y-m-d'))
                ->editColumn('status', function ($row) {
                    $map = [
                        'draft' => 'secondary',
                        'pending' => 'warning',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'posted' => 'success',
                        'cancelled' => 'dark',
                    ];
                    $cls = $map[$row->status] ?? 'secondary';
                    return '<span class="badge bg-' . $cls . '">' . ucfirst($row->status) . '</span>';
                })
                ->rawColumns(['actions', 'status', 'balance_hint'])
                ->make(true);
        }

        $expenseAccounts = FinanceAccount::orderBy('name')->get();
        $summary = $this->summaryData();

        return view('finance.petty_cash.index', compact('expenseAccounts', 'summary'));
    }

    protected function summaryData(): array
    {
        $companyId = Auth::user()->company_id ?? 1;

        $accounts = PettyCashAccount::when($companyId, fn($q) => $q->where('company_id', $companyId));
        $transactions = PettyCashTransaction::when($companyId, fn($q) => $q->where('company_id', $companyId));

        return [
            'active_accounts' => (clone $accounts)->where('status', 'active')->count(),
            'total_balance' => (clone $accounts)->sum('current_balance'),
            'pending_transactions' => (clone $transactions)->where('status', 'pending')->count(),
            'month_expenses' => (clone $transactions)
                ->where('type', 'expense')
                ->where('status', 'posted')
                ->whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->sum('amount'),
            'low_balance_accounts' => (clone $accounts)
                ->whereRaw('current_balance <= minimum_balance')
                ->count(),
        ];
    }

    public function accounts(Request $request)
    {
        $this->authorize('finance.petty_cash.view');

        if ($request->ajax()) {
            $query = PettyCashAccount::with(['custodian', 'location', 'cashGlAccount'])
                ->when(Auth::user()->company_id ?? 1, fn($q) => $q->where('company_id', Auth::user()->company_id ?? 1));

            return DataTables::of($query)
            ->addColumn('custodian_name', function ($row) {
                return trim((optional($row->custodian)->first_name ?? '') . ' ' . (optional($row->custodian)->last_name ?? '')) ?: '-';
            })
            ->addColumn('cash_gl_name', fn($row) => $row->cashGlAccount->name ?? '-')
            ->editColumn('float_amount', fn($row) => number_format($row->float_amount, 2))
            ->editColumn('minimum_balance', fn($row) => number_format($row->minimum_balance, 2))
            ->editColumn('current_balance', function ($row) {
                $class = ((float)$row->current_balance <= (float)$row->minimum_balance) ? 'text-danger fw-bold' : 'text-success fw-bold';
                return '<span class="' . $class . '">' . number_format($row->current_balance, 2) . '</span>';
            })
            ->editColumn('status', function ($row) {
                $map = [
                    'draft' => 'secondary',
                    'active' => 'success',
                    'inactive' => 'warning',
                    'closed' => 'dark',
                ];
                $cls = $map[$row->status] ?? 'secondary';
                return '<span class="badge bg-' . $cls . '">' . ucfirst($row->status) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                return view('finance.petty_cash.partials.account_actions', compact('row'))->render();
            })
            ->rawColumns(['current_balance', 'status', 'actions'])
            ->make(true);
        }

        $glAccounts = FinanceAccount::orderBy('name')->get();

        return view('finance.petty_cash.accounts', compact('glAccounts'));
    }

    public function accountsSelect2(Request $request)
    {
        $this->authorize('finance.petty_cash.view');

        $term = trim($request->get('q', ''));

        $query = PettyCashAccount::query()
            ->when(Auth::user()->company_id ?? 1, fn($q) => $q->where('company_id', Auth::user()->company_id ?? 1))
            ->where('status', 'active');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('account_code', 'like', "%{$term}%");
            });
        }

        $rows = $query->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'account_code', 'current_balance']);

        $results = $rows->map(function ($row) {
            return [
                'id' => $row->id,
                'text' => $row->account_code . ' - ' . $row->name . ' (Bal: ' . number_format((float) $row->current_balance, 2) . ')',
            ];
        });

        return response()->json([
            'results' => $results->values(),
        ]);
    }

    public function payeesSelect2(Request $request)
    {
        $this->authorize('finance.petty_cash.view');

        $type = $request->get('type');
        $term = trim($request->get('q', ''));

        $results = collect();

        if ($type === 'employee') {
            $rows = \Modules\HRM\Models\Employee::query()
                ->when($term !== '', function ($q) use ($term) {
                    $q->where(function ($sub) use ($term) {
                        $sub->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
                    });
                })
                ->orderBy('first_name')
                ->limit(30)
                ->get();

            $results = $rows->map(function ($row) {
                $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                return [
                    'id' => $row->id,
                    'text' => $name ?: ('Employee #' . $row->id),
                ];
            });
        }

        if ($type === 'supplier') {
            $rows = Supplier::query()
                ->when(Auth::user()->company_id ?? 1, fn($q) => $q->where('company_id', Auth::user()->company_id ?? 1))
                ->when($term !== '', fn($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'name']);

            $results = $rows->map(fn($row) => [
                'id' => $row->id,
                'text' => $row->name,
            ]);
        }

        if ($type === 'customer') {
            $rows = \Modules\CRM\Models\Customer::query()
                ->when(Auth::user()->company_id ?? 1, fn($q) => $q->where('company_id', Auth::user()->company_id ?? 1))
                ->when($term !== '', fn($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'name']);

            $results = $rows->map(fn($row) => [
                'id' => $row->id,
                'text' => $row->name,
            ]);
        }

        return response()->json([
            'results' => $results->values(),
        ]);
    }

    public function showAccount($id, Request $request)
    {
        $this->authorize('finance.petty_cash.view');
    
        $account = PettyCashAccount::with(['custodian', 'location', 'cashGlAccount', 'clearingGlAccount'])
            ->findOrFail($id);
    
        $expenseAccounts = FinanceAccount::orderBy('name')->get();
        
        $totalFunded = (float) PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->whereIn('type', ['funding'])
            ->where('status', 'posted')
            ->sum('amount');

        $totalReplenished = (float) PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->where('type', 'replenishment')
            ->where('status', 'posted')
            ->sum('amount');
        
        $totalSpent = (float) PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->where('type', 'expense')
            ->where('status', 'posted')
            ->sum('amount');
        
        $totalRefunded = (float) PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->where('type', 'refund')
            ->where('status', 'posted')
            ->sum('amount');
        
        $pendingAmount = (float) PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->where('status', 'pending')
            ->sum('amount');
        
        $postedAmount = (float) PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->where('status', 'posted')
            ->sum('amount');
        
        $lastReplenishment = PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->whereIn('type', ['funding', 'replenishment'])
            ->where('status', 'posted')
            ->latest('transaction_date')
            ->first();
        
        $analytics = [
            'total_funded' => $totalFunded,
            'total_replenished' => $totalReplenished,
            'total_spent' => $totalSpent,
            'total_refunded' => $totalRefunded,
            'pending_amount' => $pendingAmount,
            'posted_amount' => $postedAmount,
            'last_replenishment_date' => optional($lastReplenishment?->transaction_date)->format('Y-m-d'),
            'last_replenishment_amount' => $lastReplenishment ? (float) $lastReplenishment->amount : 0,
        ];

        if ($request->ajax()) {
            $query = PettyCashTransaction::with([
                    'expenseAccount',
                    'employeePayee',
                    'supplierPayee',
                    'customerPayee',
                ])
                ->where('petty_cash_account_id', $account->id);
    
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
    
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
    
            if ($request->filled('from_date')) {
                $query->whereDate('transaction_date', '>=', $request->from_date);
            }
    
            if ($request->filled('to_date')) {
                $query->whereDate('transaction_date', '<=', $request->to_date);
            }
    
            if ($request->filled('q')) {
                $q = trim($request->q);
                $query->where(function ($sub) use ($q) {
                    $sub->where('transaction_no', 'like', "%{$q}%")
                        ->orWhere('voucher_no', 'like', "%{$q}%")
                        ->orWhere('reference_no', 'like', "%{$q}%")
                        ->orWhere('payee', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            }
    
            return DataTables::of($query)
                ->addColumn('payee_name', fn($row) => $row->payee_display ?? '-')
                ->addColumn('expense_account_name', fn($row) => $row->expenseAccount->name ?? '-')
                ->editColumn('amount', fn($row) => number_format($row->amount, 2))
                ->editColumn('transaction_date', fn($row) => optional($row->transaction_date)->format('Y-m-d'))
                ->editColumn('status', function ($row) {
                    $map = [
                        'draft' => 'secondary',
                        'pending' => 'warning',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'posted' => 'success',
                        'cancelled' => 'dark',
                    ];
                    $cls = $map[$row->status] ?? 'secondary';
                    return '<span class="badge bg-' . $cls . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    return view('finance.petty_cash.partials.actions', compact('row'))->render();
                })
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }
    
        $transactionCount = PettyCashTransaction::where('petty_cash_account_id', $account->id)->count();
        $reconciliationCount = PettyCashReconciliation::where('petty_cash_account_id', $account->id)->count();
    
        $recentReconciliations = PettyCashReconciliation::where('petty_cash_account_id', $account->id)
            ->latest('reconciliation_date')
            ->limit(10)
            ->get();
    
        $suggestedReplenishment = max(0, (float)$account->float_amount - (float)$account->current_balance);
        $isLowBalance = (float)$account->current_balance <= (float)$account->minimum_balance;
        
        $monthly = PettyCashTransaction::selectRaw("
                DATE_FORMAT(transaction_date, '%Y-%m') as month,
                SUM(CASE WHEN type = 'expense' AND status = 'posted' THEN amount ELSE 0 END) as spent,
                SUM(CASE WHEN type = 'replenishment' AND status = 'posted' THEN amount ELSE 0 END) as replenished,
                SUM(CASE WHEN type = 'funding' AND status = 'posted' THEN amount ELSE 0 END) as funded
            ")
            ->where('petty_cash_account_id', $account->id)
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        $chartData = [
            'labels' => $monthly->pluck('month'),
            'spent' => $monthly->pluck('spent'),
            'replenished' => $monthly->pluck('replenished'),
            'funded' => $monthly->pluck('funded'),
        ];
        
        return view('finance.petty_cash.show_account', compact(
            'account',
            'transactionCount',
            'reconciliationCount',
            'recentReconciliations',
            'expenseAccounts',
            'suggestedReplenishment',
            'isLowBalance',
            'analytics',
            'chartData'
        ));
    }

    public function getAccountBalance($id)
    {
        $account = PettyCashAccount::findOrFail($id);
    
        return response()->json([
            'balance' => (float) $account->current_balance,
            'minimum_balance' => (float) $account->minimum_balance,
        ]);
    }
    
    public function storeAccount(Request $request)
    {
        $this->authorize('finance.petty_cash.accounts.manage');

        $request->validate([
            'account_code' => 'required|string|max:50|unique:petty_cash_accounts,account_code',
            'name' => 'required|string|max:150',
            'gl_cash_account_id' => 'required|integer',
            'gl_expense_clearing_account_id' => 'nullable|integer',
            'float_amount' => 'required|numeric|min:0',
            'minimum_balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,active,inactive,closed',
        ]);

        $account = PettyCashAccount::create([
            'company_id' => Auth::user()->company_id ?? 1,
            'account_code' => $request->account_code,
            'name' => $request->name,
            'custodian_employee_id' => $request->custodian_employee_id,
            'location_id' => $request->location_id,
            'gl_cash_account_id' => $request->gl_cash_account_id,
            'gl_expense_clearing_account_id' => $request->gl_expense_clearing_account_id,
            'currency_id' => $request->currency_id,
            'float_amount' => $request->float_amount,
            'minimum_balance' => $request->minimum_balance ?? 0,
            'auto_replenish_suggestion' => $request->boolean('auto_replenish_suggestion'),
            'current_balance' => $request->float_amount,
            'status' => $request->status,
            'notes' => $request->notes,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->audit('account_created', 'Petty cash account created.', null, $account->toArray(), $account->id);

        return response()->json(['success' => true, 'message' => 'Petty cash account created successfully.']);
    }

    public function storeTransaction(Request $request)
    {
        $this->authorize('finance.petty_cash.create');

        $request->validate([
            'petty_cash_account_id' => 'required|exists:petty_cash_accounts,id',
            'transaction_date' => 'required|date',
            'type' => 'required|in:funding,expense,replenishment,refund,adjustment,retirement',
            'amount' => 'required|numeric|min:0.01',
            'expense_account_id' => 'nullable|integer',
            'status' => 'required|in:draft,pending',
            'payee_type' => 'nullable|in:employee,supplier,customer,other',
            'payee_id' => 'nullable|integer',
            'payee' => 'nullable|string|max:150',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120',
        ]);

        if ($request->type === 'expense' && !$request->expense_account_id) {
            return response()->json(['success' => false, 'message' => 'Expense account is required for expense transactions.'], 422);
        }

        if ($request->type === 'expense') {
            if ($request->amount > $account->current_balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient petty cash balance.'
                ], 422);
            }
        }
        
        $payeeType = $request->payee_type ?: 'other';
        $payeeId = $request->payee_id;
        $payeeText = $request->payee;

        if (in_array($payeeType, ['employee', 'supplier', 'customer']) && empty($payeeId)) {
            return response()->json(['success' => false, 'message' => 'Please select a valid payee.'], 422);
        }

        if ($payeeType === 'other' && empty($payeeText)) {
            return response()->json(['success' => false, 'message' => 'Please enter a payee name.'], 422);
        }

        $account = PettyCashAccount::findOrFail($request->petty_cash_account_id);

        if (!in_array($account->status, ['active'])) {
            return response()->json(['success' => false, 'message' => 'Selected petty cash account is not active.'], 422);
        }

        if (in_array($request->type, ['expense', 'adjustment', 'retirement']) && (float) $request->amount > (float) $account->current_balance) {
            return response()->json(['success' => false, 'message' => 'Amount exceeds available petty cash balance.'], 422);
        }

        $nextId = (PettyCashTransaction::max('id') ?? 0) + 1;
        $transactionNo = 'PC-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
        $voucherNo = 'PCV-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);

        $payload = [
            'company_id' => Auth::user()->company_id ?? 1,
            'petty_cash_account_id' => $request->petty_cash_account_id,
            'transaction_no' => $transactionNo,
            'voucher_no' => $voucherNo,
            'transaction_date' => $request->transaction_date,
            'type' => $request->type,
            'reference_no' => $request->reference_no,
            'payee_type' => $payeeType,
            'payee_id' => in_array($payeeType, ['employee', 'supplier', 'customer']) ? $payeeId : null,
            'payee' => $payeeType === 'other' ? $payeeText : null,
            'description' => $request->description,
            'amount' => $request->amount,
            'status' => $request->status,
            'workflow_status' => $request->status === 'pending' ? 'awaiting_approval' : 'draft',
            'expense_account_id' => $request->expense_account_id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        if ($request->status === 'pending') {
            $payload['submitted_by'] = Auth::id();
            $payload['submitted_at'] = now();
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $stored = $file->store('petty-cash', 'public');

            $payload['attachment'] = $stored;

            if (Schema::hasColumn('petty_cash_transactions', 'attachment_original_name')) {
                $payload['attachment_original_name'] = $file->getClientOriginalName();
            }

            if (Schema::hasColumn('petty_cash_transactions', 'attachment_mime_type')) {
                $payload['attachment_mime_type'] = $file->getMimeType();
            }

            if (Schema::hasColumn('petty_cash_transactions', 'attachment_size')) {
                $payload['attachment_size'] = $file->getSize();
            }
        }

        $transaction = PettyCashTransaction::create($payload);

        $this->audit('transaction_created', 'Petty cash transaction created.', null, $transaction->toArray(), null, $transaction->id);

        return response()->json(['success' => true, 'message' => 'Petty cash transaction saved successfully.']);
    }

    public function editAccount($id)
    {
        $this->authorize('finance.petty_cash.accounts.manage');
    
        $row = PettyCashAccount::findOrFail($id);
    
        return response()->json([
            'success' => true,
            'data' => $row,
        ]);
    }
    
    public function updateAccount(Request $request, $id)
    {
        $this->authorize('finance.petty_cash.accounts.manage');
    
        $row = PettyCashAccount::findOrFail($id);
    
        $request->validate([
            'account_code' => 'required|string|max:50|unique:petty_cash_accounts,account_code,' . $row->id,
            'name' => 'required|string|max:150',
            'gl_cash_account_id' => 'required|integer',
            'gl_expense_clearing_account_id' => 'nullable|integer',
            'float_amount' => 'required|numeric|min:0',
            'minimum_balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,active,inactive,closed',
        ]);
    
        $old = $row->toArray();
    
        $newFloat = (float) $request->float_amount;
        $oldFloat = (float) $row->float_amount;
        $currentBalance = (float) $row->current_balance;
    
        // Adjust current balance only by the float delta, so history is preserved reasonably.
        $floatDelta = $newFloat - $oldFloat;
        $newCurrentBalance = $currentBalance + $floatDelta;
    
        if ($newCurrentBalance < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Update would make current balance negative.'
            ], 422);
        }
    
        $row->update([
            'account_code' => $request->account_code,
            'name' => $request->name,
            'custodian_employee_id' => $request->custodian_employee_id,
            'location_id' => $request->location_id,
            'gl_cash_account_id' => $request->gl_cash_account_id,
            'gl_expense_clearing_account_id' => $request->gl_expense_clearing_account_id,
            'currency_id' => $request->currency_id,
            'float_amount' => $newFloat,
            'minimum_balance' => $request->minimum_balance ?? 0,
            'auto_replenish_suggestion' => $request->boolean('auto_replenish_suggestion'),
            'current_balance' => $newCurrentBalance,
            'status' => $request->status,
            'notes' => $request->notes,
            'updated_by' => Auth::id(),
        ]);
    
        $this->audit('account_updated', 'Petty cash account updated.', $old, $row->fresh()->toArray(), $row->id);
    
        return response()->json([
            'success' => true,
            'message' => 'Petty cash account updated successfully.'
        ]);
    }
    
    public function destroyAccount($id)
    {
        $this->authorize('finance.petty_cash.accounts.manage');
    
        $row = PettyCashAccount::findOrFail($id);
    
        $hasTransactions = PettyCashTransaction::where('petty_cash_account_id', $row->id)->exists();
        $hasReconciliations = PettyCashReconciliation::where('petty_cash_account_id', $row->id)->exists();
    
        if ($hasTransactions || $hasReconciliations) {
            return response()->json([
                'success' => false,
                'message' => 'This petty cash account cannot be deleted because it already has transactions or reconciliations.'
            ], 422);
        }
    
        $old = $row->toArray();
        $row->delete();
    
        $this->audit('account_deleted', 'Petty cash account deleted.', $old, null, $row->id);
    
        return response()->json([
            'success' => true,
            'message' => 'Petty cash account deleted successfully.'
        ]);
    }
    
    public function show($id)
    {
        $this->authorize('finance.petty_cash.view');

        $row = PettyCashTransaction::with([
            'account',
            'expenseAccount',
            'employeePayee',
            'supplierPayee',
            'customerPayee',
            'submittedBy',
            'approvedBy',
            'postedBy',
        ])->findOrFail($id);

        return view('finance.petty_cash.show', compact('row'));
    }

    public function edit($id)
    {
        $this->authorize('finance.petty_cash.edit');

        $row = PettyCashTransaction::with([
            'employeePayee',
            'supplierPayee',
            'customerPayee',
        ])->findOrFail($id);

        if (in_array($row->status, ['posted', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Posted or cancelled transactions cannot be edited.'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($row->toArray(), [
                'payee_display' => $row->payee_display,
            ]),
        ]);
    }

    public function updateTransaction(Request $request, $id)
    {
        $this->authorize('finance.petty_cash.edit');

        $transaction = PettyCashTransaction::with('account')->findOrFail($id);

        if (in_array($transaction->status, ['posted', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Posted or cancelled transactions cannot be edited.'], 422);
        }

        $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|in:funding,expense,replenishment,refund,adjustment,retirement',
            'amount' => 'required|numeric|min:0.01',
            'expense_account_id' => 'nullable|integer',
            'status' => 'required|in:draft,pending',
            'payee_type' => 'nullable|in:employee,supplier,customer,other',
            'payee_id' => 'nullable|integer',
            'payee' => 'nullable|string|max:150',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120',
        ]);

        if ($request->type === 'expense' && !$request->expense_account_id) {
            return response()->json(['success' => false, 'message' => 'Expense account is required for expense transactions.'], 422);
        }

        $payeeType = $request->payee_type ?: 'other';
        $payeeId = $request->payee_id;
        $payeeText = $request->payee;

        if (in_array($payeeType, ['employee', 'supplier', 'customer']) && empty($payeeId)) {
            return response()->json(['success' => false, 'message' => 'Please select a valid payee.'], 422);
        }

        if ($payeeType === 'other' && empty($payeeText)) {
            return response()->json(['success' => false, 'message' => 'Please enter a payee name.'], 422);
        }

        $account = $transaction->account;

        if (in_array($request->type, ['expense', 'adjustment', 'retirement']) && (float) $request->amount > (float) $account->current_balance) {
            return response()->json(['success' => false, 'message' => 'Amount exceeds available petty cash balance.'], 422);
        }

        $old = $transaction->toArray();

        $payload = [
            'transaction_date' => $request->transaction_date,
            'type' => $request->type,
            'reference_no' => $request->reference_no,
            'payee_type' => $payeeType,
            'payee_id' => in_array($payeeType, ['employee', 'supplier', 'customer']) ? $payeeId : null,
            'payee' => $payeeType === 'other' ? $payeeText : null,
            'description' => $request->description,
            'amount' => $request->amount,
            'expense_account_id' => $request->expense_account_id,
            'status' => $request->status,
            'workflow_status' => $request->status === 'pending' ? 'awaiting_approval' : 'draft',
            'updated_by' => Auth::id(),
        ];

        if ($request->status === 'pending') {
            $payload['submitted_by'] = Auth::id();
            $payload['submitted_at'] = now();
        }

        if ($request->hasFile('attachment')) {
            if ($transaction->attachment && Storage::disk('public')->exists($transaction->attachment)) {
                Storage::disk('public')->delete($transaction->attachment);
            }

            $file = $request->file('attachment');
            $stored = $file->store('petty-cash', 'public');

            $payload['attachment'] = $stored;

            if (Schema::hasColumn('petty_cash_transactions', 'attachment_original_name')) {
                $payload['attachment_original_name'] = $file->getClientOriginalName();
            }

            if (Schema::hasColumn('petty_cash_transactions', 'attachment_mime_type')) {
                $payload['attachment_mime_type'] = $file->getMimeType();
            }

            if (Schema::hasColumn('petty_cash_transactions', 'attachment_size')) {
                $payload['attachment_size'] = $file->getSize();
            }
        }

        $transaction->update($payload);

        $this->audit('transaction_updated', 'Petty cash transaction updated.', $old, $transaction->fresh()->toArray(), null, $transaction->id);

        return response()->json(['success' => true, 'message' => 'Petty cash transaction updated successfully.']);
    }

    public function approve(Request $request, $id)
    {
        $this->authorize('finance.petty_cash.approve');

        $transaction = PettyCashTransaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending transactions can be approved.'], 422);
        }

        $old = $transaction->toArray();

        $transaction->update([
            'status' => 'approved',
            'workflow_status' => 'approved',
            'approval_notes' => $request->approval_notes,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        $this->audit('transaction_approved', 'Petty cash transaction approved.', $old, $transaction->fresh()->toArray(), null, $transaction->id);

        return response()->json(['success' => true, 'message' => 'Transaction approved successfully.']);
    }

    public function reject(Request $request, $id)
    {
        $this->authorize('finance.petty_cash.approve');

        $transaction = PettyCashTransaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending transactions can be rejected.'], 422);
        }

        $old = $transaction->toArray();

        $transaction->update([
            'status' => 'rejected',
            'workflow_status' => 'rejected',
            'approval_notes' => $request->approval_notes,
            'updated_by' => Auth::id(),
        ]);

        $this->audit('transaction_rejected', 'Petty cash transaction rejected.', $old, $transaction->fresh()->toArray(), null, $transaction->id);

        return response()->json(['success' => true, 'message' => 'Transaction rejected successfully.']);
    }

    public function post($id)
    {
        $this->authorize('finance.petty_cash.post');

        DB::beginTransaction();

        try {
            $transaction = PettyCashTransaction::with(['account', 'expenseAccount'])->findOrFail($id);

            if ($transaction->status !== 'approved') {
                throw new \Exception('Only approved transactions can be posted.');
            }

            if ($transaction->posted_at) {
                throw new \Exception('This transaction has already been posted.');
            }

            $account = $transaction->account;

            $oldTxn = $transaction->toArray();
            $oldAccount = $account->toArray();

            $journal = FinanceJournalEntry::create([
                'company_id' => Auth::user()->company_id ?? 1,
                'entry_no' => 'JE-PC-' . now()->format('YmdHis'),
                'entry_date' => $transaction->transaction_date,
                'reference_no' => $transaction->transaction_no,
                'memo' => 'Petty cash ' . $transaction->type . ' - ' . $transaction->description,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if (in_array($transaction->type, ['funding', 'replenishment'])) {
                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_cash_account_id,
                    'description' => 'Petty cash funding/replenishment',
                    'debit' => $transaction->amount,
                    'credit' => 0,
                ]);

                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_expense_clearing_account_id,
                    'description' => 'Petty cash funding/replenishment offset',
                    'debit' => 0,
                    'credit' => $transaction->amount,
                ]);

                $account->current_balance = (float) $account->current_balance + (float) $transaction->amount;
                $account->last_replenished_at = now();
            }

            if ($transaction->type === 'expense') {
                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $transaction->expense_account_id,
                    'description' => $transaction->description ?: 'Petty cash expense',
                    'debit' => $transaction->amount,
                    'credit' => 0,
                ]);

                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_cash_account_id,
                    'description' => 'Petty cash expense',
                    'debit' => 0,
                    'credit' => $transaction->amount,
                ]);

                $account->current_balance = (float) $account->current_balance - (float) $transaction->amount;
            }

            if ($transaction->type === 'refund') {
                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_cash_account_id,
                    'description' => 'Petty cash refund',
                    'debit' => $transaction->amount,
                    'credit' => 0,
                ]);

                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $transaction->expense_account_id ?: $account->gl_expense_clearing_account_id,
                    'description' => 'Petty cash refund offset',
                    'debit' => 0,
                    'credit' => $transaction->amount,
                ]);

                $account->current_balance = (float) $account->current_balance + (float) $transaction->amount;
            }

            if (in_array($transaction->type, ['adjustment', 'retirement'])) {
                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_expense_clearing_account_id,
                    'description' => ucfirst($transaction->type),
                    'debit' => $transaction->amount,
                    'credit' => 0,
                ]);

                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_cash_account_id,
                    'description' => ucfirst($transaction->type),
                    'debit' => 0,
                    'credit' => $transaction->amount,
                ]);

                $account->current_balance = (float) $account->current_balance - (float) $transaction->amount;
            }

            if ((float) $account->current_balance < 0) {
                throw new \Exception('Petty cash balance cannot go below zero.');
            }

            $account->updated_by = Auth::id();
            $account->save();

            $transaction->update([
                'status' => 'posted',
                'workflow_status' => 'posted',
                'finance_journal_entry_id' => $journal->id,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->audit('transaction_posted', 'Petty cash transaction posted.', $oldTxn, $transaction->fresh()->toArray(), null, $transaction->id);
            $this->audit('account_balance_changed', 'Petty cash account balance updated after posting.', $oldAccount, $account->fresh()->toArray(), $account->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ((float) $account->current_balance <= (float) $account->minimum_balance && $account->auto_replenish_suggestion)
                    ? 'Transaction posted successfully. This account is now at or below its minimum balance.'
                    : 'Transaction posted successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $this->authorize('finance.petty_cash.delete');

        $transaction = PettyCashTransaction::findOrFail($id);

        if (in_array($transaction->status, ['posted'])) {
            return response()->json(['success' => false, 'message' => 'Posted transactions cannot be deleted.'], 422);
        }

        $old = $transaction->toArray();

        if ($transaction->attachment && Storage::disk('public')->exists($transaction->attachment)) {
            Storage::disk('public')->delete($transaction->attachment);
        }

        $transaction->delete();

        $this->audit('transaction_deleted', 'Petty cash transaction deleted.', $old, null, null, $transaction->id);

        return response()->json(['success' => true, 'message' => 'Transaction deleted successfully.']);
    }

    public function voucher($id)
    {
        $this->authorize('finance.petty_cash.print');

        $row = PettyCashTransaction::with([
            'account',
            'expenseAccount',
            'employeePayee',
            'supplierPayee',
            'customerPayee',
            'submittedBy',
            'approvedBy',
            'postedBy'
        ])->findOrFail($id);

        return view('finance.petty_cash.voucher_pdf', compact('row'));
    }

    public function reconciliations(Request $request)
    {
        $this->authorize('finance.petty_cash.reconcile');

        if ($request->ajax()) {
            $query = PettyCashReconciliation::with('account')
                ->when(Auth::user()->company_id ?? 1, fn($q) => $q->where('company_id', Auth::user()->company_id ?? 1));

            return DataTables::of($query)
                ->addColumn('account_name', fn($row) => $row->account->name ?? '-')
                ->editColumn('closing_balance_system', fn($row) => number_format($row->closing_balance_system, 2))
                ->editColumn('closing_balance_counted', fn($row) => number_format($row->closing_balance_counted, 2))
                ->editColumn('variance_amount', function ($row) {
                    $cls = ((float) $row->variance_amount == 0.0) ? 'text-success' : 'text-danger fw-bold';
                    return '<span class="' . $cls . '">' . number_format($row->variance_amount, 2) . '</span>';
                })
                ->editColumn('status', function ($row) {
                    $map = [
                        'draft' => 'secondary',
                        'submitted' => 'warning',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'posted' => 'success',
                    ];
                    $cls = $map[$row->status] ?? 'secondary';
                    return '<span class="badge bg-' . $cls . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    return view('finance.petty_cash.partials.reconciliation_actions', compact('row'))->render();
                })
                ->rawColumns(['variance_amount', 'status', 'actions'])
                ->make(true);
        }

        return view('finance.petty_cash.reconciliations');
    }

    public function storeReconciliation(Request $request)
    {
        $this->authorize('finance.petty_cash.reconcile');

        $request->validate([
            'petty_cash_account_id' => 'required|exists:petty_cash_accounts,id',
            'reconciliation_date' => 'required|date',
            'closing_balance_counted' => 'required|numeric|min:0',
        ]);

        $account = PettyCashAccount::findOrFail($request->petty_cash_account_id);

        $opening = (float) $account->float_amount;

        $fundsAdded = (float) PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->whereIn('type', ['funding', 'replenishment'])
            ->where('status', 'posted')
            ->sum('amount');

        $expenses = (float) PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->where('type', 'expense')
            ->where('status', 'posted')
            ->sum('amount');

        $refunds = (float) PettyCashTransaction::where('petty_cash_account_id', $account->id)
            ->where('type', 'refund')
            ->where('status', 'posted')
            ->sum('amount');

        $systemClosing = $opening + $fundsAdded + $refunds - $expenses;
        $counted = (float) $request->closing_balance_counted;
        $variance = $counted - $systemClosing;

        $nextId = (PettyCashReconciliation::max('id') ?? 0) + 1;

        $row = PettyCashReconciliation::create([
            'company_id' => Auth::user()->company_id ?? 1,
            'petty_cash_account_id' => $account->id,
            'reconciliation_no' => 'PCR-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT),
            'reconciliation_date' => $request->reconciliation_date,
            'opening_balance' => $opening,
            'funds_added' => $fundsAdded,
            'expenses_total' => $expenses,
            'refunds_total' => $refunds,
            'closing_balance_system' => $systemClosing,
            'closing_balance_counted' => $counted,
            'variance_amount' => $variance,
            'status' => 'submitted',
            'notes' => $request->notes,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->audit('reconciliation_created', 'Petty cash reconciliation created.', null, $row->toArray(), null, null, $row->id);

        return response()->json(['success' => true, 'message' => 'Reconciliation submitted successfully.']);
    }

    public function approveReconciliation(Request $request, $id)
    {
        $this->authorize('finance.petty_cash.reconciliation.approve');

        $row = PettyCashReconciliation::findOrFail($id);

        if ($row->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Only submitted reconciliations can be approved.'], 422);
        }

        $old = $row->toArray();

        $row->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes' => trim(($row->notes ?? '') . "\n" . ($request->notes ?? '')),
            'updated_by' => Auth::id(),
        ]);

        $this->audit('reconciliation_approved', 'Petty cash reconciliation approved.', $old, $row->fresh()->toArray(), null, null, $row->id);

        return response()->json(['success' => true, 'message' => 'Reconciliation approved successfully.']);
    }

    public function rejectReconciliation(Request $request, $id)
    {
        $this->authorize('finance.petty_cash.reconciliation.approve');

        $row = PettyCashReconciliation::findOrFail($id);

        if ($row->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Only submitted reconciliations can be rejected.'], 422);
        }

        $old = $row->toArray();

        $row->update([
            'status' => 'rejected',
            'notes' => trim(($row->notes ?? '') . "\n" . ($request->notes ?? 'Rejected')),
            'updated_by' => Auth::id(),
        ]);

        $this->audit('reconciliation_rejected', 'Petty cash reconciliation rejected.', $old, $row->fresh()->toArray(), null, null, $row->id);

        return response()->json(['success' => true, 'message' => 'Reconciliation rejected successfully.']);
    }

    public function postReconciliation($id)
    {
        $this->authorize('finance.petty_cash.reconciliation.post');

        DB::beginTransaction();

        try {
            $row = PettyCashReconciliation::with('account')->findOrFail($id);

            if ($row->status !== 'approved') {
                throw new \Exception('Only approved reconciliations can be posted.');
            }

            if ((float) $row->variance_amount == 0.0) {
                $row->update([
                    'status' => 'posted',
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

                $this->audit('reconciliation_posted', 'Petty cash reconciliation posted without variance.', null, $row->fresh()->toArray(), null, null, $row->id);

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Reconciliation posted successfully.']);
            }

            $account = $row->account;

            $journal = FinanceJournalEntry::create([
                'company_id' => Auth::user()->company_id ?? 1,
                'entry_no' => 'JE-PCR-' . now()->format('YmdHis'),
                'entry_date' => $row->reconciliation_date,
                'reference_no' => $row->reconciliation_no,
                'memo' => 'Petty cash reconciliation variance posting',
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if ((float) $row->variance_amount < 0) {
                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_expense_clearing_account_id,
                    'description' => 'Petty cash shortage',
                    'debit' => abs($row->variance_amount),
                    'credit' => 0,
                ]);

                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_cash_account_id,
                    'description' => 'Petty cash shortage offset',
                    'debit' => 0,
                    'credit' => abs($row->variance_amount),
                ]);
            } else {
                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_cash_account_id,
                    'description' => 'Petty cash overage',
                    'debit' => abs($row->variance_amount),
                    'credit' => 0,
                ]);

                FinanceJournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $account->gl_expense_clearing_account_id,
                    'description' => 'Petty cash overage offset',
                    'debit' => 0,
                    'credit' => abs($row->variance_amount),
                ]);
            }

            $row->update([
                'status' => 'posted',
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->audit('reconciliation_posted', 'Petty cash reconciliation posted with variance journal.', null, $row->fresh()->toArray(), null, null, $row->id);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Reconciliation posted successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function auditTrail()
    {
        $this->authorize('finance.petty_cash.audit');

        $logs = PettyCashAudit::latest()->paginate(50);

        return view('finance.petty_cash.audit', compact('logs'));
    }

    protected function audit($action, $description = null, $oldValues = null, $newValues = null, $accountId = null, $transactionId = null, $reconciliationId = null)
    {
        PettyCashAudit::create([
            'company_id' => Auth::user()->company_id ?? 1,
            'petty_cash_account_id' => $accountId,
            'petty_cash_transaction_id' => $transactionId,
            'reconciliation_id' => $reconciliationId,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'performed_by' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
    
    public function quickReplenish(Request $request, $id)
    {
        $this->authorize('finance.petty_cash.create');
    
        $account = PettyCashAccount::findOrFail($id);
    
        if ($account->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active petty cash accounts can be replenished.'
            ], 422);
        }
    
        $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,pending',
        ]);
    
        $amount = (float) $request->amount;
    
        $nextId = (PettyCashTransaction::max('id') ?? 0) + 1;
        $transactionNo = 'PC-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
        $voucherNo = 'PCV-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    
        $payload = [
            'company_id' => Auth::user()->company_id ?? 1,
            'petty_cash_account_id' => $account->id,
            'transaction_no' => $transactionNo,
            'voucher_no' => $voucherNo,
            'transaction_date' => $request->transaction_date,
            'type' => 'replenishment',
            'reference_no' => $request->reference_no,
            'payee_type' => 'other',
            'payee_id' => null,
            'payee' => $request->payee ?: ($account->name . ' Replenishment'),
            'description' => $request->description ?: ('Replenishment for petty cash account ' . $account->account_code),
            'amount' => $amount,
            'status' => $request->status,
            'workflow_status' => $request->status === 'pending' ? 'awaiting_approval' : 'draft',
            'expense_account_id' => null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];
    
        if ($request->status === 'pending') {
            $payload['submitted_by'] = Auth::id();
            $payload['submitted_at'] = now();
        }
    
        $transaction = PettyCashTransaction::create($payload);
    
        $this->audit(
            'replenishment_created',
            'Petty cash replenishment transaction created.',
            null,
            $transaction->toArray(),
            $account->id,
            $transaction->id
        );
    
        return response()->json([
            'success' => true,
            'message' => 'Replenishment transaction created successfully.',
            'transaction_id' => $transaction->id,
        ]);
    }
}