<?php

namespace Modules\Finance\Services\FixedAssets;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Models\FixedAssets\FixedAssetTransaction;
use Modules\Finance\Services\JournalPostingService;

class FixedAssetPostingService
{
    public function postAcquisition(int $companyId, FixedAsset $asset, FixedAssetTransaction $txn, int $userId, JournalPostingService $jps): int
    {
        // Dr Fixed Asset / Cr Counter account (Bank/AP/Clearing)
        if (!$asset->asset_account_id) throw new \RuntimeException('Asset account is missing.');
        if (!$txn->counter_account_id) throw new \RuntimeException('Counter account is required (Bank/AP/Clearing).');
        if ($txn->amount <= 0) throw new \RuntimeException('Acquisition amount must be > 0.');

        $lines = [
            [
                'account_id' => $asset->asset_account_id,
                'description' => "FA Acquisition: {$asset->asset_code} {$asset->name}",
                'debit' => round((float)$txn->amount,2),
                'credit' => 0,
            ],
            [
                'account_id' => $txn->counter_account_id,
                'description' => "FA Acquisition offset: {$asset->asset_code}",
                'debit' => 0,
                'credit' => round((float)$txn->amount,2),
                'bank_account_id' => $txn->bank_account_id, // set if counter is bank/cash
            ],
        ];

        $jid = $jps->createJournal([
            'company_id' => $companyId,
            'entry_date' => $txn->txn_date,
            'reference' => $txn->reference ?? ("FA-ACQ {$asset->asset_code}"),
            'memo' => $txn->memo,
            'status' => 'posted',
            'source_type' => 'fixed_asset_txn',
            'source_id' => $txn->id,
            'posted_by' => $userId,
            'posted_at' => now(),
        ], $lines);

        return $jid;
    }

    public function postDisposal(int $companyId, FixedAsset $asset, FixedAssetTransaction $txn, int $userId, JournalPostingService $jps): int
    {
        // Disposal journal:
        // Dr Bank/Proceeds (if any)
        // Dr Accum Dep (to date)
        // Cr Fixed Asset (cost)
        // Gain: Cr Gain acct  OR Loss: Dr Loss acct

        if (!$asset->asset_account_id) throw new \RuntimeException('Asset account is missing.');
        if (!$asset->accum_depr_account_id) throw new \RuntimeException('Accumulated depreciation account is missing.');
        if (!$txn->counter_account_id && (float)$txn->amount > 0) {
            throw new \RuntimeException('Counter account is required for proceeds (Bank/Receivable).');
        }

        // posted depreciation to date
        $accum = (float)DB::table('finance_fixed_asset_depr_lines')
            ->join('finance_fixed_asset_depr_runs','finance_fixed_asset_depr_runs.id','=','finance_fixed_asset_depr_lines.depr_run_id')
            ->whereNull('finance_fixed_asset_depr_runs.deleted_at')
            ->where('finance_fixed_asset_depr_runs.status','posted')
            ->where('finance_fixed_asset_depr_lines.asset_id',$asset->id)
            ->sum('finance_fixed_asset_depr_lines.amount');

        $cost = (float)$asset->purchase_cost;
        $nbv  = max(0, $cost - $accum);
        $proceeds = max(0, (float)$txn->amount);

        $gainLoss = round($proceeds - $nbv, 2); // +gain, -loss

        $gainAcct = $asset->disposal_gain_account_id ?: $asset->category?->default_disposal_gain_account_id;
        $lossAcct = $asset->disposal_loss_account_id ?: $asset->category?->default_disposal_loss_account_id;

        if ($gainLoss > 0 && !$gainAcct) throw new \RuntimeException('Disposal Gain account missing (set in category or asset).');
        if ($gainLoss < 0 && !$lossAcct) throw new \RuntimeException('Disposal Loss account missing (set in category or asset).');

        $lines = [];

        if ($proceeds > 0) {
            $lines[] = [
                'account_id' => $txn->counter_account_id,
                'description' => "FA Disposal proceeds: {$asset->asset_code}",
                'debit' => round($proceeds,2),
                'credit' => 0,
                'bank_account_id' => $txn->bank_account_id,
            ];
        }

        if ($accum > 0) {
            $lines[] = [
                'account_id' => $asset->accum_depr_account_id,
                'description' => "Reverse accum. depreciation: {$asset->asset_code}",
                'debit' => round($accum,2),
                'credit' => 0,
            ];
        }

        // remove asset cost
        $lines[] = [
            'account_id' => $asset->asset_account_id,
            'description' => "Remove asset cost: {$asset->asset_code}",
            'debit' => 0,
            'credit' => round($cost,2),
        ];

        // gain or loss
        if ($gainLoss > 0) {
            $lines[] = [
                'account_id' => $gainAcct,
                'description' => "Gain on disposal: {$asset->asset_code}",
                'debit' => 0,
                'credit' => round($gainLoss,2),
            ];
        } elseif ($gainLoss < 0) {
            $lines[] = [
                'account_id' => $lossAcct,
                'description' => "Loss on disposal: {$asset->asset_code}",
                'debit' => round(abs($gainLoss),2),
                'credit' => 0,
            ];
        }

        $jid = $jps->createJournal([
            'company_id' => $companyId,
            'entry_date' => $txn->txn_date,
            'reference' => $txn->reference ?? ("FA-DISP {$asset->asset_code}"),
            'memo' => $txn->memo,
            'status' => 'posted',
            'source_type' => 'fixed_asset_txn',
            'source_id' => $txn->id,
            'posted_by' => $userId,
            'posted_at' => now(),
        ], $lines);

        return $jid;
    }
}