@extends('layouts.master')

@section('title', 'Manage Attribute Values')

@section('content')
<div class="container-fluid">
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Attribute Values</h1>
    <div>
      <button class="btn btn-primary" id="createValue">Add Value</button>
      <button class="btn btn-danger" id="bulkDeleteValues">Delete Selected</button>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered" id="valuesTable" width="100%" cellspacing="0">
          <thead>
            <tr>
              <th><input type="checkbox" id="selectAll"></th>
              <th>Product</th>
              <th>Attribute</th>
              <th>Value</th>
              <th>Created At</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

  <!-- Value Modal -->
  <div class="modal fade" id="valueModal" tabindex="-1" role="dialog" aria-labelledby="valueModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form id="valueForm" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="valueModalLabel">Add Value</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="value_id" name="id">
          <div class="form-group mb-3">
            <label for="product_attribute_id">Attribute</label>
            <select class="form-control" id="product_attribute_id" name="product_attribute_id" required>
              <option value="">Select Attribute</option>
              @foreach($attributes as $attr)
                <option value="{{ $attr->id }}">{{ $attr->product->product_name }} - {{ $attr->type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group mb-3">
            <label for="value">Value</label>
            <input type="text" class="form-control" id="value" name="value" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  $(function() {
    // 1. Init DataTable
    const table = $('#valuesTable').DataTable({
      responsive: true,
      processing: true,
      serverSide: true,
      ajax: '{{ route("admin.inventory.products.attributes.values.datatable") }}',
      columns: [
        { data: 'checkbox', orderable: false, searchable: false },
        { data: 'product_name', title: 'Product' },
        { data: 'attribute_type_name', title: 'Attribute' },
        { data: 'value',           title: 'Value' },
        { data: 'created_at',      title: 'Created At' },
        { data: 'actions', orderable: false, searchable: false }
      ]
    });

    // 2. Select All
    $('#selectAll').on('click', function() {
      $('input[name="value_checkbox[]"]').prop('checked', this.checked);
    });

    // 3. Open “Add Value” Modal
    $('#createValue').click(function() {
      $('#valueForm')[0].reset();
      $('#value_id').val('');
      $('#valueModalLabel').text('Add Value');
      $('#valueModal').modal('show');
    });

    // 4. Save (Create / Update)
    $('#valueForm').submit(function(e) {
      e.preventDefault();
      const id     = $('#value_id').val();
      const url    = id
                    ? `/admin/inventory/products/attributes/values/${id}`
                    : '{{ route("admin.inventory.products.attributes.values.store") }}';
      const method = id ? 'PUT' : 'POST';

      $.ajax({ url, method, data: $(this).serialize() })
        .done(res => {
          $('#valueModal').modal('hide');
          table.ajax.reload();
          Swal.fire('Success', res.message, 'success');
        })
        .fail(xhr => {
          Swal.fire('Error', xhr.responseJSON?.message || 'An error occurred', 'error');
        });
    });

    // 5. Edit
    $('#valuesTable').on('click', '.edit-value', function() {
      const id = $(this).data('id');
      $.get(`/admin/inventory/products/attributes/values/${id}/edit`)
        .done(value => {
          $('#value_id').val(value.id);
          $('#product_attribute_id').val(value.product_attribute_id);
          $('#value').val(value.value);
          $('#valueModalLabel').text('Edit Value');
          $('#valueModal').modal('show');
        })
        .fail(() => Swal.fire('Error', 'Could not load value', 'error'));
    });

    // 6. Delete Single
    $('#valuesTable').on('click', '.delete-value', function() {
      const id = $(this).data('id');
      Swal.fire({
        title: 'Delete this value?',
        icon:  'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete'
      }).then(({ isConfirmed }) => {
        if (!isConfirmed) return;
        $.ajax({ url: `/admin/attribute_values/${id}`, method: 'DELETE' })
          .done(res => {
            table.ajax.reload();
            Swal.fire('Deleted!', res.message, 'success');
          })
          .fail(() => Swal.fire('Error', 'Delete failed', 'error'));
      });
    });

    // 7. Bulk Delete
    $('#bulkDeleteValues').click(function() {
      const ids = $('input[name="value_checkbox[]"]:checked')
                  .map((_, cb) => cb.value)
                  .get();
      if (!ids.length) {
        return Swal.fire('Info', 'Select at least one', 'info');
      }
      Swal.fire({
        title: `Delete ${ids.length} values?`,
        icon:  'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete'
      }).then(({ isConfirmed }) => {
        if (!isConfirmed) return;
        $.post('{{ route("admin.inventory.products.attributes.values.bulk-delete") }}', { ids, _token: '{{ csrf_token() }}' })
          .done(res => {
            table.ajax.reload();
            Swal.fire('Deleted', res.message, 'success');
          })
          .fail(xhr => {
            Swal.fire('Error', xhr.responseJSON?.message || 'Bulk delete failed', 'error');
          });
      });
    });
  });
</script>
@endpush
