<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Services\FinanceHealthCheckService;

class FinanceHealthCheckController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.health_check.view'), 403);

        return view('finance.health_check.index');
    }

    public function run(Request $request, FinanceHealthCheckService $svc)
    {
        abort_unless($request->user()->can('finance.health_check.view'), 403);

        $companyId = (int) ($request->user()->company_id ?? 1);

        $result = $svc->run($companyId, (int) $request->user()->id);

        return response()->json([
            'ok' => true,
            'result' => $result,
        ]);
    }
}