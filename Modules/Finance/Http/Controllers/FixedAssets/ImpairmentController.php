<?php
/* =========================
   REMAINING CONTROLLERS
   ========================= */

/* -----------------------------------------------------------------
   1) ImpairmentController.php
   Path: Modules/Finance/Http/Controllers/FixedAssets/ImpairmentController.php
------------------------------------------------------------------*/
namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\Impairment;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Services\JournalPostingService;
use Modules\Finance\Services\FixedAssets\AdvancedAssetPostingService;

class ImpairmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_impairments.view'), 403);

        $companyId = $request->user()->company_id ?? 1;
        $accounts = DB::table('finance_accounts')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id','code','name']);

        return view('finance.fixed_assets.impairments', compact('accounts'));
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_impairments.view'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $rows = Impairment::where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data'=>$rows]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_impairments.create'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $data = $request->validate([
            'asset_id' => 'required|integer',
            'impair_date' => 'required|date',
            'impair_amount' => 'required|numeric|min:0.01',
            'impair_expense_account_id' => 'required|integer',
            'memo' => 'nullable|string|max:255',
        ]);

        $asset = FixedAsset::where('company_id',$companyId)->where('id',$data['asset_id'])->firstOrFail();
        if ($asset->status !== 'active') {
            return response()->json(['ok'=>false,'message'=>'Only Active assets can be impaired.'], 422);
        }

        Impairment::create([
            'company_id'=>$companyId,
            'asset_id'=>$asset->id,
            'impair_date'=>$data['impair_date'],
            'impair_amount'=>round((float)$data['impair_amount'],2),
            'impair_expense_account_id'=>$data['impair_expense_account_id'],
            'memo'=>$data['memo'] ?? null,
            'status'=>'draft',
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Impairment created (Draft).']);
    }

    public function post(Request $request, $id, JournalPostingService $jps, AdvancedAssetPostingService $svc)
    {
        abort_unless($request->user()->can('finance.fixed_asset_impairments.post'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = Impairment::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'draft') {
            return response()->json(['ok'=>false,'message'=>'Only Draft impairments can be posted.'], 422);
        }

        $asset = FixedAsset::with('category')->where('company_id',$companyId)->where('id',$row->asset_id)->firstOrFail();

        try {
            $jid = $svc->postImpairment($companyId, $row, $asset, $request->user()->id, $jps);

            // reduce asset cost by impairment amount (simple approach)
            $newCost = max(0, (float)$asset->purchase_cost - (float)$row->impair_amount);
            $asset->update(['purchase_cost'=>$newCost]);

            $row->update([
                'status'=>'posted',
                'journal_entry_id'=>$jid,
                'posted_at'=>now(),
                'posted_by'=>$request->user()->id,
                'updated_at'=>now(),
            ]);

            return response()->json(['ok'=>true,'message'=>'Impairment posted to GL and asset cost reduced.','journal_entry_id'=>$jid]);

        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }

    public function void(Request $request, $id, JournalPostingService $jps)
    {
        abort_unless($request->user()->can('finance.fixed_asset_impairments.void'), 403);

        $companyId = $request->user()->company_id ?? 1;
        $data = $request->validate(['reason'=>'required|string|max:255']);

        $row = Impairment::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'posted') {
            return response()->json(['ok'=>false,'message'=>'Only posted impairments can be voided.'], 422);
        }
        if (!$row->journal_entry_id) {
            return response()->json(['ok'=>false,'message'=>'No linked journal to reverse.'], 422);
        }

        try {
            $revId = $jps->reverseJournal($companyId, (int)$row->journal_entry_id, [
                'entry_date'=>now()->toDateString(),
                'reference'=>'REV-FA-IMPAIR-'.$row->id,
                'memo'=>'Void impairment #'.$row->id.' - '.$data['reason'],
                'posted_by'=>$request->user()->id,
                'source_type'=>'fixed_asset_impairment_void',
                'source_id'=>$row->id,
            ]);

            // restore asset cost back (simple approach)
            FixedAsset::where('company_id',$companyId)->where('id',$row->asset_id)->increment('purchase_cost', (float)$row->impair_amount);

            $row->update([
                'status'=>'voided',
                'voided_at'=>now(),
                'voided_by'=>$request->user()->id,
                'void_reason'=>$data['reason'],
                'updated_at'=>now(),
            ]);

            return response()->json(['ok'=>true,'message'=>'Impairment voided and GL reversal posted.','reversal_journal_id'=>$revId]);

        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }
}

?>