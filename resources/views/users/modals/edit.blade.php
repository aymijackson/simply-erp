<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="editUserForm">
      @csrf @method('PUT')
      <input type="hidden" id="editUserId" name="user_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row">
          <div class="col-md-6 mb-3">
            <label>Name</label>
            <input type="text" id="editUserName" name="name" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>Email</label>
            <input type="email" id="editUserEmail" name="email" class="form-control" required>
          </div>
          <div class="col-md-12 mb-3">
            <label>Assign Roles</label>
            <div class="row">
              @foreach($roles as $role)
                <div class="col-4 form-check">
                  <input type="checkbox" class="form-check-input" name="roles[]" value="{{ $role->id }}" id="editRole_{{ $role->id }}">
                  <label class="form-check-label" for="editRole_{{ $role->id }}">{{ $role->name }}</label>
                </div>
              @endforeach
            </div>
          </div>
          <div class="col-md-12 mb-3">
            <label>Assign Permissions</label>
            <div class="row">
              @foreach($permissions as $permission)
                <div class="col-4 form-check">
                  <input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $permission->id }}" id="editPerm_{{ $permission->id }}">
                  <label class="form-check-label" for="editPerm_{{ $permission->id }}">{{ $permission->name }}</label>
                </div>
              @endforeach
            </div>
          </div>
          <div class="col-md-12 mb-3">
            <label>Assign Modules</label>
            <div class="row">
              @foreach($modules as $module)
                <div class="col-4 form-check">
                  <input type="checkbox" class="form-check-input" name="modules[]" value="{{ $module->id }}" id="editModule_{{ $module->id }}">
                  <label class="form-check-label" for="editModule_{{ $module->id }}">{{ $module->name }}</label>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Update User</button>
        </div>
      </div>
    </form>
  </div>
</div>