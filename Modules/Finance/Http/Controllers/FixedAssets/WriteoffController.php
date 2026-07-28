<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\FixedAssets\Writeoff;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Services\JournalPostingService;
use Modules\Finance\Services\FixedAssets\AdvancedAssetPostingService;

class WriteoffController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_writeoffs.view'), 403);
        return view('finance.fixed_assets.writeoffs');
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_writeoffs.view'), 403);

        $companyId = $request->user()->company_id;

        $rows = Writeoff::where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data'=>$rows]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_writeoffs.create'), 403);

        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'asset_id' => 'required|integer',
            'writeoff_date' => 'required|date',
            'memo' => 'nullable|string|max:255',
        ]);

        $asset = FixedAsset::with('category')->where('company_id',$companyId)->where('id',$data['asset_id'])->firstOrFail();
        if ($asset->status !== 'active') {
            return response()->json(['ok'=>false,'message'=>'Only Active assets can be written off.'], 422);
        }

        $lossAcct = $asset->disposal_loss_account_id ?: $asset->category?->default_disposal_loss_account_id;
        if (!$lossAcct) {
            return response()->json(['ok'=>false,'message'=>'Write-off requires Disposal Loss account mapping (set category defaults).'], 422);
        }

        Writeoff::create([
            'company_id'=>$companyId,
            'asset_id'=>$asset->id,
            'writeoff_date'=>$data['writeoff_date'],
            'memo'=>$data['memo'] ?? null,
            'status'=>'draft',
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Write-off created (Draft).']);
    }

    public function post(Request $request, $id, JournalPostingService $jps, AdvancedAssetPostingService $svc)
    {
        abort_unless($request->user()->can('finance.fixed_asset_writeoffs.post'), 403);

        $companyId = $request->user()->company_id;

        $row = Writeoff::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'draft') {
            return response()->json(['ok'=>false,'message'=>'Only Draft write-offs can be posted.'], 422);
        }

        $asset = FixedAsset::with('category')->where('company_id',$companyId)->where('id',$row->asset_id)->firstOrFail();

        try {
            $jid = $svc->postWriteoff($companyId, $row, $asset, $request->user()->id, $jps);

            $asset->update([
                'status'=>'written_off',
                'disposal_date'=>$row->writeoff_date,
                'disposal_proceeds'=>0,
                'disposal_notes'=>$row->memo,
            ]);

            $row->update([
                'status'=>'posted',
                'journal_entry_id'=>$jid,
                'posted_at'=>now(),
                'posted_by'=>$request->user()->id,
                'updated_at'=>now(),
            ]);

            return response()->json(['ok'=>true,'message'=>'Write-off posted to GL and asset marked written_off.','journal_entry_id'=>$jid]);

        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }

    public function void(Request $request, $id, JournalPostingService $jps)
    {
        abort_unless($request->user()->can('finance.fixed_asset_writeoffs.void'), 403);

        $companyId = $request->user()->company_id;
        $data = $request->validate(['reason'=>'required|string|max:255']);

        $row = Writeoff::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'posted') {
            return response()->json(['ok'=>false,'message'=>'Only posted write-offs can be voided.'], 422);
        }
        if (!$row->journal_entry_id) {
            return response()->json(['ok'=>false,'message'=>'No linked journal to reverse.'], 422);
        }

        try {
            $revId = $jps->reverseJournal($companyId, (int)$row->journal_entry_id, [
                'entry_date'=>now()->toDateString(),
                'reference'=>'REV-FA-WRITEOFF-'.$row->id,
                'memo'=>'Void write-off #'.$row->id.' - '.$data['reason'],
                'posted_by'=>$request->user()->id,
                'source_type'=>'fixed_asset_writeoff_void',
                'source_id'=>$row->id,
            ]);

            // restore asset status (business default)
            FixedAsset::where('company_id',$companyId)->where('id',$row->asset_id)->update([
                'status'=>'active',
                'disposal_date'=>null,
                'disposal_proceeds'=>null,
                'disposal_notes'=>null,
            ]);

            $row->update([
                'status'=>'voided',
                'voided_at'=>now(),
                'voided_by'=>$request->user()->id,
                'void_reason'=>$data['reason'],
                'updated_at'=>now(),
            ]);

            return response()->json(['ok'=>true,'message'=>'Write-off voided and GL reversal posted.','reversal_journal_id'=>$revId]);

        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }
}