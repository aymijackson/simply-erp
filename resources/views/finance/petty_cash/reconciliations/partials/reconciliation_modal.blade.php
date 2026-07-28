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