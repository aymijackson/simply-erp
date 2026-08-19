<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Services\FinanceInitialisationService;

class FinanceInitialisationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.initialisation.view'),403);

        return view('finance.initialisation.index');
    }

    public function preview(Request $request,FinanceInitialisationService $svc)
    {
        $companyId = $request->user()->company_id ?? 1;

        return response()->json([
            'ok'=>true,
            'summary'=>$svc->preview($companyId)
        ]);
    }

    public function run(Request $request,FinanceInitialisationService $svc)
    {
        abort_unless($request->user()->can('finance.initialisation.run'),403);

        if($request->confirm_phrase !== 'INITIALISE FINANCE'){
            return response()->json([
                'ok'=>false,
                'message'=>'Type INITIALISE FINANCE to confirm'
            ],422);
        }

        $companyId = $request->user()->company_id ?? 1;

        $result = $svc->run(
            companyId:$companyId,
            actorId:$request->user()->id
        );

        return response()->json([
            'ok'=>true,
            'message'=>'Finance system initialised successfully',
            'result'=>$result
        ]);
    }
}