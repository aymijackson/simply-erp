<?php
// File: Modules/Finance/Http/Controllers/BankReconciliationImportController.php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Finance\Http\Requests\ImportStatementCsvRequest;
use Modules\Finance\Models\BankReconciliation;
use Modules\Finance\Services\BankStatementCsvImporter;

class BankReconciliationImportController extends Controller
{
    public function import($id, ImportStatementCsvRequest $request, BankStatementCsvImporter $importer)
    {
        $recon = BankReconciliation::query()->findOrFail($id);

        if (in_array($recon->status, ['closed','void'], true)) {
            return response()->json(['ok' => false, 'message' => 'Reconciliation is closed/void.'], 422);
        }

        $result = $importer->import($recon, $request->file('file'));

        return response()->json([
            'ok' => true,
            'message' => 'Import completed.',
            'result' => $result,
        ]);
    }
}