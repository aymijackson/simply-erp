<div class="modal fade" id="coaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="coaModalTitle">New Account</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="coaForm">
          <input type="hidden" id="account_id" name="id">

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="text-muted small">Type</label>
              <select class="form-control" name="account_type_id" id="account_type_id" required>
                @foreach($types as $t)
                  <option value="{{ $t->id }}">
                    {{ $t->name }} ({{ strtoupper($t->category) }} / {{ $t->normal_balance }})
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Code</label>
              <input type="text" class="form-control" name="code" id="code" required placeholder="e.g. 1100">
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Name</label>
              <input type="text" class="form-control" name="name" id="name" required placeholder="e.g. Accounts Receivable">
            </div>

            <div class="col-md-12 mb-3">
              <label class="text-muted small">Parent Account</label>
              <select class="form-control" name="parent_id" id="parent_id"></select>
              <small class="text-muted">Optional. Use parents to group accounts (Control accounts).</small>
            </div>

            <div class="col-md-12 mb-3">
              <label class="text-muted small">Description</label>
              <input type="text" class="form-control" name="description" id="description" placeholder="optional...">
            </div>

            <div class="col-md-4 mb-2">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="is_control" name="is_control" value="1">
                <label class="custom-control-label" for="is_control">Control Account</label>
              </div>
              <small class="text-muted">Control accounts are for grouping, not direct posting.</small>
            </div>

            <div class="col-md-4 mb-2">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="allow_manual_posting" name="allow_manual_posting" value="1" checked>
                <label class="custom-control-label" for="allow_manual_posting">Allow Manual Posting</label>
              </div>
              <small class="text-muted">If off, block manual JE lines.</small>
            </div>

            <div class="col-md-4 mb-2">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                <label class="custom-control-label" for="is_active">Active</label>
              </div>
              <small class="text-muted">Disable instead of deleting for audit safety.</small>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveAccountBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>

    </div>
  </div>
</div>