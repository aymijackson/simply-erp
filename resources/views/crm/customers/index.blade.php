@extends('layouts.master')

@section('title', 'Customers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Customers</h1>
        <button class="btn btn-primary" id="addCustomerBtn">
            <i class="fas fa-plus me-1"></i> Add Customer
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="customerTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Company</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button class="btn btn-danger mt-2" id="bulkDeleteBtn">Delete Selected</button>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="customerForm">
            @csrf
            <input type="hidden" name="id" id="customer_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="customerModalLabel">Add Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Company</label>
                    <select name="company_id" id="company_id" class="form-control" required>
                        <option value="">-- Select Company --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
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
    const modal = new bootstrap.Modal(document.getElementById('customerModal'));
    const table = $('#customerTable').DataTable({
        ajax: '{{ route('admin.customers.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'id' },
            { data: 'name' },
            { data: 'email' },
            { data: 'phone' },
            { data: 'company' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#addCustomerBtn').click(function () {
        $('#customerForm')[0].reset();
        $('#customer_id').val('');
        $('#customerModalLabel').text('Add Customer');
        modal.show();
    });

    $('#customerForm').submit(function (e) {
        e.preventDefault();
        const id = $('#customer_id').val();
        const url = id ? `{{ url('admin/customers') }}/${id}` : `{{ route('admin.customers.store') }}`;
        const method = id ? 'PUT' : 'POST';
        const formData = $(this).serialize();

        $.ajax({
            url,
            method: 'POST',
            data: formData + (id ? '&_method=PUT' : ''),
            success: res => {
                modal.hide();
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: err => {
                Swal.fire('Error', 'Something went wrong.', 'error');
            }
        });
    });

    $('#customerTable').on('click', '.edit-customer', function () {
        const record = $(this).data('record');
        $('#customer_id').val(record.id);
        $('#name').val(record.name);
        $('#email').val(record.email);
        $('#phone').val(record.phone);
        $('#company_id').val(record.company_id);
        $('#customerModalLabel').text('Edit Customer');
        modal.show();
    });

    $('#customerTable').on('click', '.delete-customer', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.post(`{{ url('admin/customers') }}/${id}`, { _token: '{{ csrf_token() }}', _method: 'DELETE' })
                    .done(res => {
                        table.ajax.reload();
                        Swal.fire('Deleted!', res.message, 'success');
                    });
            }
        });
    });

    $('#bulkDeleteBtn').click(function () {
        const ids = [];
        $('.row-checkbox:checked').each(function () {
            ids.push($(this).val());
        });
        if (ids.length === 0) return Swal.fire('No selection', 'Select at least one record', 'info');

        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete selected'
        }).then(result => {
            if (result.isConfirmed) {
                $.post(`{{ route('admin.customers.bulk-delete') }}`, { ids, _token: '{{ csrf_token() }}' })
                    .done(res => {
                        table.ajax.reload();
                        Swal.fire('Deleted!', res.message, 'success');
                    });
            }
        });
    });

    $('#selectAll').on('click', function () {
        $('.row-checkbox').prop('checked', this.checked);
    });
});
</script>
@endpush
