<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\Finance\Models\PettyCashReconciliation;
use Modules\Finance\Models\PettyCashAccount;

class PettyCashReconciliationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('finance.petty_cash.reconciliation.view');

        if ($request->ajax()) {
            $query = PettyCashReconciliation::query()
                ->with(['account'])
                ->when(Auth::user()->company_id ?? 1, function ($q) {
                    $q->where('company_id', Auth::user()->company_id ?? 1);
                });

            if ($request->filled('petty_cash_account_id')) {
                $query->where('petty_cash_account_id', $request->petty_cash_account_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('reconciliation_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('reconciliation_date', '<=', $request->to_date);
            }

            if ($request->filled('q')) {
                $search = trim($request->q);

                $query->where(function ($q) use ($search) {
                    $q->where('reconciliation_no', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('account', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('account_code', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->boolean('summary_only')) {
                $summaryQuery = clone $query;

                $summary = [
                    'total_reconciliations' => (clone $summaryQuery)->count(),
                    'open_reconciliations' => (clone $summaryQuery)->whereIn('status', ['draft', 'submitted'])->count(),
                    'approved_reconciliations' => (clone $summaryQuery)->where('status', 'approved')->count(),
                    'total_variance' => (float) (clone $summaryQuery)->sum('variance_amount'),
                ];

                return response()->json(['summary' => $summary]);
            }

            return DataTables::eloquent($query)
                ->addColumn('account_name', function ($row) {
                    return $row->account
                        ? ($row->account->account_code . ' - ' . $row->account->name)
                        : '-';
                })
                ->editColumn('reconciliation_date', function ($row) {
                    return optional($row->reconciliation_date)->format('Y-m-d');
                })
                ->editColumn('opening_balance', function ($row) {
                    return number_format((float) $row->opening_balance, 2);
                })
                ->editColumn('funds_added', function ($row) {
                    return number_format((float) $row->funds_added, 2);
                })
                ->editColumn('expenses_total', function ($row) {
                    return number_format((float) $row->expenses_total, 2);
                })
                ->editColumn('refunds_total', function ($row) {
                    return number_format((float) $row->refunds_total, 2);
                })
                ->editColumn('closing_balance_system', function ($row) {
                    return number_format((float) $row->closing_balance_system, 2);
                })
                ->editColumn('closing_balance_counted', function ($row) {
                    return number_format((float) $row->closing_balance_counted, 2);
                })
                ->editColumn('variance_amount', function ($row) {
                    $amount = (float) $row->variance_amount;
                    $class = $amount == 0 ? 'text-success' : 'text-danger';
                    return '<span class="' . $class . '">' . number_format($amount, 2) . '</span>';
                })
                ->editColumn('status', function ($row) {
                    $map = [
                        'draft' => 'secondary',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    ];

                    $cls = $map[$row->status] ?? 'secondary';

                    return '<span class="badge bg-' . $cls . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    return view('finance.petty_cash.reconciliations.partials.actions', compact('row'))->render();
                })
                ->rawColumns(['variance_amount', 'status', 'actions'])
                ->make(true);
        }

        return view('finance.petty_cash.reconciliations.index');
    }

    public function store(Request $request)
    {
        $this->authorize('finance.petty_cash.reconciliation.create');

        $request->validate([
            'petty_cash_account_id' => ['required', 'integer', 'exists:petty_cash_accounts,id'],
            'reconciliation_date' => ['required', 'date'],
            'closing_balance_counted' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $account = PettyCashAccount::query()
                ->when(Auth::user()->company_id ?? 1, function ($q) {
                    $q->where('company_id', Auth::user()->company_id ?? 1);
                })
                ->findOrFail($request->petty_cash_account_id);

            $openingBalance = (float) ($account->current_balance ?? 0);

            $fundsAdded = (float) $account->transactions()
                ->whereIn('type', ['funding', 'replenishment'])
                ->where('status', 'posted')
                ->whereDate('transaction_date', $request->reconciliation_date)
                ->sum('amount');

            $expensesTotal = (float) $account->transactions()
                ->where('type', 'expense')
                ->where('status', 'posted')
                ->whereDate('transaction_date', $request->reconciliation_date)
                ->sum('amount');

            $refundsTotal = (float) $account->transactions()
                ->where('type', 'refund')
                ->where('status', 'posted')
                ->whereDate('transaction_date', $request->reconciliation_date)
                ->sum('amount');

            $systemBalance = (float) $account->current_balance;
            $counted = (float) $request->closing_balance_counted;
            $variance = $counted - $systemBalance;

            $recon = PettyCashReconciliation::create([
                'company_id' => Auth::user()->company_id ?? 1,
                'petty_cash_account_id' => $account->id,
                'reconciliation_no' => $this->generateReconciliationNo(),
                'reconciliation_date' => $request->reconciliation_date,
                'opening_balance' => $openingBalance,
                'funds_added' => $fundsAdded,
                'expenses_total' => $expensesTotal,
                'refunds_total' => $refundsTotal,
                'closing_balance_system' => $systemBalance,
                'closing_balance_counted' => $counted,
                'variance_amount' => $variance,
                'status' => 'draft',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation created successfully.',
                'data' => $recon,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show($id)
    {
        $this->authorize('finance.petty_cash.reconciliation.view');

        $row = PettyCashReconciliation::with(['account'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $row,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('finance.petty_cash.reconciliation.edit');

        $row = PettyCashReconciliation::findOrFail($id);

        if (!in_array($row->status, ['draft', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or rejected reconciliations can be edited.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $row,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('finance.petty_cash.reconciliation.edit');

        $request->validate([
            'reconciliation_date' => ['required', 'date'],
            'closing_balance_counted' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $row = PettyCashReconciliation::with('account')->findOrFail($id);

            if (!in_array($row->status, ['draft', 'rejected'])) {
                throw new \Exception('Only draft or rejected reconciliations can be updated.');
            }

            $systemBalance = (float) $row->closing_balance_system;
            $counted = (float) $request->closing_balance_counted;
            $variance = $counted - $systemBalance;

            $row->update([
                'reconciliation_date' => $request->reconciliation_date,
                'closing_balance_counted' => $counted,
                'variance_amount' => $variance,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation updated successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function submit($id)
    {
        $this->authorize('finance.petty_cash.reconciliation.submit');

        try {
            $row = PettyCashReconciliation::findOrFail($id);

            if ($row->status !== 'draft' && $row->status !== 'rejected') {
                throw new \Exception('Only draft or rejected reconciliations can be submitted.');
            }

            $row->update([
                'status' => 'submitted',
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation submitted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function approve($id)
    {
        $this->authorize('finance.petty_cash.reconciliation.approve');

        DB::beginTransaction();

        try {
            $row = PettyCashReconciliation::with('account')->findOrFail($id);

            if ($row->status !== 'submitted') {
                throw new \Exception('Only submitted reconciliations can be approved.');
            }

            $row->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation approved successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reject(Request $request, $id)
    {
        $this->authorize('finance.petty_cash.reconciliation.approve');

        try {
            $row = PettyCashReconciliation::findOrFail($id);

            if ($row->status !== 'submitted') {
                throw new \Exception('Only submitted reconciliations can be rejected.');
            }

            $row->update([
                'status' => 'rejected',
                'notes' => $request->notes ?: $row->notes,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation rejected successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $this->authorize('finance.petty_cash.reconciliation.delete');

        try {
            $row = PettyCashReconciliation::findOrFail($id);

            if (!in_array($row->status, ['draft', 'rejected'])) {
                throw new \Exception('Only draft or rejected reconciliations can be deleted.');
            }

            $row->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    protected function generateReconciliationNo(): string
    {
        $prefix = 'PCR-' . now()->format('Ymd') . '-';

        $lastId = PettyCashReconciliation::max('id') ?? 0;

        return $prefix . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
    }
    
    public function accountSnapshot(Request $request)
    {
        $this->authorize('finance.petty_cash.reconciliation.view');
    
        $request->validate([
            'petty_cash_account_id' => ['required', 'integer', 'exists:petty_cash_accounts,id'],
            'reconciliation_date' => ['nullable', 'date'],
        ]);
    
        $account = PettyCashAccount::query()
            ->when(Auth::user()->company_id ?? 1, function ($q) {
                $q->where('company_id', Auth::user()->company_id ?? 1);
            })
            ->findOrFail($request->petty_cash_account_id);
    
        $date = $request->reconciliation_date ?: now()->toDateString();
    
        $fundsAdded = (float) $account->transactions()
            ->whereIn('type', ['funding', 'replenishment'])
            ->where('status', 'posted')
            ->whereDate('transaction_date', $date)
            ->sum('amount');
    
        $expensesTotal = (float) $account->transactions()
            ->where('type', 'expense')
            ->where('status', 'posted')
            ->whereDate('transaction_date', $date)
            ->sum('amount');
    
        $refundsTotal = (float) $account->transactions()
            ->where('type', 'refund')
            ->where('status', 'posted')
            ->whereDate('transaction_date', $date)
            ->sum('amount');
    
        $systemBalance = (float) ($account->current_balance ?? 0);
        $openingBalance = $systemBalance;
    
        return response()->json([
            'success' => true,
            'data' => [
                'opening_balance' => $openingBalance,
                'funds_added' => $fundsAdded,
                'expenses_total' => $expensesTotal,
                'refunds_total' => $refundsTotal,
                'closing_balance_system' => $systemBalance,
            ]
        ]);
    }
}