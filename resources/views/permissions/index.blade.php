@extends('layouts.master')

@section('title', 'Manage Permissions')

@push('styles')

@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5>Permissions Management</h5>
    <div>
      <button id="bulkDeletePermissionBtn" class="btn btn-danger btn-sm me-2">Delete Selected</button>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPermissionModal">
        Add New Permission
      </button>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="permissionsTable" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th><input type="checkbox" id="select-all-permissions"></th>
            <th>Name</th>
            <th style="width:120px">Actions</th>
          </tr>
        </thead>
        <tbody>
          {{-- AJAX-loaded --}}
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Add & Edit Modals here (unchanged) --}}
@include('permissions.modals.add')
@include('permissions.modals.edit')
@endsection

@push('scripts')

<script>
$(function () {

  const table = $('#permissionsTable').DataTable({
  processing: true,
  serverSide: true,
  ajax: '{{ route("admin.permissions.index") }}',
  columns: [
    {
      data: 'id',
      orderable: false,
      searchable: false,
      render: (id) => `<input type="checkbox" name="ids[]" value="${id}">`
    },
    { data: 'name' },
    {
      data: 'id',
      orderable: false,
      searchable: false,
      render: function (id, type, row) {
        return `
          <button class="btn btn-sm btn-warning me-1 btn-edit-permission"
                  data-id="${id}" data-name="${row.name}">
            Edit
          </button>

          <button class="btn btn-sm btn-danger btn-delete-permission"
                  data-id="${id}">
            Delete
          </button>
        `;
      }
    }
  ]
});


  // Select / Deselect all
  $(document).on('change', '#select-all-permissions', function () {
    $('.perm-check').prop('checked', this.checked);
  });

  // If any checkbox changes, update select-all state
  $(document).on('change', '.perm-check', function () {
    const all = $('.perm-check').length;
    const checked = $('.perm-check:checked').length;
    $('#select-all-permissions').prop('checked', all > 0 && all === checked);
  });

  // Bulk Delete
  $(document).on('click', '#bulkDeletePermissionBtn', function () {
    const ids = $('.perm-check:checked').map(function () { return this.value; }).get();

    if (!ids.length) {
      return Swal.fire('Warning', 'No permissions selected.', 'warning');
    }

    Swal.fire({
      title: 'Delete selected?',
      text: 'This cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete!'
    }).then(({ isConfirmed }) => {
      if (!isConfirmed) return;

      $.ajax({
        url: '{{ route("admin.permissions.bulkDelete") }}',
        type: 'DELETE',
        data: { ids: ids }
      })
      .done(res => {
        Swal.fire('Deleted', res.message ?? 'Deleted successfully.', 'success');
        $('#select-all-permissions').prop('checked', false);
        table.ajax.reload(null, false);
      })
      .fail(xhr => {
        Swal.fire('Error', xhr.responseJSON?.message ?? 'Bulk delete failed.', 'error');
      });
    });
  });

  // Single Delete
  $(document).on('click', '.btn-delete-permission', function () {
    const id = $(this).data('id');

    Swal.fire({
      title: 'Delete this permission?',
      text: 'This cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete!'
    }).then(({ isConfirmed }) => {
      if (!isConfirmed) return;

      $.ajax({
        url: `{{ url('admin/permissions') }}/${id}`,
        type: 'DELETE'
      })
      .done(res => {
        Swal.fire('Deleted', res.message ?? 'Deleted successfully.', 'success');
        table.ajax.reload(null, false);
      })
      .fail(xhr => {
        Swal.fire('Error', xhr.responseJSON?.message ?? 'Delete failed.', 'error');
      });
    });
  });

  // Add new permission
  $(document).on('submit', '#addPermissionForm', function (e) {
    e.preventDefault();

    const $form = $(this);
    const url = $form.attr('action');

    $.ajax({
      url: url,
      type: 'POST',
      data: $form.serialize(),
    })
    .done(res => {
      Swal.fire('Success', res.message ?? 'Permission created', 'success');

      const modalEl = document.getElementById('addPermissionModal');
      bootstrap.Modal.getInstance(modalEl)?.hide();

      $form[0].reset();
      table.ajax.reload(null, false);
    })
    .fail(xhr => {
      let msg = xhr.responseJSON?.message ?? 'Create failed.';
      if (xhr.responseJSON?.errors?.name?.[0]) msg = xhr.responseJSON.errors.name[0];
      Swal.fire('Error', msg, 'error');
    });
  });
  
  // Edit permission
  // populate the modal and open it
  $(document).on('click', '.btn-edit-permission', function () {
      const id = $(this).data('id');
      const name = $(this).data('name');
    
      $('#edit-permission-id').val(id);
      $('#edit-permission-name').val(name);
    
      const modal = new bootstrap.Modal(document.getElementById('editPermissionModal'));
      modal.show();
  });
  
  // submit the edit form via ajax
  $(document).on('submit', '#editPermissionForm', function (e) {
      e.preventDefault();
    
      const id = $('#edit-permission-id').val();
      if (!id) return Swal.fire('Error', 'Permission ID missing (undefined).', 'error');
    
      $.ajax({
        url: `{{ url('admin/permissions') }}/${id}`,
        type: 'PUT',
        data: { name: $('#edit-permission-name').val() }
      })
      .done(res => {
        Swal.fire('Updated', res.message ?? 'Updated', 'success');
        bootstrap.Modal.getInstance(document.getElementById('editPermissionModal'))?.hide();
        table.ajax.reload(null, false);
      })
      .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message ?? 'Update failed', 'error'));
  });



});
</script>
@endpush
