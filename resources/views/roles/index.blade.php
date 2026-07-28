@extends('layouts.master')

@section('title', 'Manage Roles')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />

<style>
  .modal-body-scroll { max-height: 70vh; overflow: auto; }

  /* Search box */
  .perm-search { position: sticky; top: 0; background: #fff; z-index: 5; padding-bottom: .75rem; }

  /* Group cards */
  .perm-group { border: 1px solid rgba(0,0,0,.08); border-radius: 12px; overflow: hidden; margin-bottom: 12px; }
  .perm-group-header {
    padding: .75rem 1rem;
    background: rgba(78,115,223,.06);
    display:flex; align-items:center; justify-content:space-between;
    cursor:pointer;
  }
  .perm-group-title { font-weight: 700; letter-spacing: .02em; margin: 0; }
  .perm-badge { font-size: .75rem; padding: .25rem .5rem; border-radius: 999px; background: rgba(78,115,223,.15); }

  .perm-tools { display:flex; gap:.5rem; align-items:center; }
  .perm-tools .btn { padding: .25rem .5rem; font-size: .75rem; }

  /* Permissions grid */
  .perm-grid { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem 1rem; padding: 1rem; }
  @media (max-width: 992px){ .perm-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 768px){ .perm-grid{ grid-template-columns: 1fr; } }

  .perm-item { padding: .35rem .5rem; border-radius: 10px; background: rgba(0,0,0,.02); }
  .perm-label { white-space: normal; word-break: break-word; font-size:.9rem; }

  /* Collapse */
  .perm-group-body { display:none; }
  .perm-group.open .perm-group-body { display:block; }
  
  .perm-badge-chip{
      display:inline-flex; align-items:center; gap:.35rem;
      padding:.2rem .55rem; border-radius:999px;
      background: rgba(78,115,223,.10);
      border: 1px solid rgba(78,115,223,.18);
      font-size:.78rem; font-weight:700;
    }
    .perm-badge-count{
      background: rgba(78,115,223,.18);
      border-radius: 999px;
      padding: .05rem .4rem;
      font-size:.75rem;
      font-weight:800;
    }


</style>
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5>Roles Management</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoleModal">
      Add New Role
    </button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="rolesTable" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Name</th>
            <th>Permissions</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {{-- AJAX Populates --}}
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Add Role Modal --}}
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="addRoleForm">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addRoleModalLabel">Add New Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body modal-body-scroll">
          <div class="mb-3">
            <label class="form-label">Role Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Assign Permissions</label>

            <div class="perm-search">
              <input type="text"
                     class="form-control form-control-sm permission-filter"
                     data-target="#addPermGroups"
                     placeholder="Search permissions...">
            </div>

            <div id="addPermGroups">
              @foreach($permissions as $module => $perms)
                @php $key = \Illuminate\Support\Str::slug($module, '_'); @endphp

                <div class="perm-group" data-module="{{ strtolower($key) }}">
                  <div class="perm-group-header perm-toggle">
                    <div class="d-flex align-items-center gap-2">
                      <h6 class="perm-group-title mb-0">{{ strtoupper($module) }}</h6>
                      <span class="perm-badge">{{ $perms->count() }}</span>
                    </div>

                    <div class="perm-tools">
                      <button type="button"
                              class="btn btn-outline-secondary btn-sm perm-check-all"
                              data-scope="add_{{ $key }}">All</button>
                      <button type="button"
                              class="btn btn-outline-secondary btn-sm perm-uncheck-all"
                              data-scope="add_{{ $key }}">None</button>
                    </div>
                  </div>

                  <div class="perm-group-body" id="add_{{ $key }}">
                    <div class="perm-grid">
                      @foreach($perms as $permission)
                        <div class="form-check perm-item perm-row" data-text="{{ strtolower($permission->name) }}">
                          <input class="form-check-input" type="checkbox"
                                 name="permissions[]" value="{{ $permission->name }}"
                                 id="addPermission_{{ $permission->id }}">
                          <label class="form-check-label perm-label" for="addPermission_{{ $permission->id }}">
                            {{ $permission->name }}
                          </label>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Create Role</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Edit Role Modal --}}
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="editRoleForm">
      @csrf
      @method('PUT')
      <input type="hidden" id="editRoleId" name="role_id">

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body modal-body-scroll">
          <div class="mb-3">
            <label class="form-label">Role Name</label>
            <input type="text" name="name" id="editRoleName" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Assign Permissions</label>

            <div class="perm-search">
              <input type="text"
                     class="form-control form-control-sm permission-filter"
                     data-target="#editPermGroups"
                     placeholder="Search permissions...">
            </div>

            <div id="editPermGroups">
              @foreach($permissions as $module => $perms)
                @php $key = \Illuminate\Support\Str::slug($module, '_'); @endphp

                <div class="perm-group" data-module="{{ strtolower($key) }}">
                  <div class="perm-group-header perm-toggle">
                    <div class="d-flex align-items-center gap-2">
                      <h6 class="perm-group-title mb-0">{{ strtoupper($module) }}</h6>
                      <span class="perm-badge">{{ $perms->count() }}</span>
                    </div>

                    <div class="perm-tools">
                      <button type="button"
                              class="btn btn-outline-secondary btn-sm perm-check-all"
                              data-scope="edit_{{ $key }}">All</button>
                      <button type="button"
                              class="btn btn-outline-secondary btn-sm perm-uncheck-all"
                              data-scope="edit_{{ $key }}">None</button>
                    </div>
                  </div>

                  <div class="perm-group-body" id="edit_{{ $key }}">
                    <div class="perm-grid">
                      @foreach($perms as $permission)
                        <div class="form-check perm-item perm-row" data-text="{{ strtolower($permission->name) }}">
                          <input class="form-check-input" type="checkbox"
                                 name="permissions[]" value="{{ $permission->name }}"
                                 id="editPermission_{{ $permission->id }}">
                          <label class="form-check-label perm-label" for="editPermission_{{ $permission->id }}">
                            {{ $permission->name }}
                          </label>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Update Role</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="permPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Permissions — <span id="permPreviewRoleName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="max-height:70vh; overflow:auto;">
        <div id="permPreviewBody"></div>
      </div>
    </div>
  </div>
