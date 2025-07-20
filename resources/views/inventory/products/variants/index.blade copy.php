@extends('layouts.master')

@section('title', 'Manage Product Variants')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">Product Variants <small class="text-muted">Inventory</small></h1>
        <div>
            <button class="btn btn-danger me-2 d-none" id="deleteSelectedBtn">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addVariantBtn">
                <i class="fas fa-plus me-1"></i> Add Product Variant
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <h6>Total Products</h6>
                        <h4 class="mb-0" id="totalProducts">{{ number_format($products_count ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-bordered w-100" id="variantTable">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="selectAllVariants"></th>
                            <th>SKU</th>
                            <th>Product</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Stock Qty</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--  Add / Edit Variant Modal  -->
<div class="modal fade" id="variantModal" tabindex="-1" aria-labelledby="variantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="variantForm" class="modal-content">
            @csrf
            <input type="hidden" id="variantId">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="variantModalLabel">Add Variant</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-12">
                    <label for="product_id" class="form-label">Product *</label>
                    <select class="form-control" id="product_id" name="product_id" required>
                        <option value="">-- Select Product --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="sku" class="form-label">SKU *</label>
                    <input type="text" class="form-control" id="sku" name="sku" required>
                </div>
                <div class="col-md-6">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price">
                </div>
                <div class="col-md-6">
                    <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" required>
                </div>

                <div class="col-md-12">
                    <label for="attribute_values" class="form-label">Attribute Values</label>
                    <select multiple class="form-control" id="attribute_values" name="attribute_values[]">
                        @foreach($attributeValues as $val)
                            <option value="{{ $val->id }}">{{ $val->productAttribute->type->name }} : {{ $val->value }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelModalBtn" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-success">Save Variant</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    /* ---------- handlers (hoisted) ---------- */
    function loadForEdit () {
        const id = $(this).data('id');
        $.getJSON(`/admin/inventory/products/variants/${id}`, fillModal);
    }

    function fillModal (data){
        resetForm();
        $('#variantModalLabel').text('Edit Variant');
        $('#variantId').val(data.id);
        $('#product_id').val(data.product_id);
        $('#sku').val(data.sku);
        $('#price').val(data.price);
        $('#stock_quantity').val(data.stock_quantity);
        $('#attribute_values').val(data.attribute_values?.map(v=>v.id)).trigger('change');
        new bootstrap.Modal('#variantModal').show();
    }
    const table = $('#variantTable').DataTable({
        responsive : true,
        serverSide : true,
        ajax : "{{ route('admin.inventory.products.variants.datatable') }}",
        columns : [
            { data:'checkbox', orderable:false, searchable:false },
            { data:'sku' },
            { data:'product_name' },
            { data:'price', className:'text-end' },
            { data:'stock_quantity', className:'text-end' },
            { data:'action', orderable:false, searchable:false, className:'text-end' },
        ],
        order:[[1,'asc']],
        drawCallback(){
            $('.edit-btn').on('click', loadForEdit);
            $('#selectAllVariants').prop('checked', false).on('change', () => {
                $('.row-checkbox').prop('checked', $('#selectAllVariants').is(':checked')).trigger('change');
            });
        }
    });


    $('#cancelModalBtn').click(function () {
        const modal = bootstrap.Modal.getInstance(document.getElementById('variantModal'));
        modal.hide();
    });


    $('#addVariantBtn').click(function () {
        resetForm();
        $('#variantModalLabel').text('Add Variant');
        new bootstrap.Modal('#variantModal').show();
    });

   
    $('#variantForm').submit(function (e) {
        e.preventDefault();

        let form = $('#variantForm')[0];
        let formData = new FormData(form);

        let productVariantId = $('#variantId').val();
        let url = productVariantId ? `/admin/inventory/products/variants/${productVariantId}` : `{{ route('admin.inventory.products.variants.store') }}`;
        let type = productVariantId ? 'POST' : 'POST'; // use POST for both, method override on backend

        if (productVariantId) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: url,
            type: type,
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('variantModal'));
                modal.hide();
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save product variant', 'error');
            }
        });
    });

    /* -------------------------------------------------------------
     * Helpers
     * ----------------------------------------------------------- */
    function resetForm(){
        $('#variantForm')[0].reset();
        $('#attribute_values').val([]).trigger('change');
        $('#variantId').val('');
    }

    $('#variantTable').on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/inventory/products/variants/') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    }
                });
            }
        });
    });

    // Select All
    $('#selectAll').on('click', function () {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        toggleDeleteSelectedBtn();
    });

    $('#variantTable').on('change', '.row-checkbox', function () {
        toggleDeleteSelectedBtn();
    });

    function toggleDeleteSelectedBtn() {
        let anyChecked = $('.row-checkbox:checked').length > 0;
        $('#deleteSelectedBtn').toggleClass('d-none', !anyChecked);
    }

    // Bulk Delete
    $('#deleteSelectedBtn').click(function () {
        const ids = $('.row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (ids.length === 0) return;

        Swal.fire({
            title: `Delete ${ids.length} selected variant(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                $.post("{{ route('admin.inventory.products.variants.bulk-delete') }}", {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                }, function (response) {
                    table.ajax.reload();
                    $('#selectAll').prop('checked', false);
                    $('#deleteSelectedBtn').addClass('d-none');
                    Swal.fire('Deleted!', response.message, 'success');
                }).fail(function () {
                    Swal.fire('Error', 'Failed to delete selected product variant.', 'error');
                });
            }
        });
    });
});
</script>
@endpush
