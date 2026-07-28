<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\HRM\Models\HrContract;
use Modules\HRM\Models\HrJobGrade;
use Modules\HRM\Models\HrJobPosition;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\Facades\DataTables;

/**
 * HrContractController
 *
 * ── PERMISSION MAP ───────────────────────────────────────────────────────────
 * index, datatable              hrm.contracts.view
 * store                         hrm.contracts.create
 * update                        hrm.contracts.edit
 * destroy                       hrm.contracts.delete
 * terminate                     hrm.contracts.terminate
 *
 * jobGrades — index, datatable, store, update, destroy
 *             hrm.job_grades.view / hrm.job_grades.manage
 * jobPositions — same pattern
 *             hrm.job_positions.view / hrm.job_positions.manage
 * ────────────────────────────────────────────────────────────────────────────
 */
class HrContractController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:hrm.contracts.view',      ['only' => ['index','datatable','employeeContracts']]);
        $this->middleware('permission:hrm.contracts.create',    ['only' => ['store']]);
        $this->middleware('permission:hrm.contracts.edit',      ['only' => ['update']]);
        $this->middleware('permission:hrm.contracts.delete',    ['only' => ['destroy']]);
        $this->middleware('permission:hrm.contracts.terminate', ['only' => ['terminate']]);
        $this->middleware('permission:hrm.job_grades.view',     ['only' => ['gradeIndex','gradeDatatable','gradeSelect2']]);
        $this->middleware('permission:hrm.job_grades.manage',   ['only' => ['gradeStore','gradeUpdate','gradeDestroy']]);
        $this->middleware('permission:hrm.job_positions.view',  ['only' => ['positionIndex','positionDatatable','positionSelect2']]);
        $this->middleware('permission:hrm.job_positions.manage',['only' => ['positionStore','positionUpdate','positionDestroy']]);
    }

    // ── Contracts ─────────────────────────────────────────────────────────────

    public function index()
    {
        return view('hrm.contracts.index');
    }

    public function datatable(Request $request)
    {
        $q = HrContract::with(['employee','jobPosition','jobGrade'])
            ->when($request->employee_id, fn($q, $v) => $q->where('employee_id', $v))
            ->when($request->status,      fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('start_date');

        return DataTables::eloquent($q)
            ->addColumn('employee_name',  fn($r) => $r->employee?->full_name ?? '-')
            ->addColumn('position_title', fn($r) => $r->jobPosition?->title ?? '-')
            ->addColumn('grade_name',     fn($r) => $r->jobGrade?->name ?? '-')
            ->addColumn('salary_fmt',     fn($r) => number_format($r->basic_salary, 2).' '.$r->currency_code)
            ->addColumn('status_badge', fn($r) => match($r->status) {
                'active'     => '<span class="badge bg-success">Active</span>',
                'expired'    => '<span class="badge bg-warning text-dark">Expired</span>',
                'terminated' => '<span class="badge bg-danger">Terminated</span>',
                default      => '<span class="badge bg-secondary">'.ucfirst($r->status).'</span>',
            })
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-contract"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                '.($r->status === 'active' ? '
                 <button class="btn btn-xs btn-danger btn-terminate" data-id="'.$r->id.'">
                    <i class="fas fa-ban"></i></button>' : '').'
                 <button class="btn btn-xs btn-outline-danger btn-delete-contract" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    public function employeeContracts(Employee $employee)
    {
        $contracts = HrContract::with(['jobPosition','jobGrade'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('start_date')
            ->get();

        return DataTables::of($contracts)
            ->addColumn('position_title', fn($r) => $r->jobPosition?->title ?? '-')
            ->addColumn('grade_name',     fn($r) => $r->jobGrade?->name ?? '-')
            ->addColumn('salary_fmt',     fn($r) => number_format($r->basic_salary, 2).' '.$r->currency_code)
            ->addColumn('status_badge', fn($r) => match($r->status) {
                'active'     => '<span class="badge bg-success">Active</span>',
                'expired'    => '<span class="badge bg-warning text-dark">Expired</span>',
                'terminated' => '<span class="badge bg-danger">Terminated</span>',
                default      => '<span class="badge bg-secondary">'.ucfirst($r->status).'</span>',
            })
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-contract"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>')
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->contractRules());

        // Only one active contract per employee
        if ($validated['status'] === 'active') {
            HrContract::where('employee_id', $validated['employee_id'])
                ->where('status', 'active')
                ->update(['status' => 'expired']);
        }

        $contract = HrContract::create([...$validated, 'created_by' => auth()->id(), 'updated_by' => auth()->id()]);

        return response()->json(['message' => 'Contract created.', 'contract' => $contract], 201);
    }

    public function update(Request $request, HrContract $hrContract)
    {
        $validated = $request->validate($this->contractRules($hrContract->id));

        if ($validated['status'] === 'active' && $hrContract->status !== 'active') {
            HrContract::where('employee_id', $validated['employee_id'])
                ->where('status', 'active')
                ->where('id', '!=', $hrContract->id)
                ->update(['status' => 'expired']);
        }

        $hrContract->update([...$validated, 'updated_by' => auth()->id()]);

        return response()->json(['message' => 'Contract updated.']);
    }

    public function destroy(HrContract $hrContract)
    {
        $hrContract->delete();
        return response()->json(['message' => 'Contract deleted.']);
    }

    public function terminate(Request $request, HrContract $hrContract)
    {
        $request->validate([
            'termination_date'   => ['required', 'date'],
            'termination_reason' => ['nullable', 'string'],
        ]);

        $hrContract->update([
            'status'             => 'terminated',
            'termination_date'   => $request->termination_date,
            'termination_reason' => $request->termination_reason,
            'updated_by'         => auth()->id(),
        ]);

        return response()->json(['message' => 'Contract terminated.']);
    }

    private function contractRules(?int $ignoreId = null): array
    {
        return [
            'employee_id'     => ['required','integer','exists:employees,id'],
            'job_position_id' => ['nullable','integer','exists:hr_job_positions,id'],
            'job_grade_id'    => ['nullable','integer','exists:hr_job_grades,id'],
            'contract_type'   => ['required', Rule::in(['permanent','fixed_term','probation','part_time','contract'])],
            'start_date'      => ['required','date'],
            'end_date'        => ['nullable','date','after_or_equal:start_date'],
            'basic_salary'    => ['required','numeric','min:0'],
            'currency_code'   => ['nullable','string','size:3'],
            'pay_frequency'   => ['required', Rule::in(['monthly','bi_weekly','weekly'])],
            'status'          => ['required', Rule::in(['draft','active','expired','terminated'])],
            'notes'           => ['nullable','string'],
        ];
    }

    // ── Job Grades ────────────────────────────────────────────────────────────

    public function gradeIndex()
    {
        return view('hrm.job_grades.index');
    }

    public function gradeDatatable()
    {
        return DataTables::eloquent(HrJobGrade::withCount('positions'))
            ->addColumn('salary_range', fn($r) =>
                $r->min_salary ? number_format($r->min_salary,2).' – '.number_format($r->max_salary ?? 0,2) : '—')
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-grade"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete-grade" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function gradeSelect2(Request $request)
    {
        return HrJobGrade::when($request->q, fn($q,$v) => $q->where('name','like',"%{$v}%"))
            ->orderBy('name')->limit(30)->get(['id','name','code'])
            ->map(fn($r) => ['id'=>$r->id,'text'=>$r->name.($r->code?" ({$r->code})":'')]);
    }

    public function gradeStore(Request $request)
    {
        $v = $request->validate(['name'=>'required|string|max:100','code'=>'nullable|string|max:30',
            'min_salary'=>'nullable|numeric|min:0','max_salary'=>'nullable|numeric|min:0']);
        $grade = HrJobGrade::create([...$v,'company_id'=>auth()->user()->company_id??1,'created_by'=>auth()->id(),'updated_by'=>auth()->id()]);
        return response()->json(['message'=>'Grade created.','grade'=>$grade],201);
    }

    public function gradeUpdate(Request $request, HrJobGrade $hrJobGrade)
    {
        $v = $request->validate(['name'=>'required|string|max:100','code'=>'nullable|string|max:30',
            'min_salary'=>'nullable|numeric|min:0','max_salary'=>'nullable|numeric|min:0']);
        $hrJobGrade->update([...$v,'updated_by'=>auth()->id()]);
        return response()->json(['message'=>'Grade updated.']);
    }

    public function gradeDestroy(HrJobGrade $hrJobGrade)
    {
        if ($hrJobGrade->positions()->exists()) {
            return response()->json(['message'=>'Cannot delete — positions exist for this grade.'],422);
        }
        $hrJobGrade->delete();
        return response()->json(['message'=>'Grade deleted.']);
    }

    // ── Job Positions ─────────────────────────────────────────────────────────

    public function positionIndex()
    {
        return view('hrm.job_positions.index');
    }

    public function positionDatatable()
    {
        return DataTables::eloquent(HrJobPosition::with(['grade','department'])->withCount('openings'))
            ->addColumn('grade_name', fn($r) => $r->grade?->name ?? '—')
            ->addColumn('dept_name',  fn($r) => $r->department?->name ?? '—')
            ->addColumn('status_badge', fn($r) => $r->is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-position"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete-position" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    public function positionSelect2(Request $request)
    {
        return HrJobPosition::where('is_active', true)
            ->when($request->q, fn($q,$v) => $q->where('title','like',"%{$v}%"))
            ->orderBy('title')->limit(30)->get(['id','title'])
            ->map(fn($r) => ['id'=>$r->id,'text'=>$r->title]);
    }

    public function positionStore(Request $request)
    {
        $v = $request->validate(['title'=>'required|string|max:150','department_id'=>'nullable|integer',
            'job_grade_id'=>'nullable|integer|exists:hr_job_grades,id','description'=>'nullable|string','is_active'=>'boolean']);
        $pos = HrJobPosition::create([...$v,'company_id'=>auth()->user()->company_id??1,'created_by'=>auth()->id(),'updated_by'=>auth()->id()]);
        return response()->json(['message'=>'Position created.','position'=>$pos],201);
    }

    public function positionUpdate(Request $request, HrJobPosition $hrJobPosition)
    {
        $v = $request->validate(['title'=>'required|string|max:150','department_id'=>'nullable|integer',
            'job_grade_id'=>'nullable|integer|exists:hr_job_grades,id','description'=>'nullable|string','is_active'=>'boolean']);
        $hrJobPosition->update([...$v,'updated_by'=>auth()->id()]);
        return response()->json(['message'=>'Position updated.']);
    }

    public function positionDestroy(HrJobPosition $hrJobPosition)
    {
        $hrJobPosition->delete();
        return response()->json(['message'=>'Position deleted.']);
    }
}