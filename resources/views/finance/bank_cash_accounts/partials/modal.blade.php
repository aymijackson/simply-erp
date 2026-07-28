<div class="modal fade" id="bcModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="bcModalTitle">New Bank/Cash Account</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="bcForm">
          <input type="hidden" name="id" id="bc_id">

          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="text-muted small">Type</label>
              <select class="form-control" name="type" id="type" required>
                <option value="bank">bank</option>
                <option value="cash">cash</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Name</label>
              <input type="text" class="form-control" name="name" id="name" required placeholder="e.g. Zenith Bank - Main">
            </div>

            <div class="col-md-3 mb-3">
              <label class="text-muted small">Currency</label>
              <input type="text" class="form-control" name="currency" id="currency" value="NGN">
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Bank Name</label>
              <input type="text" class="form-control" name="bank_name" id="bank_name" placeholder="e.g. Zenith Bank">
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Account No</label>
              <input type="text" class="form-control" name="account_no" id="account_no" placeholder="e.g. 0123456789">
            </div>

            <div class="col-md-8 mb-3">
              <label class="text-muted small">GL Account</label>
              <select class="form-control" name="gl_account_id" id="gl_account_id" required>
                <option value="">— Select —</option>
                @foreach($glAccounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
              <small class="text-muted">This links this bank/cash account to a GL account in your chart of accounts.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Opening Balance</label>
              <input type="number" step="0.01" class="form-control" name="opening_balance" id="opening_balance" value="0.00">
            </div>

            <div class="col-md-4 mb-2">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1">
                <label class="custom-control-label" for="is_default">Default</label>
              </div>
              <small class="text-muted d-block">Only one can be default.</small>
            </div>

            <div class="col-md-4 mb-2">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                <label class="custom-control-label" for="is_active">Active</label>
              </div>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveBcBtn">
          <i class="fas fa-save me-1"></i> Save
        </button>
      </div>

    </div>
  </div>
</div>