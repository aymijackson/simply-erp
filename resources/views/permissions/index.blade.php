@extends('layouts.master')

@section('title', 'Manage Permissions')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
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
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(function() {
  const table = $('#permissionsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("admin.permissions.index") }}',
    dom: 'Bfrtip',
    buttons: ['excel','pdf','print'],
    columns: [
      { data: 'checkbox', orderable: false, searchable: false },
      { data: 'name' },
      { data: 'actions', orderable: false, searchable: false }
    ]
  });

  // Render checkboxes in the first column
  table.on('xhr.dt', function() {
    $('#permissionsTable tbody tr').each(function() {
      const data = table.row(this).data();
      $(this).find('td').eq(0).html(
        `<input type="checkbox" name="ids[]" value="${data.id}">`
      );
    });
  });

  // Select / Deselect all
  $(document).on('click', '#select-all-permissions', function() {
    $('input[name="ids[]"]').prop('checked', this.checked);
  });

  // Bulk Delete
  $('#bulkDeletePermissionBtn').click(function() {
    const ids = $('input[name="ids[]"]:checked').map(function() {
      return this.value;
    }).get();

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
        data: { _token: '{{ csrf_token() }}', ids },
      })
      .done(res => {
        Swal.fire('Deleted', res.message, 'success');
        table.ajax.reload(null, false);
      })
      .fail(() => Swal.fire('Error', 'Bulk delete failed.', 'error'));
    });
  });

  // (Existing create/edit/delete single‐item code unchanged…)
});
</script>
@endpush
