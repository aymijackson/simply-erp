<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="accountForm">
            @csrf
            <input type="hidden" id="account_id" name="account_id">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountModalTitle">Edit Petty Cash Account</h5>
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
                                @foreach(\Modules\Finance\Models\FinanceAccount::orderBy('name')->get() as $gl)
                                    <option value="{{ $gl->id }}">{{ $gl->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Expense Clearing GL</label>
                            <select name="gl_expense_clearing_account_id" id="gl_expense_clearing_account_id" class="form-select">
                                <option value="">Select</option>
                                @foreach(\Modules\Finance\Models\FinanceAccount::orderBy('name')->get() as $gl)
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
                            <input type="number" step="0.01" min="0" name="minimum_balance" id="minimum_balance" class="form-control">
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
                                <input type="checkbox" class="form-check-input" id="auto_replenish_suggestion" name="auto_replenish_suggestion" value="1">
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
                    <button class="btn btn-primary" id="accountSubmitBtn">Update Account</button>
                </div>
            </div>
        </form>
    </div>
</div>