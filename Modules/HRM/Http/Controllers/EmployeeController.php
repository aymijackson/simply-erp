<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Leave;
use App\Models\Company;
use App\Models\Department;
use Yajra\DataTables\DataTables;

class EmployeeController extends Controller
{
    // =========================================================
    // AUDIT HELPER
    // =========================================================
    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        auth()->user()?->audit(
            module: 'hrm.employees',
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    // =========================================================
    // SELECT2
    // =========================================================
    public function select2(Request $request)
    {
        if ($request->filled('ids')) {
            $ids = array_filter((array) $request->input('ids'));

            $items = Employee::query()
                ->select('id', 'first_name', 'last_name', 'email', 'employee_code', 'is_active')
                ->whereIn('id', $ids)
                ->orderBy('last_name')
                ->get();

            return response()->json([
                'results' => $items->map(fn($e) => $this->toSelect2($e))->values(),
                'pagination' => ['more' => false],
            ]);
        }

        $term       = trim((string) $request->input('q', ''));
        $page       = max(1, (int) $request->input('page', 1));
        $perPage    = max(1, min(100, (int) $request->input('per_page', 20)));
        $offset     = ($page - 1) * $perPage;
        $onlyActive = filter_var($request->input('only_active', true), FILTER_VALIDATE_BOOL);
        $except     = array_filter((array) $request->input('except'));

        $q = Employee::query()
            ->select('id', 'first_name', 'last_name', 'email', 'employee_code', 'is_active');

        if ($onlyActive) {
            $q->where('is_active', true);
        }

        if (!empty($except)) {
            $q->whereNotIn('id', $except);
        }

        if ($term !== '') {
            $safe = str_replace(['%', '_'], ['\\%', '\\_'], $term);

            $q->where(function ($qq) use ($safe) {
                $qq->where('first_name', 'like', "%{$safe}%")
                    ->orWhere('last_name', 'like', "%{$safe}%")
                    ->orWhere('email', 'like', "%{$safe}%")
                    ->orWhere('employee_code', 'like', "%{$safe}%")
                    ->orWhere(DB::raw("CONCAT(first_name,' ',last_name)"), 'like', "%{$safe}%");
            });
        }

        $total = (clone $q)->count();

        $items = $q->orderBy('last_name')
            ->orderBy('first_name')
            ->skip($offset)
            ->take($perPage)
            ->get();

        return response()->json([
            'results' => $items->map(fn($e) => $this->toSelect2($e))->values(),
            'pagination' => ['more' => ($offset + $items->count()) < $total],
        ]);
    }

    private function toSelect2(Employee $e): array
    {
        $name = trim($e->first_name . ' ' . $e->last_name);
        $code = $e->employee_code ? " ({$e->employee_code})" : '';

        return [
            'id'   => $e->id,
            'text' => $name . $code,
        ];
    }

    // =========================================================
    // EMPLOYEES
    // =========================================================
    public function index()
    {
        return view('hrm.employees.index', [
            'companies'   => Company::all(),
            'departments' => Department::all(),
        ]);
    }

    public function show($id)
    {
        $employee = Employee::with([
            'company:id,name',
            'department:id,name',
        ])->findOrFail($id);

        $this->audit(
            'view',
            'Viewed employee ' . ($employee->full_name ?? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''))),
            $employee,
            ['employee_id' => $employee->id]
        );

