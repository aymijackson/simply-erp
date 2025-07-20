@extends('layouts.master')

@section('title', 'Manage Products')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">Products <small class="text-muted">Inventory</small></h1>
        <div>
            <button class="btn btn-danger me-2 d-none" id="bulkDeleteBtn">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addProductBtn">
                <i class="fas fa-plus me-1"></i> Add Product
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
                <table class="table table-bordered w-100" id="productTable">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="selectAllProducts"></th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="productForm" class="modal-content" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="productId">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="productModalLabel">Add Product</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label for="productCode" class="form-label">Product Code</label>
                    <input type="text" class="form-control" id="productCode" name="product_code" required>
                </div>
                <div class="col-md-6">
                    <label for="productName" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="productName" name="product_name" required>
                </div>
                <div class="col-md-12">
                    <label for="productDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="productDescription" name="product_description" rows="3"></textarea>
                </div>
                <div class="col-md-4">
                    <label for="productPrice" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" id="productPrice" name="product_price">
                </div>
                <div class="col-md-4">
                    <label for="averageCost" class="form-label">Average Cost</label>
                    <input type="number" step="0.01" class="form-control" id="averageCost" name="average_cost">
                </div>
                <div class="col-md-4">
                    <label for="stockQuantity" class="form-label">Stock Quantity</label>
                    <input type="number" class="form-control" id="stockQuantity" name="product_stock_quantity">
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-control" id="category" name="category_id" required>
                        <option value="">-- Select --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="manufacturer_id" class="form-label">Manufacturer</label>
                    <select class="form-control" id="manufacturer_id" name="manufacturer_id" required>
                        <option value="">Select Manufacturer</option>
                        @foreach($manufacturers as $manufacturer)
                            <option value="{{ $manufacturer->id }}">{{ $manufacturer->manufacturer_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="brand_id" class="form-label">Brand</label>
                    <select class="form-control" id="brand_id" name="brand_id" required>
                        <option value="">Select Brand</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="unit" class="form-label">Unit</label>
                    <select class="form-control" id="unit" name="unit_id" required>
                        <option value="">-- Select --</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="packSize" class="form-label">Pack Size</label>
                    <input type="text" class="form-control" id="packSize" name="pack_size">
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                        <label class="form-check-label" for="isActive">
                            Active?
                        </label>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="productImage" class="form-label">Product Image</label>
                    <input type="file" class="form-control" id="productImage" name="product_image">
                </div>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelModalBtn">Cancel</button>
            <button type="submit" class="btn btn-success">Save Product</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    let table = $('#productTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.inventory.products.datatable') }}",
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'product_code' },
            { data: 'product_name' },
            { data: 'brand_name' },
            { data: 'category_name' },
            { data: 'unit_symbol' },
            { data: 'product_price' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#cancelModalBtn').click(function () {
        const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
        modal.hide();
    });


    $('#addProductBtn').click(function () {
        $('#productForm')[0].reset();
        $('#productId').val('');
        $('#productModalLabel').text('Add Product');
        const modal = new bootstrap.Modal(document.getElementById('productModal'));
        modal.show();
    });

    $('#manufacturer_id').on('change', function () {
        let manufacturerId = $(this).val();
        $('#brand_id').html('<option value="">Loading...</option>');

        if (manufacturerId) {
            $.get(`/brands/by-manufacturer/${manufacturerId}`, function (data) {
                let options = '<option value="">Select Brand</option>';
                $.each(data, function (id, name) {
                    options += `<option value="${id}">${name}</option>`;
                });
                $('#brand_id').html(options);
            });
        } else {
            $('#brand_id').html('<option value="">Select Brand</option>');
        }
    });

    $('#productForm').submit(function (e) {
        e.preventDefault();

        let form = $('#productForm')[0];
        let formData = new FormData(form);
        formData.set('is_active', $('#isActive').is(':checked') ? 1 : 0);

        let productId = $('#productId').val();
        let url = productId ? `/admin/inventory/products/${productId}` : `{{ route('admin.inventory.products.store') }}`;
        let type = productId ? 'POST' : 'POST'; // use POST for both, method override on backend

        if (productId) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: url,
            type: type,
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
                modal.hide();
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save product', 'error');
            }
        });
    });
});
</script>
@endpush
