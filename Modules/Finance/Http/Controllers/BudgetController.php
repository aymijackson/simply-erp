<?php
// File: Modules/Finance/Http/Controllers/BudgetController.php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Http\Requests\SaveBudgetGridRequest;
use Modules\Finance\Http\Requests\StoreBudgetRequest;
use Modules\Finance\Models\Budget;
use Modules\Finance\Models\BudgetLine;
use Modules\Finance\Services\BudgetService;
use Yajra\DataTables\Facades\DataTables;

class BudgetController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('finance.budgets.view'), 403);
        return view('finance.budgets.index');
    }


    public function dt(Request $request)
    {
        abort_unless(auth()->user()->can('finance.budgets.view'), 403);
    
        $companyId = $request->user()->company_id ?? null;
    
        $q = Budget::query()
            ->when($companyId, fn($x) => $x->where('company_id', $companyId))
            ->latest('id');
    
        return DataTables::of($q)->make(true);
    }


    public function create()
    {
        abort_unless(auth()->user()->can('finance.budgets.create'), 403);

        // You can swap this for your COA table/model. Using DB for compatibility.
        $companyId = auth()->user()->company_id ?? null;

        $accounts = DB::table('finance_accounts')
            ->when($companyId, fn($x) => $x->where('company_id', $companyId))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id','name','code']);

        return view('finance.budgets.create', compact('accounts'));
    }

    public function store(StoreBudgetRequest $request, BudgetService $svc)
    {
        $data = $request->validated();
        $companyId = $request->user()->company_id ?? $request->input('company_id');

        $budget = Budget::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'period_type' => $data['period_type'],
            'currency_code' => $data['currency_code'] ?? null,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $accountIds = $data['account_ids'] ?? [];
        if (!empty($accountIds)) {
            $svc->ensureLines($budget, $accountIds);
        }

        return redirect()->route('admin.finance.budgets.edit', $budget->id)
            ->with('success', 'Budget created.');
    }

    public function edit($id, BudgetService $svc)
    {
        abort_unless(auth()->user()->can('finance.budgets.update'), 403);

        $budget = Budget::query()->findOrFail($id);
        $periods = $svc->periods($budget);

        // Lines with amounts
        $lines = BudgetLine::query()
            ->where('budget_id', $budget->id)
            ->with('amounts')
            ->orderBy('account_id')
            ->get();

        // Pull account names (adapt if your COA table is different)
        $accountIds = $lines->pluck('account_id')->unique()->values()->all();
        $accounts = DB::table('finance_accounts')
            ->whereIn('id', $accountIds)
            ->get(['id','name','code'])
            ->keyBy('id');

        // Build a matrix for the grid
        $grid = [];
        foreach ($lines as $line) {
            $row = [
                'account_id' => $line->account_id,
                'account_name' => ($accounts[$line->account_id]->name ?? ('Account #'.$line->account_id)),
                'account_code' => ($accounts[$line->account_id]->code ?? ''),
                'amounts' => [],
            ];

            $amountByStart = $line->amounts->keyBy(fn($a) => $a->period_start->format('Y-m-d'));
            foreach ($periods as $p) {
                $ps = $p['period_start'];
                $row['amounts'][$ps] = (float)($amountByStart[$ps]->amount ?? 0);
            }

            $grid[] = $row;
        }

        // For “Add account” modal
        $companyId = auth()->user()->company_id ?? null;
        $allAccounts = DB::table('finance_accounts')
            ->when($companyId, fn($x) => $x->where('company_id', $companyId))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id','name','code']);

        return view('finance.budgets.edit', compact('budget','periods','grid','allAccounts'));
    }

    public function saveGrid($id, SaveBudgetGridRequest $request, BudgetService $svc)
    {
        $budget = Budget::query()->findOrFail($id);

        if (in_array($budget->status, ['locked'], true)) {
            return response()->json(['ok' => false, 'message' => 'Budget is locked.'], 422);
        }

        $svc->upsertGrid($budget, $request->validated()['rows']);

        return response()->json(['ok' => true, 'message' => 'Budget saved.']);
    }

    public function approve($id)
    {
        abort_unless(auth()->user()->can('finance.budgets.approve'), 403);

        $budget = Budget::query()->findOrFail($id);

        if ($budget->status !== 'draft') {
            return response()->json(['ok' => false, 'message' => 'Only draft budgets can be approved.'], 422);
        }

        $budget->status = 'approved';
        $budget->approved_by = auth()->id();
        $budget->approved_at = now();
        $budget->save();

        return response()->json(['ok' => true, 'message' => 'Budget approved.']);
    }

    public function lock($id)
    {
        abort_unless(auth()->user()->can('finance.budgets.lock'), 403);

        $budget = Budget::query()->findOrFail($id);

        if ($budget->status !== 'approved') {
            return response()->json(['ok' => false, 'message' => 'Only approved budgets can be locked.'], 422);
        }

        $budget->status = 'locked';
        $budget->save();

        return response()->json(['ok' => true, 'message' => 'Budget locked.']);
    }

    public function addAccount($id, Request $request)
    {
        abort_unless(auth()->user()->can('finance.budgets.update'), 403);

        $budget = Budget::query()->findOrFail($id);
        if ($budget->status === 'locked') {
            return response()->json(['ok' => false, 'message' => 'Budget is locked.'], 422);
        }

        $request->validate(['account_id' => ['required','integer']]);

        BudgetLine::query()->firstOrCreate([
            'budget_id' => $budget->id,
            'account_id' => (int)$request->account_id,
        ]);

        return response()->json(['ok' => true, 'message' => 'Account added.']);
    }

    public function removeAccount($id, Request $request)
    {
        abort_unless(auth()->user()->can('finance.budgets.update'), 403);

        $budget = Budget::query()->findOrFail($id);
        if ($budget->status === 'locked') {
            return response()->json(['ok' => false, 'message' => 'Budget is locked.'], 422);
        }

        $request->validate(['account_id' => ['required','integer']]);

        $line = BudgetLine::query()
            ->where('budget_id', $budget->id)
            ->where('account_id', (int)$request->account_id)
            ->first();

        if ($line) {
            $line->amounts()->delete();
            $line->delete();
        }

        return response()->json(['ok' => true, 'message' => 'Account removed.']);
    }
}