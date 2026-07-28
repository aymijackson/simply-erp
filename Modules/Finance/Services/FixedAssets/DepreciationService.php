<?php

namespace Modules\Finance\Services\FixedAssets;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\FixedAsset;

class DepreciationService
{
    public function calcForPeriod(FixedAsset $asset, string $periodStart, string $periodEnd): float
    {
        if ($asset->status !== 'active') return 0.0;

        $start = new \DateTime($periodStart);
        $end   = new \DateTime($periodEnd);
        $inSvc = new \DateTime($asset->in_service_date);

        if ($end < $inSvc) return 0.0;

        $effStart = $start < $inSvc ? $inSvc : $start;

        $m1 = ((int)$effStart->format('Y'))*12 + (int)$effStart->format('n');
        $m2 = ((int)$end->format('Y'))*12 + (int)$end->format('n');
        $months = max(0, $m2 - $m1 + 1);
        if ($months <= 0) return 0.0;

        // posted depreciation to date
        $already = (float)DB::table('finance_fixed_asset_depr_lines')
            ->join('finance_fixed_asset_depr_runs','finance_fixed_asset_depr_runs.id','=','finance_fixed_asset_depr_lines.depr_run_id')
            ->whereNull('finance_fixed_asset_depr_runs.deleted_at')
            ->where('finance_fixed_asset_depr_runs.status','posted')
            ->where('finance_fixed_asset_depr_lines.asset_id',$asset->id)
            ->sum('finance_fixed_asset_depr_lines.amount');

        $base = max(0, (float)$asset->purchase_cost - (float)$asset->salvage_value);
        $remaining = max(0, $base - $already);
        if ($remaining <= 0) return 0.0;

        // straight-line (recommended default)
        $perMonth = $asset->useful_life_months > 0 ? round($base / $asset->useful_life_months, 2) : 0.0;
        $amt = round($perMonth * $months, 2);

        return min($amt, $remaining);
    }
    
    public function calcComponentForPeriod($component, $periodStart, $periodEnd): float
    {
        // Straight-line example:
        $base = max(0, (float)$component->cost - (float)$component->salvage_value);
        if ((int)$component->useful_life_months <= 0) return 0;
    
        // monthly depreciation
        $perMonth = $base / (int)$component->useful_life_months;
    
        // if you want exact by day, upgrade later; for now: monthly based on period length
        // assume monthly period
        $amt = $perMonth;
    
        return round(max(0, $amt), 2);
    }
}