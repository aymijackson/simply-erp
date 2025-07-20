<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\Lead;
use App\Models\Company;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    public function index()
    {
        $companies = Company::all();
        $employees = Employee::all();
        return view('crm.leads.index', compact('companies', 'employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'lead_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_id' => 'nullable|exists:companies,id',
            'position' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:100',
            'status' => 'required|string|in:new,contacted,qualified,converted,closed',
            'follow_up_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:employees,id',
        ]);

        $lead = Lead::create($request->only([
            'lead_name', 'email', 'phone', 'company_id', 'position',
            'source', 'status', 'notes', 'follow_up_date', 'assigned_to'
        ]));

        return response()->json(['message' => 'Lead created successfully.', 'lead' => $lead]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'lead_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_id' => 'nullable|exists:companies,id',
            'position' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:100',
            'status' => 'required|string|in:new,contacted,qualified,converted,closed',
            'follow_up_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:employees,id',
        ]);

        $lead = Lead::findOrFail($id);
        $lead->update($request->only([
            'lead_name', 'email', 'phone', 'company_id', 'position',
            'source', 'status', 'notes', 'follow_up_date', 'assigned_to'
        ]));

        return response()->json(['message' => 'Lead updated successfully.', 'lead' => $lead]);
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        Lead::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected leads deleted successfully.']);
    }

    public function datatable()
    {
        $leads = Lead::with(['company', 'assignedEmployee'])->latest();

        return DataTables::of($leads)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('company', fn($row) => optional($row->company)->name)
            ->addColumn('assigned_to', fn($row) => optional($row->assignedEmployee)->full_name ?? '-')
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-info edit-lead" data-record="'.htmlspecialchars(json_encode($row)).'">Edit</button>
                        <button class="btn btn-sm btn-danger delete-lead" data-id="'.$row->id.'">Delete</button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }
}
