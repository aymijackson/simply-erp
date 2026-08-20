<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DataTables;

class CompanyController extends Controller
{
    /**
     * Central audit helper (same style as your inventory controller)
     */
    private function audit(
        string $module,
        string $action,
        ?string $description = null,
        $subject = null,
        array $meta = []
    ): void {
        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    public function select2(Request $r)
    {
        $q = trim($r->input('q', ''));
    
        $rows = Company::query()
            ->when($q !== '', fn($qq) => $qq->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);
    
        // select2 wants: [{id,text}]
        return response()->json(
            $rows->map(fn($c) => ['id' => $c->id, 'text' => $c->name])
        );
    }

    /**
     * Display the list of companies or return JSON for DataTables.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companies = Company::select('id', 'name', 'email', 'website', 'address');

            return Datatables::of($companies)
                ->addColumn('checkbox', fn($company) =>
                    '<input type="checkbox" name="ids[]" value="' . $company->id . '">')
                ->addColumn('actions', fn($company) => '
                        <button class="btn btn-sm btn-warning edit-company" data-id="' . $company->id . '">Edit</button>
                        <button class="btn btn-sm btn-danger delete-company" data-id="' . $company->id . '">Delete</button>
                    ')
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
        $validated = $request->validate([
            'name'    => ['required','string','max:255'],
            'email'   => ['nullable','email','max:255'],
            'website' => ['nullable','url','max:255'],
            'address' => ['nullable','string','max:255'],
            'theme_mode'    => ['nullable','in:light,dark'],
            'theme_accent'  => ['nullable','in:indigo,emerald,sky,rose,amber,slate'],
            'theme_sidebar' => ['nullable','in:dark,light'],
        ]);

        $company = Company::create($validated);

        $this->audit(
            module: 'crm.companies',
            action: 'created',
            description: 'Created company ' . ($company->name ?: '#'.$company->id),
            subject: $company,
            meta: [
                'company_id' => $company->id,
                'name'       => $company->name,
                'email'      => $company->email,
                'website'    => $company->website,
                'address'    => $company->address,
            ]
        );

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
        $validated = $request->validate([
            'name'    => ['required','string','max:255'],
            'email'   => ['nullable','email','max:255'],
            'website' => ['nullable','url','max:255'],
            'address' => ['nullable','string','max:255'],
            'theme_mode'    => ['nullable','in:light,dark'],
            'theme_accent'  => ['nullable','in:indigo,emerald,sky,rose,amber,slate'],
            'theme_sidebar' => ['nullable','in:dark,light'],
        ]);

        $company = Company::findOrFail($id);

        $before = $company->only(['id','name','email','website','address','theme_mode','theme_accent','theme_sidebar']);

        $company->update($validated);

        $after = $company->fresh()->only(['id','name','email','website','address','theme_mode','theme_accent','theme_sidebar']);

        $this->audit(
            module: 'crm.companies',
            action: 'updated',
            description: 'Updated company ' . ($company->name ?: '#'.$company->id),
            subject: $company,
            meta: [
                'before' => $before,
                'after'  => $after,
            ]
        );

        return response()->json(['message' => 'Company updated successfully!']);
    }

    /**
     * Remove the specified company.
     */
    public function destroy($id)
    {
        $company = Company::findOrFail($id);

        $meta = $company->only(['id','name','email','website','address']);

        $company->delete();

        $this->audit(
            module: 'crm.companies',
            action: 'deleted',
            description: 'Deleted company ' . ($meta['name'] ?: '#'.$meta['id']),
            subject: null,
            meta: $meta
        );

        return response()->json(['message' => 'Company deleted successfully!']);
    }

    /**
     * Bulk-delete selected companies.
     */
    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer', Rule::exists('companies','id')],
        ]);

        $companies = Company::whereIn('id', $data['ids'])
            ->get(['id','name','email','website','address']);

        Company::whereIn('id', $data['ids'])->delete();

        $this->audit(
            module: 'crm.companies',
            action: 'bulk_deleted',
            description: 'Bulk deleted companies (count: '.count($data['ids']).')',
            subject: null,
            meta: [
                'count' => count($data['ids']),
                'ids'   => $data['ids'],
                'items' => $companies->map(fn($c) => $c->only(['id','name','email','website','address']))->values(),
            ]
        );

        return response()->json(['message' => 'Selected companies deleted successfully!']);
    }

    public function DepartmentsIndex()
    {
        $departments = Department::with('company')->paginate(20);
        return view('companies.departments.index', compact('departments'));
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'company_id'   => ['required','exists:companies,id'],
            'name'         => ['required','string','max:255'],
            'code'         => ['nullable','string','max:50'],
            'description'  => ['nullable','string'],
        ]);

        $department = Department::create($validated)->fresh(['company:id,name']);

        $this->audit(
            module: 'crm.departments',
            action: 'created',
            description: 'Created department ' . ($department->name ?: '#'.$department->id),
            subject: $department,
            meta: [
                'department_id' => $department->id,
                'company_id'    => $department->company_id,
                'company'       => $department->company?->name,
                'name'          => $department->name,
                'code'          => $department->code,
                'description'   => $department->description,
            ]
        );

        return response()->json(['message' => 'Department created successfully!']);
    }

    public function departmentsDatatable(Request $request)
    {
        if ($request->ajax()) {
            $products = Department::with(['company'])->select('departments.*');

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) =>
                    '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
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
                            Edit
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
        $validated = $request->validate([
            'id'          => ['required','exists:departments,id'],
            'company_id'  => ['required','exists:companies,id'],
            'name'        => ['required','string','max:255'],
            'code'        => ['nullable','string','max:50'],
            'description' => ['nullable','string'],
        ]);

        $department = Department::with('company:id,name')->findOrFail($validated['id']);

        $before = [
            'department_id' => $department->id,
            'company_id'    => $department->company_id,
            'company'       => $department->company?->name,
            'name'          => $department->name,
            'code'          => $department->code,
            'description'   => $department->description,
        ];

        $department->update([
            'company_id'  => $validated['company_id'],
            'name'        => $validated['name'],
            'code'        => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $department->load('company:id,name');

        $after = [
            'department_id' => $department->id,
            'company_id'    => $department->company_id,
            'company'       => $department->company?->name,
            'name'          => $department->name,
            'code'          => $department->code,
            'description'   => $department->description,
        ];

        $this->audit(
            module: 'crm.departments',
            action: 'updated',
            description: 'Updated department ' . ($department->name ?: '#'.$department->id),
            subject: $department,
            meta: [
                'before' => $before,
                'after'  => $after,
            ]
        );

        return response()->json(['message' => 'Department updated successfully!']);
    }

    public function destroyDepartment(Department $department)
    {
        $department->load('company:id,name');

        $meta = [
            'department_id' => $department->id,
            'company_id'    => $department->company_id,
            'company'       => $department->company?->name,
            'name'          => $department->name,
            'code'          => $department->code,
            'description'   => $department->description,
        ];

        $department->delete();

        $this->audit(
            module: 'crm.departments',
            action: 'deleted',
            description: 'Deleted department ' . ($meta['name'] ?: '#'.$meta['department_id']),
            subject: null,
            meta: $meta
        );

        return response()->json(['message' => 'Department deleted successfully!']);
    }

    public function bulkDeleteDepartment(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer', Rule::exists('departments','id')],
        ]);

        $items = Department::with('company:id,name')
            ->whereIn('id', $data['ids'])
            ->get()
            ->map(function ($d) {
                return [
                    'department_id' => $d->id,
                    'company_id'    => $d->company_id,
                    'company'       => $d->company?->name,
                    'name'          => $d->name,
                    'code'          => $d->code,
                    'description'   => $d->description,
                ];
            })->values();

        Department::whereIn('id', $data['ids'])->delete();

        $this->audit(
            module: 'crm.departments',
            action: 'bulk_deleted',
            description: 'Bulk deleted departments (count: '.count($data['ids']).')',
            subject: null,
            meta: [
                'count' => count($data['ids']),
                'ids'   => $data['ids'],
                'items' => $items,
            ]
        );

        return response()->json(['message' => 'Selected departments deleted successfully!']);
    }
}
