<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form id="editUserForm">
      @csrf @method('PUT')
      <input type="hidden" id="editUserId" name="user_id">

      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0">Edit User</h5>
            <small class="text-muted">Update user details, roles, permissions and module access.</small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          {{-- Tabs --}}
          <ul class="nav nav-tabs" id="editUserTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#editTabDetails" type="button" role="tab">Details</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#editTabRoles" type="button" role="tab">
                Roles <span class="badge bg-secondary" id="editRolesCountBadge">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#editTabPerms" type="button" role="tab">
                Permissions <span class="badge bg-secondary" id="editPermsCountBadge">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#editTabModules" type="button" role="tab">
                Modules <span class="badge bg-secondary" id="editModulesCountBadge">0</span>
              </button>
            </li>
          </ul>

          <div class="tab-content pt-3">

            {{-- DETAILS --}}
            <div class="tab-pane fade show active" id="editTabDetails" role="tabpanel">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Name</label>
                  <input type="text" id="editUserName" name="name" class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" id="editUserEmail" name="email" class="form-control" required>
                </div>
              </div>

              {{-- ✅ ACCESS CONTROL (ERP / ADMIN) --}}
              <div class="card border-0 bg-light mb-3">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="fw-semibold">Access Control</div>
                      <div class="text-muted small">
                        Controls whether the user can access ERP and/or Admin areas.
                        (Permissions still control what they can do inside.)
                      </div>
                    </div>
                    <span class="badge bg-secondary">Security</span>
                  </div>

                  <hr class="my-3">

                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="form-check form-switch">
                        {{-- Hidden input ensures a value is always sent --}}
                        <input type="hidden" name="can_access_erp" value="0">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               id="editCanAccessErp"
                               name="can_access_erp"
                               value="1">
                        <label class="form-check-label" for="editCanAccessErp">
                          Allow ERP Access
                        </label>
                      </div>
                      <div class="text-muted small mt-1">
                        Enables the ERP area (inventory, CRM, HRM, finance modules, etc.).
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-check form-switch">
                        <input type="hidden" name="can_access_admin" value="0">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               id="editCanAccessAdmin"
                               name="can_access_admin"
                               value="1">
                        <label class="form-check-label" for="editCanAccessAdmin">
                          Allow Admin Access
                        </label>
                      </div>
                      <div class="text-muted small mt-1">
                        Enables admin area access (system configuration / admin-only pages).
                      </div>
                    </div>
                  </div>

                  <div class="alert alert-warning mt-3 mb-0">
                    <strong>Note:</strong> Turning on access does not automatically grant permissions.
                    Assign a role/permission set for proper access control.
                  </div>
                </div>
              </div>

              <div class="alert alert-info mb-0">
                Tip: Roles usually grant most permissions. Use direct permissions only for exceptions.
              </div>
            </div>

            {{-- ROLES --}}
            <div class="tab-pane fade" id="editTabRoles" role="tabpanel">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <div class="flex-grow-1">
                  <input type="text" class="form-control form-control-sm"
                         placeholder="Search roles..."
                         data-filter-input="edit" data-filter-target="roles">
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-select-visible="edit" data-select-target="roles" data-select-value="1">
                  Select visible
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-select-visible="edit" data-select-target="roles" data-select-value="0">
                  Clear visible
                </button>

                <span class="ms-auto small text-muted" id="editRolesMeta">0 selected</span>
              </div>

              <div class="row g-1" data-filter-list="edit" data-filter-target="roles">
                @foreach($roles as $role)
                  <div class="col-lg-4 col-md-6">
                    <div class="form-check">
                      <input type="checkbox"
                             class="form-check-input role-check"
                             name="roles[]" value="{{ $role->id }}"
                             id="editRole_{{ $role->id }}"
                             data-filter-item="edit" data-filter-target="roles"
                             data-filter-text="{{ strtolower($role->name) }}">
                      <label class="form-check-label" for="editRole_{{ $role->id }}">{{ $role->name }}</label>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            {{-- PERMISSIONS (Grouped) --}}
            <div class="tab-pane fade" id="editTabPerms" role="tabpanel">

              <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <div class="flex-grow-1">
                  <input type="text" class="form-control form-control-sm"
                         placeholder="Search permissions (e.g., inventory.stock, crm.leads, core.locations)..."
                         data-filter-input="edit" data-filter-target="perms">
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary"
                        data-select-visible="edit" data-select-target="perms" data-select-value="1">
                  Select visible
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-select-visible="edit" data-select-target="perms" data-select-value="0">
                  Clear visible
                </button>

                <span class="ms-auto small text-muted" id="editPermsMeta">0 selected</span>
              </div>

              @php
                $permGroups = $permissions->groupBy(function($p){
                  $name = $p->name ?? '';
                  return explode('.', $name)[0] ?: 'other';
                })->sortKeys();
              @endphp

              <div class="accordion" id="editPermsAccordion" data-filter-list="edit" data-filter-target="perms">
                @foreach($permGroups as $groupName => $groupPerms)
                  @php
                    $gid = 'editPermGroup_' . preg_replace('/[^a-zA-Z0-9_]/','_', $groupName);
                  @endphp

                  <div class="accordion-item" data-perm-group="{{ strtolower($groupName) }}">
                    <h2 class="accordion-header" id="{{ $gid }}_head">
                      <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                        <button class="accordion-button collapsed p-0 flex-grow-1 bg-transparent shadow-none"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#{{ $gid }}_body"
                                aria-expanded="false"
                                aria-controls="{{ $gid }}_body">
                          <span class="me-2 text-capitalize">{{ $groupName }}</span>
                          <span class="badge bg-light text-dark ms-2"
                                data-group-count="edit"
                                data-group="{{ strtolower($groupName) }}">0/0</span>
                        </button>

                        <div class="d-flex gap-2">
                          <button type="button"
                                  class="btn btn-sm btn-outline-secondary"
                                  data-group-select="edit"
                                  data-group="{{ strtolower($groupName) }}"
                                  data-select-value="1">
                            Select group
                          </button>

                          <button type="button"
                                  class="btn btn-sm btn-outline-secondary"
                                  data-group-select="edit"
                                  data-group="{{ strtolower($groupName) }}"
                                  data-select-value="0">
                            Clear group
                          </button>
                        </div>
                      </div>
                    </h2>

                    <div id="{{ $gid }}_body" class="accordion-collapse collapse" aria-labelledby="{{ $gid }}_head"
                         data-bs-parent="#editPermsAccordion">
                      <div class="accordion-body">
                        <div class="row g-1">
                          @foreach($groupPerms as $permission)
                            @php $pname = $permission->name; @endphp
                            <div class="col-lg-4 col-md-6">
                              <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input perm-check"
                                       name="permissions[]" value="{{ $permission->id }}"
                                       id="editPerm_{{ $permission->id }}"
                                       data-filter-item="edit" data-filter-target="perms"
                                       data-filter-text="{{ strtolower($pname) }}"
                                       data-perm-group="{{ strtolower($groupName) }}">
                                <label class="form-check-label" for="editPerm_{{ $permission->id }}">
                                  {{ $pname }}
                                </label>
                              </div>
                            </div>
                          @endforeach
                        </div>
                      </div>
                    </div>

                  </div>
                @endforeach
              </div>
            </div>

            {{-- MODULES --}}
            <div class="tab-pane fade" id="editTabModules" role="tabpanel">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <div class="flex-grow-1">
                  <input type="text" class="form-control form-control-sm"
                         placeholder="Search modules..."
                         data-filter-input="edit" data-filter-target="modules">
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary"
                        data-select-visible="edit" data-select-target="modules" data-select-value="1">
                  Select visible
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-select-visible="edit" data-select-target="modules" data-select-value="0">
                  Clear visible
                </button>

                <span class="ms-auto small text-muted" id="editModulesMeta">0 selected</span>
              </div>

              <div class="row g-1" data-filter-list="edit" data-filter-target="modules">
                @foreach($modules as $module)
                  <div class="col-lg-4 col-md-6">
                    <div class="form-check">
                      <input type="checkbox"
                             class="form-check-input module-check"
                             name="modules[]" value="{{ $module->id }}"
                             id="editModule_{{ $module->id }}"
                             data-filter-item="edit" data-filter-target="modules"
                             data-filter-text="{{ strtolower($module->name) }}">
                      <label class="form-check-label" for="editModule_{{ $module->id }}">{{ $module->name }}</label>
                    </div>
                  </div>
                @endforeach
              </div>

              <div class="alert alert-warning mt-3 mb-0">
                Modules control sidebar visibility via <code>canAccessModule()</code>. Permissions still control actions inside each module.
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Update User
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
