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
                            <th>Variant (SKU)</th>
                            <th>Product</th>
                            <th>Qty / Parent</th>
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
          <select id="bomSelect" name="bom_header_id" class="form-control" required>
            <option value="">-- Select BOM --</option>
            @foreach($boms as $bom)
              <option value="{{ $bom->id }}">{{ 'Code: #'.($bom->bom_code ?? $bom->code).' — '.($bom->name ?? '') }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label for="variantSelect" class="form-label">Product Variant</label>
          <select id="variantSelect" name="product_variant_id" class="form-control" required>
            <option value="">-- Select Variant --</option>
            @isset($variants)
              @foreach($variants as $v)
                <option value="{{ $v->id }}">{{ $v->sku }} — {{ $v->product->product_name ?? $v->product->name ?? 'Product' }}</option>
              @endforeach
            @endisset
          </select>
          <small class="text-muted">Tip: Start typing SKU or product name.</small>
        </div>
        <div class="mb-3">
          <label for="quantityInput" class="form-label">Quantity per Parent</label>
          <input type="number" step="0.0001" min="0" id="quantityInput" name="qty_per_parent" class="form-control" required>
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

    // Optional: enhance selects with Select2 if available globally
    if ($.fn.select2) {
        $('#bomSelect').select2({ width:'100%', placeholder:'-- Select BOM --', dropdownParent: $('#bomItemModal') });
        $('#variantSelect').select2({
            width:'100%', placeholder:'-- Select Variant --', dropdownParent: $('#bomItemModal'),
            ajax:{
            url: '/admin/inventory/stock/transfers/fetch-variants',
            dataType:'json',
            delay:250,
            data: params => ({ q: params.term }),
            processResults: data => ({
                results: data.map(v => ({
                    id:   v.id,
                    text: v.sku + ' – ' + v.product_name
                }))
            })
        }, minimumInputLength: 2
        });
    }

    // Initialize DataTable
    const table = $('#bomItemsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.production.boms.items.datatable") }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'bom_code', name: 'bomHeader.bom_code' },
            { data: 'variant_sku', name: 'product_variant.sku' },
            { data: 'product_name', name: 'product_variant.product.product_name' },
            { data: 'qty_per_parent', name: 'qty_per_parent' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']]
    });

    // Show modal for Add
    $('#addBomItemBtn').on('click', function() {
        $('#bomItemForm')[0].reset();
        $('#bomItemId').val('');
        if ($.fn.select2) {
            $('#bomSelect').val(null).trigger('change');
            $('#variantSelect').val(null).trigger('change');
        }
        $('#bomItemModalLabel').text('Add BOM Item');
        bomModal.show();
    });

    // Submit Add/Edit form
    $('#bomItemForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#bomItemId').val();
        const url = id
            ? `{{ url('admin/production/boms/items') }}/${id}`
            : `{{ route('admin.production.boms.items.store') }}`;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function(res) {
                bomModal.hide();
                table.ajax.reload(null, false);
                Swal.fire('Success', res.message || 'Saved', 'success');
            },
            error: function(xhr) {
                const errs = xhr.responseJSON?.errors;
                let msg = xhr.responseJSON?.message || 'Failed to save';
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
        $('#quantityInput').val(data.qty_per_parent);

        // BOM + Variant
        if ($.fn.select2) {
          // handle preselected options for Select2
          if (data.bom_header_id && data.bom_code_text) {
            const o = new Option(data.bom_code_text, data.bom_header_id, true, true);
            $('#bomSelect').append(o).trigger('change');
          } else {
            $('#bomSelect').val(data.bom_header_id).trigger('change');
          }
          if (data.product_variant_id && data.variant_label) {
            const o2 = new Option(data.variant_label, data.product_variant_id, true, true);
            $('#variantSelect').append(o2).trigger('change');
          } else {
            $('#variantSelect').val(data.product_variant_id).trigger('change');
          }
        } else {
          $('#bomSelect').val(data.bom_header_id);
          $('#variantSelect').val(data.product_variant_id);
        }

        $('#bomItemModalLabel').text('Edit BOM Item');
        bomModal.show();
    });

    // Delete single
    $('#bomItemsTable').on('click', '.delete-bom-item', function() {
        const id = $(this).data('id');
        Swal.fire({ title: 'Delete this item?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!' })
        .then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: `{{ url('admin/production/boms/items') }}/${id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    table.ajax.reload(null, false);
                    Swal.fire('Deleted!', res.message || 'Item deleted', 'success');
                }
            });
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
        const ids = $('.row-checkbox:checked').map(function() { return $(this).val(); }).get();
        if (!ids.length) return;
        Swal.fire({ title: `Delete ${ids.length} selected?`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete' })
        .then(result => {
            if (!result.isConfirmed) return;
            $.post('{{ route("admin.production.boms.items.bulk-delete") }}', {
                _token: '{{ csrf_token() }}', ids: ids
            }, function(res) {
                table.ajax.reload(null, false);
                $('#bulkDeleteBtn').hide();
                Swal.fire('Deleted!', res.message || 'Items deleted', 'success');
            }).fail(() => Swal.fire('Error', 'Bulk deletion failed', 'error'));
        });
    });
});
</script>
@endpush
