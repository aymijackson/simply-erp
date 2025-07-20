<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Leave;
use App\Models\Company;
use App\Models\Department;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;


class EmployeeController extends Controller
{
    public function index()
    {
        return view('hrm.employees.index', [
            'companies' => Company::all(),
            'departments' => Department::all()
        ]);
    }

    public function datatable()
    {
        $employees = Employee::with(['company', 'department'])->select('employees.*');
        return DataTables::of($employees)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('company', fn($row) => $row->company->name ?? '-')
            ->addColumn('department', fn($row) => $row->department->name ?? '-')
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-primary edit-employee" 
                                data-id="' . $row->id . '" 
                                data-company_id="' . $row->company_id . '"
                                data-department_id="' . $row->department_id . '"
                                data-employee_code="' . $row->employee_code . '"
                                data-first_name="' . $row->first_name . '"
                                data-last_name="' . $row->last_name . '"
                                data-email="' . $row->email . '"
                                data-phone="' . $row->phone . '"
                                data-position="' . $row->position . '"
                                data-date_of_birth="' . $row->date_of_birth . '"
                                data-date_hired="' . $row->date_hired . '">
                                Edit</button>
                        <button class="btn btn-sm btn-danger delete-employee" data-id="' . $row->id . '">Delete</button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'required|exists:departments,id',
            'employee_code' => 'required|string|max:20|unique:employees,employee_code',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'date_hired' => 'nullable|date',
        ]);

        if ($request->filled('password')) {
            $data['password'] = \Hash::make($request->password);
        }
        
        Employee::create($data);
        return response()->json(['message' => 'Employee created successfully.']);
    }

    public function edit(Employee $employee)
    {
        return response()->json(['employee' => $employee]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'required|exists:departments,id',
            'employee_code' => [
                'required', 'string', 'max:20',
                Rule::unique('employees', 'employee_code')->ignore($employee->id)
            ],
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'required', 'email',
                Rule::unique('employees', 'email')->ignore($employee->id)
            ],
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'date_hired' => 'nullable|date',
            
        ]);

        if ($request->filled('password')) {
            $data['password'] = \Hash::make($request->password);
        }

        $employee->update($data);
        
        return response()->json(['message' => 'Employee updated successfully.']);
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        Employee::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected employees deleted successfully.']);
    }

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


    public function attendancesIndex()
    {
        return view('hrm.employees.attendances.index', [
            'employees' => Employee::all()
        ]);
    }

    public function attendancesDatatable()
    {
        $records = Attendance::with('employee')->select('attendances.*');
        return DataTables::of($records)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('employee', fn($row) => $row->employee->first_name . ' ' . $row->employee->last_name)
            ->addColumn('date', fn($row) => date('d-m-Y', strtotime($row->date)))
            ->addColumn('clock_in', fn($row) => date('h:i a', strtotime($row->clock_in)))
            ->addColumn('clock_out', fn($row) => date('h:i a', strtotime($row->clock_out)))
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-primary edit-attendance" 
                                data-id="' . $row->id . '" 
                                data-employee_id="' . $row->employee_id . '"
                                data-date="' . $row->date . '"
                                data-clock_in="' . $row->clock_in . '"
                                data-clock_out="' . $row->clock_out . '"
                                data-note="' . $row->note . '">Edit</button>
                        <button class="btn btn-sm btn-danger delete-attendance" data-id="' . $row->id . '">Delete</button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function storeAttendance(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'clock_in' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'clock_out' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'note' => 'nullable|string'
        ]);

        if (!empty($data['clock_in']) && !empty($data['clock_out'])) {
            if (strtotime($data['clock_in']) >= strtotime($data['clock_out'])) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => ['clock_in' => ['Clock-in must be earlier than clock-out.']]
                ], 422);
            }

            if ($this->hasOverlap($data['employee_id'], $data['date'], $data['clock_in'], $data['clock_out'])) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => ['clock_in' => ['Attendance overlaps with an existing entry.']]
                ], 422);
            }
        }

        Attendance::create($data);

        return response()->json(['message' => 'Attendance saved successfully.']);
    }


    public function updateAttendance(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'clock_in' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'clock_out' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'note' => 'nullable|string'
        ]);

        if (!empty($data['clock_in']) && !empty($data['clock_out'])) {
            if (strtotime($data['clock_in']) >= strtotime($data['clock_out'])) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => ['clock_in' => ['Clock-in must be earlier than clock-out.']]
                ], 422);
            }

            if ($this->hasOverlap($data['employee_id'], $data['date'], $data['clock_in'], $data['clock_out'], $attendance->id)) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => ['clock_in' => ['Attendance overlaps with another entry.']]
                ], 422);
            }
        }

        $attendance->update($data);

        return response()->json(['message' => 'Attendance updated successfully.']);
    }


    public function destroyAttendance(Attendance $attendance)
    {
        $attendance->delete();
        return response()->json(['message' => 'Attendance deleted successfully.']);
    }

    public function bulkDeleteAttendances(Request $request)
    {
        Attendance::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected attendances deleted successfully.']);
    }       

    public function leavesIndex()
    {
        $employees = Employee::all();
        return view('hrm.employees.leaves.index', compact('employees'));
    }

    public function leavesDatatable()
    {
        $leaves = Leave::with('employee')->latest()->get();
    
        return datatables()->of($leaves)
            ->addColumn('checkbox', fn($row) =>
                '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">'
            )
            ->addColumn('employee', fn($row) =>
                $row->employee->first_name . ' ' . $row->employee->last_name
            )
            ->addColumn('status', fn($row) =>
                ucfirst($row->status)
            )
            ->addColumn('actions', function ($row) {
                $buttons = '
                    <button class="btn btn-sm btn-info edit-leave"
                        data-id="' . $row->id . '"
                        data-employee_id="' . $row->employee_id . '"
                        data-leave_type="' . $row->leave_type . '"
                        data-start_date="' . $row->start_date . '"
                        data-end_date="' . $row->end_date . '"
                        data-reason="' . e($row->reason) . '">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-leave" data-id="' . $row->id . '">
                        <i class="fas fa-trash-alt"></i>
                    </button> ';
    
                // Toggle mutually exclusive approve/reject
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
    

    public function storeLeave(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'leave_type' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string'
        ]);

        Leave::create($data + ['status' => 'pending']);
        return response()->json(['message' => 'Leave created successfully.']);
    }

    public function updateLeave(Request $request, Leave $leave)
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'leave_type' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string'
        ]);

        $leave->update($data);
        return response()->json(['message' => 'Leave updated successfully.']);
    }

    public function destroyLeave(Leave $leave)
    {
        $leave->delete();
        return response()->json(['message' => 'Leave deleted successfully.']);
    }

    public function approveLeave($id)
    {
        $leave = Leave::findOrFail($id);
        $leave->update(['status' => 'approved']);
        return response()->json(['message' => 'Leave request approved.']);
    }

    public function rejectLeave($id)
    {
        $leave = Leave::findOrFail($id);
        $leave->update(['status' => 'rejected']);
        return response()->json(['message' => 'Leave request rejected.']);
    }

    public function bulkDeleteLeave(Request $request)
    {
        $ids = $request->input('ids');
        Leave::whereIn('id', $ids)->delete();
        return response()->json(['message' => 'Selected leave requests deleted.']);
    }
}
