<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Modules\CRM\Models\SupportTicket;
use Modules\CRM\Models\SupportTicketComment;
use Modules\CRM\Models\SupportTicketAttachment;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\Facades\DataTables;

class SupportTicketsController extends BaseController
{
    public function index()
    {
        $employees = Employee::select('id','first_name','last_name')
            ->orderBy('first_name')
            ->get();

        return view('crm.support-tickets.index', compact('employees'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['customer','assignee','creator','comments.author','attachments.uploader']);

        $employees = Employee::select('id','first_name','last_name')
            ->orderBy('first_name')
            ->get();

        return view('crm.support-tickets.show', compact('ticket','employees'));
    }

    public function datatable(Request $request)
    {
        $query = SupportTicket::query()
            ->with(['customer','assignee'])
            ->select('support_tickets.*');

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('priority')) $query->where('priority', $request->priority);
        if ($request->filled('assigned_to')) $query->where('assigned_to', $request->assigned_to);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);

        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);

        return DataTables::eloquent($query)
            ->addColumn('checkbox', fn($t) => '<input type="checkbox" class="row-checkbox" value="'.$t->id.'">')

            ->addColumn('customer_name', fn($t) => $t->customer?->name ?? '—')

            ->addColumn('assignee_name', function ($t) {
                $a = $t->assignee;
                if (!$a) return '—';
                $full = trim(($a->first_name ?? '').' '.($a->last_name ?? ''));
                return $full !== '' ? $full : ('Employee #'.$a->id);
            })

            ->addColumn('created_at_fmt', fn($t) => optional($t->created_at)->format('d-m-Y h:i a'))

            ->addColumn('actions', function ($t) {
                $view = route('admin.crm.support_tickets.show', $t->id);

                $record = htmlspecialchars(json_encode([
                    'id' => $t->id,
                    'subject' => $t->subject,
                    'description' => $t->description,
                    'status' => $t->status,
                    'priority' => $t->priority,
                    'channel' => $t->channel,
                    'category' => $t->category,
                    'customer_id' => $t->customer_id,
                    'customer_name' => $t->customer?->name ?? '',
                    'assigned_to' => $t->assigned_to,
                ]), ENT_QUOTES, 'UTF-8');

                $btnView = '<a class="btn btn-sm btn-secondary" href="'.$view.'">View</a>';

                $btnEdit = auth()->user()->can('crm.support_tickets.update')
                    ? '<button class="btn btn-sm btn-info edit-ticket" data-record="'.$record.'">Edit</button>'
                    : '';

                $btnDel = auth()->user()->can('crm.support_tickets.delete')
                    ? '<button class="btn btn-sm btn-danger delete-ticket" data-id="'.$t->id.'">Delete</button>'
                    : '';

                return trim($btnView.' '.$btnEdit.' '.$btnDel);
            })

            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'     => 'required|string|max:255',
            'description' => 'required|string',
            'status'      => 'required|in:open,pending,resolved,closed',
            'priority'    => 'required|in:low,medium,high,urgent',
            'channel'     => 'nullable|in:web,email,phone,whatsapp,other',
            'category'    => 'nullable|in:billing,technical,account,other',
            'customer_id' => 'required|exists:customers,id',
            'assigned_to' => 'nullable|exists:employees,id',
        ]);

        $data['ticket_no'] = 'TCK-'.now()->format('ymd').'-'.strtoupper(Str::random(6));

        // if your app has "current employee" helper, replace this safely
        $data['created_by'] = optional(auth()->user())->employee_id ?? null;

        $ticket = SupportTicket::create($data);

        $this->audit(
            module: 'crm',
            action: 'support_tickets.created',
            description: 'Created support ticket: '.$ticket->ticket_no,
            subject: $ticket,
            meta: ['ticket_id' => $ticket->id, 'ticket_no' => $ticket->ticket_no]
        );

        return response()->json(['message' => 'Ticket created successfully.']);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'subject'     => 'required|string|max:255',
            'description' => 'required|string',
            'status'      => 'required|in:open,pending,resolved,closed',
            'priority'    => 'required|in:low,medium,high,urgent',
            'channel'     => 'nullable|in:web,email,phone,whatsapp,other',
            'category'    => 'nullable|in:billing,technical,sales,other',
            'customer_id' => 'required|exists:customers,id',
            'assigned_to' => 'nullable|exists:employees,id',
        ]);

        $before = $ticket->only(array_keys($data));
        $ticket->update($data);
        $after = $ticket->fresh()->only(array_keys($data));

        $this->audit(
            module: 'crm',
            action: 'support_tickets.updated',
            description: 'Updated support ticket: '.$ticket->ticket_no,
            subject: $ticket,
            meta: ['before' => $before, 'after' => $after]
        );

        return response()->json(['message' => 'Ticket updated successfully.']);
    }

    public function destroy(SupportTicket $ticket)
    {
        $snapshot = $ticket->only(['id','ticket_no','subject','status','priority','customer_id','assigned_to']);
        $ticket->delete();

        $this->audit(
            module: 'crm',
            action: 'support_tickets.deleted',
            description: 'Deleted support ticket: '.$snapshot['ticket_no'],
            subject: null,
            meta: ['deleted' => $snapshot]
        );

        return response()->json(['message' => 'Ticket deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:support_tickets,id',
        ]);

        $count = SupportTicket::whereIn('id', $data['ids'])->count();
        SupportTicket::whereIn('id', $data['ids'])->delete();

        $this->audit(
            module: 'crm',
            action: 'support_tickets.bulk_deleted',
            description: "Bulk deleted {$count} ticket(s).",
            subject: null,
            meta: ['ids' => $data['ids'], 'count' => $count]
        );

        return response()->json(['message' => 'Selected tickets deleted successfully.']);
    }

    public function addComment(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'author_id' => 'required|exists:employees,id',
        ]);

        $comment = $ticket->comments()->create($data);

        $this->audit(
            module: 'crm',
            action: 'support_tickets.comment_added',
            description: 'Added comment to ticket: '.$ticket->ticket_no,
            subject: $ticket,
            meta: ['comment_id' => $comment->id]
        );

        return response()->json(['message' => 'Comment added successfully.']);
    }

    public function addAttachment(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'file' => 'required|file|max:5120', // 5MB
            'uploaded_by' => 'required|exists:employees,id',
        ]);

        $file = $request->file('file');
        $path = $file->store("crm/tickets/{$ticket->id}", 'public');

        $att = SupportTicketAttachment::create([
            'ticket_id' => $ticket->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $data['uploaded_by'],
        ]);

        $this->audit(
            module: 'crm',
            action: 'support_tickets.attachment_added',
            description: 'Added attachment to ticket: '.$ticket->ticket_no,
            subject: $ticket,
            meta: ['attachment_id' => $att->id, 'file' => $att->file_name]
        );

        return response()->json(['message' => 'Attachment uploaded successfully.']);
    }

    public function deleteAttachment(SupportTicket $ticket, SupportTicketAttachment $attachment)
    {
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        if ($attachment->file_path) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $snapshot = $attachment->only(['id','file_name','file_path','mime','size']);
        $attachment->delete();

        $this->audit(
            module: 'crm',
            action: 'support_tickets.attachment_deleted',
            description: 'Deleted attachment from ticket: '.$ticket->ticket_no,
            subject: $ticket,
            meta: ['deleted' => $snapshot]
        );

        return response()->json(['message' => 'Attachment deleted successfully.']);
    }
    
    // ---------------------------
    // Analytics (VIEW)
    // ---------------------------
    public function analytics()
    {
        $employees = Employee::select('id','first_name','last_name')
            ->orderBy('first_name')
            ->get();

        return view('crm.support-tickets.analytics', compact('employees'));
    }

    // ---------------------------
    // Analytics (KPIs)
    // ---------------------------
    public function analyticsKpis(Request $request)
    {
        $q = SupportTicket::query();
        $this->applyAnalyticsFilters($q, $request);

        $total     = (clone $q)->count();
        $open      = (clone $q)->where('status','open')->count();
        $pending   = (clone $q)->where('status','pending')->count();
        $resolved  = (clone $q)->where('status','resolved')->count();
        $closed    = (clone $q)->where('status','closed')->count();

        // Avg first response (mins) where first_response_at is present
        $avgFirstResponse = (clone $q)
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) AS v')
            ->value('v');

        // Avg resolution (mins) where resolved_at is present
        $avgResolution = (clone $q)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) AS v')
            ->value('v');

        // Backlog (open + pending)
        $backlog = (clone $q)->whereIn('status',['open','pending'])->count();

        return response()->json([
            'total' => $total,
            'open' => $open,
            'pending' => $pending,
            'resolved' => $resolved,
            'closed' => $closed,
            'backlog' => $backlog,
            'avg_first_response_mins' => $avgFirstResponse ? round((float)$avgFirstResponse, 2) : null,
            'avg_resolution_mins' => $avgResolution ? round((float)$avgResolution, 2) : null,
        ]);
    }

    // ---------------------------
    // Analytics (Trends + Distributions)
    // ---------------------------
    public function analyticsTrends(Request $request)
    {
        // Created per day
        $qCreated = SupportTicket::query();
        $this->applyAnalyticsFilters($qCreated, $request);

        $created = $qCreated
            ->selectRaw('DATE(created_at) AS d, COUNT(*) AS c')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // Resolved per day
        $qResolved = SupportTicket::query()->whereNotNull('resolved_at');
        $this->applyAnalyticsFilters($qResolved, $request);

        $resolved = $qResolved
            ->selectRaw('DATE(resolved_at) AS d, COUNT(*) AS c')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // Status distribution
        $qStatus = SupportTicket::query();
        $this->applyAnalyticsFilters($qStatus, $request);

        $status = $qStatus
            ->selectRaw('status, COUNT(*) AS c')
            ->groupBy('status')
            ->orderByRaw("FIELD(status,'open','pending','resolved','closed')")
            ->get();

        // Priority backlog (open/pending only)
        $qPriority = SupportTicket::query()->whereIn('status',['open','pending']);
        $this->applyAnalyticsFilters($qPriority, $request);

        $priority = $qPriority
            ->selectRaw('priority, COUNT(*) AS c')
            ->groupBy('priority')
            ->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
            ->get();

        return response()->json([
            'created' => $created,
            'resolved' => $resolved,
            'status' => $status,
            'priority_backlog' => $priority,
        ]);
    }

    // ---------------------------
    // Analytics (Aging buckets)
    // ---------------------------
    public function analyticsAging(Request $request)
    {
        $q = SupportTicket::query()->whereIn('status',['open','pending']);
        $this->applyAnalyticsFilters($q, $request);

        $rows = $q->selectRaw("
            CASE
              WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60 THEN '< 1 hour'
              WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 240 THEN '1-4 hours'
              WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 1440 THEN '4-24 hours'
              WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 4320 THEN '1-3 days'
              WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 10080 THEN '3-7 days'
              ELSE '> 7 days'
            END AS bucket,
            COUNT(*) AS c
        ")
        ->groupBy('bucket')
        ->get();

        // Keep stable ordering in UI
        $order = ['< 1 hour','1-4 hours','4-24 hours','1-3 days','3-7 days','> 7 days'];
        $sorted = collect($order)->map(function($b) use ($rows){
            $found = $rows->firstWhere('bucket', $b);
            return ['bucket' => $b, 'c' => $found?->c ?? 0];
        });

        return response()->json(['aging' => $sorted->values()]);
    }

    // ---------------------------
    // Analytics (Agents workload + performance)
    // ---------------------------
    public function analyticsAgents(Request $request)
    {
        // Workload (open/pending)
        $qWork = SupportTicket::query()->whereIn('status',['open','pending']);
        $this->applyAnalyticsFilters($qWork, $request);

        $workload = $qWork
            ->leftJoin('employees as e', 'e.id', '=', 'support_tickets.assigned_to')
            ->selectRaw("
                support_tickets.assigned_to AS employee_id,
                TRIM(CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,''))) AS employee_name,
                COUNT(*) AS c
            ")
            ->groupBy('employee_id','employee_name')
            ->orderByDesc('c')
            ->get();

        // Avg first response by agent
        $qFR = SupportTicket::query()->whereNotNull('first_response_at');
        $this->applyAnalyticsFilters($qFR, $request);

        $firstResponse = $qFR
            ->leftJoin('employees as e', 'e.id', '=', 'support_tickets.assigned_to')
            ->selectRaw("
                support_tickets.assigned_to AS employee_id,
                TRIM(CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,''))) AS employee_name,
                AVG(TIMESTAMPDIFF(MINUTE, support_tickets.created_at, support_tickets.first_response_at)) AS v
            ")
            ->groupBy('employee_id','employee_name')
            ->orderBy('v')
            ->get();

        // Avg resolution by agent
        $qRes = SupportTicket::query()->whereNotNull('resolved_at');
        $this->applyAnalyticsFilters($qRes, $request);

        $resolution = $qRes
            ->leftJoin('employees as e', 'e.id', '=', 'support_tickets.assigned_to')
            ->selectRaw("
                support_tickets.assigned_to AS employee_id,
                TRIM(CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,''))) AS employee_name,
                AVG(TIMESTAMPDIFF(MINUTE, support_tickets.created_at, support_tickets.resolved_at)) AS v
            ")
            ->groupBy('employee_id','employee_name')
            ->orderBy('v')
            ->get();

        return response()->json([
            'workload' => $workload,
            'first_response' => $firstResponse,
            'resolution' => $resolution,
        ]);
    }

    // ---------------------------
    // Shared filter helper
    // ---------------------------
    private function applyAnalyticsFilters($query, Request $request): void
    {
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('priority')) $query->where('priority', $request->priority);
        if ($request->filled('assigned_to')) $query->where('assigned_to', $request->assigned_to);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);

        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
    }
    
    public function exportAnalyticsCsv(Request $request)
    {
        $q = SupportTicket::query()->with(['customer','assignee'])->select('support_tickets.*');
        $this->applyAnalyticsFilters($q, $request);
    
        $tickets = $q->orderByDesc('id')->get();
    
        $filename = 'support_tickets_analytics_' . now()->format('Ymd_His') . '.csv';
    
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
        ];
    
        $callback = function() use ($tickets) {
            $out = fopen('php://output', 'w');
    
            fputcsv($out, [
                'Ticket No','Customer','Subject','Status','Priority','Channel','Category',
                'Assigned To','Created','Resolved At'
            ]);
    
            foreach ($tickets as $t) {
                $assigned = $t->assignee ? trim(($t->assignee->first_name ?? '').' '.($t->assignee->last_name ?? '')) : '—';
    
                fputcsv($out, [
                    $t->ticket_no,
                    $t->customer?->name ?? '—',
                    $t->subject,
                    $t->status,
                    $t->priority,
                    $t->channel,
                    $t->category,
                    $assigned ?: '—',
                    optional($t->created_at)->format('d-m-Y h:i a'),
                    optional($t->resolved_at)->format('d-m-Y h:i a'),
                ]);
            }
    
            fclose($out);
        };
    
        $this->audit(
            module: 'crm',
            action: 'support_tickets.analytics_exported_csv',
            description: 'Exported support tickets analytics (CSV)',
            subject: null,
            meta: ['filters' => $request->all(), 'count' => $tickets->count()]
        );
    
        return response()->stream($callback, 200, $headers);
    }
    
    public function exportAnalyticsPdf(Request $request)
    {
        $q = SupportTicket::query()->with(['customer','assignee'])->select('support_tickets.*');
        $this->applyAnalyticsFilters($q, $request);
    
        $tickets = $q->orderByDesc('id')->get();
    
        // Build KPI snapshot using your KPI endpoint logic
        $kpiReq = new Request($request->all());
        $kpis = json_decode($this->analyticsKpis($kpiReq)->getContent(), true);
    
        $data = [
            'generated_at' => now()->format('d-m-Y h:i a'),
            'filters' => $request->all(),
            'kpis' => $kpis,
            'tickets' => $tickets,
        ];
    
        $this->audit(
            module: 'crm',
            action: 'support_tickets.analytics_exported_pdf',
            description: 'Exported support tickets analytics (PDF)',
            subject: null,
            meta: ['filters' => $request->all(), 'count' => $tickets->count()]
        );
    
        $pdf = Pdf::loadView('crm.support-tickets.analytics-pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->download('support_tickets_analytics_' . now()->format('Ymd_His') . '.pdf');
    }
}
