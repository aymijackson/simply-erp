<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Services\FinanceDataFlushService;

class FinanceDataFlushController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.data_flush.view'), 403);

        return view('finance.data_flush.index');
    }

    public function preview(Request $request, FinanceDataFlushService $svc)
    {
        abort_unless($request->user()->can('finance.data_flush.view'), 403);

        $data = $this->validateRequest($request);

        if (!$this->hasAnySelection($data)) {
            return response()->json([
                'ok' => false,
                'message' => 'Select at least one flush category.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'summary' => $svc->preview(
                companyId: (int) ($request->user()->company_id ?? 1),
                options: $data
            ),
        ]);
    }

    public function run(Request $request, FinanceDataFlushService $svc)
    {
        abort_unless($request->user()->can('finance.data_flush.run'), 403);

        $data = $this->validateRequest($request);

        if (!$this->hasAnySelection($data)) {
            return response()->json([
                'ok' => false,
                'message' => 'Select at least one flush category.',
            ], 422);
        }

        if (($data['confirm_phrase'] ?? '') !== 'FLUSH FINANCE') {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid confirmation phrase. Type exactly: FLUSH FINANCE',
            ], 422);
        }

        if (!empty($data['include_setup']) && app()->environment('production')) {
            return response()->json([
                'ok' => false,
                'message' => 'Full finance setup flush is blocked in production.',
            ], 422);
        }

        $result = $svc->run(
            companyId: (int) ($request->user()->company_id ?? 1),
            options: $data,
            actorId: (int) $request->user()->id
        );

        return response()->json([
            'ok' => true,
            'message' => 'Finance data flush completed successfully.',
            'result' => $result,
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        return $request->validate([
            // Core finance transactional data
            'include_transactions'       => 'nullable|boolean',
            'include_journals'           => 'nullable|boolean',
            'include_banking'            => 'nullable|boolean',
            'include_bank_reconciliation'=> 'nullable|boolean',
            'include_budgets'            => 'nullable|boolean',

            // AR / AP
            'include_ar_ap'              => 'nullable|boolean',
            'include_receivables'        => 'nullable|boolean',
            'include_payables'           => 'nullable|boolean',

            // Operational finance modules
            'include_expenses'           => 'nullable|boolean',
            'include_fixed_assets'       => 'nullable|boolean',
            'include_petty_cash'         => 'nullable|boolean',
            'include_reconciliations'    => 'nullable|boolean',
            'include_tax'                => 'nullable|boolean',
            'include_payroll'            => 'nullable|boolean',

            // Setup / reset
            'include_setup'              => 'nullable|boolean',
            'reset_opening_balances'     => 'nullable|boolean',
            'reset_period_statuses'      => 'nullable|boolean',
            'reset_document_numbers'     => 'nullable|boolean',

            // Safety
            'confirm_phrase'             => 'nullable|string|max:100',
        ]);
    }

    protected function hasAnySelection(array $data): bool
    {
        return !empty($data['include_transactions'])
            || !empty($data['include_journals'])
            || !empty($data['include_banking'])
            || !empty($data['include_bank_reconciliation'])
            || !empty($data['include_budgets'])
            || !empty($data['include_ar_ap'])
            || !empty($data['include_receivables'])
            || !empty($data['include_payables'])
            || !empty($data['include_expenses'])
            || !empty($data['include_fixed_assets'])
            || !empty($data['include_petty_cash'])
            || !empty($data['include_reconciliations'])
            || !empty($data['include_tax'])
            || !empty($data['include_payroll'])
            || !empty($data['include_setup'])
            || !empty($data['reset_opening_balances'])
            || !empty($data['reset_period_statuses'])
            || !empty($data['reset_document_numbers']);
    }
}