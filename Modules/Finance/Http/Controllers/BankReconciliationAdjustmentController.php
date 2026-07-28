<?php
// File: Modules/Finance/Http/Controllers/BankReconciliationAdjustmentController.php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Finance\Http\Requests\CreateAdjustmentRequest;
use Modules\Finance\Models\BankStatementLine;
use Modules\Finance\Services\BankReconciliationService;

class BankReconciliationAdjustmentController extends Controller
{
    public function createAdjustment($id, CreateAdjustmentRequest $request, BankReconciliationService $svc)
    {
        $line = BankStatementLine::query()->findOrFail($id);

        $svc->createAdjustmentAndMatch($line, $request->validated());

        return response()->json(['ok' => true, 'message' => 'Adjustment posted and matched.']);
    }
}