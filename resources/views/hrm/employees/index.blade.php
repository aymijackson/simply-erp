@extends('layouts.master')

@section('title', 'Manage Employees')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Employees</h1>
        <div>
            <button class="btn btn-primary" id="createEmployeeBtn">
                <i class="fas fa-plus me-1"></i> Add Employee
            </button>
            <button class="btn btn-danger d-none" id="bulkDeleteBtn">
                <i class="fas fa-trash-alt"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="employeesTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Employee Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Company</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="employeeForm">
            @csrf
            <input type="hidden" name="id" id="employee_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="employeeModalLabel">Add Employee</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label>Employee Code</label>
                    <input type="text" class="form-control" id="employee_code" name="employee_code" required>
                </div>
                <div class="col-md-6">
                    <label>Company</label>
                    <select class="form-control" id="company_id" name="company_id" required>
                        <option value="">-- Select Company --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Department</label>
                    <select class="form-control" id="department_id" name="department_id" required>
                        <option value="">-- Select Department --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label>First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                </div>
                <div class="col-md-6">
                    <label>Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                </div>
                <div class="col-md-6">
                    <label>Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="col-md-6">
                    <label>Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
                <div class="col-md-6">
                    <label>Position</label>
                    <input type="text" class="form-control" id="position" name="position">
                </div>
                <div class="col-md-6">
                    <label>Date of Birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                </div>
                <div class="col-md-6">
                    <label>Date Hired</label>
                    <input type="date" class="form-control" id="date_hired" name="date_hired">
                </div>
                <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password">
                        <button class="btn btn-outline-secondary" type="button" id="regenBtn">
                            🔄 Regenerate
                        </button>
                    </div>
                    <small id="pwdHint" class="form-text text-muted"></small>
                </div>
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
    const modal = new bootstrap.Modal(document.getElementById('employeeModal'));

    const table = $('#employeesTable').DataTable({
        responsive: true,
        processing: true,
        ajax: '{{ route('admin.hrm.employees.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'employee_code' },
            { data: null, render: d => `${d.first_name} ${d.last_name}` },
            { data: 'email' },
            { data: 'company' },
            { data: 'department' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#createEmployeeBtn').on('click', function () {
        $('#employeeForm')[0].reset();
        $('#employee_id').val('');
        $('#employeeModalLabel').text('Add Employee');
        modal.show();
    });

    $('#employeeForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#employee_id').val();
        const url = id ? `/admin/hrm/employees/${id}` : `{{ route('admin.hrm.employees.store') }}`;
        const method = id ? 'POST' : 'POST';
        const formData = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function (res) {
                table.ajax.reload(null, false);
                modal.hide();
                Swal.fire('Success', res.message, 'success');
            },
            error: function (xhr) {
                Swal.fire('Error', 'Failed to save employee.', 'error');
            }
        });
    });

    $('#employeesTable').on('click', '.edit-employee', function () {
        const btn = $(this);
        $('#employee_id').val(btn.data('id'));
        $('#employee_code').val(btn.data('employee_code'));
        $('#first_name').val(btn.data('first_name'));
        $('#last_name').val(btn.data('last_name'));
        $('#email').val(btn.data('email'));
        $('#phone').val(btn.data('phone'));
        $('#position').val(btn.data('position'));
        $('#date_of_birth').val(btn.data('date_of_birth'));
        $('#date_hired').val(btn.data('date_hired'));
        $('#company_id').val(btn.data('company_id'));
        $('#department_id').val(btn.data('department_id'));
        $('#employeeModalLabel').text('Edit Employee');
        modal.show();
    });

    $('#employeesTable').on('click', '.delete-employee', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete this employee?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete!',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/hrm/employees/${id}`,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to delete employee.', 'error');
                    }
                });
            }
        });
    });

    $('#selectAll').on('change', function () {
        $('.row-checkbox').prop('checked', this.checked);
        toggleBulkDelete();
    });

    $('#employeesTable tbody').on('change', '.row-checkbox', function () {
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
                title: 'Delete selected employees?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete them!',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('admin.hrm.employees.bulk-delete') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE',
                            ids: ids
                        },
                        success: function (res) {
                            table.ajax.reload(null, false);
                            $('#bulkDeleteBtn').addClass('d-none');
                            Swal.fire('Deleted!', res.message, 'success');
                        },
                        error: function () {
                            Swal.fire('Error', 'Bulk deletion failed.', 'error');
                        }
                    });
                }
            });
        }
    });

    /**
     * Generates a cryptographically‑strong random password.
     * length  – desired password length
     * charset – string of allowed chars
     */
    function generatePassword(length = 16) {
        const charset =
        'ABCDEFGHJKLMNPQRSTUVWXYZ' + // dropped I & O to avoid confusion
        'abcdefghijkmnopqrstuvwxyz' + // dropped l
        '0123456789' +
        '!@#$%^&*()-_=+[]{}';
        const randomValues = new Uint32Array(length);
        window.crypto.getRandomValues(randomValues);

        return Array.from(randomValues, v => charset[v % charset.length]).join('');
    }

    function applyNewPassword() {
        const pwd = generatePassword();
        const pwdInput = document.getElementById('password');
        const pwdHint  = document.getElementById('pwdHint');

        pwdInput.value = pwd;
        pwdHint.textContent = `Generated password: ${pwd}`;
    }

    // initialise on page load
    document.addEventListener('DOMContentLoaded', applyNewPassword);

    // wire up the button
    document.getElementById('regenBtn')
            .addEventListener('click', applyNewPassword);
});
</script>
@endpush
