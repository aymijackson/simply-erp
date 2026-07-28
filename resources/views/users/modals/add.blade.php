{{-- resources/views/users/modals/add.blade.php --}}
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form id="addUserForm">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0">Add New User</h5>
            <small class="text-muted">Create a user, set access, and assign roles, permissions and module access.</small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          {{-- Tabs --}}
          <ul class="nav nav-tabs" id="addUserTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#addTabDetails" type="button" role="tab">
                Details
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#addTabRoles" type="button" role="tab">
                Roles <span class="badge bg-secondary" id="addRolesCountBadge">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#addTabPerms" type="button" role="tab">
                Permissions <span class="badge bg-secondary" id="addPermsCountBadge">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#addTabModules" type="button" role="tab">
                Modules <span class="badge bg-secondary" id="addModulesCountBadge">0</span>
              </button>
            </li>
          </ul>

          <div class="tab-content pt-3">

            {{-- DETAILS --}}
            <div class="tab-pane fade show active" id="addTabDetails" role="tabpanel">
              <div class="row">

                <div class="col-md-6 mb-3">
                  <label class="form-label">Name</label>
                  <input type="text" name="name" class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" required>
                </div>

                {{-- Access toggles (match edit modal style) --}}
                <div class="col-12 mb-3">
                  <div class="card border-0 bg-light">
                    <div class="card-body py-3">
                      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                          <div class="fw-semibold">System Access</div>
                          <small class="text-muted">Controls portal entry. Roles/permissions still control actions.</small>
                        </div>

                        <div class="d-flex flex-wrap gap-4">
                          <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox"
                                   id="addCanAccessErp" name="can_access_erp" value="1">
                            <label class="form-check-label" for="addCanAccessErp">Can access ERP</label>
                          </div>

                          <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox"
                                   id="addCanAccessAdmin" name="can_access_admin" value="1">
                            <label class="form-check-label" for="addCanAccessAdmin">Can access Admin</label>
                          </div>
                        </div>
                      </div>

                      <div class="alert alert-info mt-3 mb-0 py-2">
                        <small>
                          Tip: Roles usually grant most permissions. Use direct permissions only for exceptions.
                        </small>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- Password (enhanced, same UX as you requested) --}}
                <div class="col-md-6 mb-3">
                  <label class="form-label d-flex align-items-center justify-content-between">
                    <span>Password</span>
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnGenPassword">
                        <i class="fas fa-random me-1"></i> Generate
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnTogglePw">
                        <i class="fas fa-eye me-1"></i> Show
                      </button>
                    </div>
                  </label>

                  <input type="password"
                         name="password"
                         id="add_password"
                         class="form-control"
                         required
                         autocomplete="new-password">

                  <div class="mt-2">
                    <div class="progress" style="height:7px;">
                      <div class="progress-bar" id="pwStrengthBar" role="progressbar" style="width:0%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                      <small class="text-muted" id="pwStrengthText">Strength: -</small>
                      <small class="text-muted" id="pwTips"></small>
                    </div>
                  </div>

                  <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnUsePassword" disabled>
                      Use password (fill confirm)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopyPassword" disabled>
                      Copy
                    </button>
                    <small class="text-muted" id="pwGeneratedHint"></small>
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label d-flex align-items-center justify-content-between">
                    <span>Confirm Password</span>
                    <small class="text-muted" id="pwMatchText"></small>
                  </label>

                  <input type="password"
                         name="password_confirmation"
                         id="add_password_confirmation"
                         class="form-control"
                         required
                         autocomplete="new-password">
                </div>

              </div>
            </div>

            {{-- ROLES --}}
            <div class="tab-pane fade" id="addTabRoles" role="tabpanel">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <div class="flex-grow-1">
                  <input type="text" class="form-control form-control-sm"
                         placeholder="Search roles..."
                         data-filter-input="add" data-filter-target="roles">
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-select-visible="add" data-select-target="roles" data-select-value="1">
                  Select visible
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-select-visible="add" data-select-target="roles" data-select-value="0">
                  Clear visible
                </button>
                <span class="ms-auto small text-muted" id="addRolesMeta">0 selected</span>
              </div>

              <div class="row g-1" data-filter-list="add" data-filter-target="roles">
                @foreach($roles as $role)
                  <div class="col-lg-4 col-md-6">
                    <div class="form-check">
                      <input type="checkbox"
                             class="form-check-input role-check"
                             name="roles[]" value="{{ $role->id }}"
                             id="addRole_{{ $role->id }}"
                             data-filter-item="add" data-filter-target="roles"
                             data-filter-text="{{ strtolower($role->name) }}">
                      <label class="form-check-label" for="addRole_{{ $role->id }}">{{ $role->name }}</label>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            {{-- PERMISSIONS (Grouped like edit modal) --}}
            <div class="tab-pane fade" id="addTabPerms" role="tabpanel">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <div class="flex-grow-1">
                  <input type="text" class="form-control form-control-sm"
                         placeholder="Search permissions (e.g., inventory.stock, crm.leads, core.locations)..."
                         data-filter-input="add" data-filter-target="perms">
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary"
                        data-select-visible="add" data-select-target="perms" data-select-value="1">
                  Select visible
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-select-visible="add" data-select-target="perms" data-select-value="0">
                  Clear visible
                </button>

                <span class="ms-auto small text-muted" id="addPermsMeta">0 selected</span>
              </div>

              @php
                $permGroups = $permissions->groupBy(function($p){
                  $name = $p->name ?? '';
                  return explode('.', $name)[0] ?: 'other';
                })->sortKeys();
              @endphp

              <div class="accordion" id="addPermsAccordion" data-filter-list="add" data-filter-target="perms">
                @foreach($permGroups as $groupName => $groupPerms)
                  @php
                    $gid = 'addPermGroup_' . preg_replace('/[^a-zA-Z0-9_]/','_', $groupName);
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
                                data-group-count="add"
                                data-group="{{ strtolower($groupName) }}">0/0</span>
                        </button>

                        <div class="d-flex gap-2">
                          <button type="button"
                                  class="btn btn-sm btn-outline-secondary"
                                  data-group-select="add"
                                  data-group="{{ strtolower($groupName) }}"
                                  data-select-value="1">
                            Select group
                          </button>

                          <button type="button"
                                  class="btn btn-sm btn-outline-secondary"
                                  data-group-select="add"
                                  data-group="{{ strtolower($groupName) }}"
                                  data-select-value="0">
                            Clear group
                          </button>
                        </div>
                      </div>
                    </h2>

                    <div id="{{ $gid }}_body" class="accordion-collapse collapse" data-bs-parent="#addPermsAccordion">
                      <div class="accordion-body">
                        <div class="row g-1">
                          @foreach($groupPerms as $permission)
                            @php $pname = $permission->name; @endphp
                            <div class="col-lg-4 col-md-6">
                              <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input perm-check"
                                       name="permissions[]" value="{{ $permission->id }}"
                                       id="addPerm_{{ $permission->id }}"
                                       data-filter-item="add" data-filter-target="perms"
                                       data-filter-text="{{ strtolower($pname) }}"
                                       data-perm-group="{{ strtolower($groupName) }}">
                                <label class="form-check-label" for="addPerm_{{ $permission->id }}">{{ $pname }}</label>
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

            {{-- MODULES (same style as edit modal) --}}
            <div class="tab-pane fade" id="addTabModules" role="tabpanel">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <div class="flex-grow-1">
                  <input type="text" class="form-control form-control-sm"
                         placeholder="Search modules..."
                         data-filter-input="add" data-filter-target="modules">
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary"
                        data-select-visible="add" data-select-target="modules" data-select-value="1">
                  Select visible
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-select-visible="add" data-select-target="modules" data-select-value="0">
                  Clear visible
                </button>

                <span class="ms-auto small text-muted" id="addModulesMeta">0 selected</span>
              </div>

              <div class="row g-1" data-filter-list="add" data-filter-target="modules">
                @foreach($modules as $module)
                  <div class="col-lg-4 col-md-6">
                    <div class="form-check">
                      <input type="checkbox"
                             class="form-check-input module-check"
                             name="modules[]" value="{{ $module->id }}"
                             id="addModule_{{ $module->id }}"
                             data-filter-item="add" data-filter-target="modules"
                             data-filter-text="{{ strtolower($module->name) }}">
                      <label class="form-check-label" for="addModule_{{ $module->id }}">{{ $module->name }}</label>
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
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Create User
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const $modal = $('#addUserModal');

  const $pw    = $('#add_password');
  const $cpw   = $('#add_password_confirmation');

  const $bar   = $('#pwStrengthBar');
  const $txt   = $('#pwStrengthText');
  const $tips  = $('#pwTips');

  const $match = $('#pwMatchText');

  const $btnGen  = $('#btnGenPassword');
  const $btnUse  = $('#btnUsePassword');
  const $btnCopy = $('#btnCopyPassword');
  const $btnEye  = $('#btnTogglePw');
  const $hint    = $('#pwGeneratedHint');

  function scorePassword(pw) {
    pw = pw || '';
    let score = 0;
    const tips = [];

    if (pw.length >= 12) score += 30; else tips.push('Use 12+ chars');
    if (pw.length >= 16) score += 10;

    const hasLower = /[a-z]/.test(pw);
    const hasUpper = /[A-Z]/.test(pw);
    const hasNum   = /\d/.test(pw);
    const hasSym   = /[^A-Za-z0-9]/.test(pw);

    const variety = [hasLower, hasUpper, hasNum, hasSym].filter(Boolean).length;
    score += variety * 15;

    if (/(.)\1\1/.test(pw)) { score -= 10; tips.push('Avoid repeats'); }
    if (/password|admin|qwerty|12345/i.test(pw)) { score -= 30; tips.push('Avoid common words'); }

    score = Math.max(0, Math.min(100, score));

    let label = 'Weak';
    if (score >= 75) label = 'Strong';
    else if (score >= 50) label = 'Good';
    else if (score >= 30) label = 'Fair';

    return { score, label, tips };
  }

  function renderMatch() {
    const pw  = $pw.val() || '';
    const cpw = $cpw.val() || '';

    if (!pw && !cpw) { $match.text('').removeClass('text-success text-danger'); return; }
    if (!cpw) { $match.text('').removeClass('text-success text-danger'); return; }

    if (pw === cpw) {
      $match.text('Match').removeClass('text-danger').addClass('text-success');
    } else {
      $match.text('Not matching').removeClass('text-success').addClass('text-danger');
    }
  }

  function renderStrength() {
    const pw = $pw.val() || '';
    const { score, label, tips } = scorePassword(pw);

    $bar.css('width', score + '%');
    $bar.removeClass('bg-danger bg-warning bg-info bg-success');

    if (score >= 75) $bar.addClass('bg-success');
    else if (score >= 50) $bar.addClass('bg-info');
    else if (score >= 30) $bar.addClass('bg-warning');
    else $bar.addClass('bg-danger');

    $txt.text(`Strength: ${label} (${score}%)`);
    $tips.text(tips.slice(0,2).join(' • '));

    const hasPw = pw.length > 0;
    $btnUse.prop('disabled', !hasPw);
    $btnCopy.prop('disabled', !hasPw);

    renderMatch();
  }

  function generatePassword(len = 16) {
    const lower = 'abcdefghijkmnopqrstuvwxyz';
    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const nums  = '23456789';
    const syms  = '!@#$%^&*()-_=+[]{};:,.?';

    const pick = (str) => str[Math.floor(Math.random() * str.length)];

    let out = [pick(lower), pick(upper), pick(nums), pick(syms)];
    const all = lower + upper + nums + syms;

    while (out.length < len) out.push(pick(all));
    out = out.sort(() => Math.random() - 0.5);

    return out.join('');
  }

  async function copyToClipboard(text) {
    try {
      await navigator.clipboard.writeText(text);
      Swal.fire({ icon:'success', title:'Copied', timer:1200, showConfirmButton:false });
    } catch (e) {
      const $tmp = $('<input>');
      $('body').append($tmp);
      $tmp.val(text).select();
      document.execCommand('copy');
      $tmp.remove();
      Swal.fire({ icon:'success', title:'Copied', timer:1200, showConfirmButton:false });
    }
  }

  $pw.on('input', renderStrength);
  $cpw.on('input', renderMatch);

  $btnGen.on('click', function () {
    const pw = generatePassword(16);
    $pw.val(pw);
    $hint.text('Generated password ready.');
    renderStrength();
  });

  $btnUse.on('click', function () {
    const pw = $pw.val() || '';
    $cpw.val(pw);
    renderMatch();
    Swal.fire({ icon:'success', title:'Confirm password filled', timer:1200, showConfirmButton:false });
  });

  $btnCopy.on('click', function () {
    const pw = $pw.val() || '';
    if (!pw) return;
    copyToClipboard(pw);
  });

  $btnEye.on('click', function () {
    const isHidden = $pw.attr('type') === 'password';
    const type = isHidden ? 'text' : 'password';
    $pw.attr('type', type);
    $cpw.attr('type', type);

    $(this).html(isHidden
      ? '<i class="fas fa-eye-slash me-1"></i> Hide'
      : '<i class="fas fa-eye me-1"></i> Show'
    );
  });

  $modal.on('shown.bs.modal', function () {
    $hint.text('');
    renderStrength();
    renderMatch();

    // default toggles OFF
    $('#addCanAccessErp').prop('checked', false);
    $('#addCanAccessAdmin').prop('checked', false);
  });

  $modal.on('hidden.bs.modal', function () {
    $('#addCanAccessErp').prop('checked', false);
    $('#addCanAccessAdmin').prop('checked', false);

    $pw.val('');
    $cpw.val('');
    $hint.text('');
    $match.text('').removeClass('text-success text-danger');
    $bar.css('width','0%').removeClass('bg-danger bg-warning bg-info bg-success');
    $txt.text('Strength: -');
    $tips.text('');
    $btnUse.prop('disabled', true);
    $btnCopy.prop('disabled', true);
    $pw.attr('type','password');
    $cpw.attr('type','password');
    $btnEye.html('<i class="fas fa-eye me-1"></i> Show');
  });
})();
</script>
@endpush
