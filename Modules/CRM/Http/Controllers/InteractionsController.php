<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\Interaction;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\Facades\DataTables;

class InteractionsController extends BaseController
{
    public function index()
    {
        $employees = Employee::select('id','first_name','last_name')
            ->orderBy('first_name')
            ->get();

        return view('crm.interactions.index', compact('employees'));
    }

    /**
     * Select2: fetch interactables for Lead/Opportunity.
     * Customer uses CustomerController@select2
     *
     * Expected params:
     * - type: Modules\CRM\Models\Lead OR Modules\CRM\Models\Opportunity
     * - q: search term
     */
    public function fetchInteractables(Request $request)
    {
        $request->validate([
            'type' => 'required|in:Modules\\CRM\\Models\\Lead,Modules\\CRM\\Models\\Opportunity',
        ]);

        $model = $request->type;
        $q = trim((string) $request->get('q', ''));

        $query = (new $model)->query();

        switch ($model) {

            case 'Modules\\CRM\\Models\\Lead':
                $query->select('id', DB::raw("CONCAT(lead_name, IFNULL(CONCAT(' - ', email), '')) AS text"))
                    ->when($q !== '', function ($qq) use ($q) {
                        $qq->where('lead_name', 'like', "%{$q}%")
                           ->orWhere('email', 'like', "%{$q}%")
                           ->orWhere('phone', 'like', "%{$q}%");
                    });
                break;

            case 'Modules\\CRM\\Models\\Opportunity':
                $query->select('id', DB::raw("CONCAT(title, ' (', FORMAT(value,2), ')') AS text"))
                    ->when($q !== '', function ($qq) use ($q) {
                        $qq->where('title', 'like', "%{$q}%");
                    });
                break;
        }

        return response()->json($query->limit(50)->get());
    }

