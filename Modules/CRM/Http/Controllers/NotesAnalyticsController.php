<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\Note;

class NotesAnalyticsController extends BaseController
{
    public function index()
    {
        return view('crm.notes.analytics');
    }

    private function applyFilters($q, Request $r): void
    {
        if ($r->filled('author_id')) $q->where('author_id', $r->author_id);

        if ($r->filled('notable_type')) $q->where('notable_type', $r->notable_type);
        if ($r->filled('notable_id'))   $q->where('notable_id', $r->notable_id);

        // Created date range
        if ($r->filled('date_from')) $q->whereDate('created_at', '>=', $r->date_from);
        if ($r->filled('date_to'))   $q->whereDate('created_at', '<=', $r->date_to);
    }

    public function kpis(Request $request)
    {
        $q = Note::query();
        $this->applyFilters($q, $request);

        $total = (clone $q)->count();

        $today = now()->toDateString();
        $thisMonthStart = now()->startOfMonth()->toDateString();

        $createdToday = (clone $q)->whereDate('created_at', $today)->count();
        $createdThisMonth = (clone $q)->whereDate('created_at', '>=', $thisMonthStart)->count();

        $avgLen = (clone $q)->selectRaw('AVG(CHAR_LENGTH(content)) as a')->value('a');
        $avgLen = $avgLen ? round((float)$avgLen, 2) : 0;

        $topAuthor = (clone $q)
            ->select('author_id', DB::raw('COUNT(*) as c'))
            ->whereNotNull('author_id')
            ->groupBy('author_id')
            ->orderByDesc('c')
            ->first();

        $topNotableType = (clone $q)
            ->select('notable_type', DB::raw('COUNT(*) as c'))
            ->whereNotNull('notable_type')
            ->groupBy('notable_type')
            ->orderByDesc('c')
            ->first();

        $this->audit(
            module: 'crm',
            action: 'notes.analytics_viewed_kpis',
            description: 'Viewed notes KPIs',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'total' => (int) $total,
            'created_today' => (int) $createdToday,
            'created_this_month' => (int) $createdThisMonth,
            'avg_content_length' => (float) $avgLen,
            'top_author_id' => (int) ($topAuthor->author_id ?? 0),
            'top_author_count' => (int) ($topAuthor->c ?? 0),
            'top_notable_type' => (string) ($topNotableType->notable_type ?? ''),
            'top_notable_type_count' => (int) ($topNotableType->c ?? 0),
        ]);
    }

    public function charts(Request $request)
    {
        $base = Note::query();
        $this->applyFilters($base, $request);

        // 1) By notable type
        $byType = (clone $base)
            ->select(DB::raw("COALESCE(NULLIF(notable_type,''),'unknown') as t"), DB::raw("COUNT(*) as c"))
            ->groupBy('t')
            ->orderByDesc('c')
            ->get();

        // 2) Trend (notes created per day)
        $trend = (clone $base)
            ->select(DB::raw("DATE(created_at) as d"), DB::raw("COUNT(*) as c"))
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // 3) Top authors
        $topAuthors = (clone $base)
            ->select('author_id', DB::raw("COUNT(*) as c"))
            ->whereNotNull('author_id')
            ->groupBy('author_id')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        // 4) “Longest notes” (by content length)
        $longest = (clone $base)
            ->select('id', 'subject', 'author_id', DB::raw("CHAR_LENGTH(content) as len"), 'created_at')
            ->orderByDesc('len')
            ->limit(10)
            ->get();

        $this->audit(
            module: 'crm',
            action: 'notes.analytics_viewed_charts',
            description: 'Viewed notes charts',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'by_type' => $byType,
            'trend' => $trend,
            'top_authors' => $topAuthors,
            'longest' => $longest,
        ]);
    }
}
