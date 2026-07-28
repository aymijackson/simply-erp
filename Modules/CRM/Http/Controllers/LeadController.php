<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\Lead;
use App\Models\Company;
use Modules\HRM\Models\Employee;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    public function index()
    {
        $companies = Company::select('id','name')->orderBy('name')->get();
        $employees = Employee::select('id','first_name','last_name')->orderBy('first_name')->get();

        return view('crm.leads.index', compact('companies','employees'));
    }

    public function datatable(Request $request)
    {
        $query = Lead::query()
            ->with(['company:id,name', 'assignedEmployee:id,first_name,last_name'])
            ->select('leads.*');
    
        // optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
    
        return datatables()->eloquent($query)
            ->addColumn('checkbox', fn($l) => '<input type="checkbox" class="row-checkbox" value="'.$l->id.'">')
    
            ->addColumn('company_name', fn($l) => e($l->company?->name ?? '—'))
            ->addColumn('assigned_to_name', fn($l) => e(trim(($l->assignedEmployee?->first_name ?? '').' '.($l->assignedEmployee?->last_name ?? '')) ?: '—'))
            ->addColumn('follow_up_date', function ($row) {
                return $row->follow_up_date
                    ? \Carbon\Carbon::parse($row->follow_up_date)->format('d-m-Y')
                    : '—';
            })
            ->addColumn('actions', function ($l) {
                $record = htmlspecialchars(json_encode([
                    'id' => $l->id,
                    'lead_name' => $l->lead_name,
                    'email' => $l->email,
                    'phone' => $l->phone,
                    'company_id' => $l->company_id,
                    'company_name' => $l->company?->name,
                    'position' => $l->position,
                    'source' => $l->source,
                    'status' => $l->status,
                    'notes' => $l->notes,
                    'follow_up_date' => optional($l->follow_up_date)->format('d-m-Y'),
                    'assigned_to' => $l->assigned_to,
                ]), ENT_QUOTES, 'UTF-8');
    
                $btns = '';
                if (auth()->user()->can('crm.leads.edit')) {
                    $btns .= '<button class="btn btn-sm btn-info edit-lead" data-record="'.$record.'">Edit</button> ';
                }
                if (auth()->user()->can('crm.leads.delete')) {
                    $btns .= '<button class="btn btn-sm btn-danger delete-lead" data-id="'.$l->id.'">Delete</button>';
                }
                return $btns;
            })
    
            ->rawColumns(['checkbox', 'actions'])
            ->toJson();
    }
    

    public function store(Request $request)
    {
        // store/update validation rules
        $request->validate([
            'lead_name'      => 'required|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'company_id'     => 'required|exists:companies,id', // required now
            'position'       => 'nullable|string|max:100',
            'source'         => 'nullable|string|max:100',
            'status'         => 'required|string|in:new,contacted,qualified,converted,closed',
            'follow_up_date' => 'nullable|date',
            'assigned_to'    => 'nullable|exists:employees,id',
            'notes'          => 'nullable|string',
        ]);


        $lead = Lead::create($data);

        $this->audit(
            action: 'created',
            description: 'Created lead '.$lead->lead_name.($lead->email ? ' ('.$lead->email.')' : ''),
            subject: $lead,
            meta: [
                'id' => $lead->id,
                'lead_name' => $lead->lead_name,
                'email' => $lead->email,
                'status' => $lead->status,
                'company_id' => $lead->company_id,
                'company' => $lead->company,
                'assigned_to' => $lead->assigned_to,
                'follow_up_date' => optional($lead->follow_up_date)->toDateString(),
                'created_by' => $lead->created_by,
            ]
        );

        return response()->json(['message' => 'Lead created successfully.', 'lead' => $lead]);
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'lead_name'       => ['required','string','max:255'],
            'email'           => ['nullable','email','max:255'],
            'phone'           => ['nullable','string','max:50'],
            'company_id'     =>  ['required','exists:companies,id'], // required now
            'position'        => ['nullable','string','max:100'],
            'source'          => ['nullable','string','max:100'],
            'status'          => ['required', Rule::in(['new','contacted','qualified','converted','closed'])],
            'notes'           => ['nullable','string'],
            'follow_up_date'  => ['nullable','date'],
            'assigned_to'     => ['nullable','exists:employees,id'],
        ]);

        $before = $lead->only([
            'lead_name','email','phone','company_id','company','position','source','status','notes','follow_up_date','assigned_to',
            'created_by','updated_by','status_changed_at'
        ]);

        $lead->update($data);

        $after = $lead->fresh()->only([
            'lead_name','email','phone','company_id','company','position','source','status','notes','follow_up_date','assigned_to',
            'created_by','updated_by','status_changed_at'
        ]);

        $this->audit(
            action: 'updated',
            description: 'Updated lead '.$lead->lead_name.($lead->email ? ' ('.$lead->email.')' : ''),
            subject: $lead,
            meta: [
                'id' => $lead->id,
                'before' => $before,
                'after' => $after,
            ]
        );

        return response()->json(['message' => 'Lead updated successfully.', 'lead' => $lead->fresh()]);
    }

    public function destroy(Lead $lead)
    {
        $meta = $lead->only([
            'id','lead_name','email','status','company_id','company','assigned_to','follow_up_date','created_by','updated_by'
        ]);

        $lead->delete();

        $this->audit(
            action: 'deleted',
            description: 'Deleted lead '.$meta['lead_name'].($meta['email'] ? ' ('.$meta['email'].')' : ''),
            subject: null,
            meta: $meta
        );

        return response()->json(['message' => 'Lead deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:leads,id'],
        ]);

        $rows = Lead::whereIn('id', $data['ids'])->get(['id','lead_name','email','status','assigned_to','company_id']);

        Lead::whereIn('id', $data['ids'])->delete();

        $this->audit(
            action: 'bulk_deleted',
            description: 'Bulk deleted leads',
            subject: null,
            meta: [
                'count' => $rows->count(),
                'ids' => $rows->pluck('id')->all(),
                'items' => $rows->map(fn($r) => $r->toArray())->all(),
            ]
        );

        return response()->json(['message' => 'Selected leads deleted successfully.']);
    }

   private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        $module = 'crm.leads';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }
}
