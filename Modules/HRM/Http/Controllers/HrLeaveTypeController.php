<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\HRM\Models\HrLeaveType;
use Yajra\DataTables\Facades\DataTables;

/**
 * HrLeaveTypeController
 *
 * ── PERMISSION MAP ───────────────────────────────────────────────────────────
 * index, datatable, select2    hrm.leave_types.view
 * store                        hrm.leave_types.create
 * update                       hrm.leave_types.edit
 * destroy                      hrm.leave_types.delete
 * ────────────────────────────────────────────────────────────────────────────
 */
class HrLeaveTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:hrm.leave_types.view',   ['only' => ['index','datatable','select2']]);
        $this->middleware('permission:hrm.leave_types.create', ['only' => ['store']]);
        $this->middleware('permission:hrm.leave_types.edit',   ['only' => ['update']]);
        $this->middleware('permission:hrm.leave_types.delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('hrm.leave_types.index');
    }

    public function datatable()
    {
        $q = HrLeaveType::query()->orderBy('name');

        return DataTables::eloquent($q)
            ->addColumn('paid_badge', fn($r) => $r->is_paid
                ? '<span class="badge bg-success">Paid</span>'
                : '<span class="badge bg-secondary">Unpaid</span>')
            ->addColumn('status_badge', fn($r) => $r->is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['paid_badge','status_badge','actions'])
            ->make(true);
    }

    public function select2(Request $request)
    {
        return HrLeaveType::where('is_active', true)
            ->when($request->q, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')->limit(30)
            ->get(['id','name','code'])
            ->map(fn($r) => ['id' => $r->id, 'text' => "{$r->name} ({$r->code})"]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => ['required','string','max:100'],
            'code'               => ['required','string','max:30',
                                     Rule::unique('hr_leave_types','code')
                                         ->where('company_id', auth()->user()->company_id ?? 1)],
            'days_allowed'       => ['required','numeric','min:0'],
            'carry_over_days'    => ['nullable','numeric','min:0'],
            'is_paid'            => ['boolean'],
            'requires_approval'  => ['boolean'],
            'gender_restriction' => ['required', Rule::in(['all','male','female'])],
            'is_active'          => ['boolean'],
        ]);

        $type = HrLeaveType::create([
            ...$validated,
            'company_id' => auth()->user()->company_id ?? 1,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Leave type created.', 'type' => $type], 201);
    }

    public function update(Request $request, HrLeaveType $hrLeaveType)
    {
        $validated = $request->validate([
            'name'               => ['required','string','max:100'],
            'code'               => ['required','string','max:30',
                                     Rule::unique('hr_leave_types','code')
                                         ->where('company_id', auth()->user()->company_id ?? 1)
                                         ->ignore($hrLeaveType->id)],
            'days_allowed'       => ['required','numeric','min:0'],
            'carry_over_days'    => ['nullable','numeric','min:0'],
            'is_paid'            => ['boolean'],
            'requires_approval'  => ['boolean'],
            'gender_restriction' => ['required', Rule::in(['all','male','female'])],
            'is_active'          => ['boolean'],
        ]);

        $hrLeaveType->update([...$validated, 'updated_by' => auth()->id()]);

        return response()->json(['message' => 'Leave type updated.']);
    }

    public function destroy(HrLeaveType $hrLeaveType)
    {
        if ($hrLeaveType->leaves()->exists()) {
            return response()->json(['message' => 'Cannot delete — leave records exist for this type.'], 422);
        }
        $hrLeaveType->delete();
        return response()->json(['message' => 'Leave type deleted.']);
    }
}