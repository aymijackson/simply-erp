<?php
// File: Modules/Finance/Http/Controllers/BankReconciliationController.php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Http\Requests\StoreBankReconciliationRequest;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankReconciliation;
use Modules\Finance\Models\BankStatementLine;
use Modules\Finance\Services\BankReconciliationService;
use Yajra\DataTables\Facades\DataTables;

class BankReconciliationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.view'), 403);
        return view('finance.bank_reconciliations.index');
    }

    public function dt(Request $request)
    {
        abort_unless(auth()->user()->can('finance.budgets.view'), 403);
    
        $companyId = $request->user()->company_id ?? 1;
    
        $q = BankReconciliation::query()
            ->when($companyId, fn($x) => $x->where('company_id', $companyId))
            ->with('bankAccount:id,name')
            ->latest('id');

        // If you already use Yajra DataTables, swap this block accordingly.
        $rows = $q->paginate((int)($request->get('per_page', 25)));
    
        return DataTables::of($q)->make(true);

    }

    public function create()
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.create'), 403);

        $companyId = auth()->user()->company_id ?? 1;

        $bankAccounts = BankAccount::query()
            ->when($companyId, fn($x) => $x->where('company_id', $companyId))
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id','name']);

        return view('finance.bank_reconciliations.create', compact('bankAccounts'));
    }

    public function store(StoreBankReconciliationRequest $request, BankReconciliationService $svc)
    {
        $data = $request->validated();
        $companyId = $request->user()->company_id ?? $request->input('company_id') ?? 1;

        $recon = BankReconciliation::query()->create([
            'company_id' => $companyId,
            'bank_account_id' => (int)$data['bank_account_id'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'statement_opening_balance' => (float)$data['statement_opening_balance'],
            'statement_closing_balance' => (float)$data['statement_closing_balance'],
            'status' => 'in_progress',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // Snapshot opening balance for reference
        $recon->system_opening_balance = $svc->computeSystemOpeningBalance($recon);
        $recon->save();

        return redirect()->route('admin.finance.bank_reconciliations.show', $recon->id)
            ->with('success', 'Reconciliation created.');
    }

    public function show($id, BankReconciliationService $svc)
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.view'), 403);

        $recon = BankReconciliation::query()->with('bankAccount')->findOrFail($id);

        $systemClosing = $svc->computeSystemClosingBalance($recon);
        $difference = (float)$recon->statement_closing_balance - (float)$systemClosing;

        return view('finance.bank_reconciliations.show', compact('recon','systemClosing','difference'));
    }

    public function statementLinesDt(Request $request, $id)
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.view'), 403);

        $recon = BankReconciliation::query()->findOrFail($id);

        $q = BankStatementLine::query()
            ->where('reconciliation_id', $recon->id)
            ->with('match')
            ->orderByRaw("FIELD(status,'unmatched','suggested','matched','excluded')")
            ->orderBy('txn_date');

        // Replace with your DataTables response if needed.
        $rows = $q->paginate((int)($request->get('per_page', 50)));

        return response()->json($rows);
    }

    public function suggestions($id, Request $request, BankReconciliationService $svc)
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.view'), 403);

        $recon = BankReconciliation::query()->findOrFail($id);
        $lineId = (int)$request->get('statement_line_id');

        $line = BankStatementLine::query()
            ->where('reconciliation_id', $recon->id)
            ->findOrFail($lineId);

        return response()->json([
            'ok' => true,
            'suggestions' => $svc->suggestMatches($recon, $line, 5),
        ]);
    }

    public function close($id, BankReconciliationService $svc)
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.close'), 403);

        $recon = BankReconciliation::query()->findOrFail($id);
        $res = $svc->close($recon);

        return response()->json($res, $res['ok'] ? 200 : 422);
    }

    public function undoClose($id, BankReconciliationService $svc)
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.undo_close'), 403);

        $recon = BankReconciliation::query()->findOrFail($id);
        $svc->undoClose($recon);

        return response()->json(['ok' => true, 'message' => 'Reconciliation reopened.']);
    }
}