<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Modules\Sales\Services\SalesDataFlushService;

class SalesDataFlushController extends BaseController
{
    public function __construct()
    {
        $this->middleware('can:sales.flush.manage');
    }

    public function index()
    {
        return view('sales.data_flush.index');
    }

    public function preview(Request $request, SalesDataFlushService $svc)
    {
        $data = $this->validateRequest($request);
        return response()->json([
            'ok' => true,
            'summary' => $svc->preview($data),
        ]);
    }

    public function run(Request $request, SalesDataFlushService $svc)
    {
        $data = $this->validateRequest($request);

        if (($data['confirm_phrase'] ?? '') !== 'FLUSH SALES') {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid confirmation phrase. Type exactly: FLUSH SALES',
            ], 422);
        }

        // Optional production safety (recommended)
        if (app()->environment('production') && !empty($data['include_posted'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Posted flush is blocked in production.',
            ], 403);
        }
        
        $result = $svc->run($data, $request);
        
        $this->audit(
            module: 'sales',
            action: 'sales.data.flush',
            description: 'Flushed sales data from ERP',
            subject: 'Flushed sales data',
            meta: ['flushed'=>$data, 'payload' => $request]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Sales flush completed.',
            'result' => $result,
        ]);
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'scope' => 'required|in:draft_only,date_range,customer,full_reset',
            'include_posted' => 'nullable|boolean',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'modules' => 'required|array',
            'modules.*' => 'in:orders,deliveries,invoices,payments,credit_notes,allocations',
            'confirm_phrase' => 'nullable|string',
        ]);
    }
}
