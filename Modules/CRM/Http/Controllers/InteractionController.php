<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\Interaction;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;

class InteractionController extends Controller
{
    public function index()
    {
        $employees = Employee::select('id', 'first_name', 'last_name')->get();
        return view('crm.interactions.index', compact('employees'));
    }

    public function fetchInteractables(Request $request)
    {
        $request->validate([
            'type' => 'required|in:Modules\\CRM\\Models\\Customer,Modules\\CRM\\Models\\Lead,Modules\\CRM\\Models\\Opportunity'
        ]);

        $model = $request->type;

        $query = (new $model)->query();

        // Adjust label logic based on the model type
        switch ($model) {
            case 'Modules\\CRM\\Models\\Customer':
                $query->select('id', DB::raw("CONCAT(name, ' - ', email) AS label"));
                break;

            case 'Modules\\CRM\\Models\\Lead':
                $query->select('id', DB::raw("CONCAT(lead_name, ' - ', company) AS label"));
                break;

            case 'Modules\\CRM\\Models\\Opportunity':
                $query->select('id', DB::raw("CONCAT(title, ' (Value: ', value, ')') AS label"));
                break;
        }

        $data = $query->limit(50)->get();

        return response()->json($data);
    }

    public function datatable(Request $request)
    {
        $interactions = Interaction::with('employee', 'interactable')->latest();

        return DataTables::of($interactions)
            ->addColumn('employee', fn($row) => $row->employee?->first_name . ' ' . $row->employee?->last_name)
            ->addColumn('interaction_date', function ($row) {
                return $row->interaction_date ? $row->interaction_date->format('d-m-Y H:i a') : 'N/A';
            })
            ->addColumn('interactable', function ($row) {
                if (!$row->interactable) return '-';
                $type = class_basename($row->interactable_type);
                $value = method_exists($row->interactable, 'getLabel') 
                            ? $row->interactable->getLabel()
                            : ($row->interactable->name ?? $row->interactable->subject ?? '-');
                return "{$type}: {$value}";
            })
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-primary edit-interaction" data-record=\'' . json_encode($row) . '\'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-interaction" data-id="' . $row->id . '">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'            => 'required|string|max:255',
            'details'            => 'nullable|string',
            'interaction_type'   => 'required|in:call,email,meeting,visit,other',
            'interaction_date'   => 'required|date',
            'employee_id'        => 'required|exists:employees,id',
            'interactable_type'  => 'required|string',
            'interactable_id'    => 'required|integer',
        ]);

        Interaction::create($data);

        return response()->json(['message' => 'Interaction added successfully.']);
    }

    public function update(Request $request, $id)
    {
        $interaction = Interaction::findOrFail($id);

        $data = $request->validate([
            'subject'            => 'required|string|max:255',
            'details'            => 'nullable|string',
            'interaction_type'   => 'required|in:call,email,meeting,visit,other',
            'interaction_date'   => 'required|date',
            'employee_id'        => 'required|exists:employees,id',
            'interactable_type'  => 'required|string',
            'interactable_id'    => 'required|integer',
        ]);

        $interaction->update($data);

        return response()->json(['message' => 'Interaction updated successfully.']);
    }

    public function destroy($id)
    {
        Interaction::findOrFail($id)->delete();
        return response()->json(['message' => 'Interaction deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        Interaction::whereIn('id', $ids)->delete();
        return response()->json(['message' => 'Selected interactions deleted.']);
    }
}
