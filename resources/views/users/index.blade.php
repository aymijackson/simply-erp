@extends('layouts.master')

@section('title', 'Manage Users')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
<style>
  td { vertical-align: top; }
  .badge { white-space: normal; }
</style>
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5>Users Management</h5>
    <div>
      <button id="bulkDeleteBtn" class="btn btn-danger btn-sm me-2">Delete Selected</button>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
        Add New User
      </button>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table id="datatable" class="table table-striped table-bordered">
        <thead>
          <tr>
            <th><input type="checkbox" id="select-all"></th>
            <th>Name</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Permissions</th>
            <th>Access</th>
            <th>Modules</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

{{-- Permissions Details Modal (for DataTable "View") --}}
<div class="modal fade" id="permDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0" id="permDetailsTitle">Permissions</h5>
          <small class="text-muted">Grouped permission list for the selected user.</small>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="permDetailsBody"></div>
    </div>
  </div>
</div>

@include('users.modals.add')
@include('users.modals.edit')
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(function () {

  // -----------------------------
  // Global AJAX setup
  // -----------------------------
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
  });

  // -----------------------------
  // Bootstrap 5 modal helpers
  // -----------------------------
  function showModal(id){
    const el = document.getElementById(id);
    const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    modal.show();
  }
  function hideModal(id){
    const el = document.getElementById(id);
    const modal = bootstrap.Modal.getInstance(el);
    if (modal) modal.hide();
  }
  function resetToFirstTab(modalId){
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const firstTabBtn = modal.querySelector('.nav-tabs .nav-link');
    if (!firstTabBtn) return;

    const tab = bootstrap.Tab.getInstance(firstTabBtn) || new bootstrap.Tab(firstTabBtn);
    tab.show();
  }

  // -----------------------------
  // DataTable
  // -----------------------------
  const table = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("admin.users.list") }}',
    dom: 'Bfrtip',
    buttons: ['excel', 'pdf', 'print'],
    pageLength: 25,
    order: [[1, 'asc']],
    columns: [
      { data: 'checkbox', orderable: false, searchable: false },
      { data: 'name' },
      { data: 'email' },
      { data: 'roles', orderable: false, searchable: false },
      { data: 'permissions', orderable: false, searchable: false },
      { data: 'access', orderable: false, searchable: false },
      { data: 'modules', orderable: false, searchable: false },
      { data: 'actions', orderable: false, searchable: false }
    ]
  });

  // -----------------------------
  // Select all (current page)
  // -----------------------------
  $('#select-all').on('click', function() {
    const checked = this.checked;
    $('#datatable').find('input.user-check, input[name="ids[]"]').prop('checked', checked);
  });

  table.on('draw', function () {
    $('#select-all').prop('checked', false);
  });

  // -----------------------------
  // Bulk delete
  // -----------------------------
  $('#bulkDeleteBtn').on('click', function() {
    const ids = $('#datatable')
      .find('input.user-check:checked, input[name="ids[]"]:checked')
      .map(function() { return this.value; }).get();

    if (!ids.length) {
      return Swal.fire('Warning', 'No users selected.', 'warning');
    }

    Swal.fire({
      title: 'Delete selected users?',
      text: `You are about to delete ${ids.length} user(s).`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete!'
    }).then(({ isConfirmed }) => {
      if (!isConfirmed) return;

      $.ajax({
        url: '{{ route("admin.users.bulkDelete") }}',
        type: 'DELETE',
        data: { ids }
      }).done(res => {
        Swal.fire('Deleted', res.message || 'Users deleted!', 'success');
        table.ajax.reload(null, false);
      }).fail(xhr => {
        Swal.fire('Error', xhr.responseJSON?.message || 'Bulk delete failed.', 'error');
      });
    });
  });

  // -----------------------------
  // Add user
  // -----------------------------
  $(document).on('submit', '#addUserForm', function(e) {
    e.preventDefault();

    $.post('{{ route("admin.users.store") }}', $(this).serialize())
      .done(res => {
        hideModal('addUserModal');
        Swal.fire('Created', res.message || 'User created successfully!', 'success');
        table.ajax.reload(null, false);

        this.reset();
        $('#addUserModal').find('input[type="checkbox"]').prop('checked', false);
        resetToFirstTab('addUserModal');

        if (window.__updateUserModalCounts) window.__updateUserModalCounts('add');
      })
      .fail(xhr => {
        const errs = xhr.responseJSON?.errors;
        Swal.fire('Error', errs ? Object.values(errs).flat().join('<br/>') : 'Server error', 'error');
      });
  });

  // -----------------------------
  // Edit user (load)  ✅ UPDATED (ERP/Admin toggles)
  // -----------------------------
  $(document).on('click', '.edit-btn', function() {
  const id = $(this).data('id');

  $.get(`/admin/users/${id}/edit`, function(data) {
    $('#editUserId').val(data.user.id);
    $('#editUserName').val(data.user.name);
    $('#editUserEmail').val(data.user.email);

    // ✅ access toggles (robust)
    $('#editCanAccessErp').prop('checked', false);
    $('#editCanAccessAdmin').prop('checked', false);

    $('#editCanAccessErp').prop('checked', Number(data.user.can_access_erp) === 1);
    $('#editCanAccessAdmin').prop('checked', Number(data.user.can_access_admin) === 1);

    const $edit = $('#editUserModal');

    $edit.find('input[name="roles[]"]').prop('checked', false);
    (data.userRoleIds || []).forEach(rid => $edit.find(`#editRole_${rid}`).prop('checked', true));

    $edit.find('input[name="permissions[]"]').prop('checked', false);
    (data.userPermIds || []).forEach(pid => $edit.find(`#editPerm_${pid}`).prop('checked', true));

    $edit.find('input[name="modules[]"]').prop('checked', false);
    (data.userModuleIds || []).forEach(mid => $edit.find(`#editModule_${mid}`).prop('checked', true));

    resetToFirstTab('editUserModal');

    if (window.__updateUserModalCounts) window.__updateUserModalCounts('edit');

    showModal('editUserModal');
  }).fail(() => {
    Swal.fire('Error', 'Failed to load user details.', 'error');
  });
});


  // -----------------------------
  // Update user
  // -----------------------------
  $(document).on('submit', '#editUserForm', function(e) {
    e.preventDefault();

    const userId = $('#editUserId').val();

    $.ajax({
      url: `/admin/users/${userId}`,
      type: 'PUT',
      data: $(this).serialize()
    })
    .done(res => {
      hideModal('editUserModal');
      Swal.fire('Updated', res.message || 'User updated successfully!', 'success');
      table.ajax.reload(null, false);
      resetToFirstTab('editUserModal');
    })
    .fail(xhr => {
      const errs = xhr.responseJSON?.errors;
      Swal.fire('Error', errs ? Object.values(errs).flat().join('<br/>') : 'Server error', 'error');
    });
  });

  // -----------------------------
  // Delete single
  // -----------------------------
  $(document).on('click', '.delete-btn', function() {
    const id = $(this).data('id');

    Swal.fire({
      title: 'Delete this user?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete!'
    }).then(({ isConfirmed }) => {
      if (!isConfirmed) return;

      $.ajax({
        url: `/admin/users/${id}`,
        type: 'DELETE'
      })
      .done(res => {
        Swal.fire('Deleted', res.message || 'User deleted.', 'success');
        table.ajax.reload(null, false);
      })
      .fail(() => Swal.fire('Error', 'Delete failed', 'error'));
    });
  });

  // -----------------------------
  // Grouped checklist UX (add + edit)
  // -----------------------------
  (function () {
    const norm = (s) => (s || '').toString().toLowerCase().trim();

    function setWrapperVisible(chk, show) {
      const wrapper = chk.closest('.col-lg-4, .col-md-6, .col-12, .col-4, .col-6, .col-3') || chk.closest('div');
      if (wrapper) wrapper.style.display = show ? '' : 'none';
    }

    function isVisible(chk) {
      const wrapper = chk.closest('.col-lg-4, .col-md-6, .col-12, .col-4, .col-6, .col-3') || chk.closest('div');
      return !wrapper || wrapper.style.display !== 'none';
    }

    function updateGroupBadges(context) {
      document.querySelectorAll(`[data-group-count="${context}"]`).forEach(badge => {
        const group = badge.getAttribute('data-group');
        const all = document.querySelectorAll(`input.perm-check[data-filter-item="${context}"][data-perm-group="${group}"]`);
        const checked = Array.from(all).filter(x => x.checked).length;
        badge.textContent = `${checked}/${all.length}`;
      });
    }

    function updateCounts(context) {
      const rolesChecked = document.querySelectorAll(`input.role-check[data-filter-item="${context}"]:checked`).length;
      const permsChecked = document.querySelectorAll(`input.perm-check[data-filter-item="${context}"]:checked`).length;
      const modulesChecked = document.querySelectorAll(`input.module-check[data-filter-item="${context}"]:checked`).length;

      const rMeta = document.getElementById(`${context}RolesMeta`);
      const pMeta = document.getElementById(`${context}PermsMeta`);
      const mMeta = document.getElementById(`${context}ModulesMeta`);

      const rBadge = document.getElementById(`${context}RolesCountBadge`);
      const pBadge = document.getElementById(`${context}PermsCountBadge`);
      const mBadge = document.getElementById(`${context}ModulesCountBadge`);

      if (rMeta) rMeta.textContent = `${rolesChecked} selected`;
      if (pMeta) pMeta.textContent = `${permsChecked} selected`;
      if (mMeta) mMeta.textContent = `${modulesChecked} selected`;

      if (rBadge) rBadge.textContent = rolesChecked;
      if (pBadge) pBadge.textContent = permsChecked;
      if (mBadge) mBadge.textContent = modulesChecked;

      updateGroupBadges(context);
    }

    function applyFilter(context, target, query) {
      query = norm(query);

      const items = document.querySelectorAll(`[data-filter-item="${context}"][data-filter-target="${target}"]`);
      items.forEach(chk => {
        const text = norm(chk.getAttribute('data-filter-text'));
        const show = !query || text.includes(query);
        setWrapperVisible(chk, show);
      });

      if (target === 'perms') {
        const accordionId = context === 'add' ? 'addPermsAccordion' : 'editPermsAccordion';
        const accordion = document.getElementById(accordionId);
        if (accordion) {
          accordion.querySelectorAll('.accordion-item').forEach(group => {
            const checkboxes = group.querySelectorAll(`input.perm-check[data-filter-item="${context}"]`);
            const anyVisible = Array.from(checkboxes).some(isVisible);
            group.style.display = anyVisible ? '' : 'none';
          });
        }
      }

      updateCounts(context);
    }

    function setVisibleChecked(context, target, checked) {
      const items = document.querySelectorAll(`[data-filter-item="${context}"][data-filter-target="${target}"]`);
      items.forEach(chk => {
        if (isVisible(chk)) chk.checked = !!checked;
      });
      updateCounts(context);
    }

    function setGroupChecked(context, groupName, checked) {
      const items = document.querySelectorAll(`input.perm-check[data-filter-item="${context}"][data-perm-group="${groupName}"]`);
      items.forEach(chk => chk.checked = !!checked);
      updateCounts(context);
    }

    document.addEventListener('input', function (e) {
      const ctx = e.target.getAttribute('data-filter-input');
      const target = e.target.getAttribute('data-filter-target');
      if (!ctx || !target) return;
      applyFilter(ctx, target, e.target.value);
    });

    document.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-select-visible]');
      if (btn) {
        const ctx = btn.getAttribute('data-select-visible');
        const target = btn.getAttribute('data-select-target');
        const val = btn.getAttribute('data-select-value') === '1';
        setVisibleChecked(ctx, target, val);
        return;
      }

      const grpBtn = e.target.closest('[data-group-select]');
      if (grpBtn) {
        e.preventDefault();
        e.stopPropagation();

        const ctx = grpBtn.getAttribute('data-group-select');
        const grp = grpBtn.getAttribute('data-group');
        const val = grpBtn.getAttribute('data-select-value') === '1';
        setGroupChecked(ctx, grp, val);
        return;
      }

      const view = e.target.closest('.view-perms');
      if (view) {
        const id = view.getAttribute('data-id');
        if (!id) return;

        $.get(`/admin/users/${id}/permissions-details`, function(res){
          $('#permDetailsTitle').text(`Permissions for ${res.user.name} (${res.total})`);

          let html = '';
          const keys = Object.keys(res.grouped || {}).sort();
          if (!keys.length) {
            html = `<div class="alert alert-secondary mb-0">No permissions found.</div>`;
          } else {
            keys.forEach(g => {
              const list = res.grouped[g] || [];
              html += `<div class="mb-3">
                <div class="fw-bold text-capitalize">${g}
                  <span class="badge bg-light text-dark ms-1">${list.length}</span>
                </div>
                <div class="small text-muted mt-1">${list.map(x => `${x}`).join('<br>')}</div>
              </div>`;
            });
          }

          $('#permDetailsBody').html(html);
          showModal('permDetailsModal');
        }).fail(() => {
          Swal.fire('Error', 'Failed to load permission details.', 'error');
        });

        return;
      }
    });

    document.addEventListener('change', function (e) {
      if (e.target.matches('input.role-check, input.perm-check, input.module-check')) {
        const ctx = e.target.getAttribute('data-filter-item');
        if (ctx) updateCounts(ctx);
      }
    });

    function wireModal(modalId, context) {
      const modal = document.getElementById(modalId);
      if (!modal) return;

      modal.addEventListener('shown.bs.modal', () => updateCounts(context));

      modal.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll(`[data-filter-input="${context}"]`).forEach(i => (i.value = ''));
        applyFilter(context, 'roles', '');
        applyFilter(context, 'perms', '');
        applyFilter(context, 'modules', '');
      });
    }

    wireModal('addUserModal', 'add');
    wireModal('editUserModal', 'edit');

    window.__updateUserModalCounts = updateCounts;
  })();

});
</script>

@endpush
