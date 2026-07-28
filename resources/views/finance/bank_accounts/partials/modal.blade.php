{{-- resources/views/finance/bank_accounts/partials/modal.blade.php --}}
<div class="modal fade" id="bankModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bankModalTitle">New Bank/Cash Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="bankForm">

          {{-- IMPORTANT: no conflict with bank_id --}}
          <input type="hidden" id="bank_account_id" name="id">

          {{-- legacy mirror for display --}}
          <input type="hidden" id="bank_name" name="bank_name">

          {{-- optional country filter --}}
          <input type="hidden" id="bank_country" value="NG">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="text-muted small">Name</label>
              <input type="text" class="form-control" name="name" id="name" required placeholder="e.g. Zenith Bank - Main">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Type</label>
              <select class="form-control" name="type" id="type" required>
                <option value="bank">bank</option>
                <option value="cash">cash</option>
                <option value="wallet">wallet</option>
                <option value="mobile_money">mobile_money</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Currency</label>
              <select class="form-control" name="currency_code" id="currency_code"></select>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">GL Account</label>
              <select class="form-control" name="gl_account_id" id="gl_account_id"></select>
              <small class="text-muted">Ledger account affected by payments & reconciliation.</small>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Opening Balance</label>
              <input type="number" step="0.01" class="form-control" name="opening_balance" id="opening_balance" value="0.00">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Opening Balance Date</label>
              <input type="date" class="form-control" name="opening_balance_date" id="opening_balance_date">
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Bank</label>
              <select class="form-control" name="bank_id" id="bank_id_select"></select>
              <small class="text-muted">Select from bank list (instead of typing).</small>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Account Number</label>
              <input type="text" class="form-control" name="account_number" id="account_number" placeholder="e.g. 0123456789">
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Sort Code</label>
              <input type="text" class="form-control" name="sort_code" id="sort_code">
            </div>

            <div class="col-md-4">
              <label class="text-muted small">IBAN</label>
              <input type="text" class="form-control" name="iban" id="iban">
            </div>

            <div class="col-md-4">
              <label class="text-muted small">SWIFT</label>
              <input type="text" class="form-control" name="swift" id="swift">
            </div>

            <div class="col-md-12">
              <label class="text-muted small">Notes</label>
              <textarea class="form-control" name="notes" id="notes" rows="2" placeholder="Optional notes..."></textarea>
            </div>

            <div class="col-md-4">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                <label class="form-check-label" for="is_active">Active</label>
              </div>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveBankBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>