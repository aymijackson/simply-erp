<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Models\FixedAssets\FixedAssetDeprRun;
use Modules\Finance\Models\FixedAssets\FixedAssetDeprLine;
use Modules\Finance\Services\FixedAssets\DepreciationService;
use Modules\Finance\Services\JournalPostingService;

class DepreciationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_depreciation.view'), 403);

        $companyId = $request->user()->company_id;

        $runs = FixedAssetDeprRun::where('company_id',$companyId)->whereNull('deleted_at')->orderByDesc('id')->limit(80)->get();

        return view('finance.fixed_assets.depreciation', compact('runs'));
    }

    public function preview(Request $request, DepreciationService $svc)
    {
        abort_unless($request->user()->can('finance.fixed_asset_depreciation.run'), 403);

        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'run_date' => 'required|date',
        ]);

        $companyId = $request->user()->company_id;

        $assets = FixedAsset::where('company_id',$companyId)->whereNull('deleted_at')->where('status','active')->get();

        $lines = [];
        $total = 0;

        foreach ($assets as $a) {
            $amt = $svc->calcForPeriod($a, $data['period_start'], $data['period_end']);
            if ($amt > 0) {
                $lines[] = [
                    'asset_id' => $a->id,
                    'asset_code' => $a->asset_code,
                    'asset_name' => $a->name,
                    'amount' => $amt,
                ];
                $total += $amt;
            }
        }

        return response()->json(['ok'=>true,'count'=>count($lines),'total'=>round($total,2),'lines'=>$lines]);
    }

    public function run(Request $request, DepreciationService $svc)
    {
        abort_unless($request->user()->can('finance.fixed_asset_depreciation.run'), 403);

        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'run_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $companyId = $request->user()->company_id;

        $exists = FixedAssetDeprRun::where('company_id',$companyId)
            ->where('period_start',$data['period_start'])
            ->where('period_end',$data['period_end'])
            ->whereNull('deleted_at')
            ->whereIn('status',['draft','posted'])
            ->exists();
        if ($exists) return response()->json(['ok'=>false,'message'=>'Depreciation run already exists for this period.'], 422);

        $assets = FixedAsset::where('company_id',$companyId)->whereNull('deleted_at')->where('status','active')->get();

        return DB::transaction(function () use ($companyId, $data, $assets, $svc) {

            $run = FixedAssetDeprRun::create([
                'company_id' => $companyId,
                'run_no' => null,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'run_date' => $data['run_date'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $count = 0; $total = 0;

            foreach ($assets as $a) {
                $amt = $svc->calcForPeriod($a, $data['period_start'], $data['period_end']);
                if ($amt > 0) {
                    FixedAssetDeprLine::create([
                        'depr_run_id' => $run->id,
                        'asset_id' => $a->id,
                        'depr_date' => $data['period_end'],
                        'amount' => $amt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $count++; $total += $amt;
                }
            }

            return response()->json(['ok'=>true,'message'=>'Run created (Draft).','run_id'=>$run->id,'count'=>$count,'total'=>round($total,2)]);
        });
    }

    public function post(Request $request, $runId, JournalPostingService $jps)
    {
        abort_unless($request->user()->can('finance.fixed_asset_depreciation.post'), 403);

        $companyId = $request->user()->company_id;

        $run = FixedAssetDeprRun::where('company_id',$companyId)->where('id',$runId)->firstOrFail();
        if ($run->status !== 'draft') return response()->json(['ok'=>false,'message'=>'Only Draft runs can be posted.'], 422);

        $runLines = FixedAssetDeprLine::where('depr_run_id',$run->id)->get();
        if ($runLines->count() === 0) return response()->json(['ok'=>false,'message'=>'No depreciation lines to post.'], 422);

        $assetIds = $runLines->pluck('asset_id')->unique()->values()->all();
        $assets = FixedAsset::with('category')->where('company_id',$companyId)->whereIn('id',$assetIds)->get()->keyBy('id');

        // group: Dr Dep Exp, Cr Accum Dep
        $expTotals = [];
        $accTotals = [];

        foreach ($runLines as $l) {
            $a = $assets[$l->asset_id] ?? null;
            if (!$a) continue;

            if (!$a->depr_expense_account_id || !$a->accum_depr_account_id) {
                return response()->json(['ok'=>false,'message'=>"Asset {$a->asset_code} missing depreciation accounts."], 422);
            }

            $expTotals[$a->depr_expense_account_id] = ($expTotals[$a->depr_expense_account_id] ?? 0) + (float)$l->amount;
            $accTotals[$a->accum_depr_account_id] = ($accTotals[$a->accum_depr_account_id] ?? 0) + (float)$l->amount;
        }

        $journalLines = [];
        foreach ($expTotals as $acct => $amt) {
            $journalLines[] = [
                'account_id' => $acct,
                'description' => "Depreciation expense ({$run->period_start} to {$run->period_end})",
                'debit' => round($amt,2),
                'credit' => 0,
            ];
        }
        foreach ($accTotals as $acct => $amt) {
            $journalLines[] = [
                'account_id' => $acct,
                'description' => "Accumulated depreciation ({$run->period_start} to {$run->period_end})",
                'debit' => 0,
                'credit' => round($amt,2),
            ];
        }

        $jid = $jps->createJournal([
            'company_id' => $companyId,
            'entry_date' => $run->run_date,
            'reference' => "FA-DEPR {$run->period_start} to {$run->period_end}",
            'memo' => $run->notes,
            'status' => 'posted',
            'source_type' => 'fixed_asset_depr_run',
            'source_id' => $run->id,
            'posted_by' => $request->user()->id,
            'posted_at' => now(),
        ], $journalLines);

        $run->update([
            'status' => 'posted',
            'journal_entry_id' => $jid,
            'posted_at' => now(),
            'posted_by' => $request->user()->id,
            'updated_at' => now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Depreciation posted to GL.','journal_entry_id'=>$jid]);
    }

    public function void(Request $request, $runId, JournalPostingService $jps)
    {
        abort_unless($request->user()->can('finance.fixed_asset_depreciation.void'), 403);

        $companyId = $request->user()->company_id;

        $data = $request->validate(['reason'=>'required|string|max:255']);

        $run = FixedAssetDeprRun::where('company_id',$companyId)->where('id',$runId)->firstOrFail();
        if ($run->status !== 'posted') return response()->json(['ok'=>false,'message'=>'Only posted runs can be voided.'], 422);
        if (!$run->journal_entry_id) return response()->json(['ok'=>false,'message'=>'No linked journal to reverse.'], 422);

        try {
            $revId = $jps->reverseJournal($companyId, (int)$run->journal_entry_id, [
                'entry_date' => now()->toDateString(),
                'reference' => 'REV-FA-DEPR-'.$run->id,
                'memo' => 'Void depreciation run #'.$run->id.' - '.$data['reason'],
                'posted_by' => $request->user()->id,
                'source_type' => 'fixed_asset_depr_void',
                'source_id' => $run->id,
            ]);

            $run->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $request->user()->id,
                'void_reason' => $data['reason'],
                'updated_at' => now(),
            ]);

            return response()->json(['ok'=>true,'message'=>'Run voided and GL reversal posted.','reversal_journal_id'=>$revId]);

        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }
}