<div class="modal fade" id="editTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="editTransactionForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="edit_id" name="id">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Petty Cash Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Transaction Date</label>
                            <input type="date" id="edit_transaction_date" name="transaction_date" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Type</label>
                            <select id="edit_type" name="type" class="form-select" required>
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
                            <input type="text" id="edit_reference_no" name="reference_no" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Payee Type</label>
                            <select name="payee_type" id="edit_payee_type" class="form-select">
                                <option value="other">Other</option>
                                <option value="employee">Employee</option>
                                <option value="supplier">Supplier</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3" id="editPayeeLookupWrap" style="display:none;">
                            <label>Select Payee</label>
                            <select name="payee_id" id="edit_payee_id" class="form-select" style="width:100%;"></select>
                        </div>

                        <div class="col-md-6 mb-3" id="editPayeeTextWrap">
                            <label>Payee Name</label>
                            <input type="text" name="payee" id="edit_payee_text" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Amount</label>
                            <input type="number" id="edit_amount" name="amount" step="0.01" min="0.01" class="form-control" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-md-6 mb-3" id="editExpenseWrap">
                            <label>Expense Account</label>
                            <select id="edit_expense_account_id" name="expense_account_id" class="form-select">
                                <option value="">Select</option>
                                @foreach($expenseAccounts as $gl)
                                    <option value="{{ $gl->id }}">{{ $gl->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Status</label>
                            <select id="edit_status" name="status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="pending">Submit for Approval</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Replace Attachment</label>
                            <input type="file" name="attachment" class="form-control">
                            <small class="text-muted">jpg, png, pdf, doc, docx, xls, xlsx</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">Update Transaction</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(function () {
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

    $('#edit_type').on('change', function () {
        toggleEditExpenseField();
    });

    $('#edit_payee_type').on('change', function () {
        toggleEditPayeeFields();

        if (['employee', 'supplier', 'customer'].includes($(this).val())) {
            initEditPayeeSelect2();
        }
    });

    $('#editTransactionModal').on('hidden.bs.modal', function () {
        $('#editTransactionForm')[0].reset();

        if ($('#edit_payee_id').hasClass('select2-hidden-accessible')) {
            $('#edit_payee_id').val(null).trigger('change');
        }

        toggleEditExpenseField();
        toggleEditPayeeFields();
    });
});
</script>
@endpush