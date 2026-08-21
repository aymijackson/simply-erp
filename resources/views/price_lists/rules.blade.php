@extends('layouts.master')
@section('title', 'Pricing Rules')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0"><i class="fas fa-percentage me-2"></i>Pricing Rules</h1>
            <small class="text-muted">Sales / Pricing Rules</small>
        </div>
        @can('sales.pricing_rules.create')
        <button class="btn btn-primary btn-sm" id="btnCreate">
            <i class="fas fa-plus me-1"></i> New Rule
        </button>
        @endcan
    </div>

    {{-- Filter --}}
    <div class="card shadow mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-sm-3">
                    <label class="form-label small mb-1">Applies On</label>
                    <select id="fApplyOn" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="all">All</option>
                        <option value="product">Product</option>
                        <option value="category">Category</option>
                        <option value="customer">Customer</option>
                        <option value="price_list">Price List</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Status</label>
                    <select id="fActive" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-sm-3 d-flex gap-2">
                    <button class="btn btn-primary btn-sm w-100" id="btnFilter">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <button class="btn btn-secondary btn-sm w-100" id="btnReset">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Pricing Rules</h6>
            <button class="btn btn-sm btn-danger d-none" id="btnBulkDelete">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover w-100" id="tblRules">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="chkAll"></th>
                            <th>Name</th>
                            <th>Applies On</th>
                            <th>Discount</th>
                            <th>Priority</th>
                            <th>Validity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ===================== CREATE / EDIT MODAL ===================== --}}
