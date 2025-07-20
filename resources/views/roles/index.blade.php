@extends('layouts.master')

@section('title', 'Manage Roles')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
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
        <div class="modal-body">
          <div class="mb-3">
            <label>Role Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Assign Permissions</label>
            <div class="row">
              @foreach($permissions as $permission)
                <div class="col-md-4">
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      name="permissions[]"
                      value="{{ $permission->name }}"
                      id="permission_{{ $permission->id }}">
                    <label class="form-check-label" for="permission_{{ $permission->id }}">
                      {{ $permission->name }}
                    </label>
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
        <div class="modal-body">
          <div class="mb-3">
            <label>Role Name</label>
            <input type="text" name="name" id="editRoleName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Assign Permissions</label>
            <div class="row">
              @foreach($permissions as $permission)
                <div class="col-md-4">
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      name="permissions[]"
                      value="{{ $permission->name }}"
                      id="editPermission_{{ $permission->name }}">
                    <label class="form-check-label" for="editPermission_{{ $permission->name }}">
                      {{ $permission->name }}
                    </label>
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

  // Create
  $('#addRoleForm').submit(function(e) {
    e.preventDefault();
    $.post('{{ route("admin.roles.store") }}', $(this).serialize())
      .done(res => {
        $('#addRoleModal').modal('hide');
        Swal.fire('Success', res.message, 'success');
        table.ajax.reload(null,false);
        $(this)[0].reset();
      })
      .fail(xhr => Swal.fire('Error', Object.values(xhr.responseJSON.errors).join('<br>'), 'error'));
  });

  // Edit
  $(document).on('click','.edit-role',function(e){
    e.preventDefault();
    let id = $(this).data('id');
    $.get(`/admin/roles/${id}/edit`, data=>{
      $('#editRoleId').val(id);
      $('#editRoleName').val(data.role.name);
      $('input[name="permissions[]"]').prop('checked',false);
      data.rolePermissions.forEach(p=> $(`#editPermission_${p}`).prop('checked',true));
      $('#editRoleModal').modal('show');
    });
  });

  $('#editRoleForm').submit(function(e){
    e.preventDefault();
    let id = $('#editRoleId').val();
    $.ajax({
      url: `/admin/roles/${id}`,
      type: 'PUT',
      data: $(this).serialize()
    })
    .done(res=>{
      $('#editRoleModal').modal('hide');
      Swal.fire('Updated', res.message, 'success');
      table.ajax.reload(null,false);
    })
    .fail(xhr=> Swal.fire('Error', Object.values(xhr.responseJSON.errors).join('<br>'),'error'));
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
          Swal.fire('Deleted',res.message,'success');
          table.ajax.reload(null,false);
        })
        .fail(()=>Swal.fire('Error','Delete failed','error'));
      }
    });
  });
});
</script>
@endpush
