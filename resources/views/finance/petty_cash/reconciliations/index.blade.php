@extends('layouts.master')

@section('title', 'Petty Cash Reconciliation')

@push('styles')
<style>
    .select2-container {
        width: 100% !important;
    }
    .select2-dropdown {
        z-index: 9999 !important;
    }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div class="mb-2 mb-md-0">
            <h1 class="h3 text-primary mb-1">Petty Cash Reconciliation</h1>
            <p class="mb-0 text-muted">Review, approve and track petty cash reconciliations.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('finance.petty_cash.reconciliation.create')
            <button type="button" class="btn btn-primary btn-new-reconciliation">
                <i class="fas fa-plus me-1"></i> New Reconciliation
            </button>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-primary">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Total Reconciliations</div>
                    <div class="h5 mb-0" id="card_total_reconciliations">0</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-warning">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Draft / Submitted</div>
                    <div class="h5 mb-0" id="card_open_reconciliations">0</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-success">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Approved</div>
                    <div class="h5 mb-0" id="card_approved_reconciliations">0</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-danger">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Total Variance</div>
                    <div class="h5 mb-0" id="card_total_variance">0.00</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label class="form-label">Account</label>
                    <select id="filter_account_id" class="form-select" style="width:100%;">
                        <option value="">All Accounts</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label class="form-label">Status</label>
                    <select id="filter_status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label class="form-label">From</label>
                    <input type="date" id="filter_from_date" class="form-control">
                </div>

                <div class="col-md-2 mb-2">
                    <label class="form-label">To</label>
                    <input type="date" id="filter_to_date" class="form-control">
                </div>

                <div class="col-md-3 mb-2">
                    <label class="form-label">Search</label>
                    <input type="text" id="filter_q" class="form-control" placeholder="No, notes, account">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="reconTable" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Account</th>
                            <th>Date</th>
                            <th>Opening</th>
                            <th>Funds Added</th>
                            <th>Expenses</th>
                            <th>Refunds</th>
                            <th>System</th>
                            <th>Counted</th>
                            <th>Variance</th>
                            <th>Status</th>
                            <th width="220">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reconciliationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="reconciliationForm">
            @csrf
            <input type="hidden" id="reconciliation_id" name="id">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reconciliationModalTitle">New Reconciliation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="petty_cash_account_id" class="form-label">Petty Cash Account</label>
                            <select name="petty_cash_account_id" id="petty_cash_account_id" class="form-select" style="width:100%;" required>
                                <option value="">Select Account</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="reconciliation_date" class="form-label">Reconciliation Date</label>
                            <input type="date" name="reconciliation_date" id="reconciliation_date" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="opening_balance" class="form-label">Opening Balance</label>
                            <input type="number" step="0.01" id="opening_balance" class="form-control" readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="closing_balance_system" class="form-label">System Balance</label>
                            <input type="number" step="0.01" id="closing_balance_system" class="form-control" readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="closing_balance_counted" class="form-label">Counted Balance</label>
                            <input type="number" step="0.01" min="0" name="closing_balance_counted" id="closing_balance_counted" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="funds_added" class="form-label">Funds Added</label>
                            <input type="number" step="0.01" id="funds_added" class="form-control" readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="expenses_total" class="form-label">Expenses Total</label>
                            <input type="number" step="0.01" id="expenses_total" class="form-control" readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="refunds_total" class="form-label">Refunds Total</label>
                            <input type="number" step="0.01" id="refunds_total" class="form-control" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="variance_amount" class="form-label">Variance</label>
                            <input type="number" step="0.01" id="variance_amount" class="form-control" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="reconciliation_status" class="form-label">Status</label>
                            <input type="text" id="reconciliation_status" class="form-control" readonly value="Draft">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Enter notes or comments"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="reconciliationSubmitBtn">Save Reconciliation</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="viewReconciliationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reconciliation Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <th width="30%">Reconciliation No</th>
                            <td id="view_reconciliation_no">-</td>
                        </tr>
                        <tr>
                            <th>Account</th>
                            <td id="view_account_name">-</td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td id="view_reconciliation_date">-</td>
                        </tr>
                        <tr>
                            <th>Opening Balance</th>
                            <td id="view_opening_balance">0.00</td>
                        </tr>
                        <tr>
                            <th>Funds Added</th>
                            <td id="view_funds_added">0.00</td>
                        </tr>
                        <tr>
                            <th>Expenses Total</th>
                            <td id="view_expenses_total">0.00</td>
                        </tr>
                        <tr>
                            <th>Refunds Total</th>
                            <td id="view_refunds_total">0.00</td>
                        </tr>
                        <tr>
                            <th>System Balance</th>
                            <td id="view_closing_balance_system">0.00</td>
                        </tr>
                        <tr>
                            <th>Counted Balance</th>
                            <td id="view_closing_balance_counted">0.00</td>
                        </tr>
                        <tr>
                            <th>Variance</th>
                            <td id="view_variance_amount">0.00</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td id="view_status">-</td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td id="view_notes">-</td>
                        </tr>
                        <tr>
                            <th>Approved By</th>
                            <td id="view_approved_by">-</td>
                        </tr>
                        <tr>
                            <th>Approved At</th>
                            <td id="view_approved_at">-</td>
                        </tr>
                        <tr>
                            <th>Posted By</th>
                            <td id="view_posted_by">-</td>
                        </tr>
                        <tr>
                            <th>Posted At</th>
                            <td id="view_posted_at">-</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    const reconIndexUrl           = @json(route('admin.finance.petty_cash.reconciliations'));
    const reconStoreUrl           = @json(route('admin.finance.petty_cash.reconciliations.store'));
    const reconSnapshotUrl        = @json(route('admin.finance.petty_cash.reconciliations.account_snapshot'));
    const reconShowUrlTemplate    = @json(route('admin.finance.petty_cash.reconciliations.show', ['id' => '__ID__']));
    const reconEditUrlTemplate    = @json(route('admin.finance.petty_cash.reconciliations.edit', ['id' => '__ID__']));
    const reconUpdateUrlTemplate  = @json(route('admin.finance.petty_cash.reconciliations.update', ['id' => '__ID__']));
    const reconSubmitUrlTemplate  = @json(route('admin.finance.petty_cash.reconciliations.submit', ['id' => '__ID__']));
    const reconApproveUrlTemplate = @json(route('admin.finance.petty_cash.reconciliations.approve', ['id' => '__ID__']));
    const reconRejectUrlTemplate  = @json(route('admin.finance.petty_cash.reconciliations.reject', ['id' => '__ID__']));
    const reconDeleteUrlTemplate  = @json(route('admin.finance.petty_cash.reconciliations.destroy', ['id' => '__ID__']));

    function buildUrl(template, id) {
        return template.replace('__ID__', id);
    }

    function fmt(value) {
        return parseFloat(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function loadSummaryCards() {
        $.ajax({
            url: reconIndexUrl,
            type: 'GET',
            data: {
                summary_only: 1,
                petty_cash_account_id: $('#filter_account_id').val(),
                status: $('#filter_status').val(),
                from_date: $('#filter_from_date').val(),
                to_date: $('#filter_to_date').val(),
                q: $('#filter_q').val()
            },
            success: function (res) {
                if (!res || !res.summary) return;

                $('#card_total_reconciliations').text(res.summary.total_reconciliations ?? 0);
                $('#card_open_reconciliations').text(res.summary.open_reconciliations ?? 0);
                $('#card_approved_reconciliations').text(res.summary.approved_reconciliations ?? 0);
                $('#card_total_variance').text(fmt(res.summary.total_variance ?? 0));
            }
        });
    }

    function initAccountFilterSelect2() {
        let $el = $('#filter_account_id');

        if (!$el.length) return;

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('close');
            $el.select2('destroy');
        }

        $el.select2({
            theme: 'bootstrap-5',
            placeholder: 'All Accounts',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('admin.finance.petty_cash.accounts.select2') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.results || [] };
                },
                cache: true
            }
        });
    }

    function initReconciliationAccountSelect2(selectedId = null, selectedText = null) {
        let $el = $('#petty_cash_account_id');

        if (!$el.length) return;

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('close');
            $el.select2('destroy');
        }

        $el.empty().append('<option value="">Select Account</option>');

        $el.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Account',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#reconciliationModal'),
            ajax: {
                url: "{{ route('admin.finance.petty_cash.accounts.select2') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.results || [] };
                },
                cache: true
            }
        });

        if (selectedId && selectedText) {
            let option = new Option(selectedText, selectedId, true, true);
            $el.append(option).trigger('change');
        }
    }

    function resetReconciliationForm() {
        if ($('#reconciliationForm').length) {
            $('#reconciliationForm')[0].reset();
        }

        $('#reconciliation_id').val('');
        $('#reconciliationModalTitle').text('New Reconciliation');
        $('#reconciliationSubmitBtn').text('Save Reconciliation');
        $('#reconciliation_status').val('Draft');

        $('#opening_balance').val('');
        $('#funds_added').val('');
        $('#expenses_total').val('');
        $('#refunds_total').val('');
        $('#closing_balance_system').val('');
        $('#variance_amount').val('');

        let $account = $('#petty_cash_account_id');
        if ($account.hasClass('select2-hidden-accessible')) {
            $account.select2('close');
            $account.select2('destroy');
        }
        $account.empty().append('<option value="">Select Account</option>');
        $account.prop('disabled', false);
    }

    function calculateVariance() {
        let systemBalance = parseFloat($('#closing_balance_system').val() || 0);
        let countedBalance = parseFloat($('#closing_balance_counted').val() || 0);
        $('#variance_amount').val((countedBalance - systemBalance).toFixed(2));
    }

    function loadAccountSnapshot() {
        let accountId = $('#petty_cash_account_id').val();
        let reconciliationDate = $('#reconciliation_date').val();

        if (!accountId) return;

        $.ajax({
            url: reconSnapshotUrl,
            type: 'GET',
            data: {
                petty_cash_account_id: accountId,
                reconciliation_date: reconciliationDate
            },
            success: function (res) {
                if (!res.success || !res.data) return;

                let d = res.data;
                $('#opening_balance').val(parseFloat(d.opening_balance || 0).toFixed(2));
                $('#funds_added').val(parseFloat(d.funds_added || 0).toFixed(2));
                $('#expenses_total').val(parseFloat(d.expenses_total || 0).toFixed(2));
                $('#refunds_total').val(parseFloat(d.refunds_total || 0).toFixed(2));
                $('#closing_balance_system').val(parseFloat(d.closing_balance_system || 0).toFixed(2));
                calculateVariance();
            }
        });
    }

    function fillViewModal(d) {
        $('#view_reconciliation_no').text(d.reconciliation_no || '-');
        $('#view_account_name').text(d.account ? ((d.account.account_code || '') + ' - ' + (d.account.name || '')) : '-');
        $('#view_reconciliation_date').text(d.reconciliation_date || '-');
        $('#view_opening_balance').text(fmt(d.opening_balance));
        $('#view_funds_added').text(fmt(d.funds_added));
        $('#view_expenses_total').text(fmt(d.expenses_total));
        $('#view_refunds_total').text(fmt(d.refunds_total));
        $('#view_closing_balance_system').text(fmt(d.closing_balance_system));
        $('#view_closing_balance_counted').text(fmt(d.closing_balance_counted));
        $('#view_variance_amount').text(fmt(d.variance_amount));
        $('#view_status').text(d.status ? d.status.charAt(0).toUpperCase() + d.status.slice(1) : '-');
        $('#view_notes').text(d.notes || '-');
        $('#view_approved_by').text(d.approved_by || '-');
        $('#view_approved_at').text(d.approved_at || '-');
        $('#view_posted_by').text(d.posted_by || '-');
        $('#view_posted_at').text(d.posted_at || '-');
    }

    initAccountFilterSelect2();

    let table = $('#reconTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: reconIndexUrl,
            data: function (d) {
                d.petty_cash_account_id = $('#filter_account_id').val();
                d.status = $('#filter_status').val();
                d.from_date = $('#filter_from_date').val();
                d.to_date = $('#filter_to_date').val();
                d.q = $('#filter_q').val();
            }
        },
        columns: [
            { data: 'reconciliation_no', name: 'reconciliation_no' },
            { data: 'account_name', name: 'account.name', orderable: false },
            { data: 'reconciliation_date', name: 'reconciliation_date' },
            { data: 'opening_balance', name: 'opening_balance', className: 'text-end' },
            { data: 'funds_added', name: 'funds_added', className: 'text-end' },
            { data: 'expenses_total', name: 'expenses_total', className: 'text-end' },
            { data: 'refunds_total', name: 'refunds_total', className: 'text-end' },
            { data: 'closing_balance_system', name: 'closing_balance_system', className: 'text-end' },
            { data: 'closing_balance_counted', name: 'closing_balance_counted', className: 'text-end' },
            { data: 'variance_amount', name: 'variance_amount', className: 'text-end' },
            { data: 'status', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            loadSummaryCards();
        }
    });

    $('#filter_account_id, #filter_status, #filter_from_date, #filter_to_date').on('change', function () {
        table.ajax.reload();
    });

    $('#filter_q').on('keyup', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.btn-new-reconciliation', function () {
        resetReconciliationForm();
        $('#reconciliationModal').modal('show');
    });

    $('#reconciliationModal').on('shown.bs.modal', function () {
        if (!$('#reconciliation_id').val()) {
            initReconciliationAccountSelect2();

            if (!$('#reconciliation_date').val()) {
                $('#reconciliation_date').val(new Date().toISOString().split('T')[0]);
            }
        }
    });

    $('#reconciliationModal').on('hidden.bs.modal', function () {
        resetReconciliationForm();
    });

    $(document).on('change', '#petty_cash_account_id, #reconciliation_date', function () {
        loadAccountSnapshot();
    });

    $(document).on('input', '#closing_balance_counted', function () {
        calculateVariance();
    });

    $('#reconciliationForm').on('submit', function (e) {
        e.preventDefault();

        let id = $('#reconciliation_id').val();
        let isEdit = !!id;
        let payload = $(this).serialize();

        $.ajax({
            url: isEdit ? buildUrl(reconUpdateUrlTemplate, id) : reconStoreUrl,
            type: 'POST',
            data: isEdit ? payload + '&_method=PUT' : payload,
            beforeSend: function () {
                Swal.fire({
                    title: isEdit ? 'Updating...' : 'Saving...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                Swal.fire('Success', res.message || (isEdit ? 'Reconciliation updated successfully.' : 'Reconciliation created successfully.'), 'success');
                $('#reconciliationModal').modal('hide');
                table.ajax.reload(null, false);
                loadSummaryCards();
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Save failed.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-view-reconciliation', function () {
        let id = $(this).data('id');

        $.ajax({
            url: buildUrl(reconShowUrlTemplate, id),
            type: 'GET',
            beforeSend: function () {
                Swal.fire({
                    title: 'Loading...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                Swal.close();

                if (!res.success || !res.data) {
                    Swal.fire('Error', res.message || 'Failed to load reconciliation.', 'error');
                    return;
                }

                fillViewModal(res.data);
                $('#viewReconciliationModal').modal('show');
            },
            error: function (xhr) {
                Swal.close();
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load reconciliation.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-edit-reconciliation', function () {
        let id = $(this).data('id');

        $.ajax({
            url: buildUrl(reconEditUrlTemplate, id),
            type: 'GET',
            beforeSend: function () {
                Swal.fire({
                    title: 'Loading...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                Swal.close();

                if (!res.success || !res.data) {
                    Swal.fire('Error', res.message || 'Failed to load reconciliation.', 'error');
                    return;
                }

                let d = res.data;

                resetReconciliationForm();

                $('#reconciliation_id').val(d.id);
                $('#reconciliation_date').val(d.reconciliation_date || '');
                $('#closing_balance_counted').val(parseFloat(d.closing_balance_counted || 0).toFixed(2));
                $('#notes').val(d.notes || '');
                $('#opening_balance').val(parseFloat(d.opening_balance || 0).toFixed(2));
                $('#funds_added').val(parseFloat(d.funds_added || 0).toFixed(2));
                $('#expenses_total').val(parseFloat(d.expenses_total || 0).toFixed(2));
                $('#refunds_total').val(parseFloat(d.refunds_total || 0).toFixed(2));
                $('#closing_balance_system').val(parseFloat(d.closing_balance_system || 0).toFixed(2));
                $('#variance_amount').val(parseFloat(d.variance_amount || 0).toFixed(2));
                $('#reconciliation_status').val((d.status || 'draft').charAt(0).toUpperCase() + (d.status || 'draft').slice(1));

                initReconciliationAccountSelect2(
                    d.petty_cash_account_id,
                    (d.account?.account_code || '') + ' - ' + (d.account?.name || 'Account')
                );

                $('#petty_cash_account_id').prop('disabled', true);
                $('#reconciliationModalTitle').text('Edit Reconciliation');
                $('#reconciliationSubmitBtn').text('Update Reconciliation');

                $('#reconciliationModal').modal('show');
            },
            error: function (xhr) {
                Swal.close();
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load reconciliation.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-submit-reconciliation', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'Submit Reconciliation?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, submit'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: buildUrl(reconSubmitUrlTemplate, id),
                type: 'POST',
                data: { _token: csrfToken },
                beforeSend: function () {
                    Swal.fire({
                        title: 'Submitting...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function (res) {
                    Swal.fire('Success', res.message || 'Reconciliation submitted successfully.', 'success');
                    table.ajax.reload(null, false);
                    loadSummaryCards();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Submit failed.', 'error');
                }
            });
        });
    });

    $(document).on('click', '.btn-approve-reconciliation', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'Approve Reconciliation?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: buildUrl(reconApproveUrlTemplate, id),
                type: 'POST',
                data: { _token: csrfToken },
                beforeSend: function () {
                    Swal.fire({
                        title: 'Approving...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function (res) {
                    Swal.fire('Success', res.message || 'Reconciliation approved successfully.', 'success');
                    table.ajax.reload(null, false);
                    loadSummaryCards();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Approval failed.', 'error');
                }
            });
        });
    });

    $(document).on('click', '.btn-reject-reconciliation', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'Reject Reconciliation?',
            input: 'textarea',
            inputPlaceholder: 'Enter reason...',
            showCancelButton: true,
            confirmButtonText: 'Reject'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: buildUrl(reconRejectUrlTemplate, id),
                type: 'POST',
                data: {
                    _token: csrfToken,
                    notes: result.value || ''
                },
                beforeSend: function () {
                    Swal.fire({
                        title: 'Rejecting...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function (res) {
                    Swal.fire('Success', res.message || 'Reconciliation rejected successfully.', 'success');
                    table.ajax.reload(null, false);
                    loadSummaryCards();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Reject failed.', 'error');
                }
            });
        });
    });

    $(document).on('click', '.btn-delete-reconciliation', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'Delete Reconciliation?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: buildUrl(reconDeleteUrlTemplate, id),
                type: 'POST',
                data: {
                    _token: csrfToken,
                    _method: 'DELETE'
                },
                beforeSend: function () {
                    Swal.fire({
                        title: 'Deleting...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function (res) {
                    Swal.fire('Deleted', res.message || 'Reconciliation deleted successfully.', 'success');
                    table.ajax.reload(null, false);
                    loadSummaryCards();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Delete failed.', 'error');
                }
            });
        });
    });

    loadSummaryCards();
});
</script>
@endpush