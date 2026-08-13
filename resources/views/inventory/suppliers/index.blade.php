@extends('layouts.master')

@section('title', 'Manage Suppliers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">Suppliers <small class="text-muted">Inventory</small></h1>
        <button class="btn btn-primary" id="addSupplierBtn">
            <i class="fas fa-plus me-1"></i> Add Supplier
        </button>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3">
                        <i class="fas fa-people-carry"></i>
                    </div>
                    <div>
                        <h6>Total Suppliers</h6>
                        <h4 class="mb-0" id="totalSuppliers">{{ number_format($suppliers_count ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <button class="btn btn-danger btn-sm mb-3" id="bulkDeleteBtn" style="display:none;">
                <i class="fas fa-trash"></i> Delete Selected
            </button>
            <div class="table-responsive">
                <table class="table table-bordered w-100" id="supplierTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Currency</th>
                            <th>Payment Terms</th>
                            <th>Lead Time (days)</th>
                            <th>Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="supplierForm" class="modal-content">
            @csrf
            <input type="hidden" id="supplierId">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="supplierModalLabel">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Default Currency</label>
                    <input type="text" class="form-control" id="default_currency" maxlength="3" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Terms</label>
                    <input type="text" class="form-control" id="payment_terms">
                </div>
                <div class="mb-3">
                    <label class="form-label">Lead Time (days)</label>
                    <input type="number" class="form-control" id="lead_time_days" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Rating</label>
                    <input type="number" class="form-control" id="rating" step="0.1" min="0" max="5">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Supplier</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    let table = $('#supplierTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.suppliers.datatable') }}",
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'status' },
            { data: 'default_currency' },
            { data: 'payment_terms' },
            { data: 'lead_time_days' },
            { data: 'rating' },
            { data: 'action', orderable: false, searchable: false },
        ],
        drawCallback: function () {
            $.get("{{ route('admin.suppliers.metrics') }}", function (response) {
                $('#totalSuppliers').text(response.total);
            });
        }
    });

    $('#addSupplierBtn').click(function () {
        $('#supplierForm')[0].reset();
        $('#supplierId').val('');
        $('#supplierModalLabel').text('Add Supplier');
        $('#supplierModal').modal('show');
    });

    $('#supplierTable').on('click', '.edit', function () {
        let row = $(this).closest('tr');
        let data = table.row(row).data();

        $('#supplierId').val($(this).data('id'));
        $('#name').val($(this).data('name'));
        $('#status').val($(this).data('status'));
        $('#default_currency').val($(this).data('currency'));
        $('#payment_terms').val($(this).data('payment_terms'));
        $('#lead_time_days').val($(this).data('lead_time_days'));
        $('#rating').val($(this).data('rating'));

        $('#supplierModalLabel').text('Edit Supplier');
        $('#supplierModal').modal('show');
    });

    $('#supplierForm').on('submit', function (e) {
        e.preventDefault();
        let supplierId = $('#supplierId').val();
        let formData = {
            id: supplierId,
            name: $('#name').val(),
            status: $('#status').val(),
            default_currency: $('#default_currency').val(),
            payment_terms: $('#payment_terms').val(),
            lead_time_days: $('#lead_time_days').val(),
            rating: $('#rating').val(),
            _token: '{{ csrf_token() }}'
        };

        const url = supplierId ? `{{ url('admin/inventory/suppliers') }}/${supplierId}` : `{{ route('admin.suppliers.store') }}`;

        $.ajax({
            url: url,
            type: supplierId ? 'PUT' : 'POST',
            data: formData,
            success: function (response) {
                $('#supplierModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', response.message, 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save supplier.', 'error');
            }
        });
    });

    $('#supplierTable').on('click', '.delete', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/inventory/suppliers/') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    }
                });
            }
        });
    });

    $('#selectAll').on('change', function () {
        $('.supplier_checkbox').prop('checked', this.checked);
        $('#bulkDeleteBtn').toggle($('.supplier_checkbox:checked').length > 0);
    });

    $('#supplierTable tbody').on('change', '.supplier_checkbox', function () {
        $('#bulkDeleteBtn').toggle($('.supplier_checkbox:checked').length > 0);
    });

    $('#bulkDeleteBtn').on('click', function () {
        let ids = $('.supplier_checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (ids.length === 0) return;

        Swal.fire({
            title: 'Are you sure?',
            text: 'You are about to delete selected suppliers!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('admin.suppliers.bulk-delete') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: ids
                    },
                    success: function (response) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    }
                });
            }
        });
    });
});
</script>
@endpush
