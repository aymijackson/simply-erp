@extends('layouts.master')

@section('title', 'Manage Suppliers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">Suppliers <small class="text-muted">Vendors</small></h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success" id="exportCsvBtn">
                <i class="fas fa-file-csv me-1"></i> Export CSV
            </button>
            <button class="btn btn-outline-danger" id="exportPdfBtn">
                <i class="fas fa-file-pdf me-1"></i> Export PDF
            </button>
            <button class="btn btn-primary" id="addSupplierBtn">
                <i class="fas fa-plus me-1"></i> Add Supplier
            </button>
        </div>
    </div>

    {{-- Metrics --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3" style="width:42px;height:42px;line-height:42px;">
                        <i class="fas fa-people-carry"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Total Suppliers</h6>
                        <h4 class="mb-0" id="totalSuppliers">{{ number_format($suppliers_count ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select class="form-control" id="f_status">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Currency</label>
                    <input type="text" class="form-control" id="f_currency" maxlength="3" placeholder="e.g. NGN">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Rating (min)</label>
                    <input type="number" class="form-control" id="f_rating_min" step="0.1" min="0" max="5" placeholder="0">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Rating (max)</label>
                    <input type="number" class="form-control" id="f_rating_max" step="0.1" min="0" max="5" placeholder="5">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Lead Time (min)</label>
                    <input type="number" class="form-control" id="f_lead_min" min="0" placeholder="0">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Lead Time (max)</label>
                    <input type="number" class="form-control" id="f_lead_max" min="0" placeholder="999">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Created From</label>
                    <input type="date" class="form-control" id="f_date_from">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Created To</label>
                    <input type="date" class="form-control" id="f_date_to">
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Quick Search</label>
                    <input type="text" class="form-control" id="f_search" placeholder="Search name, terms...">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" id="applyFiltersBtn">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <button class="btn btn-outline-secondary w-100" id="resetFiltersBtn">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>

            <small class="text-muted d-block mt-2">
                Exports use the same filters as the table.
            </small>
        </div>
    </div>

    {{-- Table --}}
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
                            <th>Created</th>
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

    function getFilterParams(){
        return {
            status: ($('#f_status').val() || ''),
            currency: ($('#f_currency').val() || '').trim(),
            rating_min: ($('#f_rating_min').val() || ''),
            rating_max: ($('#f_rating_max').val() || ''),
            lead_min: ($('#f_lead_min').val() || ''),
            lead_max: ($('#f_lead_max').val() || ''),
            date_from: ($('#f_date_from').val() || ''),
            date_to: ($('#f_date_to').val() || ''),
            quick_search: ($('#f_search').val() || '').trim(),
        };
    }

    function buildQueryString(params){
        const usp = new URLSearchParams();
        Object.keys(params).forEach(k => {
            if (params[k] !== null && params[k] !== undefined && params[k] !== '') usp.append(k, params[k]);
        });
        return usp.toString();
    }

    let table = $('#supplierTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.suppliers.datatable') }}",
            data: function(d){
                // send filters to datatable endpoint
                Object.assign(d, getFilterParams());
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'status' },
            { data: 'default_currency' },
            { data: 'payment_terms' },
            { data: 'lead_time_days' },
            { data: 'rating' },
            { data: 'created_at' }, // make sure backend returns formatted date
            { data: 'action', orderable: false, searchable: false },
        ],
        order: [[7, 'desc']],
        drawCallback: function () {
            $.get("{{ route('admin.suppliers.metrics') }}", getFilterParams(), function (response) {
                // optional: let metrics reflect filters, or keep it total-only (your choice in backend)
                $('#totalSuppliers').text(response.total);
            });
        }
    });

    // Apply filters
    $('#applyFiltersBtn').on('click', function(){
        table.ajax.reload();
    });

    // Press Enter in search triggers apply
    $('#f_search').on('keydown', function(e){
        if(e.key === 'Enter'){
            e.preventDefault();
            table.ajax.reload();
        }
    });

    // Reset filters
    $('#resetFiltersBtn').on('click', function(){
        $('#f_status').val('');
        $('#f_currency').val('');
        $('#f_rating_min').val('');
        $('#f_rating_max').val('');
        $('#f_lead_min').val('');
        $('#f_lead_max').val('');
        $('#f_date_from').val('');
        $('#f_date_to').val('');
        $('#f_search').val('');
        table.ajax.reload();
    });

    // Export CSV/PDF using same filters
    $('#exportCsvBtn').on('click', function(){
        const qs = buildQueryString(getFilterParams());
        const url = "{{ route('admin.suppliers.export.csv') }}" + (qs ? ('?' + qs) : '');
        window.location.href = url;
    });

    $('#exportPdfBtn').on('click', function(){
        const qs = buildQueryString(getFilterParams());
        const url = "{{ route('admin.suppliers.export.pdf') }}" + (qs ? ('?' + qs) : '');
        window.location.href = url;
    });

    // Add Supplier
    $('#addSupplierBtn').click(function () {
        $('#supplierForm')[0].reset();
        $('#supplierId').val('');
        $('#supplierModalLabel').text('Add Supplier');
        $('#supplierModal').modal('show');
    });

    // Edit Supplier (uses data-* attributes from your action button)
    $('#supplierTable').on('click', '.edit', function () {
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

    // Save (Add/Update)
    $('#supplierForm').on('submit', function (e) {
        e.preventDefault();
        let supplierId = $('#supplierId').val();

        let formData = {
            name: $('#name').val(),
            status: $('#status').val(),
            default_currency: $('#default_currency').val(),
            payment_terms: $('#payment_terms').val(),
            lead_time_days: $('#lead_time_days').val(),
            rating: $('#rating').val(),
            _token: '{{ csrf_token() }}'
        };

        const url = supplierId
            ? `{{ url('admin/suppliers') }}/${supplierId}`
            : `{{ route('admin.suppliers.store') }}`;

        $.ajax({
            url: url,
            type: supplierId ? 'PUT' : 'POST',
            data: formData,
            success: function (response) {
                $('#supplierModal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire('Success', response.message, 'success');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Failed to save supplier.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Delete single
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
                    url: `{{ url('admin/suppliers') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', response.message, 'success');
                    },
                    error: function(){
                        Swal.fire('Error', 'Failed to delete supplier.', 'error');
                    }
                });
            }
        });
    });

    // Bulk select
    $('#selectAll').on('change', function () {
        $('.supplier_checkbox').prop('checked', this.checked);
        $('#bulkDeleteBtn').toggle($('.supplier_checkbox:checked').length > 0);
    });

    $('#supplierTable tbody').on('change', '.supplier_checkbox', function () {
        $('#bulkDeleteBtn').toggle($('.supplier_checkbox:checked').length > 0);
    });

    // Bulk delete
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
                    data: { _token: '{{ csrf_token() }}', ids: ids },
                    success: function (response) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', response.message, 'success');
                        $('#bulkDeleteBtn').hide();
                        $('#selectAll').prop('checked', false);
                    },
                    error: function(){
                        Swal.fire('Error', 'Failed to delete selected suppliers.', 'error');
                    }
                });
            }
        });
    });

});
</script>
@endpush
