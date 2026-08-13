@extends('layouts.master')

@section('title','Manufacturers')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css"/>
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <h5>Manufacturers</h5>
    <div>
      <button id="bulkDeleteBtn" class="btn btn-danger btn-sm d-none">Delete Selected</button>
      <button class="btn btn-primary btn-sm" id="addBtn" data-bs-toggle="modal" data-bs-target="#manufacturerModal">
        Add Manufacturer
      </button>
    </div>
  </div>
  <div class="card-body">
    <table id="dt" class="table table-striped table-bordered nowrap w-100">
      <thead>
        <tr>
          <th><input type="checkbox" id="select-all"></th>
          <th>Name</th>
          <th>Website</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="manufacturerModal" tabindex="-1" aria-labelledby="manufacturerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="manufacturerForm" class="modal-content">
      @csrf
      <input type="hidden" name="id" id="manufacturerId">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="manufacturerModalLabel">Add Manufacturer</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="manufacturerName" class="form-label">Name</label>
          <input type="text" class="form-control" name="manufacturer_name" id="manufacturerName" required>
        </div>
        <div class="mb-3">
          <label for="manufacturerWebsite" class="form-label">Website</label>
          <input type="url" class="form-control" name="website" id="manufacturerWebsite">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Save</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script>
$(function () {
  const table = $('#dt').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("admin.inventory.products.manufacturers.datatable") }}',
    columns: [
      { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
      { data: 'manufacturer_name', name: 'manufacturer_name' },
      { data: 'website', name: 'website' },
      { data: 'action', name: 'action', orderable: false, searchable: false }
    ]
  });

  $('#select-all').on('change', function () {
    $('.row-checkbox').prop('checked', $(this).prop('checked'));
    toggleBulkDelete();
  });

  $(document).on('change', '.row-checkbox', toggleBulkDelete);

  function toggleBulkDelete() {
    const anyChecked = $('.row-checkbox:checked').length > 0;
    $('#bulkDeleteBtn').toggleClass('d-none', !anyChecked);
  }

  $('#addBtn').click(function () {
    $('#manufacturerForm')[0].reset();
    $('#manufacturerId').val('');
    $('#manufacturerModalLabel').text('Add Manufacturer');
  });

  $(document).on('click', '.edit-btn', function () {
    const btn = $(this).data();
    $('#manufacturerId').val(btn.id);
    $('#manufacturerName').val(btn.manufacturer_name);
    $('#manufacturerWebsite').val(btn.website);
    $('#manufacturerModalLabel').text('Edit Manufacturer');
    $('#manufacturerModal').modal('show');
  });

  $('#manufacturerForm').submit(function (e) {
    e.preventDefault();
    const id = $('#manufacturerId').val();
    const url = id 
      ? `{{ url('admin/inventory/products/manufacturers') }}/${id}`
      : `{{ route('admin.inventory.products.manufacturers.store') }}`;
    const type = id ? 'PUT' : 'POST';

    $.ajax({
      url: url,
      method: type,
      data: $(this).serialize(),
      success: function (res) {
        $('#manufacturerModal').modal('hide');
        table.ajax.reload();
        Swal.fire('Success', res.message, 'success');
      },
      error: function () {
        Swal.fire('Error', 'Could not save manufacturer', 'error');
      }
    });
  });

  $(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    Swal.fire({
      title: 'Are you sure?',
      text: 'This cannot be undone!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it'
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: `{{ url('admin/inventory/products/manufacturers') }}/${id}`,
          method: 'DELETE',
          data: { _token: '{{ csrf_token() }}' },
          success: function (res) {
            table.ajax.reload();
            Swal.fire('Deleted!', res.message, 'success');
          }
        });
      }
    });
  });

  $('#bulkDeleteBtn').click(function () {
    const ids = $('.row-checkbox:checked').map(function () {
      return $(this).val();
    }).get();

    if (!ids.length) return;

    Swal.fire({
      title: `Delete ${ids.length} selected manufacturers?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d33'
    }).then(result => {
      if (result.isConfirmed) {
        $.post('{{ route("admin.inventory.products.manufacturers.bulk-delete") }}', {
          _token: '{{ csrf_token() }}',
          ids
        }, function (res) {
          table.ajax.reload();
          $('#select-all').prop('checked', false);
          $('#bulkDeleteBtn').addClass('d-none');
          Swal.fire('Deleted!', res.message, 'success');
        }).fail(function () {
          Swal.fire('Error', 'Bulk delete failed.', 'error');
        });
      }
    });
  });
});
</script>
@endpush