        return view('hrm.employees.show', compact('employee'));
    }

    public function datatable()
    {
        $employees = Employee::with(['company', 'department'])->select('employees.*');

        return DataTables::of($employees)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('company', fn($row) => $row->company->name ?? '-')
            ->addColumn('department', fn($row) => $row->department->name ?? '-')
            ->addColumn('actions', function ($row) {
                return '<a href="' . route('admin.hrm.employees.show', $row->id) . '" class="btn btn-sm btn-outline-primary">View</a>
                        <button class="btn btn-sm btn-primary edit-employee"
                                data-id="' . $row->id . '"
                                data-company_id="' . $row->company_id . '"
                                data-department_id="' . $row->department_id . '"
                                data-employee_code="' . e($row->employee_code) . '"
                                data-first_name="' . e($row->first_name) . '"
                                data-last_name="' . e($row->last_name) . '"
                                data-email="' . e($row->email) . '"
                                data-phone="' . e($row->phone) . '"
                                data-position="' . e($row->position) . '"
                                data-date_of_birth="' . $row->date_of_birth . '"
                                data-date_hired="' . $row->date_hired . '">
                                Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-employee" data-id="' . $row->id . '">Delete</button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id'    => 'required|exists:companies,id',
            'department_id' => 'required|exists:departments,id',
            'employee_code' => 'required|string|max:20|unique:employees,employee_code',
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => 'required|email|unique:employees,email',
            'phone'         => 'nullable|string|max:20',
            'position'      => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'date_hired'    => 'nullable|date',
        ]);

        if ($request->filled('password')) {
            $data['password'] = \Hash::make($request->password);
        }

        $employee = Employee::create($data);

        $this->audit(
            'created',
            'Created employee ' . trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
            $employee,
            $data
        );

        return response()->json(['message' => 'Employee created successfully.']);
    }

    public function edit(Employee $employee)
    {
        return response()->json(['employee' => $employee]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'company_id'    => 'required|exists:companies,id',
            'department_id' => 'required|exists:departments,id',
            'employee_code' => [
                'required', 'string', 'max:20',
                Rule::unique('employees', 'employee_code')->ignore($employee->id),
            ],
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => [
                'required', 'email',
                Rule::unique('employees', 'email')->ignore($employee->id),
            ],
            'phone'         => 'nullable|string|max:20',
            'position'      => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'date_hired'    => 'nullable|date',
        ]);

        if ($request->filled('password')) {
            $data['password'] = \Hash::make($request->password);
        }

        $before = $employee->toArray();

        $employee->update($data);

        $this->audit(
            'updated',
            'Updated employee ' . trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
            $employee,
            ['before' => $before, 'after' => $employee->fresh()->toArray()]
        );

        return response()->json(['message' => 'Employee updated successfully.']);
    }

    public function destroy(Employee $employee)
    {
        $meta = $employee->toArray();

        $employee->delete();

        $this->audit(
            'deleted',
            'Deleted employee',
            null,
            $meta
        );

        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = (array) $request->input('ids', []);

        $rows = Employee::whereIn('id', $ids)->get();
        $meta = $rows->map->toArray()->values()->all();

        Employee::whereIn('id', $ids)->delete();

        $this->audit(
            'bulk_deleted',
            'Deleted selected employees',
            null,
            ['ids' => $ids, 'rows' => $meta]
        );

        return response()->json(['message' => 'Selected employees deleted successfully.']);
    }

    // =========================================================
    // ATTENDANCE HELPERS
    // =========================================================
    protected function hasOverlap($employeeId, $date, $clockIn, $clockOut, $excludeId = null): bool
    {
        return Attendance::where('employee_id', $employeeId)
            ->where('date', $date)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($query) use ($clockIn, $clockOut) {
                $query->where(function ($q) use ($clockIn, $clockOut) {
                    $q->where('clock_in', '<', $clockOut)
                        ->where('clock_out', '>', $clockIn);
                });
            })
            ->exists();
    }

    // =========================================================
    // ATTENDANCE INDEX/DATATABLE
    // =========================================================
    public function attendancesIndex()
    {
        return view('hrm.employees.attendances.index', [
            'employees' => Employee::all(),
        ]);
    }

    public function attendancesDatatable()
    {
        $records = Attendance::with('employee')->select('attendances.*');

        return DataTables::of($records)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('employee', fn($row) => trim(($row->employee->first_name ?? '') . ' ' . ($row->employee->last_name ?? '')))
            ->addColumn('date', fn($row) => $row->date ? date('d-m-Y', strtotime($row->date)) : '-')
            ->addColumn('clock_in', fn($row) => $row->clock_in ? date('h:i a', strtotime($row->clock_in)) : '-')
            ->addColumn('clock_out', fn($row) => $row->clock_out ? date('h:i a', strtotime($row->clock_out)) : '-')
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-primary edit-attendance"
                                data-id="' . $row->id . '"
                                data-employee_id="' . $row->employee_id . '"
                                data-date="' . $row->date . '"
                                data-clock_in="' . $row->clock_in . '"
                                data-clock_out="' . $row->clock_out . '"
                                data-note="' . e($row->note) . '">Edit</button>
                        <button class="btn btn-sm btn-danger delete-attendance" data-id="' . $row->id . '">Delete</button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    // =========================================================
    // EMPLOYEE SHOW PAGE - ATTENDANCE DATATABLE
    // =========================================================
    public function employeeAttendanceDatatable($employeeId)
    {
        $query = Attendance::query()
            ->where('employee_id', $employeeId)
            ->select([
                'id',
                'employee_id',
                'date',
                'clock_in',
                'clock_out',
                'note',
                'created_at',
                'updated_at',
            ]);

        return DataTables::of($query)
            ->editColumn('date', fn($row) => $row->date ? date('Y-m-d', strtotime($row->date)) : '-')
            ->editColumn('clock_in', fn($row) => $row->clock_in ?: '-')
            ->editColumn('clock_out', fn($row) => $row->clock_out ?: '-')
            ->editColumn('note', fn($row) => $row->note ?: '-')
            ->editColumn('created_at', fn($row) => $row->created_at ? date('Y-m-d H:i', strtotime($row->created_at)) : '-')
            ->editColumn('updated_at', fn($row) => $row->updated_at ? date('Y-m-d H:i', strtotime($row->updated_at)) : '-')
            ->addColumn('action', function ($row) {
                $btn = '';

                if (auth()->user()?->can('hrm.attendance.edit')) {
                    $btn .= '<button class="btn btn-sm btn-primary btn-edit-attendance"
                                data-id="' . $row->id . '"
                                data-employee_id="' . $row->employee_id . '"
                                data-date="' . $row->date . '"
                                data-clock_in="' . $row->clock_in . '"
                                data-clock_out="' . $row->clock_out . '"
                                data-note="' . e($row->note) . '">
                                <i class="fas fa-edit"></i>
                             </button> ';
                }

                if (auth()->user()?->can('hrm.attendance.delete')) {
                    $btn .= '<button class="btn btn-sm btn-danger delete-attendance-record" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i>
                             </button>';
                }

                return $btn ?: '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // =========================================================
    // ATTENDANCE CRUD
    // =========================================================
    public function storeAttendance(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'clock_in'    => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'clock_out'   => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'note'        => 'nullable|string',
        ]);

        if (!empty($data['clock_in']) && !empty($data['clock_out'])) {
            if (strtotime($data['clock_in']) >= strtotime($data['clock_out'])) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors'  => ['clock_in' => ['Clock-in must be earlier than clock-out.']],
                ], 422);
            }

            if ($this->hasOverlap($data['employee_id'], $data['date'], $data['clock_in'], $data['clock_out'])) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors'  => ['clock_in' => ['Attendance overlaps with an existing entry.']],
                ], 422);
            }
        }

        $attendance = Attendance::create($data);

        $this->audit(
            'attendance.created',
            'Created attendance record',
            $attendance,
            $data
        );

        return response()->json(['message' => 'Attendance saved successfully.']);
    }

    public function editAttendance(Attendance $attendance)
    {
        return response()->json(['attendance' => $attendance]);
    }

    public function updateAttendance(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'clock_in'    => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'clock_out'   => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'note'        => 'nullable|string',
        ]);

        if (!empty($data['clock_in']) && !empty($data['clock_out'])) {
            if (strtotime($data['clock_in']) >= strtotime($data['clock_out'])) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors'  => ['clock_in' => ['Clock-in must be earlier than clock-out.']],
                ], 422);
            }

            if ($this->hasOverlap($data['employee_id'], $data['date'], $data['clock_in'], $data['clock_out'], $attendance->id)) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors'  => ['clock_in' => ['Attendance overlaps with another entry.']],
                ], 422);
            }
        }

        $before = $attendance->toArray();

        $attendance->update($data);

        $this->audit(
            'attendance.updated',
            'Updated attendance record',
            $attendance,
            ['before' => $before, 'after' => $attendance->fresh()->toArray()]
        );

        return response()->json(['message' => 'Attendance updated successfully.']);
    }

    public function destroyAttendance(Attendance $attendance)
    {
        $meta = $attendance->toArray();

        $attendance->delete();

        $this->audit(
            'attendance.deleted',
            'Deleted attendance',
            null,
            $meta
        );

        return response()->json(['message' => 'Attendance deleted successfully.']);
    }

    public function bulkDeleteAttendances(Request $request)
    {
        $ids = (array) $request->input('ids', []);

        $rows = Attendance::whereIn('id', $ids)->get();
        $meta = $rows->map->toArray()->values()->all();

        Attendance::whereIn('id', $ids)->delete();

        $this->audit(
            'attendance.bulk_deleted',
            'Deleted selected attendance records',
            null,
            ['ids' => $ids, 'rows' => $meta]
        );

        return response()->json(['message' => 'Selected attendances deleted successfully.']);
    }

    // =========================================================
    // LEAVES INDEX/DATATABLE
    // =========================================================
    public function leavesIndex()
    {
        $employees = Employee::all();

        return view('hrm.employees.leaves.index', compact('employees'));
    }

    public function leavesDatatable()
    {
        $leaves = Leave::with('employee')->latest()->get();

        return datatables()->of($leaves)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('employee', fn($row) => trim(($row->employee->first_name ?? '') . ' ' . ($row->employee->last_name ?? '')))
            ->addColumn('status', fn($row) => ucfirst($row->status))
            ->addColumn('actions', function ($row) {
                $buttons = '
                    <button class="btn btn-sm btn-info edit-leave"
                        data-id="' . $row->id . '"
                        data-employee_id="' . $row->employee_id . '"
                        data-leave_type="' . e($row->leave_type) . '"
                        data-start_date="' . $row->start_date . '"
                        data-end_date="' . $row->end_date . '"
                        data-reason="' . e($row->reason) . '">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-leave" data-id="' . $row->id . '">
                        <i class="fas fa-trash-alt"></i>
                    </button> ';

                if ($row->status === 'pending') {
                    $buttons .= '
                        <button class="btn btn-sm btn-success approve-leave" data-id="' . $row->id . '">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-warning reject-leave" data-id="' . $row->id . '">
                            <i class="fas fa-times"></i>
                        </button>';
                } elseif ($row->status === 'approved') {
                    $buttons .= '
                        <button class="btn btn-sm btn-warning reject-leave" data-id="' . $row->id . '">
                            <i class="fas fa-times"></i>
                        </button>';
                } elseif ($row->status === 'rejected') {
                    $buttons .= '
                        <button class="btn btn-sm btn-success approve-leave" data-id="' . $row->id . '">
                            <i class="fas fa-check"></i>
                        </button>';
                }

                return $buttons;
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    // =========================================================
    // EMPLOYEE SHOW PAGE - LEAVE DATATABLE
    // =========================================================
    public function employeeLeaveDatatable($employeeId)
    {
        $query = Leave::query()
            ->where('employee_id', $employeeId)
            ->select([
                'id',
                'employee_id',
                'start_date',
                'end_date',
                'leave_type',
                'reason',
                'status',
                'created_at',
                'updated_at',
            ]);

        return DataTables::of($query)
            ->editColumn('start_date', fn($row) => $row->start_date ? date('Y-m-d', strtotime($row->start_date)) : '-')
            ->editColumn('end_date', fn($row) => $row->end_date ? date('Y-m-d', strtotime($row->end_date)) : '-')
            ->editColumn('leave_type', fn($row) => $row->leave_type ?: '-')
            ->editColumn('reason', fn($row) => $row->reason ?: '-')
            ->editColumn('status', function ($row) {
                return match ($row->status) {
                    'approved' => '<span class="badge bg-success">Approved</span>',
                    'rejected' => '<span class="badge bg-danger">Rejected</span>',
                    default    => '<span class="badge bg-warning text-dark">Pending</span>',
                };
            })
            ->editColumn('created_at', fn($row) => $row->created_at ? date('Y-m-d H:i', strtotime($row->created_at)) : '-')
            ->editColumn('updated_at', fn($row) => $row->updated_at ? date('Y-m-d H:i', strtotime($row->updated_at)) : '-')
            ->addColumn('action', function ($row) {
                $btn = '';

                if (auth()->user()?->can('hrm.leave.edit')) {
                    $btn .= '<button class="btn btn-sm btn-info btn-edit-leave"
                                data-id="' . $row->id . '"
                                data-employee_id="' . $row->employee_id . '"
                                data-leave_type="' . e($row->leave_type) . '"
                                data-start_date="' . $row->start_date . '"
                                data-end_date="' . $row->end_date . '"
                                data-reason="' . e($row->reason) . '"
                                data-status="' . e($row->status) . '">
                                <i class="fas fa-edit"></i>
                             </button> ';
                }

                if ($row->status !== 'approved' && auth()->user()?->can('hrm.leave.approve')) {
                    $btn .= '<button class="btn btn-sm btn-success btn-approve-leave" data-id="' . $row->id . '">
                                <i class="fas fa-check"></i>
                             </button> ';
                }

                if ($row->status !== 'rejected' && auth()->user()?->can('hrm.leave.reject')) {
                    $btn .= '<button class="btn btn-sm btn-warning btn-reject-leave" data-id="' . $row->id . '">
                                <i class="fas fa-times"></i>
                             </button> ';
                }

                if (auth()->user()?->can('hrm.leave.delete')) {
                    $btn .= '<button class="btn btn-sm btn-danger delete-leave-record" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i>
                             </button>';
                }

                return $btn ?: '-';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    // =========================================================
    // LEAVE CRUD
    // =========================================================
    public function storeLeave(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'leave_type'  => 'required|string|max:100',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
        ]);

        $leave = Leave::create($data + ['status' => 'pending']);

        $this->audit(
            'leave.created',
            'Created leave request',
            $leave,
            $data
        );

        return response()->json(['message' => 'Leave created successfully.']);
    }

    public function editLeave(Leave $leave)
    {
        return response()->json(['leave' => $leave]);
    }

    public function updateLeave(Request $request, Leave $leave)
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'leave_type'  => 'required|string|max:100',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
        ]);

        $before = $leave->toArray();

        $leave->update($data);

        $this->audit(
            'leave.updated',
            'Updated leave request',
            $leave,
            ['before' => $before, 'after' => $leave->fresh()->toArray()]
        );

        return response()->json(['message' => 'Leave updated successfully.']);
    }

    public function destroyLeave(Leave $leave)
    {
        $meta = $leave->toArray();

        $leave->delete();

        $this->audit(
            'leave.deleted',
            'Deleted leave',
            null,
            $meta
        );

        return response()->json(['message' => 'Leave deleted successfully.']);
    }

    public function approveLeave($id)
    {
        $leave = Leave::findOrFail($id);
        $before = $leave->status;

        $leave->update(['status' => 'approved']);

        $this->audit(
            'leave.approved',
            'Approved leave',
            $leave,
            ['before' => $before, 'after' => 'approved']
        );

        return response()->json(['message' => 'Leave request approved.']);
    }

    public function rejectLeave($id)
    {
        $leave = Leave::findOrFail($id);
        $before = $leave->status;

        $leave->update(['status' => 'rejected']);

        $this->audit(
            'leave.rejected',
            'Rejected leave',
            $leave,
            ['before' => $before, 'after' => 'rejected']
        );

        return response()->json(['message' => 'Leave request rejected.']);
    }

    public function bulkDeleteLeave(Request $request)
    {
        $ids = (array) $request->input('ids', []);

        $rows = Leave::whereIn('id', $ids)->get();
        $meta = $rows->map->toArray()->values()->all();

        Leave::whereIn('id', $ids)->delete();

        $this->audit(
            'leave.bulk_deleted',
            'Deleted selected leave requests',
            null,
            ['ids' => $ids, 'rows' => $meta]
        );

        return response()->json(['message' => 'Selected leave requests deleted.']);
    }

    // =========================================================
    // EMPLOYEE SHOW PAGE - PAYROLL DATATABLE
    // =========================================================
    public function employeePayrollDatatable($employeeId)
    {
        $query = DB::table('payrolls')
            ->where('employee_id', $employeeId)
            ->select([
                'id',
                'employee_id',
                'pay_date',
                'basic_salary',
                'net_salary',
                'total_deductions',
                'total_allowances',
                'status',
                'is_paid',
                'remarks',
                'created_at',
                'updated_at',
            ]);

        return DataTables::of($query)
            ->editColumn('pay_date', fn($row) => $row->pay_date ? date('Y-m-d', strtotime($row->pay_date)) : '-')
            ->editColumn('basic_salary', fn($row) => number_format((float) ($row->basic_salary ?? 0), 2))
            ->editColumn('net_salary', fn($row) => number_format((float) ($row->net_salary ?? 0), 2))
            ->editColumn('total_deductions', fn($row) => number_format((float) ($row->total_deductions ?? 0), 2))
            ->editColumn('total_allowances', fn($row) => number_format((float) ($row->total_allowances ?? 0), 2))
            ->editColumn('status', fn($row) => $row->status ? ucfirst($row->status) : '-')
            ->editColumn('is_paid', fn($row) => (int) ($row->is_paid ?? 0) === 1 ? 'Yes' : 'No')
            ->editColumn('remarks', fn($row) => $row->remarks ?: '-')
            ->editColumn('created_at', fn($row) => $row->created_at ? date('Y-m-d H:i', strtotime($row->created_at)) : '-')
            ->editColumn('updated_at', fn($row) => $row->updated_at ? date('Y-m-d H:i', strtotime($row->updated_at)) : '-')
            ->addColumn('action', function ($row) {
                $btn = '';

                if (auth()->user()?->can('hrm.payroll.edit')) {
                    $btn .= '<button class="btn btn-sm btn-primary btn-edit-payroll"
                                data-id="' . $row->id . '"
                                data-employee_id="' . $row->employee_id . '"
                                data-pay_date="' . $row->pay_date . '"
                                data-basic_salary="' . $row->basic_salary . '"
                                data-net_salary="' . $row->net_salary . '"
                                data-total_deductions="' . $row->total_deductions . '"
                                data-total_allowances="' . $row->total_allowances . '"
                                data-status="' . e($row->status) . '"
                                data-is_paid="' . (int) ($row->is_paid ?? 0) . '"
                                data-remarks="' . e($row->remarks) . '">
                                <i class="fas fa-edit"></i>
                             </button> ';
                }

                if (auth()->user()?->can('hrm.payroll.delete')) {
                    $btn .= '<button class="btn btn-sm btn-danger delete-payroll-record" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i>
                             </button>';
                }

                return $btn ?: '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // =========================================================
    // EMPLOYEE SHOW PAGE - PERFORMANCE DATATABLE
    // =========================================================
    public function employeePerformanceDatatable($employeeId)
    {
        $query = DB::table('performances')
            ->where('employee_id', $employeeId)
            ->select([
                'id',
                'employee_id',
                'goal_title',
                'kpi_description',
                'review_period',
                'score',
                'rating',
                'comments',
                'review_date',
                'reviewed_by',
                'created_at',
                'updated_at',
            ]);

        return DataTables::of($query)
            ->editColumn('goal_title', fn($row) => $row->goal_title ?: '-')
            ->editColumn('kpi_description', fn($row) => $row->kpi_description ?: '-')
            ->editColumn('review_period', fn($row) => $row->review_period ?: '-')
            ->editColumn('score', fn($row) => is_null($row->score) ? '-' : $row->score)
            ->editColumn('rating', fn($row) => is_null($row->rating) ? '-' : $row->rating)
            ->editColumn('comments', fn($row) => $row->comments ?: '-')
            ->addColumn('reviewed_by', function ($row) {
                if (!$row->reviewed_by) {
                    return '-';
                }

                $reviewer = Employee::find($row->reviewed_by);
                return $reviewer?->full_name ?: trim(($reviewer->first_name ?? '') . ' ' . ($reviewer->last_name ?? '')) ?: $row->reviewed_by;
            })
            ->editColumn('review_date', fn($row) => $row->review_date ? date('Y-m-d', strtotime($row->review_date)) : '-')
            ->editColumn('created_at', fn($row) => $row->created_at ? date('Y-m-d H:i', strtotime($row->created_at)) : '-')
            ->editColumn('updated_at', fn($row) => $row->updated_at ? date('Y-m-d H:i', strtotime($row->updated_at)) : '-')
            ->addColumn('action', function ($row) {
                $btn = '';

                if (auth()->user()?->can('hrm.performance.edit')) {
                    $btn .= '<button class="btn btn-sm btn-primary btn-edit-performance"
                                data-id="' . $row->id . '"
                                data-employee_id="' . $row->employee_id . '"
                                data-goal_title="' . e($row->goal_title) . '"
                                data-kpi_description="' . e($row->kpi_description) . '"
                                data-review_period="' . e($row->review_period) . '"
                                data-score="' . e($row->score) . '"
                                data-rating="' . e($row->rating) . '"
                                data-comments="' . e($row->comments) . '"
                                data-review_date="' . $row->review_date . '"
                                data-reviewed_by="' . e($row->reviewed_by) . '">
                                <i class="fas fa-edit"></i>
                             </button> ';
                }

                if (auth()->user()?->can('hrm.performance.delete')) {
                    $btn .= '<button class="btn btn-sm btn-danger delete-performance-record" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i>
                             </button>';
                }

                return $btn ?: '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}