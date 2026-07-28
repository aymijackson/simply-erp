<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Finance\Models\ExchangeRate;
use Modules\Finance\Services\ExchangeRateService;

class ExchangeRateController extends Controller
{
    public function __construct(protected ExchangeRateService $service) {}

    // -------------------------------------------------------
    // Index
    // -------------------------------------------------------

    public function index(): View
    {
        return view('finance.exchange_rates.index');
    }

    // -------------------------------------------------------
    // Datatable (AJAX)
    // -------------------------------------------------------

    public function datatable(Request $request): JsonResponse
    {
        $rows = $this->service->datatable($request->only([
            'base_currency', 'quote_currency', 'is_active',
        ]));

        $data = $rows->map(fn(ExchangeRate $r) => [
            'id'             => $r->id,
            'base_currency'  => $r->base_currency,
            'quote_currency' => $r->quote_currency,
            'rate'           => number_format((float) $r->rate, 2),
            'rate_date'      => $r->rate_date?->format('d M Y'),
            'source'         => ucfirst($r->source),
            'is_active'      => $r->is_active,
            'created_at'     => $r->created_at?->format('d M Y'),
        ]);

        return response()->json(['data' => $data]);
    }

    // -------------------------------------------------------
    // Store
    // -------------------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'base_currency'  => 'required|string|size:3',
            'quote_currency' => 'required|string|size:3|different:base_currency',
            'rate'           => 'required|numeric|min:0.01',
            'rate_date'      => 'required|date',
            'source'         => 'required|in:manual,api,bank',
            'is_active'      => 'boolean',
        ]);

        $rate = $this->service->store($validated);

        return response()->json([
            'message' => 'Exchange rate created.',
            'rate'    => $rate,
        ], 201);
    }

    // -------------------------------------------------------
    // Show single (for edit modal)
    // -------------------------------------------------------

    public function show(ExchangeRate $exchangeRate): JsonResponse
    {
        return response()->json($exchangeRate);
    }

    // -------------------------------------------------------
    // Update
    // -------------------------------------------------------

    public function update(Request $request, ExchangeRate $exchangeRate): JsonResponse
    {
        $validated = $request->validate([
            'base_currency'  => 'required|string|size:3',
            'quote_currency' => 'required|string|size:3|different:base_currency',
            'rate'           => 'required|numeric|min:0.01',
            'rate_date'      => 'required|date',
            'source'         => 'required|in:manual,api,bank',
            'is_active'      => 'boolean',
        ]);

        $rate = $this->service->update($exchangeRate, $validated);

        return response()->json([
            'message' => 'Exchange rate updated.',
            'rate'    => $rate,
        ]);
    }

    // -------------------------------------------------------
    // Destroy
    // -------------------------------------------------------

    public function destroy(ExchangeRate $exchangeRate): JsonResponse
    {
        $this->service->destroy($exchangeRate);

        return response()->json(['message' => 'Exchange rate deleted.']);
    }

    // -------------------------------------------------------
    // Bulk delete
    // -------------------------------------------------------

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $count = $this->service->bulkDelete($request->ids);

        return response()->json(['message' => "$count record(s) deleted."]);
    }

    // -------------------------------------------------------
    // Toggle active
    // -------------------------------------------------------

    public function toggleActive(ExchangeRate $exchangeRate): JsonResponse
    {
        $rate = $this->service->toggleActive($exchangeRate);

        return response()->json([
            'message'   => 'Status updated.',
            'is_active' => $rate->is_active,
        ]);
    }

    // -------------------------------------------------------
    // Lookup – latest rate for a pair (used by other modules)
    // -------------------------------------------------------

    public function latestRate(Request $request): JsonResponse
    {
        $request->validate([
            'base'  => 'required|string|size:3',
            'quote' => 'required|string|size:3',
        ]);

        $rate = $this->service->latestRate(
            strtoupper($request->base),
            strtoupper($request->quote),
        );

        if (! $rate) {
            return response()->json(['rate' => null, 'message' => 'No rate found.'], 404);
        }

        return response()->json([
            'id'        => $rate->id,
            'rate'      => (float) $rate->rate,
            'rate_date' => $rate->rate_date->toDateString(),
        ]);
    }

    // -------------------------------------------------------
    // Lookup – active pairs (Select2 / dropdown use)
    // -------------------------------------------------------

    public function activePairs(): JsonResponse
    {
        return response()->json($this->service->activePairs());
    }
}