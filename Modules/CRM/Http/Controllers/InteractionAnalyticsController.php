<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\Interaction;

class InteractionAnalyticsController extends BaseController
{
    public function index()
    {
        return view('crm.interactions.analytics');
    }

    private function applyFilters($q, Request $r): void
    {
        if ($r->filled('employee_id')) $q->where('employee_id', $r->employee_id);
        if ($r->filled('interaction_type')) $q->where('interaction_type', $r->interaction_type);

        // Interactable filters
        if ($r->filled('interactable_type')) $q->where('interactable_type', $r->interactable_type);
        if ($r->filled('interactable_id'))   $q->where('interactable_id', $r->interactable_id);

        // Date range (interaction_date)
        if ($r->filled('date_from')) $q->whereDate('interaction_date', '>=', $r->date_from);
        if ($r->filled('date_to'))   $q->whereDate('interaction_date', '<=', $r->date_to);
    }

    public function kpis(Request $request)
    {
        $q = Interaction::query();
        $this->applyFilters($q, $request);

        $total = (clone $q)->count();

        $calls = (clone $q)->where('interaction_type', 'call')->count();
        $emails = (clone $q)->where('interaction_type', 'email')->count();
        $meetings = (clone $q)->where('interaction_type', 'meeting')->count();
        $visits = (clone $q)->where('interaction_type', 'visit')->count();
        $other = (clone $q)->where('interaction_type', 'other')->count();

        // Active period span (days) for avg/day
        $minDate = (clone $q)->min(DB::raw("DATE(interaction_date)"));
        $maxDate = (clone $q)->max(DB::raw("DATE(interaction_date)"));

        $days = 0;
        if ($minDate && $maxDate) {
            $days = max(1, now()->parse($minDate)->diffInDays(now()->parse($maxDate)) + 1);
        }
        $avgPerDay = $days > 0 ? round($total / $days, 2) : 0;

        // Top interactable type share
        $topType = (clone $q)
            ->select(DB::raw("COALESCE(NULLIF(interactable_type,''),'Unknown') as t"), DB::raw("COUNT(*) as c"))
            ->groupBy('t')
            ->orderByDesc('c')
            ->first();

        $this->audit(
            module: 'crm',
            action: 'interactions.analytics_viewed_kpis',
            description: 'Viewed interactions KPIs',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'total' => (int) $total,
            'calls' => (int) $calls,
            'emails' => (int) $emails,
            'meetings' => (int) $meetings,
            'visits' => (int) $visits,
            'other' => (int) $other,
            'avg_per_day' => (float) $avgPerDay,
            'top_interactable_type' => $topType?->t ? class_basename($topType->t) : '—',
            'top_interactable_count' => (int) ($topType?->c ?? 0),
        ]);
    }

    public function charts(Request $request)
    {
        $base = Interaction::query();
        $this->applyFilters($base, $request);

        // 1) By interaction type (count)
        $byType = (clone $base)
            ->select(DB::raw("COALESCE(NULLIF(interaction_type,''),'unknown') as t"), DB::raw("COUNT(*) as c"))
            ->groupBy('t')
            ->orderByDesc('c')
            ->get();

        // 2) By interactable type (count)
        $byRelatedType = (clone $base)
            ->select(DB::raw("COALESCE(NULLIF(interactable_type,''),'Unknown') as t"), DB::raw("COUNT(*) as c"))
            ->groupBy('t')
            ->orderByDesc('c')
            ->get()
            ->map(function ($r) {
                $r->t = $r->t === 'Unknown' ? 'Unknown' : class_basename($r->t);
                return $r;
            });

        // 3) Trend (daily count)
        $trend = (clone $base)
            ->select(DB::raw("DATE(interaction_date) as d"), DB::raw("COUNT(*) as c"))
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // 4) Top employees (count)
        $topEmployees = (clone $base)
            ->select('employee_id', DB::raw("COUNT(*) as c"))
            ->whereNotNull('employee_id')
            ->groupBy('employee_id')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $this->audit(
            module: 'crm',
            action: 'interactions.analytics_viewed_charts',
            description: 'Viewed interactions charts',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'by_type' => $byType,
            'by_related_type' => $byRelatedType,
            'trend' => $trend,
            'top_employees' => $topEmployees,
        ]);
    }
}