<div class="modal fade" id="modalRule" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">New Pricing Rule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmRule" novalidate>
                    @csrf
                    <input type="hidden" id="ruleId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="r_name" name="name" required maxlength="150">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Applies On <span class="text-danger">*</span></label>
                            <select class="form-select" id="r_apply_on" name="apply_on" required>
                                <option value="all">All</option>
                                <option value="product">Product</option>
                                <option value="category">Category</option>
                                <option value="customer">Customer</option>
                                <option value="price_list">Price List</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Target ID</label>
                            <input type="number" class="form-control" id="r_apply_to_id" name="apply_to_id" min="1">
                            <small class="text-muted">ID of the product/category/customer/price list (leave blank when Applies On is "All")</small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="r_discount_type" name="discount_type" required>
                                <option value="percent">Percent</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Discount Value <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="r_discount_value" name="discount_value" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Priority</label>
                            <input type="number" min="0" class="form-control" id="r_priority" name="priority" value="0">
                            <small class="text-muted">Lower number = evaluated first</small>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="r_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="r_is_active">Active</label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Min Order Qty</label>
                            <input type="number" step="0.0001" min="0" class="form-control" id="r_min_order_qty" name="min_order_qty">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Min Order Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="r_min_order_amount" name="min_order_amount">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Valid From</label>
                            <input type="date" class="form-control" id="r_valid_from" name="valid_from">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Valid To</label>
                            <input type="date" class="form-control" id="r_valid_to" name="valid_to">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSave">
                    <i class="fas fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');
    const URLS = {
        datatable  : '{{ route('admin.sales.pricing-rules.datatable') }}',
        store      : '{{ route('admin.sales.pricing-rules.store') }}',
        update     : (id) => `/admin/sales/pricing-rules/${id}`,
        destroy    : (id) => `/admin/sales/pricing-rules/${id}`,
        toggle     : (id) => `/admin/sales/pricing-rules/${id}/toggle`,
        bulkDelete : '{{ route('admin.sales.pricing-rules.bulk-delete') }}',
    };

    let table;

    function buildTable(extra = {}) {
        if (table) table.destroy();
        table = $('#tblRules').DataTable({
            processing: true, serverSide: true,
            ajax: { url: URLS.datatable, data: extra, dataSrc: 'data' },
            columns: [
                { data: null, orderable: false, searchable: false,
                  render: (_, __, r) => `<input type="checkbox" class="chk-row" value="${r.id}">` },
                { data: 'name' },
                { data: 'apply_on', render: v => `<span class="badge bg-info text-dark">${v.replace('_', ' ')}</span>` },
                { data: 'discount_display' },
                { data: 'priority' },
                { data: 'validity' },
                { data: 'status_badge', orderable: false },
                { data: 'actions', orderable: false, searchable: false },
            ],
            order: [[4, 'asc']],
            responsive: true,
        });
    }

    buildTable();

    $('#btnFilter').on('click', () => buildTable({
        apply_on:  $('#fApplyOn').val() || undefined,
        is_active: $('#fActive').val()  || undefined,
    }));
    $('#btnReset').on('click', () => {
        $('#fApplyOn, #fActive').val(''); buildTable();
    });

    // ── Modal ───────────────────────────────────────────────────────────────
    const $modal = new bootstrap.Modal(document.getElementById('modalRule'));

    function openCreate() {
        $('#frmRule')[0].reset();
        $('#ruleId').val('');
        $('#r_is_active').prop('checked', true);
        $('#modalTitle').text('New Pricing Rule');
        $modal.show();
    }

    function openEdit(record) {
        $('#ruleId').val(record.id);
        $('#r_name').val(record.name);
        $('#r_apply_on').val(record.apply_on);
        $('#r_apply_to_id').val(record.apply_to_id);
        $('#r_discount_type').val(record.discount_type);
        $('#r_discount_value').val(record.discount_value);
        $('#r_priority').val(record.priority);
        $('#r_is_active').prop('checked', !!record.is_active);
        $('#r_min_order_qty').val(record.min_order_qty);
        $('#r_min_order_amount').val(record.min_order_amount);
        $('#r_valid_from').val(record.valid_from ? record.valid_from.substring(0, 10) : '');
        $('#r_valid_to').val(record.valid_to ? record.valid_to.substring(0, 10) : '');
        $('#modalTitle').text('Edit Pricing Rule');
        $modal.show();
    }

    $('#btnCreate').on('click', openCreate);

    $('#btnSave').on('click', function () {
        if (!$('#frmRule')[0].checkValidity()) {
            $('#frmRule')[0].reportValidity(); return;
        }
        const id = $('#ruleId').val();
        const url = id ? URLS.update(id) : URLS.store;

        // Checkboxes don't serialize when unchecked - send is_active explicitly.
        const data = $('#frmRule').serializeArray();
        if (!$('#r_is_active').is(':checked')) data.push({ name: 'is_active', value: '0' });
        if (id) data.push({ name: '_method', value: 'PUT' });

        $.post(url, $.param(data))
            .done(() => {
                $modal.hide(); table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Saved', timer: 1500, showConfirmButton: false });
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error saving.');
                Swal.fire('Error', msg, 'error');
            });
    });

    // ── Actions (delegated) - datatable() already embeds the row as JSON ────
    $('#tblRules').on('click', '.btn-edit-rule', function () {
        openEdit(JSON.parse($(this).attr('data-record')));
    });

    $('#tblRules').on('click', '.btn-toggle-rule', function () {
        const id = $(this).data('id');
        $.post(URLS.toggle(id), { _token: CSRF })
            .done(() => table.ajax.reload())
            .fail(() => Swal.fire('Error', 'Could not update status.', 'error'));
    });

    $('#tblRules').on('click', '.btn-delete-rule', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Delete this rule?', icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#e74a3b',
                    confirmButtonText: 'Delete' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.destroy(id), { _token: CSRF, _method: 'DELETE' })
                    .done(() => table.ajax.reload())
                    .fail(() => Swal.fire('Error', 'Could not delete.', 'error'));
            });
    });

    // ── Bulk ────────────────────────────────────────────────────────────────
    $('#chkAll').on('change', function () {
        $('.chk-row').prop('checked', this.checked);
        $('#btnBulkDelete').toggleClass('d-none', !this.checked);
    });
    $('#tblRules').on('change', '.chk-row', function () {
        $('#btnBulkDelete').toggleClass('d-none', !$('.chk-row:checked').length);
    });
    $('#btnBulkDelete').on('click', function () {
        const ids = $('.chk-row:checked').map(function () { return $(this).val(); }).get();
        Swal.fire({ title: `Delete ${ids.length} rule(s)?`, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#e74a3b' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.bulkDelete, { _token: CSRF, ids })
                    .done(() => { $('#chkAll').prop('checked', false); table.ajax.reload(); })
                    .fail(() => Swal.fire('Error', 'Bulk delete failed.', 'error'));
            });
    });
})();
</script>
@endpush
