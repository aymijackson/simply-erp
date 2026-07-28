<?php

namespace Modules\Finance\Services\FixedAssets;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Models\FixedAssets\Revaluation;
use Modules\Finance\Models\FixedAssets\Impairment;
use Modules\Finance\Models\FixedAssets\Writeoff;
use Modules\Finance\Services\JournalPostingService;

class AdvancedAssetPostingService
{
    public function postRevaluation(int $companyId, Revaluation $reval, FixedAsset $asset, int $userId, JournalPostingService $jps): int
    {
        if ($reval->delta == 0) throw new \RuntimeException('Revaluation delta cannot be zero.');
        if (!$asset->asset_account_id) throw new \RuntimeException('Asset account missing.');

        if (!$reval->revaluation_account_id) {
            throw new \RuntimeException('Revaluation account required (reserve or P&L depending on method).');
        }

        // delta > 0: Dr Asset / Cr RevalReserve(or income)
        // delta < 0: Dr RevalReserve(or expense) / Cr Asset
        $delta = round((float)$reval->delta,2);

        $lines = [];
        if ($delta > 0) {
            $lines[] = ['account_id'=>$asset->asset_account_id,'debit'=>$delta,'credit'=>0,'description'=>"FA Revaluation increase: {$asset->asset_code}"];
            $lines[] = ['account_id'=>$reval->revaluation_account_id,'debit'=>0,'credit'=>$delta,'description'=>"Revaluation credit: {$asset->asset_code}"];
        } else {
            $d = round(abs($delta),2);
            $lines[] = ['account_id'=>$reval->revaluation_account_id,'debit'=>$d,'credit'=>0,'description'=>"Revaluation debit: {$asset->asset_code}"];
            $lines[] = ['account_id'=>$asset->asset_account_id,'debit'=>0,'credit'=>$d,'description'=>"FA Revaluation decrease: {$asset->asset_code}"];
        }

        return $jps->createJournal([
            'company_id'=>$companyId,
            'entry_date'=>$reval->reval_date,
            'reference'=>"FA-REVAL {$asset->asset_code}",
            'memo'=>$reval->memo,
            'status'=>'posted',
            'source_type'=>'fixed_asset_revaluation',
            'source_id'=>$reval->id,
            'posted_by'=>$userId,
            'posted_at'=>now(),
        ], $lines);
    }

    public function postImpairment(int $companyId, Impairment $imp, FixedAsset $asset, int $userId, JournalPostingService $jps): int
    {
        if ($imp->impair_amount <= 0) throw new \RuntimeException('Impairment amount must be > 0.');
        if (!$asset->asset_account_id) throw new \RuntimeException('Asset account missing.');

        // Dr Impairment Expense / Cr Asset (write down cost)
        $amt = round((float)$imp->impair_amount,2);

        $lines = [
            ['account_id'=>$imp->impair_expense_account_id,'debit'=>$amt,'credit'=>0,'description'=>"Impairment expense: {$asset->asset_code}"],
            ['account_id'=>$asset->asset_account_id,'debit'=>0,'credit'=>$amt,'description'=>"Asset write-down: {$asset->asset_code}"],
        ];

        return $jps->createJournal([
            'company_id'=>$companyId,
            'entry_date'=>$imp->impair_date,
            'reference'=>"FA-IMPAIR {$asset->asset_code}",
            'memo'=>$imp->memo,
            'status'=>'posted',
            'source_type'=>'fixed_asset_impairment',
            'source_id'=>$imp->id,
            'posted_by'=>$userId,
            'posted_at'=>now(),
        ], $lines);
    }

    public function postWriteoff(int $companyId, Writeoff $wo, FixedAsset $asset, int $userId, JournalPostingService $jps): int
    {
        // Write-off = disposal with proceeds = 0 (gain/loss becomes loss=NBV)
        // We'll reuse the same logic you already have in FixedAssetPostingService->postDisposal by creating a synthetic txn shape
        // But here we do it directly for clarity.

        if (!$asset->asset_account_id || !$asset->accum_depr_account_id) {
            throw new \RuntimeException('Asset/Accum Dep accounts missing.');
        }

        // posted depreciation to date
        $accum = (float)DB::table('finance_fixed_asset_depr_lines')
            ->join('finance_fixed_asset_depr_runs','finance_fixed_asset_depr_runs.id','=','finance_fixed_asset_depr_lines.depr_run_id')
            ->whereNull('finance_fixed_asset_depr_runs.deleted_at')
            ->where('finance_fixed_asset_depr_runs.status','posted')
            ->where('finance_fixed_asset_depr_lines.asset_id',$asset->id)
            ->sum('finance_fixed_asset_depr_lines.amount');

        $cost = (float)$asset->purchase_cost;
        $nbv = max(0, $cost - $accum);

        // Loss account
        $lossAcct = $asset->disposal_loss_account_id ?: $asset->category?->default_disposal_loss_account_id;
        if (!$lossAcct) throw new \RuntimeException('Write-off requires Disposal Loss account mapping.');

        $lines = [];

        if ($accum > 0) {
            $lines[] = ['account_id'=>$asset->accum_depr_account_id,'debit'=>round($accum,2),'credit'=>0,'description'=>"Reverse accum dep (WO): {$asset->asset_code}"];
        }

        $lines[] = ['account_id'=>$asset->asset_account_id,'debit'=>0,'credit'=>round($cost,2),'description'=>"Remove asset cost (WO): {$asset->asset_code}"];

        if ($nbv > 0) {
            $lines[] = ['account_id'=>$lossAcct,'debit'=>round($nbv,2),'credit'=>0,'description'=>"Write-off loss (NBV): {$asset->asset_code}"];
        }

        return $jps->createJournal([
            'company_id'=>$companyId,
            'entry_date'=>$wo->writeoff_date,
            'reference'=>"FA-WRITEOFF {$asset->asset_code}",
            'memo'=>$wo->memo,
            'status'=>'posted',
            'source_type'=>'fixed_asset_writeoff',
            'source_id'=>$wo->id,
            'posted_by'=>$userId,
            'posted_at'=>now(),
        ], $lines);
    }
}