</div>


@endsection

@push('scripts')

<script>
$(function() {
  const table = $('#rolesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("admin.roles.index") }}',
    dom: 'Bfrtip',
    buttons: ['excel','pdf','print'],
    columns: [
      { data: 'name' },
      { data: 'permissions', orderable: false },
      { data: 'actions', orderable: false },
    ]
  });

    $(document).on('click', '.perm-view-btn', function () {
      const name = $(this).data('name');
      const details = $(this).data('details'); // html (escaped already)
    
      $('#permPreviewRoleName').text(name);
      $('#permPreviewBody').html(details);
    
      const modalEl = document.getElementById('permPreviewModal');
      new bootstrap.Modal(modalEl).show();
    });


  // Toggle group open/close (click header but not buttons)
  $(document).on('click', '.perm-toggle', function(e){
    if ($(e.target).closest('button').length) return;
    $(this).closest('.perm-group').toggleClass('open');
  });

  // Search filter
  $(document).on('input', '.permission-filter', function(){
    const q = $(this).val().toLowerCase().trim();
    const wrap = $($(this).data('target'));

    wrap.find('.perm-row').each(function(){
      const text = $(this).data('text');
      $(this).toggle(!q || (text && text.includes(q)));
    });

    wrap.find('.perm-group').each(function(){
      const anyVisible = $(this).find('.perm-row:visible').length > 0;
      $(this).toggle(anyVisible || !q);

      if (q && anyVisible) $(this).addClass('open');
      if (!q) $(this).removeClass('open'); // optional: collapse all when clearing search
    });
  });

  // All / None within a group (scoped to the current modal)
  $(document).on('click', '.perm-check-all', function(){
    const scope = '#' + $(this).data('scope');
    const modal = $(this).closest('.modal');
    modal.find(scope).find('.perm-row:visible input[name="permissions[]"]').prop('checked', true);
  });

  $(document).on('click', '.perm-uncheck-all', function(){
    const scope = '#' + $(this).data('scope');
    const modal = $(this).closest('.modal');
    modal.find(scope).find('.perm-row:visible input[name="permissions[]"]').prop('checked', false);
  });

  // Create
  $('#addRoleForm').submit(function(e) {
    e.preventDefault();
    $.post('{{ route("admin.roles.store") }}', $(this).serialize())
      .done(res => {
        const modalEl = document.getElementById('addRoleModal');
        bootstrap.Modal.getInstance(modalEl)?.hide();

        Swal.fire('Success', res.message ?? 'Role created', 'success');
        table.ajax.reload(null,false);
        this.reset();

        $('#addRoleModal .permission-filter').val('').trigger('input');
      })
      .fail(xhr => {
        const msg = xhr.responseJSON?.message
          || Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>')
          || 'Create failed';
        Swal.fire('Error', msg, 'error');
      });
  });

  // Edit (populate)
  $(document).on('click','.edit-role',function(e){
    e.preventDefault();
    let id = $(this).data('id');

    $.get(`/admin/roles/${id}/edit`, data=>{
      $('#editRoleId').val(id);
      $('#editRoleName').val(data.role.name);

      // ONLY uncheck in edit modal
      $('#editRoleModal input[name="permissions[]"]').prop('checked', false);

      // Tick by value (safe for dots/hyphens)
      (data.rolePermissions || []).forEach(p => {
        $('#editRoleModal input[name="permissions[]"][value="'+p+'"]').prop('checked', true);
      });

      // reset search
      $('#editRoleModal .permission-filter').val('').trigger('input');

      const modalEl = document.getElementById('editRoleModal');
      new bootstrap.Modal(modalEl).show();
    });
  });

  // Update
  $('#editRoleForm').submit(function(e){
    e.preventDefault();
    let id = $('#editRoleId').val();

    $.ajax({
      url: `/admin/roles/${id}`,
      type: 'PUT',
      data: $(this).serialize()
    })
    .done(res=>{
      const modalEl = document.getElementById('editRoleModal');
      bootstrap.Modal.getInstance(modalEl)?.hide();

      Swal.fire('Updated', res.message ?? 'Role updated', 'success');
      table.ajax.reload(null,false);
    })
    .fail(xhr=> {
      const msg = xhr.responseJSON?.message
        || Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>')
        || 'Update failed';
      Swal.fire('Error', msg, 'error');
    });
  });

  // Delete
  $(document).on('click','.delete-role',function(e){
    e.preventDefault();
    let id = $(this).data('id');
    Swal.fire({
      title:'Are you sure?',
      text: "This role will be deleted!",
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Yes, delete!'
    }).then(({isConfirmed})=>{
      if(isConfirmed){
        $.ajax({
          url:`/admin/roles/${id}`,
          type:'DELETE',
          data:{_token:'{{ csrf_token() }}'}
        })
        .done(res=>{
          Swal.fire('Deleted',res.message ?? 'Role deleted','success');
          table.ajax.reload(null,false);
        })
        .fail(()=>Swal.fire('Error','Delete failed','error'));
      }
    });
  });
});
</script>
@endpush
