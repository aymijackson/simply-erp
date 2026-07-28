<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiscalPeriodController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $years = DB::table('finance_fiscal_years')
            ->where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get();
    
        $periodsQuery = DB::table('finance_fiscal_periods as p')
            ->leftJoin('finance_fiscal_years as y', 'y.id', '=', 'p.fiscal_year_id')
            ->where('p.company_id', $companyId)
            ->select([
                'p.*',
                'y.name as fiscal_year_name'
            ])
            ->orderByDesc('p.start_date');
    
        if ($request->filled('fiscal_year_id')) {
            $periodsQuery->where('p.fiscal_year_id', $request->fiscal_year_id);
        }
    
        if ($request->status === 'open') {
            $periodsQuery->where('p.is_closed', 0);
        }
    
        if ($request->status === 'closed') {
            $periodsQuery->where('p.is_closed', 1);
        }
    
        $periods = $periodsQuery->get();
    
        return view('finance.periods.index', compact('years', 'periods', 'companyId'));
    }

    public function close(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $request->validate([
            'lock_note' => ['nullable','string','max:255'],
        ]);

        DB::transaction(function () use ($companyId, $id, $request) {

            $period = DB::table('finance_fiscal_periods')
                ->where('company_id', $companyId)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            abort_unless($period, 404);

            // Optional safety: prevent closing if there are unposted journals in this period
            $hasDrafts = DB::table('finance_journal_entries')
                ->where('company_id', $companyId)
                ->whereBetween('entry_date', [$period->start_date, $period->end_date])
                ->where('status', '!=', 'posted')
                ->exists();

            if ($hasDrafts) {
                abort(422, 'Cannot close period: there are unposted/draft journal entries within this period.');
            }

            DB::table('finance_fiscal_periods')
                ->where('id', $id)
                ->update([
                    'is_closed' => 1,
                    'closed_at' => now(),
                    'closed_by' => auth()->id(),
                    'reopened_at' => null,
                    'reopened_by' => null,
                    'lock_note' => $request->lock_note,
                ]);
        });

        return back()->with('success', 'Fiscal period closed.');
    }

    public function reopen(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $request->validate([
            'lock_note' => ['nullable','string','max:255'],
        ]);

        DB::table('finance_fiscal_periods')
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->update([
                'is_closed' => 0,
                'reopened_at' => now(),
                'reopened_by' => auth()->id(),
                'lock_note' => $request->lock_note,
            ]);

        return back()->with('success', 'Fiscal period reopened.');
    }
}
