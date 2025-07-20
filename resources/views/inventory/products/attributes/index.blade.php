@extends('layouts.master')

@section('title', 'Product Attributes')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Product Attributes</h1>
        <button class="btn btn-danger mb-3 d-none" id="bulkDeleteBtn">
            <i class="fas fa-trash-alt me-1"></i> Delete Selected
        </button>
        <button class="btn btn-primary" id="addAttrBtn">
            <i class="fas fa-plus me-1"></i> Add Attribute
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="attributesTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllAttrs"></th>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Attribute Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="attributeModal" tabindex="-1" aria-labelledby="attributeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="attributeForm">
            @csrf
            <input type="hidden" id="attributeId" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="attributeModalLabel">Add Product Attribute</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="productId" class="form-label">Product</label>
                    <select class="form-control" id="productId" name="product_id" required>
                        <option value="">-- Select Product --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="attributeTypeId" class="form-label">Attribute Type</label>
                    <select class="form-control" id="attributeTypeId" name="attribute_type_id" required>
                        <option value="">-- Select Attribute Type --</option>
                        @foreach($attributeTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
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
$(function () {
    const attrModalEl = document.getElementById('attributeModal');
    const attrModal = new bootstrap.Modal(attrModalEl);

    const table = $('#attributesTable').DataTable({
        ajax: '{{ route('admin.inventory.products.attributes.datatable') }}',
        columns: [
            {
                data: 'checkbox',
                orderable: false,
                searchable: false
            },
            { data: 'id' },
            { data: 'product_name' },
            { data: 'attribute_type_name' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });


    $('#addAttrBtn').on('click', function () {
        $('#attributeForm')[0].reset();
        $('#attributeId').val('');
        $('#attributeModalLabel').text('Add Product Attribute');
        attrModal.show();
    });

    $('#attributesTable').on('click', '.edit-btn', function () {
        const id = $(this).data('id');
        const productId = $(this).data('product-id');
        const attributeTypeId = $(this).data('attribute-type-id');

        $('#attributeId').val(id);
        $('#productId').val(productId);
        $('#attributeTypeId').val(attributeTypeId);
        $('#attributeModalLabel').text('Edit Product Attribute');
        attrModal.show();
    });

    $('#attributesTable').on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/inventory/products/attributes/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    }
                });
            }
        });
    });

    $('#attributeForm').submit(function (e) {
        e.preventDefault();
        const id = $('#attributeId').val();
        const url = id ? `/admin/inventory/products/attributes/${id}` : `{{ route('admin.inventory.products.attributes.store') }}`;
        const formData = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function (res) {
                table.ajax.reload(null, false);
                attrModal.hide();
                Swal.fire('Success', res.message, 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save product attribute.', 'error');
            }
        });
    });

    // Handle select all
$('#selectAllAttrs').on('click', function () {
    $('.attr-checkbox').prop('checked', this.checked);
    toggleBulkDelete();
});

// Show/hide bulk delete button
$(document).on('change', '.attr-checkbox', toggleBulkDelete);

function toggleBulkDelete() {
    let selected = $('.attr-checkbox:checked').length;
    $('#bulkDeleteBtn').toggleClass('d-none', selected === 0);
}

    // Bulk delete
    $('#bulkDeleteBtn').on('click', function () {
        let ids = $('.attr-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (ids.length === 0) return;

        Swal.fire({
            title: 'Are you sure?',
            text: 'Selected attributes will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('admin.inventory.products.attributes.bulk-delete') }}",
                    method: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: ids
                    },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        $('#selectAllAttrs').prop('checked', false);
                        toggleBulkDelete();
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: function () {
                        Swal.fire('Error', 'Could not delete selected attributes.', 'error');
                    }
                });
            }
        });
    });

    // Show/hide bulk delete button based on selection
function toggleBulkDeleteButton() {
    const anyChecked = $('.row-checkbox:checked').length > 0;
    $('#bulkDeleteBtn').toggleClass('d-none', !anyChecked);
}

// Attach checkbox change event
$(document).on('change', '.row-checkbox, #selectAll', function () {
    toggleBulkDeleteButton();
});


});
</script>
@endpush
