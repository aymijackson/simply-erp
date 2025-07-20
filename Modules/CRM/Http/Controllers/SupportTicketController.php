<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\SupportTicket;
use Modules\CRM\Models\Customer;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\DataTables;
use App\Notifications\TicketCreated;
use Modules\CRM\Notifications\TicketStatusUpdated;
use Illuminate\Support\Facades\Mail;
use Modules\CRM\App\Mails\SupportTicketNotification;

class SupportTicketController extends Controller
{
    public function index()
    {
        $employees = Employee::select('id', 'first_name', 'last_name')->get();
        $customers = Customer::select('id', 'name')->get();
        return view('crm.support-tickets.index', compact('employees', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:open,in_progress,closed,pending,resolved',
            'customer_id' => 'nullable|exists:customers,id',
            'assigned_to' => 'nullable|exists:employees,id',
            'attachments.*' => 'nullable|file|max:5120', // max 5MB per file
        ]);

        SupportTicket::create($validated);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets', 'public');
                SupportTicketAttachment::create([
                    'support_ticket_id' => $ticket->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        $user = auth()->user(); // Or ticket->user if assigning
        $user->notify(new TicketCreated($ticket));

        return response()->json(['message' => 'Support ticket created successfully.']);
    }

    public function update(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:open,in_progress,closed,pending,resolved',
            'customer_id' => 'nullable|exists:customers,id',
            'assigned_to' => 'nullable|exists:employees,id',
        ]);

        $originalStatus = $ticket->getOriginal('status');

        $ticket->update($validated);

        if ($originalStatus !== $ticket->status) {
            $user = auth()->user(); // Or ticket->user
            $user->notify(new TicketStatusUpdated($ticket));
            Mail::to($ticket->employee->email)->send(
                new SupportTicketNotification($ticket, 'Support Ticket Status Updated')
            );
        }

        return response()->json(['message' => 'Support ticket updated successfully.']);
    }

    public function edit($id)
    {
        $ticket = SupportTicket::findOrFail($id);
        return response()->json($ticket);
    }

    public function destroy($id)
    {
        SupportTicket::findOrFail($id)->delete();
        return response()->json(['message' => 'Support ticket deleted.']);
    }

    public function deleteAttachment(SupportTicketAttachment $attachment)
{
    // Optional: Only allow uploader or admin
    if ($attachment->uploaded_by !== auth()->id() && !auth()->user()->is_admin) {
        abort(403);
    }

    Storage::disk('public')->delete($attachment->file_path);
    $attachment->delete();

    return response()->json(['message' => 'Attachment deleted successfully']);
}

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        SupportTicket::whereIn('id', $ids)->delete();
        return response()->json(['message' => 'Selected support tickets deleted.']);
    }

    public function datatable()
    {
        $tickets = SupportTicket::with(['employee', 'customer']);

        return DataTables::of($tickets)
            ->addColumn('checkbox', function ($ticket) {
                return '<input type="checkbox" class="row-checkbox" value="' . $ticket->id . '">';
            })
            ->addColumn('assigned_to', function ($ticket) {
                return optional($ticket->assignedTo)->full_name ?? 'Unassigned';
            })
            ->addColumn('created_by', function ($ticket) {
                return optional($ticket->createdBy)->full_name ?? 'System';
            })
            ->addColumn('title', function ($ticket) {
                return e($ticket->title);
            })
            ->addColumn('status', function ($ticket) {
                return ucfirst(str_replace('_', ' ', $ticket->status));
            })
            ->addColumn('priority', function ($ticket) {
                return ucfirst($ticket->priority);
            })
            ->addColumn('created_at', function ($ticket) {
                return $ticket->created_at->format('d M Y');
            })
            ->addColumn('actions', function ($ticket) {
                $record = htmlspecialchars(json_encode($ticket), ENT_QUOTES, 'UTF-8');
                return '
                    <button class="btn btn-sm btn-info edit-ticket" data-record="' . $record . '">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-ticket" data-id="' . $ticket->id . '">
                        <i class="fas fa-trash-alt"></i>
                    </button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function storeSupportTicketUpdate(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:support_tickets,id',
            'message' => 'required|string',
        ]);

        $update = SupportTicketUpdate::create([
            'support_ticket_id' => $request->ticket_id,
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        return response()->json(['message' => 'Update added', 'data' => $update]);
    }
}
