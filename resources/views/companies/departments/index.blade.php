@extends('layouts.master')

@section('title', 'Manage Departments')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Departments</h1>
        <button class="btn btn-primary" id="createDepartment">
            <i class="fas fa-plus me-1"></i> Add Department
        </button>
        <button class="btn btn-danger d-none" id="bulkDeleteBtn">
            <i class="fas fa-trash-alt"></i> Delete Selected
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="departmentsTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Company</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="departmentForm">
            @csrf
            <input type="hidden" id="department_id" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="departmentModalLabel">Add Department</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control" id="code" name="code">
                </div>
                <div class="mb-3">
                    <label for="company_id" class="form-label">Company</label>
                    <select class="form-control" id="company_id" name="company_id" required>
                        <option value="">-- Select Company --</option>
                        @foreach(App\Models\Company::all() as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description"></textarea>
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
    const modal = new bootstrap.Modal(document.getElementById('departmentModal'));

    const table = $('#departmentsTable').DataTable({
        ajax: '{{ route('admin.companies.departments.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'code' },
            { data: 'company_name' },
            { data: 'actions', orderable: false, searchable: false },
        ]
    });

    $('#createDepartment').on('click', function () {
        $('#departmentForm')[0].reset();
        $('#department_id').val('');
        $('#departmentModalLabel').text('Add Department');
        modal.show();
    });

    $('#departmentForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#department_id').val();
        const url = id ? `/admin/companies/departments/${id}` : `{{ route('admin.companies.departments.store') }}`;
        const method = id ? 'POST' : 'POST';
        const data = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.ajax({
            url: url,
            method: method,
            data: data,
            success: function (res) {
                table.ajax.reload(null, false);
                modal.hide();
                Swal.fire('Success', res.message, 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save department.', 'error');
            }
        });
    });

    $('#departmentsTable').on('click', '.edit-department', function () {
    const btn = $(this);
    $('#department_id').val(btn.data('id'));
    $('#name').val(btn.data('name'));
    $('#code').val(btn.data('code'));
    $('#company_id').val(btn.data('company_id'));
    $('#description').val(btn.data('description'));
    $('#departmentModalLabel').text('Edit Department');
    modal.show();
});
    $('#departmentsTable').on('click', '.delete-department', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete this department?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) { 
                $.ajax({
                    url: `/admin/companies/departments/${id}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to delete department.', 'error');
                    }
                });
            }
        });
    });

    $('#selectAll').on('change', function () {
        $('.row-checkbox').prop('checked', this.checked);
        toggleBulkDelete();
    });

    $('#departmentsTable tbody').on('change', '.row-checkbox', function () {
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
                    $.ajax({
                        url: '{{ route('admin.companies.departments.bulk-delete') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
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
});
</script>
@endpush
