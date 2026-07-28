<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\Opportunity;

class OpportunityAnalyticsController extends BaseController
{
    public function index()
    {
        return view('crm.opportunities.analytics');
    }

    private function applyFilters($q, Request $r): void
    {
        if ($r->filled('customer_id')) $q->where('customer_id', $r->customer_id);
        if ($r->filled('owner_id'))    $q->where('owner_id', $r->owner_id);
        if ($r->filled('stage'))       $q->where('stage', $r->stage);

        // close date filters (most useful for pipeline reporting)
        if ($r->filled('close_from'))  $q->whereDate('close_date', '>=', $r->close_from);
        if ($r->filled('close_to'))    $q->whereDate('close_date', '<=', $r->close_to);

        // created date filters (optional)
        if ($r->filled('date_from'))   $q->whereDate('created_at', '>=', $r->date_from);
        if ($r->filled('date_to'))     $q->whereDate('created_at', '<=', $r->date_to);
    }

    public function kpis(Request $request)
    {
        $q = Opportunity::query();
        $this->applyFilters($q, $request);

        $total_count = (clone $q)->count();
        $total_value = (clone $q)->sum('value');

        // probability-weighted value (forecast)
        $weighted_value = (clone $q)
            ->select(DB::raw("SUM(value * (COALESCE(probability,0)/100)) as w"))
            ->value('w') ?? 0;

        // closing soon (next 30 days)
        $closing_soon = (clone $q)
            ->whereNotNull('close_date')
            ->whereBetween(DB::raw('DATE(close_date)'), [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();

        // high probability deals (>=70%)
        $high_prob_count = (clone $q)
            ->whereNotNull('probability')
            ->where('probability', '>=', 70)
            ->count();

        $avg_value = $total_count > 0 ? round($total_value / $total_count, 2) : 0;

        $this->audit(
            module: 'crm',
            action: 'opportunities.analytics_viewed_kpis',
            description: 'Viewed opportunities KPIs',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'total_count' => (int) $total_count,
            'total_value' => (float) $total_value,
            'weighted_value' => (float) $weighted_value,
            'avg_value' => (float) $avg_value,
            'closing_soon' => (int) $closing_soon,
            'high_prob_count' => (int) $high_prob_count,
        ]);
    }

    public function charts(Request $request)
    {
        $base = Opportunity::query();
        $this->applyFilters($base, $request);

        // Stage distribution (count + total value)
        $byStage = (clone $base)
            ->select(
                DB::raw("COALESCE(NULLIF(stage,''),'Unknown') as stage"),
                DB::raw("COUNT(*) as c"),
                DB::raw("SUM(value) as v")
            )
            ->groupBy('stage')
            ->orderByDesc('v')
            ->get();

        // Owner performance (top 10 by value)
        $topOwners = (clone $base)
            ->select('owner_id', DB::raw("COUNT(*) as c"), DB::raw("SUM(value) as v"))
            ->whereNotNull('owner_id')
            ->groupBy('owner_id')
            ->orderByDesc('v')
            ->limit(10)
            ->get();

        // Close date trend (daily value)
        $closeTrend = (clone $base)
            ->whereNotNull('close_date')
            ->select(DB::raw("DATE(close_date) as d"), DB::raw("SUM(value) as v"), DB::raw("COUNT(*) as c"))
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // Probability buckets
        $probBuckets = (clone $base)
            ->select(DB::raw("
                CASE
                  WHEN probability IS NULL THEN 'Unknown'
                  WHEN probability < 30 THEN '0-29'
                  WHEN probability < 60 THEN '30-59'
                  WHEN probability < 80 THEN '60-79'
                  ELSE '80-100'
                END as bucket
            "), DB::raw("COUNT(*) as c"), DB::raw("SUM(value) as v"))
            ->groupBy('bucket')
            ->orderByRaw("FIELD(bucket,'0-29','30-59','60-79','80-100','Unknown')")
            ->get();

        $this->audit(
            module: 'crm',
            action: 'opportunities.analytics_viewed_charts',
            description: 'Viewed opportunities charts',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'by_stage' => $byStage,
            'top_owners' => $topOwners,
            'close_trend' => $closeTrend,
            'prob_buckets' => $probBuckets,
        ]);
    }
}
