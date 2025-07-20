<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;
use DataTables;

class CompanyController extends Controller
{
    /**
     * Display the list of companies or return JSON for DataTables.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companies = Company::select('id', 'name', 'email', 'website', 'address');

            return Datatables::of($companies)
                ->addColumn('checkbox', function ($company) {
                    return '<input type="checkbox" name="ids[]" value="' . $company->id . '">';
                })
                ->addColumn('actions', function ($company) {
                    return '
                        <button class="btn btn-sm btn-warning edit-company" data-id="' . $company->id . '">Edit</button>
                        <button class="btn btn-sm btn-danger delete-company" data-id="' . $company->id . '">Delete</button>
                    ';
                })
                ->rawColumns(['checkbox', 'actions'])
                ->make(true);
        }

        return view('companies.index');
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        Company::create($request->only('name', 'email', 'website', 'address'));
        
        // log the creation activity here
        // activity()->log('Company created: ' . $request->name);

        return response()->json(['message' => 'Company created successfully!']);
    }

    /**
     * Return the specified company for editing.
     */
    public function edit($id)
    {
        $company = Company::findOrFail($id);

        return response()->json(['company' => $company]);
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $company = Company::findOrFail($id);
        $company->update($request->only('name', 'email', 'website', 'address'));
        // log the update activity here
        // activity()->log('Company updated: ' . $request->name);

        return response()->json(['message' => 'Company updated successfully!']);
    }

    /**
     * Remove the specified company.
     */
    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Company deleted successfully!']);
    }

    /**
     * Bulk-delete selected companies.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'No companies selected.'], 422);
        }

        Company::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected companies deleted successfully!']);
    }

    public function DepartmentsIndex()
    {
        $departments = Department::with('company')->paginate(20);
        return view('companies.departments.index', compact('departments'));
    }


    public function storeDepartment(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        Department::create($request->all());

        return response()->json(['message' => 'Department created successfully!']);
    }

    public function departmentsDatatable(Request $request)
    {
        if ($request->ajax()) {
            $products = Department::with(['company'])->select('departments.*');

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
                ->addColumn('name', fn($row) => $row->name ?? '')
                ->addColumn('code', fn($row) => $row->code ?? '')
                ->addColumn('company_name', fn($row) => $row->company->name ?? '')
                ->addColumn('actions', function ($row) {
                    return '<button class="btn btn-sm btn-warning edit-department"
                            data-id="'.$row->id.'"
                            data-name="'.e($row->name).'"
                            data-code="'.e($row->code).'"
                            data-company_id="'.$row->company_id.'"
                            data-description="'.e($row->description).'"
                        >
                            Edit</i>
                        </button>
                            <button type="button" class="btn btn-sm btn-danger delete-department" data-id="'.$row->id.'">Delete</button>';
                })
                ->rawColumns(['checkbox', 'actions'])
                ->make(true);
        }
    }

    public function editDepartment(Department $department)
    {
        $companies = Company::all();
        return view('admin.hrm.departments.edit', compact('department', 'companies'));
    }

    public function updateDepartment(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $department = Department::findOrFail($request->id); // <-- important

        $department->update($request->only(['company_id', 'name', 'code', 'description']));

        return response()->json(['message' => 'Department updated successfully!']);
    }

    public function destroyDepartment(Department $department)
    { 
        $department->delete();
        return response()->json(['message' => 'Department deleted successfully!']);
    }

    public function bulkDeleteDepartment(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:departments,id',
        ]);

        Department::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected departments deleted successfully!']);
    }
}
