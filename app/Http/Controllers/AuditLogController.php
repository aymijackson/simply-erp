<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // For filter dropdowns (fast enough; these are small sets)
        $modules = AuditLog::query()
            ->select('module')->distinct()->orderBy('module')->pluck('module');

        $actions = AuditLog::query()
            ->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('audit.index', compact('modules', 'actions'));
    }

    public function list(Request $request)
    {
        $q = AuditLog::query()
            ->with(['user:id,name,email'])
            ->select('audit_logs.*');

        // Filters
        if ($request->filled('module')) {
            $q->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $q->where('action', $request->action);
        }

        if ($request->filled('user')) {
            $user = trim($request->user);
            $q->whereHas('user', function ($uq) use ($user) {
                $uq->where('name', 'like', "%{$user}%")
                   ->orWhere('email', 'like', "%{$user}%");
            });
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($w) use ($term) {
                $w->where('description', 'like', "%{$term}%")
                  ->orWhere('route', 'like', "%{$term}%")
                  ->orWhere('url', 'like', "%{$term}%")
                  ->orWhere('ip', 'like', "%{$term}%");
            });
        }

        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subDays(7)->startOfDay();
            $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : now()->endOfDay();
            $q->whereBetween('created_at', [$from, $to]);
        }

        return DataTables::of($q)
            ->addColumn('dt', function ($log) {
                return optional($log->created_at)->format('Y-m-d H:i:s');
            })
            ->addColumn('user', function ($log) {
                if (!$log->user) return '<span class="badge bg-secondary">System</span>';
                $name = e($log->user->name);
                $email = e($log->user->email);
                return "<div class='fw-semibold'>{$name}</div><div class='text-muted small'>{$email}</div>";
            })
            ->addColumn('subject', function ($log) {
                if (!$log->subject_type || !$log->subject_id) return '<span class="text-muted">—</span>';
                $type = class_basename($log->subject_type);
                return "<span class='badge bg-light text-dark'>{$type} #{$log->subject_id}</span>";
            })
            ->addColumn('desc', function ($log) {
                $d = $log->description ?: '';
                $short = e(mb_strimwidth($d, 0, 120, '…'));
                $route = $log->route ? "<div class='text-muted small'>Route: ".e($log->route)."</div>" : "";
                return "<div>{$short}</div>{$route}";
            })
            ->addColumn('actions', function ($log) {
                $btn = '<button type="button" class="btn btn-sm btn-outline-primary view-log" data-id="'.$log->id.'">
                          <i class="fas fa-eye me-1"></i> View
                        </button>';
                return $btn;
            })
            ->rawColumns(['user','subject','desc','actions'])
            ->make(true);
    }

    public function show($id)
    {
        $log = AuditLog::query()->with('user:id,name,email')->findOrFail($id);

        return response()->json([
            'id'          => $log->id,
            'created_at'  => optional($log->created_at)->format('Y-m-d H:i:s'),
            'user'        => $log->user ? [
                'name' => $log->user->name,
                'email' => $log->user->email,
            ] : null,
            'module'      => $log->module,
            'action'      => $log->action,
            'description' => $log->description,
            'subject'     => [
                'type' => $log->subject_type,
                'id'   => $log->subject_id,
            ],
            'route'       => $log->route,
            'url'         => $log->url,
            'method'      => $log->method,
            'ip'  => $log->ip,
            'user_agent'  => $log->user_agent,
            'properties'  => $log->meta,
        ]);
    }

    /**
     * Export CSV using current filters.
     * Logs export action.
     */
    public function export(Request $request)
    {
        $this->authorize('audit.export');

        $q = AuditLog::query()->with('user:id,name,email');

        foreach (['module','action'] as $f) {
            if ($request->filled($f)) $q->where($f, $request->input($f));
        }

        if ($request->filled('user')) {
            $user = trim($request->user);
            $q->whereHas('user', function ($uq) use ($user) {
                $uq->where('name', 'like', "%{$user}%")
                   ->orWhere('email', 'like', "%{$user}%");
            });
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($w) use ($term) {
                $w->where('description', 'like', "%{$term}%")
                  ->orWhere('route', 'like', "%{$term}%")
                  ->orWhere('url', 'like', "%{$term}%")
                  ->orWhere('ip_address', 'like', "%{$term}%");
            });
        }

        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subDays(7)->startOfDay();
            $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : now()->endOfDay();
            $q->whereBetween('created_at', [$from, $to]);
        }

        $filename = 'audit_logs_' . now()->format('Ymd_His') . '.csv';

        // Log the export (best effort)
        if (auth()->check() && method_exists(auth()->user(), 'logActivity')) {
            auth()->user()->logActivity('audit', 'export', "Exported audit logs CSV", null, [
                'filters' => $request->only(['from','to','module','action','user','q'])
            ]);
        }

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'ID','DateTime','User','Email','Module','Action','Description',
                'SubjectType','SubjectId','Route','Method','IP','URL'
            ]);

            $q->orderByDesc('id')->chunk(2000, function ($rows) use ($out) {
                foreach ($rows as $log) {
                    fputcsv($out, [
                        $log->id,
                        optional($log->created_at)->format('Y-m-d H:i:s'),
                        optional($log->user)->name,
                        optional($log->user)->email,
                        $log->module,
                        $log->action,
                        $log->description,
                        $log->subject_type,
                        $log->subject_id,
                        $log->route,
                        $log->method,
                        $log->ip_address,
                        $log->url,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Retention purge (NOT clear all).
     * Deletes logs older than X days.
     * Logs purge action AFTER deleting.
     */
    public function purge(Request $request)
    {
        $this->authorize('audit.purge');

        $data = $request->validate([
            'days' => 'required|integer|min:30|max:3650',
            'confirm' => 'required|string'
        ]);

        if (strtoupper(trim($data['confirm'])) !== 'PURGE') {
            return response()->json(['message' => 'Confirmation text must be PURGE.'], 422);
        }

        $cutoff = now()->subDays((int)$data['days']);

        // count first
        $count = AuditLog::query()->where('created_at', '<', $cutoff)->count();

        // delete
        AuditLog::query()->where('created_at', '<', $cutoff)->delete();

        // log purge event (created AFTER delete so it remains)
        if (auth()->check() && method_exists(auth()->user(), 'logActivity')) {
            auth()->user()->logActivity('audit', 'purge', "Purged audit logs older than {$data['days']} days", null, [
                'days' => (int)$data['days'],
                'cutoff' => $cutoff->toDateTimeString(),
                'deleted_count' => $count,
            ]);
        }

        return response()->json([
            'message' => "Purged {$count} audit log(s) older than {$data['days']} days."
        ]);
    }
}
