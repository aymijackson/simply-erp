<?php
// File: Modules/Finance/Http/Controllers/BudgetReportController.php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Budget;
use Modules\Finance\Services\BudgetService;

class BudgetReportController extends Controller
{
    public function budgetVsActual($id, Request $request, BudgetService $svc)
    {
        abort_unless(auth()->user()->can('finance.budgets.report'), 403);

        $budget = Budget::query()->findOrFail($id);

        $data = $svc->budgetVsActual($budget);

        // Attach account metadata
        $accountIds = collect($data['rows'])->pluck('account_id')->unique()->values()->all();

        $accounts = DB::table('finance_accounts')
            ->whereIn('id', $accountIds)
            ->get(['id','name','code'])
            ->keyBy('id');

        $rows = array_map(function ($r) use ($accounts) {
            $acc = $accounts[$r['account_id']] ?? null;
            $r['account_code'] = $acc->code ?? '';
            $r['account_name'] = $acc->name ?? ('Account #'.$r['account_id']);
            return $r;
        }, $data['rows']);

        return view('finance.budgets.report', [
            'budget' => $budget,
            'rows' => $rows,
        ]);
    }
}