    public function datatable(Request $request)
    {
        $query = Interaction::query()
            ->with(['interactable', 'employee'])
            ->select('interactions.*');

        // Filters
        if ($request->filled('interactable_type')) {
            $query->where('interactable_type', $request->interactable_type);
        }

        if ($request->filled('interactable_id')) {
            $query->where('interactable_id', $request->interactable_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('interaction_type')) {
            $query->where('interaction_type', $request->interaction_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('interaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('interaction_date', '<=', $request->date_to);
        }

        return DataTables::eloquent($query)
            ->addColumn('checkbox', fn($i) => '<input type="checkbox" class="row-checkbox" value="'.$i->id.'">')

            // show last name only (Customer/Lead/Opportunity)
            ->addColumn('interactable_type_short', fn($i) => class_basename($i->interactable_type))

            ->addColumn('interactable_label', function ($i) {
                if (!$i->interactable) return '—';

                return match (class_basename($i->interactable_type)) {
                    'Customer'    => $i->interactable->name ?? '—',
                    'Lead'        => $i->interactable->lead_name ?? '—',
                    'Opportunity' => $i->interactable->title ?? '—',
                    default       => '—',
                };
            })

            ->addColumn('employee_name', function ($i) {
                $e = $i->employee;
                if (!$e) return '—';
                $full = trim(($e->first_name ?? '').' '.($e->last_name ?? ''));
                return $full !== '' ? $full : ('Employee #'.$e->id);
            })

            ->addColumn('interaction_date_fmt', fn($i) => optional($i->interaction_date)->format('d-m-Y h:i a'))

            ->addColumn('actions', function ($i) {
                $record = htmlspecialchars(json_encode([
                    'id'                 => $i->id,
                    'subject'            => $i->subject,
                    'details'            => $i->details,
                    'interaction_type'   => $i->interaction_type,
                    'interaction_date'   => optional($i->interaction_date)->format('Y-m-d\TH:i'),
                    'employee_id'        => $i->employee_id,
                    'interactable_type'  => $i->interactable_type,
                    'interactable_id'    => $i->interactable_id,
                    'interactable_label' => ($i->interactable ? (
                        match (class_basename($i->interactable_type)) {
                            'Customer'    => $i->interactable->name ?? '',
                            'Lead'        => $i->interactable->lead_name ?? '',
                            'Opportunity' => $i->interactable->title ?? '',
                            default       => '',
                        }
                    ) : ''),
                ]), ENT_QUOTES, 'UTF-8');

                $btnEdit = auth()->user()->can('crm.interactions.update')
                    ? '<button class="btn btn-sm btn-info edit-interaction" data-record="'.$record.'">Edit</button>'
                    : '';

                $btnDel  = auth()->user()->can('crm.interactions.delete')
                    ? '<button class="btn btn-sm btn-danger delete-interaction" data-id="'.$i->id.'">Delete</button>'
                    : '';

                return trim($btnEdit.' '.$btnDel);
            })

            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'            => 'required|string|max:255',
            'details'            => 'nullable|string',
            'interaction_type'   => 'required|in:call,email,meeting,message,other',
            'interaction_date'   => 'required|date',
            'employee_id'        => 'required|exists:employees,id',
            'interactable_type'  => 'required|in:Modules\\CRM\\Models\\Customer,Modules\\CRM\\Models\\Lead,Modules\\CRM\\Models\\Opportunity',
            'interactable_id'    => 'required|integer',
        ]);

        // ensure interactable exists
        $model = $data['interactable_type'];
        abort_unless((new $model)->whereKey($data['interactable_id'])->exists(), 422, 'Selected record not found.');

        $interaction = Interaction::create($data);

        $this->audit(
            module: 'crm',
            action: 'interactions.created',
            description: 'Created interaction: '.$interaction->subject,
            subject: $interaction,
            meta: [
                'id' => $interaction->id,
                'interaction_type' => $interaction->interaction_type,
                'interaction_date' => $interaction->interaction_date,
                'employee_id' => $interaction->employee_id,
                'interactable_type' => $interaction->interactable_type,
                'interactable_id' => $interaction->interactable_id,
            ]
        );

        return response()->json(['message' => 'Interaction created successfully.']);
    }

    public function update(Request $request, Interaction $interaction)
    {
        $data = $request->validate([
            'subject'            => 'required|string|max:255',
            'details'            => 'nullable|string',
            'interaction_type'   => 'required|in:call,email,meeting,message,other',
            'interaction_date'   => 'required|date',
            'employee_id'        => 'required|exists:employees,id',
            'interactable_type'  => 'required|in:Modules\\CRM\\Models\\Customer,Modules\\CRM\\Models\\Lead,Modules\\CRM\\Models\\Opportunity',
            'interactable_id'    => 'required|integer',
        ]);

        $model = $data['interactable_type'];
        abort_unless((new $model)->whereKey($data['interactable_id'])->exists(), 422, 'Selected record not found.');

        $before = $interaction->only([
            'subject','details','interaction_type','interaction_date','employee_id','interactable_type','interactable_id'
        ]);

        $interaction->update($data);

        $after = $interaction->fresh()->only([
            'subject','details','interaction_type','interaction_date','employee_id','interactable_type','interactable_id'
        ]);

        $this->audit(
            module: 'crm',
            action: 'interactions.updated',
            description: 'Updated interaction: '.$interaction->subject,
            subject: $interaction,
            meta: ['before' => $before, 'after' => $after]
        );

        return response()->json(['message' => 'Interaction updated successfully.']);
    }

    public function destroy(Interaction $interaction)
    {
        $snapshot = $interaction->only([
            'id','subject','interaction_type','interaction_date','employee_id','interactable_type','interactable_id'
        ]);

        $interaction->delete();

        $this->audit(
            module: 'crm',
            action: 'interactions.deleted',
            description: 'Deleted interaction: '.$snapshot['subject'],
            subject: null,
            meta: ['deleted' => $snapshot]
        );

        return response()->json(['message' => 'Interaction deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:interactions,id',
        ]);

        $count = Interaction::whereIn('id', $data['ids'])->count();
        Interaction::whereIn('id', $data['ids'])->delete();

        $this->audit(
            module: 'crm',
            action: 'interactions.bulk_deleted',
            description: "Bulk deleted {$count} interaction(s).",
            subject: null,
            meta: ['ids' => $data['ids'], 'count' => $count]
        );

        return response()->json(['message' => 'Selected interactions deleted successfully.']);
    }
}
