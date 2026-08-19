<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\Revaluation;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Services\JournalPostingService;
use Modules\Finance\Services\FixedAssets\AdvancedAssetPostingService;

class RevaluationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_revaluations.view'), 403);

        $companyId = $request->user()->company_id ?? 1;
        $accounts = DB::table('finance_accounts')->where('company_id',$companyId)->whereNull('deleted_at')->orderBy('name')->get(['id','code','name']);

        return view('finance.fixed_assets.revaluations', compact('accounts'));
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_revaluations.view'), 403);
        $companyId = $request->user()->company_id ?? 1;

        $rows = Revaluation::where('company_id',$companyId)->whereNull('deleted_at')->orderByDesc('id')->get();
        return response()->json(['data'=>$rows]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_revaluations.create'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $data = $request->validate([
            'asset_id' => 'required|integer',
            'reval_date' => 'required|date',
            'new_cost' => 'required|numeric|min:0',
            'method' => 'required|in:reserve,pnl',
            'revaluation_account_id' => 'required|integer',
            'memo' => 'nullable|string|max:255',
        ]);

        $asset = FixedAsset::with('category')->where('company_id',$companyId)->where('id',$data['asset_id'])->firstOrFail();
        if ($asset->status !== 'active') return response()->json(['ok'=>false,'message'=>'Only Active assets can be revalued.'], 422);

        $old = (float)$asset->purchase_cost;
        $new = (float)$data['new_cost'];
        $delta = round($new - $old, 2);

        Revaluation::create([
            'company_id'=>$companyId,
            'asset_id'=>$asset->id,
            'reval_date'=>$data['reval_date'],
            'old_cost'=>$old,
            'new_cost'=>$new,
            'delta'=>$delta,
            'method'=>$data['method'],
            'revaluation_account_id'=>$data['revaluation_account_id'],
            'memo'=>$data['memo'] ?? null,
            'status'=>'draft',
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Revaluation created (Draft).']);
    }

    public function post(Request $request, $id, JournalPostingService $jps, AdvancedAssetPostingService $svc)
    {
        abort_unless($request->user()->can('finance.fixed_asset_revaluations.post'), 403);

        $companyId = $request->user()->company_id ?? 1;
        $row = Revaluation::where('company_id',$companyId)->where('id',$id)->firstOrFail();

        if ($row->status !== 'draft') return response()->json(['ok'=>false,'message'=>'Only Draft revaluations can be posted.'], 422);

        $asset = FixedAsset::with('category')->where('company_id',$companyId)->where('id',$row->asset_id)->firstOrFail();

        try {
            $jid = $svc->postRevaluation($companyId, $row, $asset, $request->user()->id, $jps);

            // Update asset master cost
            $asset->update(['purchase_cost'=>$row->new_cost]);

            $row->update([
                'status'=>'posted',
                'journal_entry_id'=>$jid,
                'posted_at'=>now(),
                'posted_by'=>$request->user()->id,
                'updated_at'=>now(),
            ]);

            return response()->json(['ok'=>true,'message'=>'Revaluation posted to GL and asset cost updated.','journal_entry_id'=>$jid]);

        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }

    public function void(Request $request, $id, JournalPostingService $jps)
    {
        abort_unless($request->user()->can('finance.fixed_asset_revaluations.void'), 403);

        $companyId = $request->user()->company_id ?? 1;
        $data = $request->validate(['reason'=>'required|string|max:255']);

        $row = Revaluation::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'posted') return response()->json(['ok'=>false,'message'=>'Only posted revaluations can be voided.'], 422);
        if (!$row->journal_entry_id) return response()->json(['ok'=>false,'message'=>'No linked journal to reverse.'], 422);

        try {
            $revId = $jps->reverseJournal($companyId, (int)$row->journal_entry_id, [
                'entry_date'=>now()->toDateString(),
                'reference'=>'REV-FA-REVAL-'.$row->id,
                'memo'=>'Void revaluation #'.$row->id.' - '.$data['reason'],
                'posted_by'=>$request->user()->id,
                'source_type'=>'fixed_asset_revaluation_void',
                'source_id'=>$row->id,
            ]);

            // revert asset cost back
            FixedAsset::where('company_id',$companyId)->where('id',$row->asset_id)->update([
                'purchase_cost'=>$row->old_cost
            ]);

            $row->update([
                'status'=>'voided',
                'voided_at'=>now(),
                'voided_by'=>$request->user()->id,
                'void_reason'=>$data['reason'],
                'updated_at'=>now(),
            ]);

            return response()->json(['ok'=>true,'message'=>'Revaluation voided and GL reversal posted.','reversal_journal_id'=>$revId]);

        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }
}