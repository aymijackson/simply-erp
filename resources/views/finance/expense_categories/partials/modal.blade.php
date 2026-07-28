<div class="modal fade" id="catModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="catModalTitle">New Expense Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="catForm">
          <input type="hidden" id="cat_id" name="id">

          <div class="row g-3">
            <div class="col-md-7">
              <label class="text-muted small">Category Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="name" required placeholder="e.g. Fuel, Utilities, Office Supplies">
            </div>

            <div class="col-md-5">
              <label class="text-muted small">GL Expense Account (optional)</label>
              <select class="form-control" name="gl_account_id" id="gl_account_id"></select>
              <small class="text-muted">Used as the default expense account during posting (can be overridden per line).</small>
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
        <button type="button" class="btn btn-primary" id="saveCatBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>