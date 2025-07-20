@extends('layouts.master')

@section('title', 'Attendance Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Employee Attendance</h1>
        <button class="btn btn-primary" id="addAttendanceBtn">
            <i class="fas fa-plus me-1"></i> Add Attendance
        </button>
        <button class="btn btn-danger d-none" id="bulkDeleteBtn">
            <i class="fas fa-trash-alt"></i> Delete Selected
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="attendanceTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Note</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="attendanceForm">
            @csrf
            <input type="hidden" id="attendance_id" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="attendanceModalLabel">Add Attendance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select class="form-control" name="employee_id" id="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" id="date" required>
                </div>
                <div class="mb-3">
                    <label for="clock_in" class="form-label">Clock In</label>
                    <input type="time" class="form-control" name="clock_in" id="clock_in" step="60" required>
                </div>
                <div class="mb-3">
                    <label for="clock_out" class="form-label">Clock Out</label>
                    <input type="time" class="form-control" name="clock_out" id="clock_out" step="60" required>
                </div>
                <div class="mb-3">
                    <label for="note" class="form-label">Note</label>
                    <textarea class="form-control" name="note" id="note"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));

    const table = $('#attendanceTable').DataTable({
        ajax: '{{ route('admin.hrm.employees.attendance.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'id' },
            { data: 'employee' },
            { data: 'date' },
            { data: 'clock_in' },
            { data: 'clock_out' },
            { data: 'note' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#addAttendanceBtn').on('click', function () {
        $('#attendanceForm')[0].reset();
        $('#attendance_id').val('');
        $('#attendanceModalLabel').text('Add Attendance');
        modal.show();
    });

    $('#attendanceForm').submit(function (e) {
        e.preventDefault();
        const id = $('#attendance_id').val();
        const method = id ? 'PUT' : 'POST';
        const url = id ? `/admin/hrm/employees/attendances/${id}` : `{{ route('admin.hrm.employees.attendance.store') }}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: $(this).serialize() + (id ? '&_method=PUT' : ''),
            success: function (res) {
                table.ajax.reload(null, false);
                modal.hide();
                Swal.fire('Success', res.message, 'success');
            },
            error: function (xhr) {
                let msg = xhr.responseJSON?.message || 'Validation failed';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    $('#attendanceTable').on('click', '.edit-attendance', function () {
        const btn = $(this);
        $('#attendance_id').val(btn.data('id'));
        $('#employee_id').val(btn.data('employee_id'));
        $('#date').val(btn.data('date'));
        $('#clock_in').val(btn.data('clock_in'));
        $('#clock_out').val(btn.data('clock_out'));
        $('#note').val(btn.data('note'));
        $('#attendanceModalLabel').text('Edit Attendance');
        modal.show();
    });

    $('#attendanceTable').on('click', '.delete-attendance', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete this attendance?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/hrm/employees/attendances/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to delete attendance.', 'error');
                    }
                });
            }
        });
    });

    $('#selectAll').on('click', function () {
        $('.row-checkbox').prop('checked', this.checked);
        toggleBulkDelete();
    });

    $('#attendanceTable tbody').on('change', '.row-checkbox', function () {
        toggleBulkDelete();
    });

    function toggleBulkDelete() {
        const anyChecked = $('.row-checkbox:checked').length > 0;
        $('#bulkDeleteBtn').toggleClass('d-none', !anyChecked);
    }

    $('#bulkDeleteBtn').on('click', function () {
        const ids = $('.row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (ids.length > 0) {
            Swal.fire({
                title: 'Delete Selected?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete them!',
                confirmButtonColor: '#d33'
            }).then(result => {
                if (result.isConfirmed) {
                    $.post(`{{ route('admin.hrm.employees.attendance.bulk-delete') }}`, {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE',
                        ids: ids
                    }, function (res) {
                        table.ajax.reload(null, false);
                        $('#bulkDeleteBtn').addClass('d-none');
                        Swal.fire('Deleted!', res.message, 'success');
                    }).fail(() => Swal.fire('Error', 'Bulk deletion failed.', 'error'));
                }
            });
        }
    });
});
</script>
@endpush
