@extends('layouts.master')

@section('title','Petty Cash Accounts')

@push('styles')
<style>
    .select2-container {
        width: 100% !important;
    }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between mb-3">
        <h1 class="h3 text-primary">Petty Cash Accounts</h1>

        @can('finance.petty_cash.accounts.manage')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#accountModal">
            <i class="fas fa-plus"></i> New Account
        </button>
        @endcan
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="accountsTable" width="100%">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Custodian</th>
                            <th>GL Account</th>
                            <th>Float</th>
                            <th>Min Balance</th>
                            <th>Current</th>
                            <th>Status</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@can('finance.petty_cash.accounts.manage')
<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="accountForm">
            @csrf
            <input type="hidden" id="account_id" name="account_id">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountModalTitle">New Petty Cash Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Account Code</label>
                            <input type="text" name="account_code" id="account_code" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Account Name</label>
                            <input type="text" name="name" id="account_name" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Cash GL Account</label>
                            <select name="gl_cash_account_id" id="gl_cash_account_id" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($glAccounts as $gl)
                                    <option value="{{ $gl->id }}">{{ $gl->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Expense Clearing GL</label>
                            <select name="gl_expense_clearing_account_id" id="gl_expense_clearing_account_id" class="form-select">
                                <option value="">Select</option>
                                @foreach($glAccounts as $gl)
                                    <option value="{{ $gl->id }}">{{ $gl->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Float Amount</label>
                            <input type="number" step="0.01" min="0" name="float_amount" id="float_amount" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Minimum Balance</label>
                            <input type="number" step="0.01" min="0" name="minimum_balance" id="minimum_balance" class="form-control" value="0">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Status</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="auto_replenish_suggestion" name="auto_replenish_suggestion" value="1" checked>
                                <label class="form-check-label" for="auto_replenish_suggestion">
                                    Enable low balance replenishment suggestion
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" id="accountSubmitBtn">Save Account</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
$(function(){
    let table = $('#accountsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.finance.petty_cash.accounts') }}",
        columns: [
            {data:'account_code', name:'account_code'},
            {data:'name', name:'name'},
            {data:'custodian_name', name:'custodian_name', orderable:false, searchable:false},
            {data:'cash_gl_name', name:'cashGlAccount.name', orderable:false, searchable:false},
            {data:'float_amount', name:'float_amount', className:'text-end'},
            {data:'minimum_balance', name:'minimum_balance', className:'text-end'},
            {data:'current_balance', name:'current_balance', className:'text-end'},
            {data:'status', name:'status'},
            {data:'actions', name:'actions', orderable:false, searchable:false}
        ]
    });

    function resetAccountForm() {
        $('#accountForm')[0].reset();
        $('#account_id').val('');
        $('#accountModalTitle').text('New Petty Cash Account');
        $('#accountSubmitBtn').text('Save Account');
        $('#auto_replenish_suggestion').prop('checked', true);
    }

    $('#accountForm').on('submit', function(e){
        e.preventDefault();

        let id = $('#account_id').val();
        let isEdit = !!id;

        $.ajax({
            url: isEdit
                ? `/admin/finance/petty-cash/accounts/${id}`
                : "{{ route('admin.finance.petty_cash.accounts.store') }}",
            type: isEdit ? "POST" : "POST",
            data: isEdit
                ? $(this).serialize() + '&_method=PUT'
                : $(this).serialize(),
            beforeSend: function(){
                Swal.fire({
                    title: isEdit ? 'Updating...' : 'Saving...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function(res){
                Swal.fire('Success', res.message, 'success');
                $('#accountModal').modal('hide');
                resetAccountForm();
                table.ajax.reload();
            },
            error: function(xhr){
                Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-edit-account', function(){
        let id = $(this).data('id');

        $.get(`/admin/finance/petty-cash/accounts/${id}/edit`, function(res){
            if (!res.success) {
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
        }).fail(function(xhr){
            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load account.', 'error');
        });
    });

    $(document).on('click', '.btn-delete-account', function(){
        let id = $(this).data('id');

        Swal.fire({
            title: 'Delete Account?',
            text: 'This will only work if the account has no transactions or reconciliations.',
            icon: 'warning',
            showCancelButton: true
        }).then((res)=>{
            if(res.isConfirmed){
                $.ajax({
                    url: `/admin/finance/petty-cash/accounts/${id}`,
                    type: 'DELETE',
                    data: {_token: $('meta[name="csrf-token"]').attr('content')},
                    success: function(res){
                        Swal.fire('Deleted', res.message, 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr){
                        Swal.fire('Error', xhr.responseJSON?.message || 'Delete failed.', 'error');
                    }
                });
            }
        });
    });

    $('#accountModal').on('hidden.bs.modal', function(){
        resetAccountForm();
    });
});
</script>
@endpush