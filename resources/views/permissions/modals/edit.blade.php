<!-- resources/views/permissions/modals/edit.blade.php -->

<div class="modal fade" id="editPermissionModal" tabindex="-1" aria-labelledby="editPermissionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editPermissionForm">
      @csrf
      @method('PUT')
      <input type="hidden" id="edit-permission-id" name="id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editPermissionModalLabel">Edit Permission</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit-permission-name" class="form-label">Permission Name</label>
            <input type="text" id="edit-permission-name" name="name" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Update Permission</button>
        </div>
      </div>
    </form>
  </div>
</div>
