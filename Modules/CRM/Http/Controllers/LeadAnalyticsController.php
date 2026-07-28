<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\Lead;

class LeadAnalyticsController extends BaseController
{
    public function index()
    {
        // Just loads the page. Filters will be AJAX.
        return view('crm.leads.analytics');
    }

    private function applyFilters($q, Request $r): void
    {
        if ($r->filled('company_id')) $q->where('company_id', $r->company_id);
        if ($r->filled('status'))     $q->where('status', $r->status);
        if ($r->filled('assigned_to'))$q->where('assigned_to', $r->assigned_to);
        if ($r->filled('source'))     $q->where('source', $r->source);

        if ($r->filled('date_from'))  $q->whereDate('created_at', '>=', $r->date_from);
        if ($r->filled('date_to'))    $q->whereDate('created_at', '<=', $r->date_to);
    }

    public function kpis(Request $request)
    {
        $q = Lead::query();
        $this->applyFilters($q, $request);

        $total = (clone $q)->count();

        $new        = (clone $q)->where('status','new')->count();
        $contacted  = (clone $q)->where('status','contacted')->count();
        $qualified  = (clone $q)->where('status','qualified')->count();
        $converted  = (clone $q)->where('status','converted')->count();
        $closed     = (clone $q)->where('status','closed')->count();

        // Overdue follow-up: follow_up_date in past AND not converted/closed
        $overdue_followups = (clone $q)
            ->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<', now()->toDateString())
            ->whereNotIn('status', ['converted','closed'])
            ->count();

        $conversion_rate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

        $this->audit(
            module: 'crm',
            action: 'leads.analytics_viewed_kpis',
            description: 'Viewed leads KPIs',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'total' => $total,
            'new' => $new,
            'contacted' => $contacted,
            'qualified' => $qualified,
            'converted' => $converted,
            'closed' => $closed,
            'overdue_followups' => $overdue_followups,
            'conversion_rate' => $conversion_rate,
        ]);
    }

    public function charts(Request $request)
    {
        $base = Lead::query();
        $this->applyFilters($base, $request);

        // Status distribution
        $byStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->orderByDesc('c')
            ->get();

        // Source distribution
        $bySource = (clone $base)
            ->select(DB::raw("COALESCE(NULLIF(source,''),'Unknown') as source"), DB::raw('COUNT(*) as c'))
            ->groupBy('source')
            ->orderByDesc('c')
            ->limit(12)
            ->get();

        // Leads created trend (daily)
        $trend = (clone $base)
            ->select(DB::raw("DATE(created_at) as d"), DB::raw("COUNT(*) as c"))
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // Top assignees
        $topAssignees = (clone $base)
            ->select('assigned_to', DB::raw('COUNT(*) as c'))
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $this->audit(
            module: 'crm',
            action: 'leads.analytics_viewed_charts',
            description: 'Viewed leads charts',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'by_status' => $byStatus,
            'by_source' => $bySource,
            'trend' => $trend,
            'top_assignees' => $topAssignees,
        ]);
    }
}
