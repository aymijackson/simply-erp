{{-- resources/views/hrm/employees/show.blade.php --}}
@extends('layouts.master')

@section('title', 'Employee Details')

@push('styles')
<style>
    .tab-pane .table td,
    .tab-pane .table th {
        vertical-align: middle;
    }

    .select2-container {
        width: 100% !important;
    }

    .summary-card .card-body {
        padding: 1rem 1.1rem;
    }

    .summary-label {
        font-size: .78rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .summary-value {
        font-size: 1.05rem;
        font-weight: 600;
        color: #212529;
    }

    .nav-tabs .nav-link {
        font-weight: 500;
    }

    .table-responsive {
        min-height: 220px;
    }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div class="mb-2 mb-md-0">
            <h1 class="h3 text-primary mb-0">
                {{ trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) ?: 'Employee Details' }}
            </h1>
            <p class="text-muted mb-0">
                Employee Profile • Overview, Attendance, Leave, Payroll, Performance & Documents
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.hrm.employees.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>

            @can('hrm.employees.edit')
                <a href="{{ route('admin.hrm.employees.edit', $employee->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit Employee
                </a>
            @endcan
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm summary-card">
                <div class="card-body">
                    <div class="summary-label">Employee Code</div>
                    <div class="summary-value">{{ $employee->employee_code ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-2">
            <div class="card shadow-sm summary-card">
                <div class="card-body">
                    <div class="summary-label">Department</div>
                    <div class="summary-value">{{ optional($employee->department)->name ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-2">
            <div class="card shadow-sm summary-card">
                <div class="card-body">
                    <div class="summary-label">Position</div>
                    <div class="summary-value">{{ $employee->position ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-2">
            <div class="card shadow-sm summary-card">
                <div class="card-body">
                    <div class="summary-label">Status</div>
                    <div class="summary-value">
                        @if((int)($employee->is_active ?? 0) === 1)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="employeeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overviewPane" type="button" role="tab">
                        <i class="fas fa-info-circle me-1"></i> Overview
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendancePane" type="button" role="tab">
                        <i class="fas fa-user-check me-1"></i> Attendance
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="leave-tab" data-bs-toggle="tab" data-bs-target="#leavePane" type="button" role="tab">
                        <i class="fas fa-calendar-alt me-1"></i> Leave
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payroll-tab" data-bs-toggle="tab" data-bs-target="#payrollPane" type="button" role="tab">
                        <i class="fas fa-money-check-alt me-1"></i> Payroll
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performancePane" type="button" role="tab">
                        <i class="fas fa-chart-line me-1"></i> Performance
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documentsPane" type="button" role="tab">
                        <i class="fas fa-folder-open me-1"></i> Documents
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">

                {{-- Overview --}}
                <div class="tab-pane fade show active" id="overviewPane" role="tabpanel" aria-labelledby="overview-tab">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width:35%;">Employee Code</th>
                                    <td>{{ $employee->employee_code ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>First Name</th>
                                    <td>{{ $employee->first_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Last Name</th>
                                    <td>{{ $employee->last_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>{{ $employee->email ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td>{{ $employee->phone ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Position</th>
                                    <td>{{ $employee->position ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6 mb-3">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width:35%;">Company</th>
                                    <td>{{ optional($employee->company)->name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td>{{ optional($employee->department)->name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td>
                                        {{ $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('d M Y') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date Hired</th>
                                    <td>
                                        {{ $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->format('d M Y') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{{ (int)($employee->is_active ?? 0) === 1 ? 'Active' : 'Inactive' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ optional($employee->created_at)->format('d M Y H:i') ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Attendance --}}
                <div class="tab-pane fade" id="attendancePane" role="tabpanel" aria-labelledby="attendance-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Attendance Records</h5>

                        @can('hrm.attendance.create')
                            <button type="button" class="btn btn-sm btn-primary" id="addAttendanceBtn">
                                <i class="fas fa-plus me-1"></i> Add Attendance
                            </button>
                        @endcan
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered w-100" id="attendanceTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Clock In</th>
                                    <th>Clock Out</th>
                                    <th>Note</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th style="width:120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- Leave --}}
                <div class="tab-pane fade" id="leavePane" role="tabpanel" aria-labelledby="leave-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Leave Records</h5>

                        @can('hrm.leave.create')
                            <button type="button" class="btn btn-sm btn-primary" id="addLeaveBtn">
                                <i class="fas fa-plus me-1"></i> Add Leave
                            </button>
                        @endcan
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered w-100" id="leaveTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Leave Type</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th style="width:180px;">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- Payroll --}}
                <div class="tab-pane fade" id="payrollPane" role="tabpanel" aria-labelledby="payroll-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Payroll Records</h5>

                        @can('hrm.payroll.create')
                            <button type="button" class="btn btn-sm btn-primary" id="addPayrollBtn">
                                <i class="fas fa-plus me-1"></i> Add Payroll
                            </button>
                        @endcan
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered w-100" id="payrollTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Pay Date</th>
                                    <th>Basic Salary</th>
                                    <th>Net Salary</th>
                                    <th>Total Deductions</th>
                                    <th>Total Allowances</th>
                                    <th>Status</th>
                                    <th>Paid?</th>
                                    <th>Remarks</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th style="width:120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- Performance --}}
                <div class="tab-pane fade" id="performancePane" role="tabpanel" aria-labelledby="performance-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Performance Reviews</h5>

                        @can('hrm.performance.create')
                            <button type="button" class="btn btn-sm btn-primary" id="addPerformanceBtn">
                                <i class="fas fa-plus me-1"></i> Add Review
                            </button>
                        @endcan
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered w-100" id="performanceTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Goal Title</th>
                                    <th>KPI Description</th>
                                    <th>Review Period</th>
                                    <th>Score</th>
                                    <th>Rating</th>
                                    <th>Comments</th>
                                    <th>Review Date</th>
                                    <th>Reviewed By</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th style="width:120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- Documents --}}
                <div class="tab-pane fade" id="documentsPane" role="tabpanel" aria-labelledby="documents-tab">
                    @include('documents.partials.linked-documents-tab', [
                        'model' => $employee
                    ])
                </div>

            </div>
        </div>
    </div>

</div>

{{-- Attendance Modal --}}
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="attendanceForm" class="modal-content">
            @csrf

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Attendance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="attendance_employee_id" value="{{ $employee->id }}">

                <div class="mb-2">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" id="attendance_date" required>
                </div>

                <div class="mb-2">
                    <label class="form-label">Clock In</label>
                    <input type="time" class="form-control" id="attendance_clock_in">
                </div>

                <div class="mb-2">
                    <label class="form-label">Clock Out</label>
                    <input type="time" class="form-control" id="attendance_clock_out">
                </div>

                <div class="mb-2">
                    <label class="form-label">Note</label>
                    <textarea class="form-control" id="attendance_note" rows="2"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Leave Modal --}}
<div class="modal fade" id="leaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="leaveForm" class="modal-content">
            @csrf

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Leave</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="leave_employee_id" value="{{ $employee->id }}">

                <div class="mb-2">
                    <label class="form-label">Leave Type</label>
                    <input type="text" class="form-control" id="leave_type" required>
                </div>

                <div class="mb-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="leave_start_date" required>
                </div>

                <div class="mb-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" id="leave_end_date" required>
                </div>

                <div class="mb-2">
                    <label class="form-label">Reason</label>
                    <textarea class="form-control" id="leave_reason" rows="2"></textarea>
                </div>

                <div class="alert alert-light border mb-0">
                    New leave requests are saved with
                    <span class="badge bg-warning text-dark">Pending</span>
                    status based on your current controller logic.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Payroll Modal --}}
<div class="modal fade" id="payrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="payrollForm" class="modal-content">
            @csrf

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Payroll</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="payroll_employee_id" value="{{ $employee->id }}">

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Pay Date</label>
                        <input type="date" class="form-control" id="payroll_pay_date" required>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="payroll_status" required>
                            <option value="draft">Draft</option>
                            <option value="processed">Processed</option>
                            <option value="approved">Approved</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Basic Salary</label>
                        <input type="number" step="0.01" class="form-control" id="payroll_basic_salary" required>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Net Salary</label>
                        <input type="number" step="0.01" class="form-control" id="payroll_net_salary" required>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Total Deductions</label>
                        <input type="number" step="0.01" class="form-control" id="payroll_total_deductions" value="0">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Total Allowances</label>
                        <input type="number" step="0.01" class="form-control" id="payroll_total_allowances" value="0">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Paid?</label>
                        <select class="form-select" id="payroll_is_paid">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" id="payroll_remarks" rows="2"></textarea>
                    </div>
                </div>

                <div class="alert alert-warning mb-0">
                    Payroll create action will only work if you already have the corresponding store route and controller method implemented.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Performance Modal --}}
<div class="modal fade" id="performanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="performanceForm" class="modal-content">
            @csrf

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Performance Review</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="performance_employee_id" value="{{ $employee->id }}">

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Goal Title</label>
                        <input type="text" class="form-control" id="performance_goal_title" required>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Review Period</label>
                        <input type="text" class="form-control" id="performance_review_period" placeholder="e.g. Q1 2026">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Score</label>
                        <input type="number" step="0.01" class="form-control" id="performance_score">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Rating</label>
                        <input type="text" class="form-control" id="performance_rating">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Review Date</label>
                        <input type="date" class="form-control" id="performance_review_date">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Reviewed By</label>
                        <select class="form-select" id="performance_reviewed_by">
                            <option value="">-- Select --</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label class="form-label">KPI Description</label>
                        <textarea class="form-control" id="performance_kpi_description" rows="2"></textarea>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label class="form-label">Comments</label>
                        <textarea class="form-control" id="performance_comments" rows="2"></textarea>
                    </div>
                </div>

                <div class="alert alert-warning mb-0">
                    Performance create action will only work if you already have the corresponding store route and controller method implemented.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const employeeId = {{ (int) $employee->id }};
    const csrf = $('meta[name="csrf-token"]').attr('content');

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf }
    });

    const attendanceModal  = new bootstrap.Modal(document.getElementById('attendanceModal'));
    const leaveModal       = new bootstrap.Modal(document.getElementById('leaveModal'));
    const payrollModal     = new bootstrap.Modal(document.getElementById('payrollModal'));
    const performanceModal = new bootstrap.Modal(document.getElementById('performanceModal'));

    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message || 'Operation completed successfully.'
        });
    }

    function showError(xhr, fallbackMessage = 'Something went wrong.') {
        let msg = fallbackMessage;

        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            } else if (xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
        }

        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: msg
        });
    }

    function confirmDelete(title, text) {
        return Swal.fire({
            icon: 'warning',
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel'
        });
    }

    const attendanceTable = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.hrm.employees.show.attendance.datatable', $employee->id) }}",
        columns: [
            { data: 'date', defaultContent: '' },
            { data: 'clock_in', defaultContent: '' },
            { data: 'clock_out', defaultContent: '' },
            { data: 'note', defaultContent: '' },
            { data: 'created_at', defaultContent: '' },
            { data: 'updated_at', defaultContent: '' },
            { data: 'action', orderable: false, searchable: false, defaultContent: '' }
        ],
        language: { emptyTable: "No attendance records found." }
    });

    const leaveTable = $('#leaveTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.hrm.employees.show.leave.datatable', $employee->id) }}",
        columns: [
            { data: 'start_date', defaultContent: '' },
            { data: 'end_date', defaultContent: '' },
            { data: 'leave_type', defaultContent: '' },
            { data: 'reason', defaultContent: '' },
            { data: 'status', defaultContent: '' },
            { data: 'created_at', defaultContent: '' },
            { data: 'updated_at', defaultContent: '' },
            { data: 'action', orderable: false, searchable: false, defaultContent: '' }
        ],
        language: { emptyTable: "No leave records found." }
    });

    const payrollTable = $('#payrollTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.hrm.employees.show.payroll.datatable', $employee->id) }}",
        columns: [
            { data: 'pay_date', defaultContent: '' },
            { data: 'basic_salary', defaultContent: '' },
            { data: 'net_salary', defaultContent: '' },
            { data: 'total_deductions', defaultContent: '' },
            { data: 'total_allowances', defaultContent: '' },
            { data: 'status', defaultContent: '' },
            { data: 'is_paid', defaultContent: '' },
            { data: 'remarks', defaultContent: '' },
            { data: 'created_at', defaultContent: '' },
            { data: 'updated_at', defaultContent: '' },
            { data: 'action', orderable: false, searchable: false, defaultContent: '' }
        ],
        language: { emptyTable: "No payroll records found." }
    });

    const performanceTable = $('#performanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.hrm.employees.show.performance.datatable', $employee->id) }}",
        columns: [
            { data: 'goal_title', defaultContent: '' },
            { data: 'kpi_description', defaultContent: '' },
            { data: 'review_period', defaultContent: '' },
            { data: 'score', defaultContent: '' },
            { data: 'rating', defaultContent: '' },
            { data: 'comments', defaultContent: '' },
            { data: 'review_date', defaultContent: '' },
            { data: 'reviewed_by', defaultContent: '' },
            { data: 'created_at', defaultContent: '' },
            { data: 'updated_at', defaultContent: '' },
            { data: 'action', orderable: false, searchable: false, defaultContent: '' }
        ],
        language: { emptyTable: "No performance records found." }
    });

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    $('#performance_reviewed_by').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#performanceModal'),
        placeholder: 'Select reviewer...',
        allowClear: true,
        ajax: {
            url: "{{ route('admin.hrm.employees.select2') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || '',
                    only_active: true,
                    except: [employeeId]
                };
            },
            processResults: function (data) {
                return data;
            }
        }
    });

    $('#addAttendanceBtn').on('click', function () {
        $('#attendanceForm')[0].reset();
        attendanceModal.show();
    });

    $('#addLeaveBtn').on('click', function () {
        $('#leaveForm')[0].reset();
        leaveModal.show();
    });

    $('#addPayrollBtn').on('click', function () {
        $('#payrollForm')[0].reset();
        $('#payroll_status').val('draft');
        $('#payroll_is_paid').val('0');
        payrollModal.show();
    });

    $('#addPerformanceBtn').on('click', function () {
        $('#performanceForm')[0].reset();
        $('#performance_reviewed_by').val(null).trigger('change');
        performanceModal.show();
    });

    /*
    |--------------------------------------------------------------------------
    | CREATE ATTENDANCE
    |--------------------------------------------------------------------------
    */
    $('#attendanceForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('admin.hrm.employees.attendances.store') }}",
            type: 'POST',
            data: {
                employee_id: employeeId,
                date: $('#attendance_date').val(),
                clock_in: $('#attendance_clock_in').val(),
                clock_out: $('#attendance_clock_out').val(),
                note: $('#attendance_note').val()
            },
            success: function (resp) {
                attendanceModal.hide();
                attendanceTable.ajax.reload(null, false);
                showSuccess(resp.message || 'Attendance saved successfully.');
            },
            error: function (xhr) {
                showError(xhr, 'Failed to save attendance.');
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | CREATE LEAVE
    |--------------------------------------------------------------------------
    */
    $('#leaveForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('admin.hrm.employees.leaves.store') }}",
            type: 'POST',
            data: {
                employee_id: employeeId,
                leave_type: $('#leave_type').val(),
                start_date: $('#leave_start_date').val(),
                end_date: $('#leave_end_date').val(),
                reason: $('#leave_reason').val()
            },
            success: function (resp) {
                leaveModal.hide();
                leaveTable.ajax.reload(null, false);
                showSuccess(resp.message || 'Leave created successfully.');
            },
            error: function (xhr) {
                showError(xhr, 'Failed to save leave.');
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | CREATE PAYROLL
    |--------------------------------------------------------------------------
    | This assumes the route exists in your app.
    |--------------------------------------------------------------------------
    */
    $('#payrollForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('admin.hrm.employees.payrolls.store') }}",
            type: 'POST',
            data: {
                employee_id: employeeId,
                pay_date: $('#payroll_pay_date').val(),
                basic_salary: $('#payroll_basic_salary').val(),
                net_salary: $('#payroll_net_salary').val(),
                total_deductions: $('#payroll_total_deductions').val(),
                total_allowances: $('#payroll_total_allowances').val(),
                status: $('#payroll_status').val(),
                is_paid: $('#payroll_is_paid').val(),
                remarks: $('#payroll_remarks').val()
            },
            success: function (resp) {
                payrollModal.hide();
                payrollTable.ajax.reload(null, false);
                showSuccess(resp.message || 'Payroll saved successfully.');
            },
            error: function (xhr) {
                showError(xhr, 'Failed to save payroll.');
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | CREATE PERFORMANCE
    |--------------------------------------------------------------------------
    | This assumes the route exists in your app.
    |--------------------------------------------------------------------------
    */
    $('#performanceForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('admin.hrm.employees.performances.store') }}",
            type: 'POST',
            data: {
                employee_id: employeeId,
                goal_title: $('#performance_goal_title').val(),
                kpi_description: $('#performance_kpi_description').val(),
                review_period: $('#performance_review_period').val(),
                score: $('#performance_score').val(),
                rating: $('#performance_rating').val(),
                comments: $('#performance_comments').val(),
                review_date: $('#performance_review_date').val(),
                reviewed_by: $('#performance_reviewed_by').val()
            },
            success: function (resp) {
                performanceModal.hide();
                performanceTable.ajax.reload(null, false);
                showSuccess(resp.message || 'Performance review saved successfully.');
            },
            error: function (xhr) {
                showError(xhr, 'Failed to save performance review.');
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | DELETE ATTENDANCE
    |--------------------------------------------------------------------------
    | Controller button class:
    | delete-attendance-record
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.delete-attendance-record', function () {
        const id = $(this).data('id');

        confirmDelete('Delete attendance?', 'This attendance record will be deleted permanently.')
            .then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ url('admin/hrm/employees/attendances') }}/" + id,
                    type: 'DELETE',
                    success: function (resp) {
                        attendanceTable.ajax.reload(null, false);
                        showSuccess(resp.message || 'Attendance deleted successfully.');
                    },
                    error: function (xhr) {
                        showError(xhr, 'Failed to delete attendance.');
                    }
                });
            });
    });

    /*
    |--------------------------------------------------------------------------
    | APPROVE LEAVE
    |--------------------------------------------------------------------------
    | Controller button class:
    | btn-approve-leave
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.btn-approve-leave', function () {
        const id = $(this).data('id');

        Swal.fire({
            icon: 'question',
            title: 'Approve leave?',
            text: 'This leave request will be marked as approved.',
            showCancelButton: true,
            confirmButtonText: 'Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ url('admin/hrm/employees/leaves') }}/" + id + "/approve",
                type: 'POST',
                success: function (resp) {
                    leaveTable.ajax.reload(null, false);
                    showSuccess(resp.message || 'Leave approved.');
                },
                error: function (xhr) {
                    showError(xhr, 'Failed to approve leave.');
                }
            });
        });
    });

    /*
    |--------------------------------------------------------------------------
    | REJECT LEAVE
    |--------------------------------------------------------------------------
    | Controller button class:
    | btn-reject-leave
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.btn-reject-leave', function () {
        const id = $(this).data('id');

        Swal.fire({
            icon: 'warning',
            title: 'Reject leave?',
            text: 'This leave request will be marked as rejected.',
            showCancelButton: true,
            confirmButtonText: 'Reject',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ url('admin/hrm/employees/leaves') }}/" + id + "/reject",
                type: 'POST',
                success: function (resp) {
                    leaveTable.ajax.reload(null, false);
                    showSuccess(resp.message || 'Leave rejected.');
                },
                error: function (xhr) {
                    showError(xhr, 'Failed to reject leave.');
                }
            });
        });
    });

    /*
    |--------------------------------------------------------------------------
    | DELETE LEAVE
    |--------------------------------------------------------------------------
    | Controller button class:
    | delete-leave-record
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.delete-leave-record', function () {
        const id = $(this).data('id');

        confirmDelete('Delete leave?', 'This leave record will be deleted permanently.')
            .then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ url('admin/hrm/employees/leaves') }}/" + id,
                    type: 'DELETE',
                    success: function (resp) {
                        leaveTable.ajax.reload(null, false);
                        showSuccess(resp.message || 'Leave deleted successfully.');
                    },
                    error: function (xhr) {
                        showError(xhr, 'Failed to delete leave.');
                    }
                });
            });
    });

    /*
    |--------------------------------------------------------------------------
    | DELETE PAYROLL
    |--------------------------------------------------------------------------
    | Controller button class:
    | delete-payroll-record
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.delete-payroll-record', function () {
        const id = $(this).data('id');

        confirmDelete('Delete payroll?', 'This payroll record will be deleted permanently.')
            .then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ url('admin/hrm/employees/payrolls') }}/" + id,
                    type: 'DELETE',
                    success: function (resp) {
                        payrollTable.ajax.reload(null, false);
                        showSuccess(resp.message || 'Payroll deleted successfully.');
                    },
                    error: function (xhr) {
                        showError(xhr, 'Failed to delete payroll.');
                    }
                });
            });
    });

    /*
    |--------------------------------------------------------------------------
    | DELETE PERFORMANCE
    |--------------------------------------------------------------------------
    | Controller button class:
    | delete-performance-record
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.delete-performance-record', function () {
        const id = $(this).data('id');

        confirmDelete('Delete performance review?', 'This performance review will be deleted permanently.')
            .then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ url('admin/hrm/employees/performances') }}/" + id,
                    type: 'DELETE',
                    success: function (resp) {
                        performanceTable.ajax.reload(null, false);
                        showSuccess(resp.message || 'Performance deleted successfully.');
                    },
                    error: function (xhr) {
                        showError(xhr, 'Failed to delete performance record.');
                    }
                });
            });
    });
});
</script>
@endpush