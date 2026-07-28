@extends('layouts.master')

@section('title', 'Manage Units')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-primary">Units <small class="text-muted">Inventory</small></h1>
    <div>
      <button class="btn btn-danger me-2" id="bulkDeleteBtn" style="display:none;">
        <i class="fas fa-trash-alt me-1"></i> Delete Selected
      </button>
      <button class="btn btn-primary" id="addUnitBtn">
        <i class="fas fa-plus me-1"></i> Add Unit
      </button>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3">
            <i class="fas fa-balance-scale"></i>
          </div>
          <div>
            <h6>Total Units</h6>
            <h4 class="mb-0" id="totalUnits">{{ number_format($units_count ?? 0) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered w-100" id="unitTable">
          <thead class="thead-light">
            <tr>
              <th style="width:40px;"><input type="checkbox" id="selectAllUnits"></th>
              <th>Name</th>
              <th>Symbol</th>
              <th style="width:140px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="unitModal" tabindex="-1" aria-labelledby="unitModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="unitForm" class="modal-content">
      @csrf
      <input type="hidden" id="unitId">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="unitModalLabel">Add Unit</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label for="unitName" class="form-label">Unit Name</label>
          <input type="text" class="form-control" id="unitName" required>
        </div>
        <div class="mb-3">
          <label for="unitSymbol" class="form-label">Symbol</label>
          <input type="text" class="form-control" id="unitSymbol">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save Unit</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  // ✅ CSRF header for PUT/DELETE etc.
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  const table = $('#unitTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    rowId: 'id',
    ajax: "{{ route('admin.inventory.products.units.datatable') }}",
    columns: [
      { data: 'checkbox', orderable: false, searchable: false },
      { data: 'name' },
      { data: 'symbol' },
      { data: 'action', orderable: false, searchable: false }
    ],
    drawCallback: function () {
      // refresh metrics
      $.get("{{ route('admin.inventory.products.units.metrics') }}", function (res) {
        $('#totalUnits').text(res.total ?? 0);
      });

      // keep bulk state correct
      toggleBulkDeleteBtn();
    }
  });

  // ✅ helper to correctly get row data even when responsive creates "child" rows
  function getRowDataFromButton(btn) {
    let $tr = $(btn).closest('tr');

    // If it's a child row, the actual data is in the previous row
    if ($tr.hasClass('child')) {
      $tr = $tr.prev();
    }

    const data = table.row($tr).data();
    return data || null;
  }

  // ---------------------------
  // Select All / Bulk toggle
  // ---------------------------
  $('#selectAllUnits').on('change', function () {
    $('.unit-checkbox').prop('checked', this.checked);
    toggleBulkDeleteBtn();
  });

  $(document).on('change', '.unit-checkbox', function () {
    // If any unchecked, uncheck master
    const total = $('.unit-checkbox').length;
    const checked = $('.unit-checkbox:checked').length;
    $('#selectAllUnits').prop('checked', total > 0 && total === checked);

    toggleBulkDeleteBtn();
  });

  function toggleBulkDeleteBtn() {
    $('#bulkDeleteBtn').toggle($('.unit-checkbox:checked').length > 0);
  }

  // ---------------------------
  // Add Unit
  // ---------------------------
  $('#addUnitBtn').on('click', function () {
    $('#unitForm')[0].reset();
    $('#unitId').val('');
    $('#unitModalLabel').text('Add Unit');
    $('#unitModal').modal('show');
  });

  // ---------------------------
  // Edit Unit
  // ---------------------------
  $(document).on('click', '.edit-btn', function (e) {
    e.preventDefault();

    // Prefer data-id on button if your backend sets it; otherwise use row data
    const data = getRowDataFromButton(this);
    const id = $(this).data('id') || data?.id;

    if (!id) {
      Swal.fire('Error', 'Could not detect Unit ID for editing.', 'error');
      return;
    }

    $('#unitId').val(id);
    $('#unitName').val(data?.name ?? $(this).data('name') ?? '');
    $('#unitSymbol').val(data?.symbol ?? $(this).data('symbol') ?? '');
    $('#unitModalLabel').text('Edit Unit');
    $('#unitModal').modal('show');
  });

  // ---------------------------
  // Save Unit (Create / Update)
  // ---------------------------
  $('#unitForm').on('submit', function (e) {
    e.preventDefault();

    const id = $('#unitId').val();
    const payload = {
      name: $('#unitName').val(),
      symbol: $('#unitSymbol').val()
    };

    const url = id
      ? `{{ url('admin/inventory/products/units') }}/${id}`
      : `{{ route('admin.inventory.products.units.store') }}`;

    $.ajax({
      url,
      type: id ? 'PUT' : 'POST',
      data: payload
    })
    .done(function (res) {
      $('#unitModal').modal('hide');
      table.ajax.reload(null, false);
      Swal.fire('Success', res.message ?? 'Saved successfully', 'success');
    })
    .fail(function (xhr) {
      let msg = 'Failed to save unit.';
      if (xhr.status === 422 && xhr.responseJSON?.errors) {
        msg = Object.values(xhr.responseJSON.errors)[0][0];
      } else if (xhr.responseJSON?.message) {
        msg = xhr.responseJSON.message;
      }
      Swal.fire('Error', msg, 'error');
    });
  });

  // ---------------------------
  // Delete Unit
  // ---------------------------
  $(document).on('click', '.delete-btn', function (e) {
    e.preventDefault();

    const data = getRowDataFromButton(this);
    const id = $(this).data('id') || data?.id;

    if (!id) {
      Swal.fire('Error', 'Could not detect Unit ID for deletion.', 'error');
      return;
    }

    Swal.fire({
      title: 'Are you sure?',
      text: 'This action cannot be undone!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (!result.isConfirmed) return;

      $.ajax({
        url: `{{ url('admin/inventory/products/units') }}/${id}`,
        type: 'DELETE'
      })
      .done(function (res) {
        table.ajax.reload(null, false);
        Swal.fire('Deleted!', res.message ?? 'Deleted successfully', 'success');
      })
      .fail(function (xhr) {
        Swal.fire('Error', xhr.responseJSON?.message ?? 'Delete failed', 'error');
      });
    });
  });

  // ---------------------------
  // Bulk Delete
  // ---------------------------
  $('#bulkDeleteBtn').on('click', function () {
    const ids = $('.unit-checkbox:checked').map(function () {
      return $(this).val();
    }).get();

    if (!ids.length) return;

    Swal.fire({
      title: `Delete ${ids.length} selected unit(s)?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d33'
    }).then(result => {
      if (!result.isConfirmed) return;

      $.post("{{ route('admin.inventory.products.units.bulk-delete') }}", { ids })
        .done(function (res) {
          table.ajax.reload(null, false);
          $('#selectAllUnits').prop('checked', false);
          $('#bulkDeleteBtn').hide();
          Swal.fire('Deleted!', res.message ?? 'Deleted successfully', 'success');
        })
        .fail(function (xhr) {
          Swal.fire('Error', xhr.responseJSON?.message ?? 'Failed to delete units.', 'error');
        });
    });
  });

});
</script>
@endpush
