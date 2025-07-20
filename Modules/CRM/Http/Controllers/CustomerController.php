<?php
namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\Customer;
use App\Models\Company;
use Yajra\DataTables\DataTables;

class CustomerController extends Controller
{
    public function index()
    {
        $companies = Company::all();
        return view('crm.customers.index', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        Customer::create(
            $validated
        );

        return response()->json(['message' => 'Customer saved successfully.']);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        Customer::updateOrCreate(
            ['id' => $request->id],
            $validated
        );

        return response()->json(['message' => 'Customer saved successfully.']);
    }

    public function destroy($id)
    {
        Customer::destroy($id);
        return response()->json(['message' => 'Customer deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        Customer::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected customers deleted.']);
    }

    public function datatable()
    {
        $query = Customer::with('company')->select('customers.*');

        return DataTables::of($query)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('company', fn($row) => $row->company->name ?? '')
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-info edit-customer" data-id="'.$row->id.'" data-record="'.e(json_encode($row)).'"><i class="fas fa-edit"></i></button> 
                        <button class="btn btn-sm btn-danger delete-customer" data-id="'.$row->id.'"><i class="fas fa-trash-alt"></i></button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }
}
