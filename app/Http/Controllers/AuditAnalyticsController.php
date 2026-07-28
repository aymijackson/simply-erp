<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class AuditAnalyticsController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function index()
    {
        return view('audit.analytics');
    }

    public function data(Request $request)
    {
        $this->authorize('core.audit.view.analytics');

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subDays(30)->startOfDay();
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : now()->endOfDay();

        $base = AuditLog::query()->whereBetween('created_at', [$from, $to]);

        $total = (clone $base)->count();

        $trend = (clone $base)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $topModules = (clone $base)
            ->selectRaw('module as k, COUNT(*) as c')
            ->groupBy('k')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $topActions = (clone $base)
            ->selectRaw('action as k, COUNT(*) as c')
            ->groupBy('k')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $topUsers = (clone $base)
            ->selectRaw('user_id, COUNT(*) as c')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('c')
            ->limit(10)
            ->with('user:id,name,email')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => optional($row->user)->name ?: 'Unknown',
                    'email' => optional($row->user)->email,
                    'count' => (int)$row->c,
                ];
            });

        // last 24h + today quick stats
        $today = (clone $base)->whereDate('created_at', now()->toDateString())->count();
        $last7 = AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count();
        $last24h = AuditLog::query()->where('created_at', '>=', now()->subHours(24))->count();

        return response()->json([
            'range' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'totals' => [
                'total' => $total,
                'today' => $today,
                'last7' => $last7,
                'last24h' => $last24h,
            ],
            'trend' => $trend,
            'topModules' => $topModules,
            'topActions' => $topActions,
            'topUsers' => $topUsers,
        ]);
    }
}
