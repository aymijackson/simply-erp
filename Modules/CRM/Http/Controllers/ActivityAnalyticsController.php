<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\Activity;

class ActivityAnalyticsController extends BaseController
{
    public function index()
    {
        return view('crm.activities.analytics');
    }

    private function applyFilters($q, Request $r): void
    {
        if ($r->filled('owner_id'))       $q->where('owner_id', $r->owner_id);
        if ($r->filled('status'))         $q->where('status', $r->status);
        if ($r->filled('activity_type'))  $q->where('activity_type', $r->activity_type);

        // Related filters (optional)
        if ($r->filled('related_type')) $q->where('related_type', $r->related_type);
        if ($r->filled('related_id'))   $q->where('related_id', $r->related_id);

        // Due date range
        if ($r->filled('due_from')) $q->whereDate('due_date', '>=', $r->due_from);
        if ($r->filled('due_to'))   $q->whereDate('due_date', '<=', $r->due_to);
    }

    public function kpis(Request $request)
    {
        $q = Activity::query();
        $this->applyFilters($q, $request);

        $total     = (clone $q)->count();
        $pending   = (clone $q)->where('status', 'pending')->count();
        $completed = (clone $q)->where('status', 'completed')->count();
        $overdue   = (clone $q)->where('status', 'overdue')->count();

        $today = now()->toDateString();
        $dueToday = (clone $q)->whereDate('due_date', $today)->count();

        $next7 = now()->addDays(7)->toDateString();
        $dueNext7 = (clone $q)->whereDate('due_date', '>=', $today)
                              ->whereDate('due_date', '<=', $next7)
                              ->count();

        $completionRate = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        // Top owner
        $topOwner = (clone $q)
            ->select('owner_id', DB::raw('COUNT(*) as c'))
            ->whereNotNull('owner_id')
            ->groupBy('owner_id')
            ->orderByDesc('c')
            ->first();

        $this->audit(
            module: 'crm',
            action: 'activities.analytics_viewed_kpis',
            description: 'Viewed activities KPIs',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'total' => (int) $total,
            'pending' => (int) $pending,
            'completed' => (int) $completed,
            'overdue' => (int) $overdue,
            'due_today' => (int) $dueToday,
            'due_next_7' => (int) $dueNext7,
            'completion_rate' => (float) $completionRate,
            'top_owner_id' => (int) ($topOwner->owner_id ?? 0),
            'top_owner_count' => (int) ($topOwner->c ?? 0),
        ]);
    }

    public function charts(Request $request)
    {
        $base = Activity::query();
        $this->applyFilters($base, $request);

        // 1) By status
        $byStatus = (clone $base)
            ->select(DB::raw("COALESCE(NULLIF(status,''),'unknown') as s"), DB::raw("COUNT(*) as c"))
            ->groupBy('s')
            ->orderByDesc('c')
            ->get();

        // 2) By type
        $byType = (clone $base)
            ->select(DB::raw("COALESCE(NULLIF(activity_type,''),'unknown') as t"), DB::raw("COUNT(*) as c"))
            ->groupBy('t')
            ->orderByDesc('c')
            ->get();

        // 3) Due-date trend (daily due count)
        $trend = (clone $base)
            ->select(DB::raw("DATE(due_date) as d"), DB::raw("COUNT(*) as c"))
            ->whereNotNull('due_date')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // 4) Top owners (count)
        $topOwners = (clone $base)
            ->select('owner_id', DB::raw("COUNT(*) as c"))
            ->whereNotNull('owner_id')
            ->groupBy('owner_id')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $this->audit(
            module: 'crm',
            action: 'activities.analytics_viewed_charts',
            description: 'Viewed activities charts',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'by_status' => $byStatus,
            'by_type' => $byType,
            'trend' => $trend,
            'top_owners' => $topOwners,
        ]);
    }
}
