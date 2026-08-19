<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Models\FixedAssets\FixedAssetTransaction;
use Modules\Finance\Services\JournalPostingService;
use Modules\Finance\Services\FixedAssets\FixedAssetPostingService;

class FixedAssetTransactionController extends Controller
{
    public function index(Request $request, $assetId)
    {
        abort_unless($request->user()->can('finance.fixed_asset_transactions.view'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $asset = FixedAsset::where('company_id',$companyId)->where('id',$assetId)->firstOrFail();

        $txns = FixedAssetTransaction::where('company_id',$companyId)
            ->where('asset_id',$asset->id)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['ok'=>true,'data'=>$txns]);
    }

    public function store(Request $request, $assetId)
    {
        abort_unless($request->user()->can('finance.fixed_asset_transactions.create'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $asset = FixedAsset::with('category')->where('company_id',$companyId)->where('id',$assetId)->firstOrFail();

        $data = $request->validate([
            'txn_type' => 'required|in:acquisition,disposal',
            'txn_date' => 'required|date',
            'reference' => 'nullable|string|max:120',
            'memo' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'counter_account_id' => 'nullable|integer',
            'bank_account_id' => 'nullable|integer',
        ]);

        // controls by type
        if ($data['txn_type'] === 'acquisition') {
            if ($asset->status === 'disposed') {
                return response()->json(['ok'=>false,'message'=>'Cannot acquire into a disposed asset.'], 422);
            }
            if (empty($data['counter_account_id'])) {
                return response()->json(['ok'=>false,'message'=>'Counter account is required for acquisition (Bank/AP/Clearing).'], 422);
            }
        }

        if ($data['txn_type'] === 'disposal') {
            if ($asset->status !== 'active') {
                return response()->json(['ok'=>false,'message'=>'Only Active assets can be disposed.'], 422);
            }
            // if proceeds > 0, counter required
            if ((float)$data['amount'] > 0 && empty($data['counter_account_id'])) {
                return response()->json(['ok'=>false,'message'=>'Counter account required for disposal proceeds (Bank/Receivable).'], 422);
            }
        }

        $txn = FixedAssetTransaction::create([
            'company_id' => $companyId,
            'asset_id' => $asset->id,
            'txn_type' => $data['txn_type'],
            'txn_date' => $data['txn_date'],
            'reference' => $data['reference'] ?? null,
            'memo' => $data['memo'] ?? null,
            'amount' => round((float)$data['amount'],2),
            'counter_account_id' => $data['counter_account_id'] ?? null,
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Transaction created (Draft).', 'id'=>$txn->id]);
    }

    public function post(Request $request, $txnId, JournalPostingService $jps, FixedAssetPostingService $fps)
    {
        abort_unless($request->user()->can('finance.fixed_asset_transactions.post'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $txn = FixedAssetTransaction::where('company_id',$companyId)->where('id',$txnId)->firstOrFail();
        if ($txn->status !== 'draft') {
            return response()->json(['ok'=>false,'message'=>'Only Draft transactions can be posted.'], 422);
        }

        $asset = FixedAsset::with('category')->where('company_id',$companyId)->where('id',$txn->asset_id)->firstOrFail();

        try {
            $jid = null;

            if ($txn->txn_type === 'acquisition') {
                $jid = $fps->postAcquisition($companyId, $asset, $txn, $request->user()->id, $jps);

                // Optionally activate asset if still draft (some businesses do)
                if ($asset->status === 'draft') {
                    $asset->update(['status'=>'active']);
                }
            }

            if ($txn->txn_type === 'disposal') {
                $jid = $fps->postDisposal($companyId, $asset, $txn, $request->user()->id, $jps);

                $asset->update([
                    'status' => 'disposed',
                    'disposal_date' => $txn->txn_date,
                    'disposal_proceeds' => $txn->amount,
                    'disposal_notes' => $txn->memo,
                ]);
            }

            $txn->update([
                'status' => 'posted',
                'journal_entry_id' => $jid,
                'posted_at' => now(),
                'posted_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

            return response()->json(['ok'=>true,'message'=>'Transaction posted to GL.','journal_entry_id'=>$jid]);

        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }

    public function void(Request $request, $txnId, JournalPostingService $jps)
    {
        abort_unless($request->user()->can('finance.fixed_asset_transactions.void'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $txn = FixedAssetTransaction::where('company_id',$companyId)->where('id',$txnId)->firstOrFail();
        if ($txn->status !== 'posted') {
            return response()->json(['ok'=>false,'message'=>'Only posted transactions can be voided (will be reversed in GL).'], 422);
        }
        if (!$txn->journal_entry_id) {
            return response()->json(['ok'=>false,'message'=>'No linked journal to reverse.'], 422);
        }

        try {
            $revId = $jps->reverseJournal($companyId, (int)$txn->journal_entry_id, [
                'entry_date' => now()->toDateString(),
                'reference' => 'REV-FA-TXN-'.$txn->id,
                'memo' => 'Void FA transaction #'.$txn->id.' - '.$data['reason'],
                'posted_by' => $request->user()->id,
                'source_type' => 'fixed_asset_txn_void',
                'source_id' => $txn->id,
            ]);

            // update txn
            $txn->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $request->user()->id,
                'void_reason' => $data['reason'],
                'updated_at' => now(),
            ]);

            // If this was a disposal, re-open asset to active (business decision; sensible default)
            if ($txn->txn_type === 'disposal') {
                FixedAsset::where('company_id',$companyId)->where('id',$txn->asset_id)->update([
                    'status' => 'active',
                    'disposal_date' => null,
                    'disposal_proceeds' => null,
                    'disposal_notes' => null,
                ]);
            }

            return response()->json(['ok'=>true,'message'=>'Transaction voided and GL reversal posted.','reversal_journal_id'=>$revId]);

        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }
}