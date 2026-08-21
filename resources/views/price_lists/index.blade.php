@extends('layouts.master')
@section('title', 'Price Lists')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0"><i class="fas fa-tags me-2"></i>Price Lists</h1>
            <small class="text-muted">Sales / Price Lists</small>
        </div>
        @can('sales.price_lists.create')
        <button class="btn btn-primary btn-sm" id="btnCreate">
            <i class="fas fa-plus me-1"></i> New Price List
        </button>
        @endcan
    </div>

    {{-- Filter --}}
    <div class="card shadow mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-sm-3">
                    <label class="form-label small mb-1">Type</label>
                    <select id="fType" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="sale">Sale</option>
                        <option value="purchase">Purchase</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Currency</label>
                    <input type="text" id="fCurrency" class="form-control form-control-sm text-uppercase" maxlength="3" placeholder="USD">
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
            <h6 class="m-0 font-weight-bold text-primary">Price Lists</h6>
            <button class="btn btn-sm btn-danger d-none" id="btnBulkDelete">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover w-100" id="tblPriceLists">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="chkAll"></th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Currency</th>
                            <th>Items</th>
                            <th>Validity</th>
                            <th>Default</th>
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
<div class="modal fade" id="modalPriceList" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">New Price List</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmPriceList" novalidate>
                    @csrf
                    <input type="hidden" id="plId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pl_name" name="name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Code</label>
                            <input type="text" class="form-control text-uppercase" id="pl_code" name="code">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Currency <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="pl_currency" name="currency_code" maxlength="3" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="pl_type" name="type" required>
                                <option value="sale">Sale</option>
                                <option value="purchase">Purchase</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Valid From</label>
                            <input type="date" class="form-control" id="pl_valid_from" name="valid_from">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Valid To</label>
                            <input type="date" class="form-control" id="pl_valid_to" name="valid_to">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="pl_is_default" name="is_default" value="1">
                                <label class="form-check-label" for="pl_is_default">Default</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="pl_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="pl_is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="pl_notes" name="notes" rows="2"></textarea>
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
        datatable  : '{{ route('admin.sales.price-lists.datatable') }}',
        store      : '{{ route('admin.sales.price-lists.store') }}',
        update     : (id) => `/admin/sales/price-lists/${id}`,
        destroy    : (id) => `/admin/sales/price-lists/${id}`,
        bulkDelete : '{{ route('admin.sales.price-lists.bulk-delete') }}',
    };

    let table;

    function buildTable(extra = {}) {
        if (table) table.destroy();
        table = $('#tblPriceLists').DataTable({
            processing: true, serverSide: true,
            ajax: { url: URLS.datatable, data: extra, dataSrc: 'data' },
            columns: [
                { data: null, orderable: false, searchable: false,
                  render: (_, __, r) => `<input type="checkbox" class="chk-row" value="${r.id}">` },
                { data: 'name',
                  render: (v, _, r) => `<a href="/admin/sales/price-lists/${r.id}" class="fw-semibold">${v}</a>` },
                { data: 'code', defaultContent: '-' },
                { data: 'type', render: v => `<span class="badge bg-info text-dark">${v}</span>` },
                { data: 'currency_code' },
                { data: 'items_count' },
                { data: 'validity' },
                { data: 'default_badge', orderable: false },
                { data: 'status_badge',  orderable: false },
                { data: 'actions',       orderable: false, searchable: false },
            ],
            order: [[1, 'asc']],
            responsive: true,
        });
    }

    buildTable();

    $('#btnFilter').on('click', () => buildTable({
        type:      $('#fType').val()     || undefined,
        currency:  $('#fCurrency').val() || undefined,
        is_active: $('#fActive').val()   || undefined,
    }));
    $('#btnReset').on('click', () => {
        $('#fType, #fActive').val(''); $('#fCurrency').val(''); buildTable();
    });

    // ── Modal ───────────────────────────────────────────────────────────────
    const $modal   = new bootstrap.Modal(document.getElementById('modalPriceList'));

    function openCreate() {
        $('#frmPriceList')[0].reset();
        $('#plId').val('');
        $('#pl_is_active').prop('checked', true);
        $('#pl_is_default').prop('checked', false);
        $('#modalTitle').text('New Price List');
        $modal.show();
    }

    function openEdit(r) {
        $('#plId').val(r.id);
        $('#pl_name').val(r.name);
        $('#pl_code').val(r.code);
        $('#pl_currency').val(r.currency_code);
        $('#pl_type').val(r.type);
        $('#pl_valid_from').val(r.valid_from ? r.valid_from.substring(0,10) : '');
        $('#pl_valid_to').val(r.valid_to   ? r.valid_to.substring(0,10)   : '');
        $('#pl_is_default').prop('checked', !!r.is_default);
        $('#pl_is_active').prop('checked',  !!r.is_active);
        $('#pl_notes').val(r.notes);
        $('#modalTitle').text('Edit Price List');
        $modal.show();
    }

    $('#btnCreate').on('click', openCreate);

    $('#btnSave').on('click', function () {
        if (!$('#frmPriceList')[0].checkValidity()) {
            $('#frmPriceList')[0].reportValidity(); return;
        }
        const id   = $('#plId').val();
        const url  = id ? URLS.update(id) : URLS.store;
        const data = $('#frmPriceList').serialize() + (id ? '&_method=PUT' : '');
        $.post(url, data)
            .done(() => { $modal.hide(); table.ajax.reload();
                Swal.fire({ icon:'success', title:'Saved', timer:1500, showConfirmButton:false }); })
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error saving.');
                Swal.fire('Error', msg, 'error');
            });
    });

    // ── Actions (delegated) ─────────────────────────────────────────────────
    $('#tblPriceLists').on('click', '.btn-edit-pl', function () { openEdit($(this).data('record')); });

    $('#tblPriceLists').on('click', '.btn-delete-pl', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete price list?', icon:'warning',
                    showCancelButton:true, confirmButtonColor:'#e74a3b',
                    confirmButtonText:'Delete' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.destroy(id), { _token:CSRF, _method:'DELETE' })
                    .done(() => table.ajax.reload())
                    .fail(() => Swal.fire('Error','Could not delete.','error'));
            });
    });

    // ── Bulk ────────────────────────────────────────────────────────────────
    $('#chkAll').on('change', function () {
        $('.chk-row').prop('checked', this.checked);
        $('#btnBulkDelete').toggleClass('d-none', !this.checked);
    });
    $('#tblPriceLists').on('change', '.chk-row', function () {
        $('#btnBulkDelete').toggleClass('d-none', !$('.chk-row:checked').length);
    });
    $('#btnBulkDelete').on('click', function () {
        const ids = $('.chk-row:checked').map(function () { return $(this).val(); }).get();
        Swal.fire({ title:`Delete ${ids.length} list(s)?`, icon:'warning',
                    showCancelButton:true, confirmButtonColor:'#e74a3b' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.bulkDelete, { _token:CSRF, ids })
                    .done(() => { $('#chkAll').prop('checked',false); table.ajax.reload(); })
                    .fail(() => Swal.fire('Error','Bulk delete failed.','error'));
            });
    });
})();
</script>
@endpush