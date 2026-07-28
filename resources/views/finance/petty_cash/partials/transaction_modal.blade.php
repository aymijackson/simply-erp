<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="transactionForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Petty Cash Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Petty Cash Account</label>
                            <select name="petty_cash_account_id" id="petty_cash_account_id" class="form-select" required style="width:100%;">
                                <option value="">Select Petty Cash Account</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-2">
                            <small id="balanceInfo" class="text-muted"></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Transaction Date</label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Type</label>
                            <select name="type" class="form-select" id="typeField" required>
                                <option value="">Select</option>
                                <option value="funding">Funding</option>
                                <option value="expense">Expense</option>
                                <option value="replenishment">Replenishment</option>
                                <option value="refund">Refund</option>
                                <option value="adjustment">Adjustment</option>
                                <option value="retirement">Retirement</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Reference No</label>
                            <input type="text" name="reference_no" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Payee Type</label>
                            <select name="payee_type" id="payee_type" class="form-select">
                                <option value="other">Other</option>
                                <option value="employee">Employee</option>
                                <option value="supplier">Supplier</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3" id="payeeLookupWrap" style="display:none;">
                            <label>Select Payee</label>
                            <select name="payee_id" id="payee_id" class="form-select" style="width:100%;"></select>
                        </div>

                        <div class="col-md-6 mb-3" id="payeeTextWrap">
                            <label>Payee Name</label>
                            <input type="text" name="payee" id="payee_text" class="form-control" placeholder="Enter payee name">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Amount</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-md-6 mb-3" id="expenseAccountWrap">
                            <label>Expense Account</label>
                            <select name="expense_account_id" class="form-select">
                                <option value="">Select</option>
                                @foreach($expenseAccounts as $gl)
                                    <option value="{{ $gl->id }}">{{ $gl->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Status</label>
                            <select name="status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="pending">Submit for Approval</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Attachment</label>
                            <input type="file" name="attachment" class="form-control">
                            <small class="text-muted">jpg, png, pdf, doc, docx, xls, xlsx</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">Save Transaction</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    function initPettyCashAccountSelect2() {
        let $el = $('#petty_cash_account_id');

        if (!$el.length) return;

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#transactionModal'),
            placeholder: 'Select Petty Cash Account',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('admin.finance.petty_cash.accounts.select2') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
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
    }

    function initPayeeSelect2() {
        let $el = $('#payee_id');

        if (!$el.length) return;

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#transactionModal'),
            placeholder: 'Select Payee',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('admin.finance.petty_cash.payees.select2') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        type: $('#payee_type').val(),
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
    }

    function toggleExpenseField() {
        let type = $('#typeField').val();
        if (type === 'expense') {
            $('#expenseAccountWrap').show();
        } else {
            $('#expenseAccountWrap').hide();
            $('#expenseAccountWrap select[name="expense_account_id"]').val('').trigger('change');
        }
    }

    function togglePayeeFields() {
        let type = $('#payee_type').val();

        if (['employee', 'supplier', 'customer'].includes(type)) {
            $('#payeeLookupWrap').show();
            $('#payeeTextWrap').hide();
            $('#payee_text').val('');
            initPayeeSelect2();
        } else {
            $('#payeeLookupWrap').hide();
            $('#payeeTextWrap').show();
            if ($('#payee_id').hasClass('select2-hidden-accessible')) {
                $('#payee_id').val(null).trigger('change');
            }
        }
    }
    
    function lockAccountField(accountId = null) {
        if (accountId) {
            $('#petty_cash_account_id').prop('disabled', true);
        } else {
            $('#petty_cash_account_id').prop('disabled', false);
        }
    }

    $('#transactionModal').on('shown.bs.modal', function () {
        initPettyCashAccountSelect2();
        toggleExpenseField();
        togglePayeeFields();
    });

    $('#typeField').on('change', toggleExpenseField);

    $('#payee_type').on('change', function () {
        togglePayeeFields();
    });

    $('#transactionModal').on('hidden.bs.modal', function () {
        $('#transactionForm')[0].reset();

        if ($('#petty_cash_account_id').hasClass('select2-hidden-accessible')) {
            $('#petty_cash_account_id').val(null).trigger('change');
        }

        if ($('#payee_id').hasClass('select2-hidden-accessible')) {
            $('#payee_id').val(null).trigger('change');
        }

        $('#petty_cash_account_id').prop('disabled', false);

        $('#expenseAccountWrap select[name="expense_account_id"]').val('').trigger('change');
        toggleExpenseField();
        togglePayeeFields();
    });
    
    let currentBalance = 0;
    let minimumBalance = 0;
    
    // FETCH BALANCE WHEN ACCOUNT CHANGES
    $(document).on('change', '#petty_cash_account_id', function () {
        let accountId = $(this).val();
    
        if (!accountId) {
            $('#balanceInfo').html('');
            return;
        }
    
        $.get(`/admin/finance/petty-cash/accounts/${accountId}/balance`, function (res) {
            currentBalance = parseFloat(res.balance);
            minimumBalance = parseFloat(res.minimum_balance);
    
            $('#balanceInfo').html(
                `Balance: <strong>${currentBalance.toFixed(2)}</strong> | Min: ${minimumBalance.toFixed(2)}`
            );
        });
    });
    
    // VALIDATE AMOUNT
    $(document).on('input', '[name="amount"]', function () {
        let amount = parseFloat($(this).val() || 0);
        let type = $('#typeField').val();
    
        if (type !== 'expense') return;
    
        if (amount > currentBalance) {
            $('#balanceInfo').html(
                `<span class="text-danger fw-bold">
                    Insufficient balance! Available: ${currentBalance.toFixed(2)}
                </span>`
            );
        } else if ((currentBalance - amount) < minimumBalance) {
            $('#balanceInfo').html(
                `<span class="text-warning fw-bold">
                    Warning: Balance will fall below minimum (${minimumBalance.toFixed(2)})
                </span>`
            );
        } else {
            $('#balanceInfo').html(
                `<span class="text-success">
                    Remaining: ${(currentBalance - amount).toFixed(2)}
                </span>`
            );
        }
    });
});
</script>
@endpush