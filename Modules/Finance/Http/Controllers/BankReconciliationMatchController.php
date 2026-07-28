<?php
// File: Modules/Finance/Http/Controllers/BankReconciliationMatchController.php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Http\Requests\MatchStatementLineRequest;
use Modules\Finance\Models\BankStatementLine;
use Modules\Finance\Models\JournalEntryLine;
use Modules\Finance\Services\BankReconciliationService;

class BankReconciliationMatchController extends Controller
{
    public function match($id, MatchStatementLineRequest $request, BankReconciliationService $svc)
    {
        $line = BankStatementLine::query()->findOrFail($id);

        $jel = JournalEntryLine::query()->findOrFail((int)$request->validated()['journal_entry_line_id']);

        // Safety: enforce bank_account_id equality with reconciliation bank account
        $recon = $line->reconciliation()->firstOrFail();
        if ((int)$jel->bank_account_id !== (int)$recon->bank_account_id) {
            return response()->json(['ok' => false, 'message' => 'Selected journal line is not for this bank account.'], 422);
        }

        $svc->matchLine($line, $jel, 'manual');

        return response()->json(['ok' => true, 'message' => 'Matched successfully.']);
    }

    public function unmatch($id, BankReconciliationService $svc)
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.update'), 403);

        $line = BankStatementLine::query()->findOrFail($id);
        $svc->unmatchLine($line);

        return response()->json(['ok' => true, 'message' => 'Unmatched successfully.']);
    }

    public function exclude($id, Request $request, BankReconciliationService $svc)
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.update'), 403);

        $request->validate(['reason' => ['nullable','string','max:255']]);

        $line = BankStatementLine::query()->findOrFail($id);
        $svc->excludeLine($line, $request->input('reason'));

        return response()->json(['ok' => true, 'message' => 'Line excluded.']);
    }
    
    public function undoExclude($id)
    {
        abort_unless(auth()->user()->can('finance.bank_reconciliation.undo_exclude'), 403);
    
        $line = BankStatementLine::query()->findOrFail($id);
    
        if ($line->status !== 'excluded') {
            return response()->json([
                'ok' => false,
                'message' => 'Line is not excluded.'
            ], 422);
        }
    
        $line->status = 'unmatched';
        $line->exclude_reason = null;
        $line->save();
    
        return response()->json([
            'ok' => true,
            'message' => 'Exclusion undone.'
        ]);
    }
}