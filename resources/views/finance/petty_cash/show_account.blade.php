@extends('layouts.master')

@section('title', 'Petty Cash Account Details')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div class="mb-2 mb-md-0">
            <h1 class="h3 text-primary mb-1">Petty Cash Account Details</h1>
            <p class="mb-0 text-muted">{{ $account->account_code }} - {{ $account->name }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.finance.petty_cash.accounts') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>

            @can('finance.petty_cash.create')
            <button type="button"
                    class="btn btn-primary btn-new-transaction"
                    data-account-id="{{ $account->id }}"
                    data-account-text="{{ $account->account_code }} - {{ $account->name }}">
                <i class="fas fa-plus me-1"></i> New Transaction
            </button>

            @if($suggestedReplenishment > 0)
            <button type="button"
                    class="btn btn-success btn-quick-replenish"
                    data-account-id="{{ $account->id }}"
                    data-account-text="{{ $account->account_code }} - {{ $account->name }}"
                    data-suggested="{{ number_format($suggestedReplenishment, 2, '.', '') }}">
                <i class="fas fa-wallet me-1"></i> Replenish Now
            </button>
            @endif
            @endcan

            @can('finance.petty_cash.accounts.manage')
            <button type="button"
                    class="btn btn-warning btn-edit-account"
                    data-id="{{ $account->id }}">
                <i class="fas fa-edit me-1"></i> Edit Account
            </button>
            @endcan
        </div>
    </div>

    @if($isLowBalance)
    <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <strong>Low balance warning.</strong>
            Current balance is {{ number_format($account->current_balance, 2) }},
            which is at or below the minimum balance of {{ number_format($account->minimum_balance, 2) }}.
            Suggested replenishment: <strong>{{ number_format($suggestedReplenishment, 2) }}</strong>.
        </div>

        @can('finance.petty_cash.create')
        @if($suggestedReplenishment > 0)
        <button type="button"
                class="btn btn-sm btn-success btn-quick-replenish"
                data-account-id="{{ $account->id }}"
                data-account-text="{{ $account->account_code }} - {{ $account->name }}"
                data-suggested="{{ number_format($suggestedReplenishment, 2, '.', '') }}">
            Replenish Now
        </button>
        @endif
        @endcan
    </div>
    @endif

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-primary">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Current Balance</div>
                    <div class="h5 mb-0">{{ number_format($account->current_balance, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-info">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Float Amount</div>
                    <div class="h5 mb-0">{{ number_format($account->float_amount, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-warning">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Transactions</div>
                    <div class="h5 mb-0">{{ number_format($transactionCount) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-danger">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Reconciliations</div>
                    <div class="h5 mb-0">{{ number_format($reconciliationCount) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-success">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Total Funded</div>
                    <div class="h5 mb-0">{{ number_format($analytics['total_funded'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-primary">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Total Replenished</div>
                    <div class="h5 mb-0">{{ number_format($analytics['total_replenished'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-danger">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Total Spent</div>
                    <div class="h5 mb-0">{{ number_format($analytics['total_spent'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-info">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Total Refunded</div>
                    <div class="h5 mb-0">{{ number_format($analytics['total_refunded'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card shadow border-left-warning">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Pending Amount</div>
                    <div class="h5 mb-0">{{ number_format($analytics['pending_amount'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card shadow border-left-dark">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Posted Amount</div>
                    <div class="h5 mb-0">{{ number_format($analytics['posted_amount'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card shadow border-left-secondary">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Last Replenishment</div>
                    <div class="h6 mb-1">
                        {{ $analytics['last_replenishment_date'] ?: 'N/A' }}
                    </div>
                    <div class="small text-muted">
                        Amount: {{ number_format($analytics['last_replenishment_amount'], 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header">
            <strong>Account Information</strong>
        </div>
        <div class="card-body">
            <table class="table table-bordered mb-0">
                <tr>
                    <th width="20%">Account Code</th>
                    <td>{{ $account->account_code }}</td>
                    <th width="20%">Status</th>
                    <td>{{ ucfirst($account->status) }}</td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>{{ $account->name }}</td>
                    <th>Custodian</th>
                    <td>{{ trim((optional($account->custodian)->first_name ?? '') . ' ' . (optional($account->custodian)->last_name ?? '')) ?: '-' }}</td>
                </tr>
                <tr>
                    <th>Cash GL Account</th>
                    <td>{{ $account->cashGlAccount->name ?? '-' }}</td>
                    <th>Clearing GL Account</th>
                    <td>{{ $account->clearingGlAccount->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Float Amount</th>
                    <td>{{ number_format($account->float_amount, 2) }}</td>
                    <th>Minimum Balance</th>
                    <td>{{ number_format($account->minimum_balance, 2) }}</td>
                </tr>
                <tr>
                    <th>Current Balance</th>
                    <td>{{ number_format($account->current_balance, 2) }}</td>
                    <th>Auto Replenish Suggestion</th>
                    <td>{{ $account->auto_replenish_suggestion ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Notes</th>
                    <td colspan="3">{{ $account->notes ?: '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header">
            <strong>Monthly Petty Cash Trend</strong>
        </div>
        <div class="card-body">
            <canvas id="pettyCashChart" height="100"></canvas>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header">
            <strong>Transactions</strong>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3 mb-2">
                    <select class="form-select" id="txn_filter_type">
                        <option value="">All Types</option>
                        <option value="funding">Funding</option>
                        <option value="expense">Expense</option>
                        <option value="replenishment">Replenishment</option>
                        <option value="refund">Refund</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="retirement">Retirement</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select class="form-select" id="txn_filter_status">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="posted">Posted</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" class="form-control" id="txn_filter_from_date">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" class="form-control" id="txn_filter_to_date">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="text" class="form-control" id="txn_filter_q" placeholder="Search">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="accountTransactionsTable" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Voucher</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Payee</th>
                            <th>Reference</th>
                            <th>Expense A/C</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th width="220">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header">
            <strong>Recent Reconciliations</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Date</th>
                            <th>System Balance</th>
                            <th>Counted</th>
                            <th>Variance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentReconciliations as $row)
                            <tr>
                                <td>{{ $row->reconciliation_no }}</td>
                                <td>{{ optional($row->reconciliation_date)->format('Y-m-d') }}</td>
                                <td>{{ number_format($row->closing_balance_system, 2) }}</td>
                                <td>{{ number_format($row->closing_balance_counted, 2) }}</td>
                                <td>{{ number_format($row->variance_amount, 2) }}</td>
                                <td>{{ ucfirst($row->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No reconciliations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('finance.petty_cash.partials.transaction_modal')
@include('finance.petty_cash.accounts_modal')
@include('finance.petty_cash.partials.edit_transaction_modal')
@endsection

@php
    $safeChartData = $chartData ?? [
        'labels' => [],
        'spent' => [],
        'replenished' => [],
        'funded' => [],
    ];
@endphp

@push('scripts')
<script>
$(function () {
    const accountEditUrlTemplate     = @json(route('admin.finance.petty_cash.accounts.edit', ['id' => '__ID__']));
    const accountUpdateUrlTemplate   = @json(route('admin.finance.petty_cash.accounts.update', ['id' => '__ID__']));
    const accountShowAjaxUrl         = @json(route('admin.finance.petty_cash.accounts.show', $account->id));
    const accountStoreUrl            = @json(route('admin.finance.petty_cash.accounts.store'));

    const pettyCashStoreUrl          = @json(route('admin.finance.petty_cash.store'));
    const pettyCashEditUrlTemplate   = @json(route('admin.finance.petty_cash.edit', ['id' => '__ID__']));
    const pettyCashUpdateUrlTemplate = @json(route('admin.finance.petty_cash.update', ['id' => '__ID__']));
    const pettyCashDeleteUrlTemplate = @json(route('admin.finance.petty_cash.destroy', ['id' => '__ID__']));
    const pettyCashApproveUrlTemplate = @json(route('admin.finance.petty_cash.approve', ['id' => '__ID__']));
    const pettyCashRejectUrlTemplate  = @json(route('admin.finance.petty_cash.reject', ['id' => '__ID__']));
    const pettyCashPostUrlTemplate    = @json(route('admin.finance.petty_cash.post', ['id' => '__ID__']));

    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const currentAccountBalance = parseFloat(@json((float) $account->current_balance)) || 0;

    function buildUrl(template, id) {
        return template.replace('__ID__', id);
    }

    function refreshAccountPage(message = null, title = 'Success') {
        if (message) {
            Swal.fire({
                icon: 'success',
                title: title,
                text: message,
                timer: 1200,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            location.reload();
        }
    }

    function resetAccountModal() {
        if ($('#accountForm').length) {
            $('#accountForm')[0].reset();
        }

        $('#account_id').val('');
        $('#accountModalTitle').text('Edit Petty Cash Account');
        $('#accountSubmitBtn').text('Update Account');
    }

    function initEditPayeeSelect2(selectedId = null, selectedText = null) {
        let $el = $('#edit_payee_id');

        if (!$el.length) return;

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('close');
            $el.select2('destroy');
        }

        $el.empty();

        $el.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Payee',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#editTransactionModal'),
            ajax: {
                url: "{{ route('admin.finance.petty_cash.payees.select2') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        type: $('#edit_payee_type').val(),
                        q: params.term || ''
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            }
        });

        if (selectedId && selectedText) {
            let option = new Option(selectedText, selectedId, true, true);
            $el.append(option).trigger('change');
        }
    }

    function toggleEditExpenseField() {
        let type = $('#edit_type').val();

        if (type === 'expense') {
            $('#editExpenseWrap').show();
        } else {
            $('#editExpenseWrap').hide();
            $('#edit_expense_account_id').val('');
        }
    }

    function toggleEditPayeeFields() {
        let type = $('#edit_payee_type').val();

        if (['employee', 'supplier', 'customer'].includes(type)) {
            $('#editPayeeLookupWrap').show();
            $('#editPayeeTextWrap').hide();
            $('#edit_payee_text').val('');
        } else {
            $('#editPayeeLookupWrap').hide();
            $('#editPayeeTextWrap').show();

            if ($('#edit_payee_id').hasClass('select2-hidden-accessible')) {
                $('#edit_payee_id').val(null).trigger('change');
            }
        }
    }

    function resetEditTransactionModal() {
        if ($('#editTransactionForm').length) {
            $('#editTransactionForm')[0].reset();
        }

        $('#edit_id').val('');
        $('#edit_expense_account_id').val('');
        $('#edit_status').val('draft');
        $('#edit_payee_type').val('other');
        $('#edit_payee_text').val('');

        if ($('#edit_payee_id').hasClass('select2-hidden-accessible')) {
            $('#edit_payee_id').select2('close');
            $('#edit_payee_id').select2('destroy');
        }

        $('#edit_payee_id').empty();
        toggleEditExpenseField();
        toggleEditPayeeFields();
    }

    function resetTransactionModalForm() {
        if ($('#transactionForm').length) {
            $('#transactionForm')[0].reset();
        }

        if ($('#petty_cash_account_id').length && $('#petty_cash_account_id').hasClass('select2-hidden-accessible')) {
            $('#petty_cash_account_id').val(null).trigger('change');
        }

        if ($('#payee_id').length && $('#payee_id').hasClass('select2-hidden-accessible')) {
            $('#payee_id').val(null).trigger('change');
        }

        $('#petty_cash_account_id').prop('disabled', false);
        $('#typeField').prop('disabled', false);
    }

    let table = $('#accountTransactionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: accountShowAjaxUrl,
            data: function (d) {
                d.type = $('#txn_filter_type').val();
                d.status = $('#txn_filter_status').val();
                d.from_date = $('#txn_filter_from_date').val();
                d.to_date = $('#txn_filter_to_date').val();
                d.q = $('#txn_filter_q').val();
            }
        },
        columns: [
            { data: 'transaction_no', name: 'transaction_no' },
            { data: 'voucher_no', name: 'voucher_no' },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'type', name: 'type' },
            { data: 'payee_name', name: 'payee', orderable: false },
            { data: 'reference_no', name: 'reference_no' },
            { data: 'expense_account_name', name: 'expenseAccount.name', orderable: false },
            { data: 'amount', name: 'amount', className: 'text-end' },
            { data: 'status', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#txn_filter_type, #txn_filter_status, #txn_filter_from_date, #txn_filter_to_date').on('change', function () {
        table.ajax.reload();
    });

    $('#txn_filter_q').on('keyup', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.btn-edit-account', function (e) {
        e.preventDefault();

        let id = $(this).data('id');

        if (!id) {
            Swal.fire('Error', 'Account ID is missing.', 'error');
            return;
        }

        $.ajax({
            url: buildUrl(accountEditUrlTemplate, id),
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
                    Swal.fire('Error', res.message || 'Failed to load account.', 'error');
                    return;
                }

                let d = res.data;

                $('#account_id').val(d.id);
                $('#account_code').val(d.account_code);
                $('#account_name').val(d.name);
                $('#gl_cash_account_id').val(d.gl_cash_account_id);
                $('#gl_expense_clearing_account_id').val(d.gl_expense_clearing_account_id);
                $('#float_amount').val(d.float_amount);
                $('#minimum_balance').val(d.minimum_balance);
                $('#status').val(d.status);
                $('#notes').val(d.notes);
                $('#auto_replenish_suggestion').prop('checked', !!parseInt(d.auto_replenish_suggestion));

                $('#accountModalTitle').text('Edit Petty Cash Account');
                $('#accountSubmitBtn').text('Update Account');

                $('#accountModal').modal('show');
            },
            error: function (xhr) {
                Swal.close();
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load account.', 'error');
            }
        });
    });

    $('#accountForm').on('submit', function (e) {
        e.preventDefault();

        let id = $('#account_id').val();
        let isEdit = !!id;

        $.ajax({
            url: isEdit ? buildUrl(accountUpdateUrlTemplate, id) : accountStoreUrl,
            type: 'POST',
            data: isEdit ? $(this).serialize() + '&_method=PUT' : $(this).serialize(),
            beforeSend: function () {
                Swal.fire({
                    title: isEdit ? 'Updating...' : 'Saving...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                $('#accountModal').modal('hide');
                refreshAccountPage(res.message || 'Account updated successfully.');
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong.', 'error');
            }
        });
    });

    $('#accountModal').on('hidden.bs.modal', function () {
        resetAccountModal();
    });

    $(document).on('click', '.btn-new-transaction', function () {
        let accountId = $(this).data('account-id');
        let accountText = $(this).data('account-text');

        $('#transactionModal').modal('show');

        setTimeout(function () {
            let $el = $('#petty_cash_account_id');

            if ($el.hasClass('select2-hidden-accessible')) {
                let option = new Option(accountText, accountId, true, true);
                $el.append(option).trigger('change');
            }
        }, 300);
    });

    $(document).on('click', '.btn-quick-replenish', function () {
        let accountId = $(this).data('account-id');
        let accountText = $(this).data('account-text');
        let suggested = parseFloat($(this).data('suggested') || 0);

        $('#transactionModal').modal('show');

        setTimeout(function () {
            let $account = $('#petty_cash_account_id');

            if ($account.hasClass('select2-hidden-accessible')) {
                let option = new Option(accountText, accountId, true, true);
                $account.append(option).trigger('change');
            }

            $('#typeField').val('replenishment').trigger('change');
            $('[name="amount"]').val(suggested.toFixed(2));
            $('[name="reference_no"]').val('');
            $('#payee_type').val('other').trigger('change');
            $('#payee_text').val(accountText + ' Replenishment');
            $('[name="description"]').val('Replenishment for petty cash account ' + accountText);
            $('[name="status"]').val('pending');

            $('#petty_cash_account_id').prop('disabled', true);
            $('#typeField').prop('disabled', true);
        }, 300);
    });

    $('#transactionForm').on('submit', function (e) {
        e.preventDefault();

        let amount = parseFloat($('[name="amount"]').val() || 0);
        let type = $('#typeField').val();

        if (type === 'expense' && amount > currentAccountBalance) {
            Swal.fire('Error', 'Insufficient petty cash balance.', 'error');
            return;
        }

        let fd = new FormData(this);

        $.ajax({
            url: pettyCashStoreUrl,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            beforeSend: function () {
                Swal.fire({
                    title: 'Saving...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                $('#transactionModal').modal('hide');
                resetTransactionModalForm();
                refreshAccountPage(res.message || 'Transaction created successfully.');
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong.', 'error');
            }
        });
    });

    $('#transactionModal').on('hidden.bs.modal', function () {
        resetTransactionModalForm();
    });

    $('#edit_type').on('change', function () {
        toggleEditExpenseField();
    });

    $('#edit_payee_type').on('change', function () {
        toggleEditPayeeFields();

        if (['employee', 'supplier', 'customer'].includes($(this).val())) {
            initEditPayeeSelect2();
        } else {
            if ($('#edit_payee_id').hasClass('select2-hidden-accessible')) {
                $('#edit_payee_id').select2('close');
                $('#edit_payee_id').select2('destroy');
            }
            $('#edit_payee_id').empty();
        }
    });

    $(document).on('click', '.btn-edit-transaction', function (e) {
        e.preventDefault();

        let id = $(this).data('id');

        if (!id) {
            Swal.fire('Error', 'Transaction ID is missing.', 'error');
            return;
        }

        $.ajax({
            url: buildUrl(pettyCashEditUrlTemplate, id),
            type: 'GET',
            beforeSend: function () {
                Swal.fire({
                    title: 'Loading transaction...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                Swal.close();

                if (!res.success || !res.data) {
                    Swal.fire('Error', res.message || 'Failed to load transaction.', 'error');
                    return;
                }

                let d = res.data;

                resetEditTransactionModal();

                $('#edit_id').val(d.id);
                $('#edit_transaction_date').val(d.transaction_date || '');
                $('#edit_type').val(d.type || '').trigger('change');
                $('#edit_reference_no').val(d.reference_no || '');
                $('#edit_amount').val(d.amount || '');
                $('#edit_description').val(d.description || '');
                $('#edit_expense_account_id').val(d.expense_account_id || '');
                $('#edit_status').val(d.status || 'draft');
                $('#edit_payee_type').val(d.payee_type || 'other');

                toggleEditPayeeFields();

                if (['employee', 'supplier', 'customer'].includes(d.payee_type)) {
                    initEditPayeeSelect2(d.payee_id, d.payee_display || d.payee || 'Selected Payee');
                } else {
                    $('#edit_payee_text').val(d.payee || '');
                }

                toggleEditExpenseField();
                $('#editTransactionModal').modal('show');
            },
            error: function (xhr) {
                Swal.close();
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load record.', 'error');
            }
        });
    });

    $('#editTransactionForm').on('submit', function (e) {
        e.preventDefault();

        let id = $('#edit_id').val();

        if (!id) {
            Swal.fire('Error', 'Transaction ID is missing.', 'error');
            return;
        }

        let fd = new FormData(this);
        fd.append('_method', 'PUT');
        fd.append('_token', csrfToken);

        $.ajax({
            url: buildUrl(pettyCashUpdateUrlTemplate, id),
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            beforeSend: function () {
                Swal.fire({
                    title: 'Updating...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                $('#editTransactionModal').modal('hide');
                resetEditTransactionModal();
                refreshAccountPage(res.message || 'Transaction updated successfully.');
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Update failed.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-approve', function () {
        let id = $(this).data('id');

        if (!id) {
            Swal.fire('Error', 'Transaction ID is missing.', 'error');
            return;
        }

        Swal.fire({
            title: 'Approve Transaction?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: buildUrl(pettyCashApproveUrlTemplate, id),
                type: 'POST',
                data: {
                    _token: csrfToken
                },
                beforeSend: function () {
                    Swal.fire({
                        title: 'Approving...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function (res) {
                    refreshAccountPage(res.message || 'Transaction approved successfully.');
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Approve failed.', 'error');
                }
            });
        });
    });

    $(document).on('click', '.btn-reject', function () {
        let id = $(this).data('id');

        if (!id) {
            Swal.fire('Error', 'Transaction ID is missing.', 'error');
            return;
        }

        Swal.fire({
            title: 'Reject Transaction?',
            input: 'textarea',
            inputPlaceholder: 'Enter reason...',
            inputAttributes: {
                'aria-label': 'Enter reason'
            },
            showCancelButton: true,
            confirmButtonText: 'Reject',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: buildUrl(pettyCashRejectUrlTemplate, id),
                type: 'POST',
                data: {
                    _token: csrfToken,
                    approval_notes: result.value || ''
                },
                beforeSend: function () {
                    Swal.fire({
                        title: 'Rejecting...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function (res) {
                    refreshAccountPage(res.message || 'Transaction rejected successfully.');
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Reject failed.', 'error');
                }
            });
        });
    });

    $(document).on('click', '.btn-post', function () {
        let id = $(this).data('id');

        if (!id) {
            Swal.fire('Error', 'Transaction ID is missing.', 'error');
            return;
        }

        Swal.fire({
            title: 'Post Transaction?',
            text: 'This will affect accounts.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, post',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: buildUrl(pettyCashPostUrlTemplate, id),
                type: 'POST',
                data: {
                    _token: csrfToken
                },
                beforeSend: function () {
                    Swal.fire({
                        title: 'Posting...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function (res) {
                    refreshAccountPage(res.message || 'Transaction posted successfully.');
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Post failed.', 'error');
                }
            });
        });
    });

    $(document).on('click', '.btn-delete', function () {
        let id = $(this).data('id');

        if (!id) {
            Swal.fire('Error', 'Transaction ID is missing.', 'error');
            return;
        }

        Swal.fire({
            title: 'Delete Transaction?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: buildUrl(pettyCashDeleteUrlTemplate, id),
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
                    refreshAccountPage(res.message || 'Transaction deleted successfully.', 'Deleted');
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Delete failed.', 'error');
                }
            });
        });
    });

    $('#editTransactionModal').on('hidden.bs.modal', function () {
        resetEditTransactionModal();
    });

    const chartCanvas = document.getElementById('pettyCashChart');
    const chartData = @json($safeChartData);

    if (chartCanvas && typeof Chart !== 'undefined') {
        const ctx = chartCanvas.getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Spent',
                        data: chartData.spent,
                        borderWidth: 2,
                        fill: false
                    },
                    {
                        label: 'Replenished',
                        data: chartData.replenished,
                        borderWidth: 2,
                        fill: false
                    },
                    {
                        label: 'Funded',
                        data: chartData.funded,
                        borderWidth: 2,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
@endpush