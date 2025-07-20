@extends('layouts.master')

@section('title', 'Bill of Material Items')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">BOM Items</h1>
        <div>
            <button class="btn btn-danger d-none" id="bulkDeleteBtn">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addBomItemBtn">
                <i class="fas fa-plus me-1"></i> Add BOM Item
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="bomItemsTable" class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>#</th>
                            <th>BOM Code</th>
                            <th>Raw Material</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="bomItemModal" tabindex="-1" aria-labelledby="bomItemModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="bomItemForm" class="modal-content">
      @csrf
      <input type="hidden" id="bomItemId" name="id">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="bomItemModalLabel">Add BOM Item</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="bomSelect" class="form-label">Bill of Material</label>
          <select id="bomSelect" name="bill_of_material_id" class="form-control" required>
            <option value="">-- Select BOM --</option>
            @foreach($boms as $bom)
              <option value="{{ $bom->id }}">{{ 'product:'. $bom->product->product_name .' version:'. $bom->version }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label for="rawMaterialSelect" class="form-label">Raw Material</label>
          <select id="rawMaterialSelect" name="raw_material_id" class="form-control" required>
            <option value="">-- Select Raw Material --</option>
            @foreach($rawMaterials as $rm)
              <option value="{{ $rm->id }}">{{ $rm->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label for="quantityInput" class="form-label">Quantity</label>
          <input type="number" step="0.0001" min="0" id="quantityInput" name="quantity" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const modalEl = document.getElementById('bomItemModal');
    const bomModal = new bootstrap.Modal(modalEl);

    // Initialize DataTable
    const table = $('#bomItemsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.production.boms.items.datatable") }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'bom_code', name: 'billOfMaterial.code' },
            { data: 'raw_material', name: 'rawMaterial.name' },
            { data: 'quantity', name: 'quantity' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']]
    });

    // Show modal for Add
    $('#addBomItemBtn').on('click', function() {
        $('#bomItemForm')[0].reset();
        $('#bomItemId').val('');
        $('#bomItemModalLabel').text('Add BOM Item');
        bomModal.show();
    });

    // Submit Add/Edit form
    $('#bomItemForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#bomItemId').val();
        const url = id
            ? `/bom-items/${id}`         // update route
            : `{{ route('admin.production.boms.items.store') }}`;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function(res) {
                bomModal.hide();
                table.ajax.reload(null, false);
                Swal.fire('Success', res.message, 'success');
            },
            error: function(xhr) {
                const errs = xhr.responseJSON?.errors;
                let msg = 'Failed to save';
                if (errs) {
                    msg = Object.values(errs).flat().join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Edit button
    $('#bomItemsTable').on('click', '.edit-bom-item', function() {
        const data = $(this).data('record');
        $('#bomItemId').val(data.id);
        $('#bomSelect').val(data.bill_of_material_id);
        $('#rawMaterialSelect').val(data.raw_material_id);
        $('#quantityInput').val(data.quantity);
        $('#bomItemModalLabel').text('Edit BOM Item');
        bomModal.show();
    });

    // Delete single
    $('#bomItemsTable').on('click', '.delete-bom-item', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/bom-items/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    }
                });
            }
        });
    });

    // Select all / Bulk delete
    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
        $('#bulkDeleteBtn').toggle($('.row-checkbox:checked').length > 0);
    });

    $('#bomItemsTable tbody').on('change', '.row-checkbox', function() {
        $('#bulkDeleteBtn').toggle($('.row-checkbox:checked').length > 0);
    });

    $('#bulkDeleteBtn').on('click', function() {
        const ids = $('.row-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (!ids.length) return;

        Swal.fire({
            title: `Delete ${ids.length} selected?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (result.isConfirmed) {
                $.post('{{ route("admin.production.boms.items.bulk-delete") }}', {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                }, function(res) {
                    table.ajax.reload(null, false);
                    $('#bulkDeleteBtn').hide();
                    Swal.fire('Deleted!', res.message, 'success');
                }).fail(() => {
                    Swal.fire('Error', 'Bulk deletion failed', 'error');
                });
            }
        });
    });
});
</script>
@endpush
