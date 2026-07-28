<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Services\YearEndCloseService;

class YearCloseController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $years = DB::table('finance_fiscal_years')
            ->where('company_id', $companyId)
            ->orderBy('start_date', 'desc')
            ->get();

        $settings = DB::table('finance_company_settings')
            ->where('company_id', $companyId)
            ->first();

        $closes = DB::table('finance_year_closes as yc')
            ->join('finance_fiscal_years as fy', 'fy.id', '=', 'yc.fiscal_year_id')
            ->where('yc.company_id', $companyId)
            ->orderByDesc('yc.closed_at')
            ->select([
                'yc.id',
                'yc.fiscal_year_id',
                'fy.name as fiscal_year_name',
                'fy.start_date',
                'fy.end_date',
                'yc.net_profit',
                'yc.closing_journal_entry_id',
                'yc.closed_at',
                'yc.closed_by',
                'yc.note',
            ])
            ->get();
        
        return view('finance.year_close.index', compact('years','settings','closes'));
    }

    public function run(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $data = $request->validate([
            'fiscal_year_id' => ['required','integer'],
            'note' => ['nullable','string','max:255'],
        ]);

        $closingJeId = YearEndCloseService::closeYear($companyId, (int)$data['fiscal_year_id'], $data['note'] ?? null);

        return back()->with('success', $closingJeId
            ? "Year closed successfully. Closing journal posted (JE ID: {$closingJeId})."
            : "Year closed successfully. Net profit was 0.00 so no closing journal was posted."
        );
    }
}