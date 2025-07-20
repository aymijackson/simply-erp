<!-- resources/views/permissions/modals/add.blade.php -->

<div class="modal fade" id="addPermissionModal" tabindex="-1" aria-labelledby="addPermissionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addPermissionForm">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addPermissionModalLabel">Add New Permission</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="permission-name" class="form-label">Permission Name</label>
            <input type="text" id="permission-name" name="name" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Create Permission</button>
        </div>
      </div>
    </form>
  </div>
</div>
