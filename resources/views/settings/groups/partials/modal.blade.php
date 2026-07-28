<div class="modal fade" id="groupModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="groupModalTitle">New Group</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="groupForm">
          <input type="hidden" id="group_id" name="id">

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="text-muted small">Module</label>
              <input type="text" class="form-control" name="module" id="module" required placeholder="e.g sales, finance, inventory">
              <small class="text-muted">Lowercase recommended.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Code</label>
              <input type="text" class="form-control" name="code" id="code" required placeholder="e.g receipt, tax, numbering">
              <small class="text-muted">Unique per module.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Sort Order</label>
              <input type="number" class="form-control" name="sort_order" id="sort_order" value="0">
            </div>

            <div class="col-md-12 mb-3">
              <label class="text-muted small">Name</label>
              <input type="text" class="form-control" name="name" id="name" required placeholder="e.g Receipt Settings">
            </div>

            <div class="col-md-12 mb-3">
              <label class="text-muted small">Description</label>
              <input type="text" class="form-control" name="description" id="description" placeholder="Optional short explanation">
            </div>

            <div class="col-md-12">
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
        <button type="button" class="btn btn-primary" id="saveGroupBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>
