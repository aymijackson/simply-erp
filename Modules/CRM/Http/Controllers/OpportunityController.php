<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\Opportunity;
use Modules\CRM\Models\Customer;
use Modules\HRM\Models\Employee;

class OpportunityController extends Controller
{
    public function index()
    {
        $customers = Customer::all();
        $employees = Employee::all();
        return view('crm.opportunities.index', compact('customers', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'value' => 'required|numeric',
            'stage' => 'required|string',
            'probability' => 'nullable|numeric|min:0|max:100',
            'close_date' => 'nullable|date',
            'owner_id' => 'required|exists:employees,id',
            'notes' => 'nullable|string'
        ]);

        Opportunity::create($data);

        return response()->json(['message' => 'Opportunity created successfully']);
    }

    public function update(Request $request, $id)
    {
        $opportunity = Opportunity::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'value' => 'required|numeric',
            'stage' => 'required|string',
            'probability' => 'nullable|numeric|min:0|max:100',
            'close_date' => 'nullable|date',
            'owner_id' => 'required|exists:employees,id',
            'notes' => 'nullable|string'
        ]);

        $opportunity->update($data);

        return response()->json(['message' => 'Opportunity updated successfully']);
    }

    public function destroy($id)
    {
        Opportunity::findOrFail($id)->delete();
        return response()->json(['message' => 'Opportunity deleted']);
    }

    public function bulkDelete(Request $request)
    {
        Opportunity::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected opportunities deleted']);
    }

    public function datatable()
    {
        $opportunities = Opportunity::with(['customer', 'owner'])->get();

        return datatables()->of($opportunities)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('close_date', fn($row) => $row->close_date ? date('d-m-Y', strtotime($row->close_date)) : date('d-m-Y'))
            ->addColumn('customer', fn($row) => $row->customer?->name)
            ->addColumn('owner', fn($row) => $row->owner?->first_name . ' ' . $row->owner?->last_name)
            ->addColumn('actions', function($row){
                $record = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                return '<button class="btn btn-sm btn-info edit-opportunity" data-record="' . $record . '">Edit</button>
                        <button class="btn btn-sm btn-danger delete-opportunity" data-id="'.$row->id.'">Delete</button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }
}
