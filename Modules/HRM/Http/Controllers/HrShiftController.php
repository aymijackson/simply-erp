<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HRM\Models\HrShift;
use Modules\HRM\Models\HrRoster;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\Facades\DataTables;

/**
 * HrShiftController
 *
 * ── PERMISSION MAP ───────────────────────────────────────────────────────────
 * index, datatable              hrm.shifts.view
 * store, update, destroy        hrm.shifts.manage
 * rosterDatatable               hrm.rosters.view
 * storeRoster, destroyRoster    hrm.rosters.manage
 * ────────────────────────────────────────────────────────────────────────────
 */
class HrShiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:hrm.shifts.view',   ['only' => ['index','datatable','select2']]);
        $this->middleware('permission:hrm.shifts.manage', ['only' => ['store','update','destroy']]);
        $this->middleware('permission:hrm.rosters.view',  ['only' => ['rosterIndex','rosterDatatable']]);
        $this->middleware('permission:hrm.rosters.manage',['only' => ['storeRoster','destroyRoster']]);
    }

    // ── Shifts ────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('hrm.shifts.index');
    }

    public function datatable()
    {
        $q = HrShift::withCount('rosters')->orderBy('name');

        return DataTables::eloquent($q)
            ->addColumn('hours', fn($r) => $r->working_hours . ' hrs')
            ->addColumn('overnight_badge', fn($r) => $r->is_overnight
                ? '<span class="badge bg-warning text-dark">Overnight</span>' : '')
            ->addColumn('status_badge', fn($r) => $r->is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-shift"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete-shift" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['overnight_badge','status_badge','actions'])
            ->make(true);
    }

    public function select2(Request $request)
    {
        return HrShift::where('is_active', true)
            ->when($request->q, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')->limit(30)
            ->get(['id','name','start_time','end_time'])
            ->map(fn($r) => [
                'id'   => $r->id,
                'text' => "{$r->name} ({$r->start_time}–{$r->end_time})",
            ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required','string','max:100'],
            'start_time'    => ['required','date_format:H:i'],
            'end_time'      => ['required','date_format:H:i'],
            'break_minutes' => ['nullable','integer','min:0'],
            'is_overnight'  => ['boolean'],
            'is_active'     => ['boolean'],
        ]);

        $shift = HrShift::create([
            ...$validated,
            'company_id' => auth()->user()->company_id ?? 1,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Shift created.', 'shift' => $shift], 201);
    }

    public function update(Request $request, HrShift $hrShift)
    {
        $validated = $request->validate([
            'name'          => ['required','string','max:100'],
            'start_time'    => ['required','date_format:H:i'],
            'end_time'      => ['required','date_format:H:i'],
            'break_minutes' => ['nullable','integer','min:0'],
            'is_overnight'  => ['boolean'],
            'is_active'     => ['boolean'],
        ]);

        $hrShift->update([...$validated, 'updated_by' => auth()->id()]);

        return response()->json(['message' => 'Shift updated.']);
    }

    public function destroy(HrShift $hrShift)
    {
        $hrShift->delete();
        return response()->json(['message' => 'Shift deleted.']);
    }

    // ── Rosters ───────────────────────────────────────────────────────────────

    public function rosterIndex()
    {
        $employees = Employee::where('is_active', true)->orderBy('last_name')->get(['id','first_name','last_name']);
        $shifts    = HrShift::where('is_active', true)->orderBy('name')->get(['id','name']);
        return view('hrm.rosters.index', compact('employees','shifts'));
    }

    public function rosterDatatable(Request $request)
    {
        $q = HrRoster::with(['employee','shift'])
            ->when($request->employee_id, fn($q, $v) => $q->where('employee_id', $v))
            ->when($request->shift_id,    fn($q, $v) => $q->where('shift_id', $v))
            ->when($request->date_from,   fn($q, $v) => $q->where('roster_date', '>=', $v))
            ->when($request->date_to,     fn($q, $v) => $q->where('roster_date', '<=', $v))
            ->orderBy('roster_date','desc');

        return DataTables::eloquent($q)
            ->addColumn('employee_name', fn($r) => $r->employee?->full_name ?? '-')
            ->addColumn('shift_name',    fn($r) => $r->shift?->name ?? '-')
            ->addColumn('shift_hours',   fn($r) => $r->shift?->working_hours.' hrs' ?? '-')
            ->addColumn('roster_date_fmt', fn($r) => $r->roster_date?->format('d M Y') ?? '-')
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-danger btn-delete-roster" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function storeRoster(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required','integer','exists:employees,id'],
            'shift_id'    => ['required','integer','exists:hr_shifts,id'],
            'roster_date' => ['required','date'],
            'note'        => ['nullable','string'],
        ]);

        $roster = HrRoster::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'roster_date' => $validated['roster_date']],
            ['shift_id' => $validated['shift_id'], 'note' => $validated['note'] ?? null, 'created_by' => auth()->id()]
        );

        return response()->json(['message' => 'Roster saved.', 'roster' => $roster], 201);
    }

    public function destroyRoster(HrRoster $hrRoster)
    {
        $hrRoster->delete();
        return response()->json(['message' => 'Roster deleted.']);
    }
}