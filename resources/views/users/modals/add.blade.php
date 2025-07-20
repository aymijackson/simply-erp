<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="addUserForm">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row">
          <div class="col-md-6 mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
          </div>
          <div class="col-md-12 mb-3">
            <label>Assign Roles</label>
            <div class="row">
              @foreach($roles as $role)
                <div class="col-4 form-check">
                  <input type="checkbox" class="form-check-input" name="roles[]" value="{{ $role->id }}" id="addRole_{{ $role->id }}">
                  <label class="form-check-label" for="addRole_{{ $role->id }}">{{ $role->name }}</label>
                </div>
              @endforeach
            </div>
          </div>
          <div class="col-md-12 mb-3">
            <label>Assign Permissions</label>
            <div class="row">
              @foreach($permissions as $permission)
                <div class="col-4 form-check">
                  <input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $permission->id }}" id="addPerm_{{ $permission->id }}">
                  <label class="form-check-label" for="addPerm_{{ $permission->id }}">{{ $permission->name }}</label>
                </div>
              @endforeach
            </div>
          </div>
          <div class="col-md-12 mb-3">
            <label>Assign Modules</label>
            <div class="row">
              @foreach($modules as $module)
                <div class="col-4 form-check">
                  <input type="checkbox" class="form-check-input" name="modules[]" value="{{ $module->id }}" id="addModule_{{ $module->id }}">
                  <label class="form-check-label" for="addModule_{{ $module->id }}">{{ $module->name }}</label>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Create User</button>
        </div>
      </div>
    </form>
  </div>
</div>