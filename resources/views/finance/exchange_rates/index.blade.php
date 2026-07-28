{{-- resources/views/finance/exchange_rates/index.blade.php --}}
@extends('layouts.master')

@section('content')

{{-- Page Header --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-exchange-alt me-2 text-primary"></i> Exchange Rates
    </h1>
    @can('finance.exchange_rates.create')
        <button class="btn btn-primary btn-sm shadow-sm" id="btnCreate">
            <i class="fas fa-plus fa-sm me-1"></i> Add Rate
        </button>
    @endcan
</div>

{{-- Filter Bar --}}
<div class="card shadow mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small mb-1">Base Currency</label>
                <input type="text" id="filterBase" class="form-control form-control-sm"
                       placeholder="e.g. USD" maxlength="3">
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">Quote Currency</label>
                <input type="text" id="filterQuote" class="form-control form-control-sm"
                       placeholder="e.g. NGN" maxlength="3">
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">Status</label>
                <select id="filterActive" class="form-select form-select-sm">
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

{{-- Table Card --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Exchange Rate Records</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-danger d-none" id="btnBulkDelete">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover w-100" id="tblExchangeRates">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px">
                            <input type="checkbox" id="chkAll">
                        </th>
                        <th>Base</th>
                        <th>Quote</th>
                        <th>Rate</th>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="width:100px">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- ===================== CREATE / EDIT MODAL ===================== --}}
<div class="modal fade" id="modalRate" tabindex="-1" aria-labelledby="modalRateLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRateLabel">Add Exchange Rate</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmRate" novalidate>
                    @csrf
                    <input type="hidden" id="rateId">

                    <div class="row g-3">
                        {{-- Base Currency --}}
                        <div class="col-6">
                            <label class="form-label fw-semibold">Base Currency <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="base_currency"
                                   name="base_currency" maxlength="3" placeholder="USD" required>
                            <div class="invalid-feedback">3-letter code required.</div>
                        </div>

                        {{-- Quote Currency --}}
                        <div class="col-6">
                            <label class="form-label fw-semibold">Quote Currency <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="quote_currency"
                                   name="quote_currency" maxlength="3" placeholder="NGN" required>
                            <div class="invalid-feedback">3-letter code required (must differ from base).</div>
                        </div>

                        {{-- Rate --}}
                        <div class="col-6">
                            <label class="form-label fw-semibold">Rate <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="rate" name="rate"
                                   step="0.01" min="0.01" placeholder="1550.00" required>
                            <div class="form-text">Quote units per 1 base unit.</div>
                            <div class="invalid-feedback">Enter a positive rate.</div>
                        </div>

                        {{-- Rate Date --}}
                        <div class="col-6">
                            <label class="form-label fw-semibold">Effective Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="rate_date" name="rate_date" required>
                            <div class="invalid-feedback">Date is required.</div>
                        </div>

                        {{-- Source --}}
                        <div class="col-6">
                            <label class="form-label fw-semibold">Source <span class="text-danger">*</span></label>
                            <select class="form-select" id="source" name="source" required>
                                <option value="manual">Manual</option>
                                <option value="api">API</option>
                                <option value="bank">Bank</option>
                            </select>
                        </div>

                        {{-- Active --}}
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active"
                                       name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSave">
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
    'use strict';

    // -------------------------------------------------------
    // Config
    // -------------------------------------------------------
    const URLS = {
        datatable  : '{{ route('admin.finance.exchange_rates.datatable') }}',
        store      : '{{ route('admin.finance.exchange_rates.store') }}',
        show       : (id) => `/admin/finance/exchange-rates/${id}`,
        update     : (id) => `/admin/finance/exchange-rates/${id}`,
        destroy    : (id) => `/admin/finance/exchange-rates/${id}`,
        bulkDelete : '{{ route('admin.finance.exchange_rates.bulk_delete') }}',
        toggle     : (id) => `/admin/finance/exchange-rates/${id}/toggle-active`,
    };

    const CSRF = $('meta[name="csrf-token"]').attr('content');

    // -------------------------------------------------------
    // DataTable
    // -------------------------------------------------------
    let table;

    function buildTable(extra = {}) {
        if (table) { table.destroy(); }

        table = $('#tblExchangeRates').DataTable({
            processing  : true,
            serverSide  : false,
            ajax        : {
                url  : URLS.datatable,
                data : extra,
                dataSrc: 'data',
            },
            columns: [
                {
                    data: 'id', orderable: false, searchable: false,
                    render: (id) =>
                        `<input type="checkbox" class="chk-row" value="${id}">`,
                },
                { data: 'base_currency'  },
                { data: 'quote_currency' },
                {
                    data: 'rate',
                    render: (v, t, row) =>
                        `<span class="fw-semibold">${v}</span>`,
                },
                { data: 'rate_date' },
                { data: 'source'   },
                {
                    data: 'is_active',
                    render: (v) => v
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>',
                },
                { data: 'created_at' },
                {
                    data: null, orderable: false, searchable: false,
                    render: (_, __, row) => `
                        <div class="d-flex gap-1">
                            <button class="btn btn-xs btn-warning btn-edit" data-id="${row.id}"
                                title="Edit"><i class="fas fa-pencil-alt"></i></button>
                            <button class="btn btn-xs btn-${row.is_active ? 'secondary' : 'success'} btn-toggle"
                                data-id="${row.id}" title="${row.is_active ? 'Deactivate' : 'Activate'}">
                                <i class="fas fa-${row.is_active ? 'toggle-off' : 'toggle-on'}"></i>
                            </button>
                            <button class="btn btn-xs btn-danger btn-delete" data-id="${row.id}"
                                title="Delete"><i class="fas fa-trash"></i></button>
                        </div>`,
                },
            ],
            order: [[4, 'desc']],
            pageLength: 25,
            responsive: true,
        });
    }

    buildTable();

    // -------------------------------------------------------
    // Filter
    // -------------------------------------------------------
    $('#btnFilter').on('click', function () {
        buildTable({
            base_currency  : $('#filterBase').val().trim().toUpperCase() || undefined,
            quote_currency : $('#filterQuote').val().trim().toUpperCase() || undefined,
            is_active      : $('#filterActive').val() || undefined,
        });
    });

    $('#btnReset').on('click', function () {
        $('#filterBase, #filterQuote').val('');
        $('#filterActive').val('');
        buildTable();
    });

    // -------------------------------------------------------
    // Modal helpers
    // -------------------------------------------------------
    const $modal     = $('#modalRate');
    const $modalBS   = new bootstrap.Modal($modal[0]);
    const $form      = $('#frmRate');
    const $rateIdFld = $('#rateId');

    function openCreate() {
        $form[0].reset();
        $rateIdFld.val('');
        $('#is_active').prop('checked', true);
        $('#rate_date').val(new Date().toISOString().split('T')[0]);
        $('#modalRateLabel').text('Add Exchange Rate');
        $modalBS.show();
    }

    function openEdit(id) {
        $.get(URLS.show(id))
            .done(function (r) {
                $rateIdFld.val(r.id);
                $('#base_currency').val(r.base_currency);
                $('#quote_currency').val(r.quote_currency);
                $('#rate').val(r.rate);
                $('#rate_date').val(r.rate_date ? r.rate_date.substring(0, 10) : '');
                $('#source').val(r.source);
                $('#is_active').prop('checked', !!r.is_active);
                $('#modalRateLabel').text('Edit Exchange Rate');
                $modalBS.show();
            })
            .fail(() => Swal.fire('Error', 'Could not load record.', 'error'));
    }

    // -------------------------------------------------------
    // Save (create or update)
    // -------------------------------------------------------
    $('#btnCreate').on('click', openCreate);

    $('#btnSave').on('click', function () {
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }

        const id = $rateIdFld.val();
        const payload = {
            _token         : CSRF,
            base_currency  : $('#base_currency').val().toUpperCase(),
            quote_currency : $('#quote_currency').val().toUpperCase(),
            rate           : $('#rate').val(),
            rate_date      : $('#rate_date').val(),
            source         : $('#source').val(),
            is_active      : $('#is_active').is(':checked') ? 1 : 0,
        };

        const isEdit  = !!id;
        const url     = isEdit ? URLS.update(id) : URLS.store;
        const method  = isEdit ? 'PUT' : 'POST';

        if (isEdit) payload._method = 'PUT';

        $.ajax({ url, method: 'POST', data: payload })
            .done(function () {
                $modalBS.hide();
                table.ajax.reload();
                Swal.fire({
                    icon: 'success', title: 'Saved',
                    text: isEdit ? 'Rate updated.' : 'Rate created.',
                    timer: 1800, showConfirmButton: false,
                });
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message || 'An error occurred.';
                const errors = xhr.responseJSON?.errors;
                let detail = msg;
                if (errors) {
                    detail = Object.values(errors).flat().join('\n');
                }
                Swal.fire('Error', detail, 'error');
            });
    });

    // -------------------------------------------------------
    // Edit / Delete / Toggle (event delegation)
    // -------------------------------------------------------
    $('#tblExchangeRates').on('click', '.btn-edit', function () {
        openEdit($(this).data('id'));
    });

    $('#tblExchangeRates').on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete this rate?',
            text : 'This cannot be undone.',
            icon : 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            confirmButtonText: 'Yes, delete',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url   : URLS.destroy(id),
                method: 'POST',
                data  : { _token: CSRF, _method: 'DELETE' },
            }).done(function () {
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Deleted', timer: 1500, showConfirmButton: false });
            }).fail(() => Swal.fire('Error', 'Could not delete.', 'error'));
        });
    });

    $('#tblExchangeRates').on('click', '.btn-toggle', function () {
        const id = $(this).data('id');
        $.ajax({ url: URLS.toggle(id), method: 'POST', data: { _token: CSRF } })
            .done(() => table.ajax.reload())
            .fail(() => Swal.fire('Error', 'Could not update status.', 'error'));
    });

    // -------------------------------------------------------
    // Bulk select / delete
    // -------------------------------------------------------
    $('#chkAll').on('change', function () {
        $('.chk-row').prop('checked', this.checked);
        toggleBulkBtn();
    });

    $('#tblExchangeRates').on('change', '.chk-row', toggleBulkBtn);

    function toggleBulkBtn() {
        const any = $('.chk-row:checked').length > 0;
        $('#btnBulkDelete').toggleClass('d-none', !any);
    }

    $('#btnBulkDelete').on('click', function () {
        const ids = $('.chk-row:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) return;

        Swal.fire({
            title: `Delete ${ids.length} record(s)?`,
            icon : 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            confirmButtonText: 'Yes, delete',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url   : URLS.bulkDelete,
                method: 'POST',
                data  : { _token: CSRF, ids },
            }).done(function () {
                $('#chkAll').prop('checked', false);
                toggleBulkBtn();
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Deleted', timer: 1500, showConfirmButton: false });
            }).fail(() => Swal.fire('Error', 'Bulk delete failed.', 'error'));
        });
    });

})();
</script>
@endpush