@extends('layouts.master')

@section('title', 'Petty Cash')

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

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary">Petty Cash Transactions</h1>
        @can('finance.petty_cash.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
            <i class="fas fa-plus me-1"></i> New Transaction
        </button>
        @endcan
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-primary">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Active Accounts</div>
                    <div class="h5 mb-0">{{ number_format($summary['active_accounts']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-success">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Total Balance</div>
                    <div class="h5 mb-0">{{ number_format($summary['total_balance'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-warning">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Pending Transactions</div>
                    <div class="h5 mb-0">{{ number_format($summary['pending_transactions']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow border-left-danger">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Low Balance Accounts</div>
                    <div class="h5 mb-0">{{ number_format($summary['low_balance_accounts']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 mb-2">
                    <select class="form-select" id="filter_account" style="width:100%;">
                        <option value="">All Accounts</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-select" id="filter_type">
                        <option value="">All Types</option>
                        <option value="funding">Funding</option>
                        <option value="expense">Expense</option>
                        <option value="replenishment">Replenishment</option>
                        <option value="refund">Refund</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="retirement">Retirement</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-select" id="filter_status">
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
                    <input type="date" class="form-control" id="filter_from_date">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" class="form-control" id="filter_to_date">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="text" class="form-control" id="filter_q" placeholder="Search">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="pettyCashTable" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Voucher</th>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Payee</th>
                            <th>Reference</th>
                            <th>Expense A/C</th>
                            <th>Amount</th>
                            <th>Balance Hint</th>
                            <th>Status</th>
                            <th width="220">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@include('finance.petty_cash.partials.transaction_modal')
@include('finance.petty_cash.partials.edit_transaction_modal')
@endsection

@push('scripts')
<script>
$(function () {
    let table;

    function initFilterAccountSelect2() {
        let $filter = $('#filter_account');

        if (!$filter.length) return;
        if ($filter.hasClass('select2-hidden-accessible')) return;

        $filter.select2({
            theme: 'bootstrap-5',
            placeholder: 'All Accounts',
            allowClear: true,
            width: '100%',
            dropdownParent: $filter.parent(),
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

    function destroyFilterAccountSelect2() {
        let $filter = $('#filter_account');

        if (!$filter.length) return;

        if ($filter.hasClass('select2-hidden-accessible')) {
            $filter.select2('close');
            $filter.select2('destroy');
        }

        $filter.siblings('.select2').remove();
    }

    initFilterAccountSelect2();

    table = $('#pettyCashTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.finance.petty_cash.index') }}",
            data: function (d) {
                d.petty_cash_account_id = $('#filter_account').val();
                d.type = $('#filter_type').val();
                d.status = $('#filter_status').val();
                d.from_date = $('#filter_from_date').val();
                d.to_date = $('#filter_to_date').val();
                d.q = $('#filter_q').val();
            }
        },
        columns: [
            {data: 'transaction_no', name: 'transaction_no'},
            {data: 'voucher_no', name: 'voucher_no'},
            {data: 'transaction_date', name: 'transaction_date'},
            {data: 'account_name', name: 'account.name'},
            {data: 'type', name: 'type'},
            {data: 'payee_name', name: 'payee', orderable: false},
            {data: 'reference_no', name: 'reference_no'},
            {data: 'expense_account_name', name: 'expenseAccount.name'},
            {data: 'amount', name: 'amount', className: 'text-end'},
            {data: 'balance_hint', name: 'balance_hint', orderable: false, searchable: false},
            {data: 'status', name: 'status'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ]
    });

    $(document).on('change', '#filter_account, #filter_type, #filter_status, #filter_from_date, #filter_to_date', function () {
        table.ajax.reload();
    });

    $('#filter_q').on('keyup', function () {
        table.ajax.reload();
    });

    $('#transactionModal, #editTransactionModal').on('show.bs.modal', function () {
        destroyFilterAccountSelect2();
    });

    $('#transactionModal, #editTransactionModal').on('hidden.bs.modal', function () {
        initFilterAccountSelect2();
    });

    $('#transactionForm').submit(function(e){
        e.preventDefault();
        
        let amount = parseFloat($('[name="amount"]').val() || 0);
        let type = $('#typeField').val();
    
        if (type === 'expense' && amount > currentBalance) {
            Swal.fire('Error', 'Insufficient petty cash balance.', 'error');
            return;
        }
        
        let fd = new FormData(this);

        $.ajax({
            url: "{{ route('admin.finance.petty_cash.store') }}",
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            beforeSend: function(){
                Swal.fire({title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
            },
            success: function(res){
                Swal.fire('Success', res.message, 'success');
                $('#transactionModal').modal('hide');
                $('#transactionForm')[0].reset();

                if ($('#petty_cash_account_id').length && $('#petty_cash_account_id').hasClass('select2-hidden-accessible')) {
                    $('#petty_cash_account_id').val(null).trigger('change');
                }

                if ($('#payee_id').length && $('#payee_id').hasClass('select2-hidden-accessible')) {
                    $('#payee_id').val(null).trigger('change');
                }

                table.ajax.reload();
            },
            error: function(xhr){
                Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong.', 'error');
            }
        });
    });

    function initEditPayeeSelect2(selectedId = null, selectedText = null) {
        let $el = $('#edit_payee_id');

        if (!$el.length) return;

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

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
            $('#edit_payee_id').val(null).trigger('change');
        }
    }

    $(document).on('click', '.btn-edit-transaction', function(){
        let id = $(this).data('id');

        $.get(`/admin/finance/petty-cash/${id}/edit`, function(res){
            if(!res.success){
                Swal.fire('Error', res.message, 'error');
                return;
            }

            let d = res.data;
            $('#edit_id').val(d.id);
            $('#edit_transaction_date').val(d.transaction_date);
            $('#edit_type').val(d.type).trigger('change');
            $('#edit_reference_no').val(d.reference_no);
            $('#edit_amount').val(d.amount);
            $('#edit_description').val(d.description);
            $('#edit_expense_account_id').val(d.expense_account_id);
            $('#edit_status').val(d.status);

            $('#edit_payee_type').val(d.payee_type || 'other');
            $('#edit_payee_text').val(d.payee || '');

            toggleEditExpenseField();
            toggleEditPayeeFields();

            if (['employee','supplier','customer'].includes(d.payee_type)) {
                initEditPayeeSelect2(d.payee_id, d.payee_display || 'Selected Payee');
            }

            $('#editTransactionModal').modal('show');
        }).fail(function(xhr){
            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load record.', 'error');
        });
    });

    $('#editTransactionForm').submit(function(e){
        e.preventDefault();

        let id = $('#edit_id').val();
        let fd = new FormData(this);
        fd.append('_method', 'PUT');

        $.ajax({
            url: `/admin/finance/petty-cash/${id}`,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            beforeSend: function(){
                Swal.fire({title: 'Updating...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
            },
            success: function(res){
                Swal.fire('Success', res.message, 'success');
                $('#editTransactionModal').modal('hide');
                $('#editTransactionForm')[0].reset();
                table.ajax.reload();
            },
            error: function(xhr){
                Swal.fire('Error', xhr.responseJSON?.message || 'Update failed.', 'error');
            }
        });
    });

    $('#edit_type').on('change', toggleEditExpenseField);

    $('#edit_payee_type').on('change', function () {
        toggleEditPayeeFields();
        if (['employee','supplier','customer'].includes($(this).val())) {
            initEditPayeeSelect2();
        }
    });

    // APPROVE
    $(document).on('click', '.btn-approve', function(){
        let id = $(this).data('id');

        Swal.fire({
            title: 'Approve Transaction?',
            icon: 'question',
            showCancelButton: true
        }).then((res)=>{
            if(res.isConfirmed){
                $.post(`/admin/finance/petty-cash/${id}/approve`, {
                    _token: $('meta[name="csrf-token"]').attr('content')
                }, function(res){
                    Swal.fire('Success', res.message, 'success');
                    $('#pettyCashTable').DataTable().ajax.reload();
                });
            }
        });
    });

    // REJECT
    $(document).on('click', '.btn-reject', function(){
        let id = $(this).data('id');

        Swal.fire({
            title: 'Reject Transaction?',
            input: 'textarea',
            inputPlaceholder: 'Enter reason...',
            showCancelButton: true
        }).then((res)=>{
            if(res.isConfirmed){
                $.post(`/admin/finance/petty-cash/${id}/reject`, {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    approval_notes: res.value
                }, function(res){
                    Swal.fire('Success', res.message, 'success');
                    $('#pettyCashTable').DataTable().ajax.reload();
                });
            }
        });
    });

    // POST
    $(document).on('click', '.btn-post', function(){
        let id = $(this).data('id');

        Swal.fire({
            title: 'Post Transaction?',
            text: 'This will affect accounts',
            icon: 'warning',
            showCancelButton: true
        }).then((res)=>{
            if(res.isConfirmed){
                $.post(`/admin/finance/petty-cash/${id}/post`, {
                    _token: $('meta[name="csrf-token"]').attr('content')
                }, function(res){
                    Swal.fire('Success', res.message, 'success');
                    $('#pettyCashTable').DataTable().ajax.reload();
                });
            }
        });
    });

    // DELETE
    $(document).on('click', '.btn-delete', function(){
        let id = $(this).data('id');

        Swal.fire({
            title: 'Delete Transaction?',
            icon: 'warning',
            showCancelButton: true
        }).then((res)=>{
            if(res.isConfirmed){
                $.ajax({
                    url: `/admin/finance/petty-cash/${id}`,
                    type: 'DELETE',
                    data: {_token: $('meta[name="csrf-token"]').attr('content')},
                    success: function(res){
                        Swal.fire('Deleted', res.message, 'success');
                        $('#pettyCashTable').DataTable().ajax.reload();
                    }
                });
            }
        });
    });
});
</script>
@endpush