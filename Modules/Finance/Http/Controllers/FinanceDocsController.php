<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FinanceDocsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.reports.view') || $request->user()->can('finance.chart_of_accounts.view'), 403);

        $ctx = $this->context($request);

        return view('finance.docs.index', $ctx);
    }

    public function pdf(Request $request)
    {
        abort_unless($request->user()->can('finance.reports.view') || $request->user()->can('finance.chart_of_accounts.view'), 403);

        $ctx = $this->context($request);

        // Recommended: barryvdh/laravel-dompdf
        // composer require barryvdh/laravel-dompdf
        // then ensure provider is loaded (Laravel auto-discovery usually)

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            // Fallback: open print-friendly HTML if PDF lib not installed
            return response()
                ->view('finance.docs.pdf', $ctx)
                ->header('X-Info', 'Install barryvdh/laravel-dompdf to enable PDF download.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.docs.pdf', $ctx)
            ->setPaper('a4', 'portrait');

        $filename = 'finance-module-sop-workflow-architecture.pdf';
        return $pdf->download($filename);
    }

    private function context(Request $request): array
    {
        $company = $request->user()->company ?? null;

        return [
            'companyName' => $company->name ?? 'Your Company',
            'appName' => config('app.name', 'Simply-ERP'),
            'version' => config('app.version', '1.0.0'),
            'generatedAt' => now()->format('Y-m-d H:i'),
            'author' => $request->user()->name ?? 'System',
        ];
    }
}