@extends('layouts.master')

@section('title', 'Customers')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary mb-0">
            <i class="fas fa-user-tag me-2"></i> Customers
        </h1>
        @can('core.master_data.customers.create')
        <button class="btn btn-primary" id="addCustomerBtn">
            <i class="fas fa-plus me-1"></i> Add Customer
        </button>
        @endcan
    </div>

    {{-- Table Card --}}
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="customerTable">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Credit Terms</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <button class="btn btn-danger btn-sm mt-2" id="bulkDeleteBtn">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
        </div>
    </div>
</div>

{{-- ===================== CREATE / EDIT MODAL ===================== --}}
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="customerModalLabel">Add Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="customerForm" novalidate>
                    @csrf
                    <input type="hidden" id="customer_id">

                    {{-- ── Section: Basic Info ───────────────────────────── --}}
                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-1">
                        <i class="fas fa-user me-1"></i> Basic Information
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company <span class="text-danger">*</span></label>
                            <select class="form-select" id="company_id" name="company_id" required>
                                <option value="">-- Select Company --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Position / Title</label>
                            <input type="text" class="form-control" id="position" name="position">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Website</label>
                            <input type="url" class="form-control" id="website" name="website"
                                   placeholder="https://example.com">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Currency</label>
                            <input type="text" class="form-control text-uppercase" id="currency_code"
                                   name="currency_code" maxlength="3" placeholder="USD">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                    </div>

                    {{-- ── Section: Credit & Finance ─────────────────────── --}}
                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-1">
                        <i class="fas fa-credit-card me-1"></i> Credit & Finance
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tax ID / VAT No.</label>
                            <input type="text" class="form-control" id="tax_id" name="tax_id">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Credit Limit</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <input type="number" class="form-control" id="credit_limit"
                                       name="credit_limit" min="0" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Payment Terms (days)</label>
                            <input type="number" class="form-control" id="credit_terms_days"
                                   name="credit_terms_days" min="0" value="0"
                                   placeholder="e.g. 30 for Net 30">
                        </div>
                    </div>

                    {{-- ── Section: Notes ───────────────────────────────── --}}
                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-1">
                        <i class="fas fa-sticky-note me-1"></i> Notes
                    </h6>
                    <div class="mb-3">
                        <textarea class="form-control" id="internal_notes" name="internal_notes" rows="3"
                                  placeholder="Internal notes about this customer..."></textarea>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveCustomer">
                    <i class="fas fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    const CSRF  = $('meta[name="csrf-token"]').attr('content');
    const modal = new bootstrap.Modal(document.getElementById('customerModal'));

    // ── DataTable ──────────────────────────────────────────────────────────
    const table = $('#customerTable').DataTable({
        processing : true,
        serverSide : true,
        ajax       : '{{ route('admin.customers.datatable') }}',
        columns    : [
            { data: 'checkbox',     orderable: false, searchable: false },
            { data: 'name' },
            { data: 'email' },
            { data: 'phone' },
            { data: 'company' },
            { data: 'credit_terms' },
            { data: 'status_badge', orderable: false },
            { data: 'actions',      orderable: false, searchable: false },
        ],
    });

    // ── Select all ─────────────────────────────────────────────────────────
    $('#selectAll').on('change', function () {
        $('.row-checkbox').prop('checked', this.checked);
    });

    // ── Open create modal ──────────────────────────────────────────────────
    $('#addCustomerBtn').on('click', function () {
        $('#customerForm')[0].reset();
        $('#customer_id').val('');
        $('#customerModalLabel').text('Add Customer');
        $('#credit_limit').val(0);
        $('#credit_terms_days').val(0);
        $('#status').val('active');
        modal.show();
    });

    // ── Open edit modal ────────────────────────────────────────────────────
    $('#customerTable').on('click', '.btn-edit-customer', function () {
        const r = $(this).data('record');
        $('#customer_id').val(r.id);
        $('#name').val(r.name);
        $('#email').val(r.email);
        $('#phone').val(r.phone);
        $('#position').val(r.position);
        $('#company_id').val(r.company_id);
        $('#address').val(r.address);
        $('#tax_id').val(r.tax_id);
        $('#credit_limit').val(r.credit_limit ?? 0);
        $('#credit_terms_days').val(r.credit_terms_days ?? 0);
        $('#currency_code').val(r.currency_code);
        $('#website').val(r.website);
        $('#internal_notes').val(r.internal_notes);
        $('#status').val(r.status ?? 'active');
        $('#customerModalLabel').text('Edit Customer');
        modal.show();
    });

    // ── Save (create / update) ─────────────────────────────────────────────
    $('#btnSaveCustomer').on('click', function () {
        const id     = $('#customer_id').val();
        const url    = id ? `/admin/customers/${id}` : '{{ route('admin.customers.store') }}';
        const data   = $('#customerForm').serialize()
                     + (id ? '&_method=PUT' : '');

        $.post(url, data)
            .done(res => {
                modal.hide();
                table.ajax.reload();
                Swal.fire({ icon:'success', title:'Saved', text: res.message,
                            timer:1800, showConfirmButton:false });
            })
            .fail(xhr => {
                const errors = xhr.responseJSON?.errors;
                const msg    = errors
                    ? Object.values(errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Something went wrong.');
                Swal.fire('Error', msg, 'error');
            });
    });

    // ── Delete ─────────────────────────────────────────────────────────────
    $('#customerTable').on('click', '.btn-delete-customer', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete this customer?', icon:'warning',
            showCancelButton: true, confirmButtonColor:'#e74a3b',
            confirmButtonText:'Yes, delete',
        }).then(r => {
            if (!r.isConfirmed) return;
            $.post(`/admin/customers/${id}`, { _token:CSRF, _method:'DELETE' })
                .done(res => {
                    table.ajax.reload();
                    Swal.fire({ icon:'success', title:'Deleted',
                                timer:1500, showConfirmButton:false });
                });
        });
    });

    // ── Bulk delete ────────────────────────────────────────────────────────
    $('#bulkDeleteBtn').on('click', function () {
        const ids = $('.row-checkbox:checked').map(function(){ return $(this).val(); }).get();
        if (!ids.length) return Swal.fire('No selection','Select at least one record','info');

        Swal.fire({
            title:`Delete ${ids.length} customer(s)?`, icon:'warning',
            showCancelButton:true, confirmButtonColor:'#e74a3b',
            confirmButtonText:'Yes, delete',
        }).then(r => {
            if (!r.isConfirmed) return;
            $.post('{{ route('admin.customers.bulk-delete') }}', { _token:CSRF, ids })
                .done(res => {
                    table.ajax.reload();
                    Swal.fire({ icon:'success', title:'Deleted', text:res.message,
                                timer:1500, showConfirmButton:false });
                });
        });
    });
});
</script>
@endpush