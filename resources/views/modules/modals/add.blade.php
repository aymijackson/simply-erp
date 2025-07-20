<div class="modal fade" id="addModuleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addModuleForm">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Module</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Slug</label>
            <input type="text" name="slug" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary">Create Module</button>
        </div>
      </div>
    </form>
  </div>
</div>
