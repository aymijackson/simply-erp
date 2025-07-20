<div class="modal fade" id="editModuleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editModuleForm">
      @csrf @method('PUT')
      <input type="hidden" id="editModuleId" name="module_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Module</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Name</label>
            <input type="text" id="editModuleName" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Slug</label>
            <input type="text" id="editModuleSlug" name="slug" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success">Update Module</button>
        </div>
      </div>
    </form>
  </div>
</div>
