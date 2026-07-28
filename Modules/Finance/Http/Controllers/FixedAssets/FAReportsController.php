<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\FixedAsset;

class FAReportsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_reports.view'), 403);
        return view('finance.fixed_assets.reports.index');
    }

    public function register(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_reports.view'), 403);

        $companyId = $request->user()->company_id;

        $assets = FixedAsset::with('category')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderBy('asset_code')
            ->get();

        $accumMap = DB::table('finance_fixed_asset_depr_lines')
            ->join('finance_fixed_asset_depr_runs','finance_fixed_asset_depr_runs.id','=','finance_fixed_asset_depr_lines.depr_run_id')
            ->where('finance_fixed_asset_depr_runs.company_id',$companyId)
            ->where('finance_fixed_asset_depr_runs.status','posted')
            ->whereNull('finance_fixed_asset_depr_runs.deleted_at')
            ->select('finance_fixed_asset_depr_lines.asset_id', DB::raw('SUM(finance_fixed_asset_depr_lines.amount) as total'))
            ->groupBy('finance_fixed_asset_depr_lines.asset_id')
            ->pluck('total','asset_id');

        return view('finance.fixed_assets.reports.register', compact('assets','accumMap'));
    }

    public function registerPdf(Request $request)
    {
        // Requires barryvdh/laravel-dompdf; if not present, fallback to HTML
        $html = $this->register($request)->render();

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4','landscape');
            return $pdf->download('fixed_assets_register.pdf');
        }

        return response($html);
    }

    public function depreciation(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_reports.view'), 403);

        $companyId = $request->user()->company_id;

        $runs = DB::table('finance_fixed_asset_depr_runs')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('finance.fixed_assets.reports.depreciation', compact('runs'));
    }

    public function depreciationPdf(Request $request)
    {
        $html = $this->depreciation($request)->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4','portrait')->download('fa_depreciation_summary.pdf');
        }
        return response($html);
    }

    public function movements(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_reports.view'), 403);
        $companyId = $request->user()->company_id;

        $txns = DB::table('finance_fixed_asset_transactions')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $transfers = DB::table('finance_fixed_asset_transfers')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('finance.fixed_assets.reports.movements', compact('txns','transfers'));
    }

    public function movementsPdf(Request $request)
    {
        $html = $this->movements($request)->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4','landscape')->download('fa_movements.pdf');
        }
        return response($html);
    }

    public function forecast(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_reports.view'), 403);

        $companyId = $request->user()->company_id;

        $assets = FixedAsset::where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->where('status','active')
            ->get();

        // simple monthly forecast: base/useful_life_months
        $rows = [];
        foreach ($assets as $a) {
            $base = max(0,(float)$a->purchase_cost - (float)$a->salvage_value);
            $perMonth = $a->useful_life_months > 0 ? round($base / $a->useful_life_months,2) : 0;
            $rows[] = [
                'asset_code'=>$a->asset_code,
                'name'=>$a->name,
                'per_month'=>$perMonth,
                'life_months'=>$a->useful_life_months,
            ];
        }

        return view('finance.fixed_assets.reports.forecast', compact('rows'));
    }

    public function forecastPdf(Request $request)
    {
        $html = $this->forecast($request)->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4','portrait')->download('fa_depreciation_forecast.pdf');
        }
        return response($html);
    }
}