@extends('layouts.master')

@section('title', 'Manage Products')

@push('styles')
<style>
    .metric-card {
        border: 1px solid #e3e6f0;
        border-radius: .75rem;
        overflow: hidden;
    }

    .metric-icon {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.1rem;
    }

    .table-product-thumb {
        width: 46px;
        height: 46px;
        object-fit: cover;
        border-radius: .5rem;
        border: 1px solid #e3e6f0;
        background: #f8f9fc;
    }

    .table-product-thumb-placeholder {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .5rem;
        border: 1px solid #e3e6f0;
        background: #f8f9fc;
        color: #b7b9cc;
    }

    .dataTables_wrapper .dataTables_filter input {
        margin-left: .5rem;
    }

    .form-section-title {
        font-size: .85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6c757d;
        margin-bottom: .75rem;
    }

    .image-preview-wrap {
        border: 1px dashed #d1d3e2;
        border-radius: .75rem;
        min-height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fc;
        overflow: hidden;
    }

    .image-preview-wrap img {
        max-width: 100%;
        max-height: 220px;
        object-fit: contain;
    }

    .existing-image-note {
        font-size: .85rem;
        color: #6c757d;
    }

    .select2-container--default .select2-selection--multiple {
        min-height: 38px;
        border: 1px solid #d1d3e2;
    }

    .product-code-badge {
        font-size: .75rem;
        font-weight: 600;
        padding: .35rem .5rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
    }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h1 class="h3 text-primary mb-1">Products <small class="text-muted">Inventory</small></h1>
            <div class="text-muted">Manage products, categories, pricing, images, and variant setup.</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-danger d-none" id="bulkDeleteBtn">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addProductBtn">
                <i class="fas fa-plus me-1"></i> Add Product
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm metric-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="metric-icon bg-primary text-white shadow me-3">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Products</div>
                        <h4 class="mb-0" id="totalProducts">{{ number_format($products_count ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle w-100" id="productTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAllProducts"></th>
                            <th width="70">Image</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th width="170">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add/Edit Product Modal --}}
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form id="productForm" class="modal-content" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="productId" name="product_id">
            <input type="hidden" id="removeImage" name="remove_image" value="0">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="productModalLabel">Add Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row">
                    {{-- Left side --}}
                    <div class="col-lg-8">
                        <div class="form-section-title">Basic Information</div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="productCode" class="form-label">Product Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="productCode" name="product_code" required>
                            </div>

                            <div class="col-md-6">
                                <label for="productName" class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="productName" name="product_name" required>
                            </div>

                            <div class="col-md-12">
                                <label for="productDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="productDescription" name="product_description" rows="3"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="productPrice" class="form-label">Selling Price</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="productPrice" name="product_price">
                            </div>

                            <div class="col-md-4">
                                <label for="averageCost" class="form-label">Average Cost</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="averageCost" name="average_cost">
                            </div>

                            <div class="col-md-4">
                                <label for="stockQuantity" class="form-label">Stock Quantity</label>
                                <input type="number" min="0" class="form-control" id="stockQuantity" name="product_stock_quantity">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="form-section-title">Classification</div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="categories" class="form-label">Categories <span class="text-danger">*</span></label>
                                <select class="form-control" id="categories" name="category_ids[]" multiple required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">You can choose one or more categories.</small>
                            </div>

                            <div class="col-md-4">
                                <label for="manufacturer_id" class="form-label">Manufacturer</label>
                                <select class="form-control" id="manufacturer_id" name="manufacturer_id">
                                    <option value="">Select Manufacturer</option>
                                    @foreach($manufacturers as $manufacturer)
                                        <option value="{{ $manufacturer->id }}">{{ $manufacturer->manufacturer_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="brand_id" class="form-label">Brand <span class="text-danger">*</span></label>
                                <select class="form-control" id="brand_id" name="brand_id" required>
                                    <option value="">Select Brand</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="unit" class="form-label">Unit <span class="text-danger">*</span></label>
                                <select class="form-control" id="unit" name="unit_id" required>
                                    <option value="">-- Select --</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="packSize" class="form-label">Pack Size</label>
                                <input type="text" class="form-control" id="packSize" name="pack_size">
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                                    <label class="form-check-label" for="isActive">
                                        Product is active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right side --}}
                    <div class="col-lg-4">
                        <div class="form-section-title">Primary Product Image</div>

                        <div class="image-preview-wrap mb-3" id="productImagePreviewWrap">
                            <div class="text-center text-muted" id="productImagePlaceholder">
                                <i class="fas fa-image fa-3x mb-2"></i>
                                <div>No image selected</div>
                            </div>
                            <img src="" alt="Preview" id="productImagePreview" style="display:none;">
                        </div>

                        <div class="mb-3">
                            <label for="productImage" class="form-label">Upload Image</label>
                            <input type="file" class="form-control" id="productImage" name="product_image" accept="image/*">
                            <small class="text-muted">You can upload more images later from the product details page.</small>
                        </div>

                        <div class="existing-image-note mb-3 d-none" id="existingImageNoteWrap">
                            <div class="mb-2">
                                <strong>Existing Image:</strong> This product already has a primary image.
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeExistingImageBtn">
                                <i class="fas fa-trash"></i> Remove Existing Image
                            </button>
                        </div>

                        <div class="alert alert-light border">
                            <div class="small text-muted mb-1">Tip</div>
                            <div class="small">
                                After saving the product, use the details page to manage:
                                <ul class="mb-0 ps-3 mt-2">
                                    <li>Multiple product images</li>
                                    <li>Variants</li>
                                    <li>Linked documents</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>{{-- row --}}
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelModalBtn">Cancel</button>
                <button type="submit" class="btn btn-success" id="saveProductBtn">
                    <i class="fas fa-save me-1"></i> Save Product
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const CSRF = @json(csrf_token());
    const productModalEl = document.getElementById('productModal');
    const productModal = new bootstrap.Modal(productModalEl);

    function openModal() {
        productModal.show();
    }

    function closeModal() {
        productModal.hide();
    }

    function setImagePreview(url = null) {
        if (url) {
            $('#productImagePreview').attr('src', url).show();
            $('#productImagePlaceholder').hide();
        } else {
            $('#productImagePreview').attr('src', '').hide();
            $('#productImagePlaceholder').show();
        }
    }

    function resetForm() {
        $('#productForm')[0].reset();
        $('#productId').val('');
        $('#removeImage').val('0');
        $('#categories').val(null).trigger('change');
        $('#brand_id').html('<option value="">Select Brand</option>');
        $('#manufacturer_id').val('');
        $('#productModalLabel').text('Add Product');
        $('#existingImageNoteWrap').addClass('d-none');
        setImagePreview(null);
    }

    function loadBrands(manufacturerId, selectedBrandId = null) {
        $('#brand_id').html('<option value="">Loading...</option>');

        if (!manufacturerId) {
            $('#brand_id').html('<option value="">Select Brand</option>');
            return;
        }

        $.get(`/brands/by-manufacturer/${manufacturerId}`, function (data) {
            let options = '<option value="">Select Brand</option>';

            $.each(data, function (id, name) {
                options += `<option value="${id}">${name}</option>`;
            });

            $('#brand_id').html(options);

            if (selectedBrandId) {
                $('#brand_id').val(String(selectedBrandId));
            }
        }).fail(function () {
            $('#brand_id').html('<option value="">Select Brand</option>');
        });
    }

    function updateBulkDeleteVisibility() {
        const anyChecked = $('#productTable tbody .row-checkbox:checked').length > 0;
        $('#bulkDeleteBtn').toggleClass('d-none', !anyChecked);
    }

    function parseError(xhr, fallback = 'Something went wrong.') {
        if (xhr.responseJSON?.errors) {
            return Object.values(xhr.responseJSON.errors).flat().join('<br>');
        }
        return xhr.responseJSON?.message || fallback;
    }

    $('#categories').select2({
        width: '100%',
        placeholder: '-- Select categories --',
        dropdownParent: $('#productModal')
    });

    const table = $('#productTable').DataTable({
        processing: true,
        responsive: true,
        serverSide: true,
        ajax: "{{ route('admin.inventory.products.datatable') }}",
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'image', orderable: false, searchable: false },
            { data: 'product_code' },
            { data: 'product_name' },
            { data: 'brand_name' },
            { data: 'category_names' },
            { data: 'uom' },
            { data: 'product_price' },
            { data: 'action', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            updateBulkDeleteVisibility();
        }
    });

    $('#addProductBtn').on('click', function () {
        resetForm();
        openModal();
    });

    $('#manufacturer_id').on('change', function () {
        loadBrands($(this).val(), null);
    });

    $('#productImage').on('change', function () {
        const file = this.files?.[0];
        if (!file) {
            setImagePreview(null);
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            setImagePreview(e.target.result);
        };
        reader.readAsDataURL(file);
    });

    $('#removeExistingImageBtn').on('click', function () {
        $('#removeImage').val('1');
        setImagePreview(null);
        $('#existingImageNoteWrap').removeClass('d-none').html(`
            <div class="alert alert-warning py-2 mb-0">
                Existing image will be removed when you save this product.
            </div>
        `);
    });

    $('#productForm').on('submit', function (e) {
        e.preventDefault();

        const form = $('#productForm')[0];
        const formData = new FormData(form);
        formData.set('is_active', $('#isActive').is(':checked') ? 1 : 0);

        const productId = $('#productId').val();
        const url = productId
            ? `{{ url('/admin/inventory/products') }}/${productId}`
            : `{{ route('admin.inventory.products.store') }}`;

        if (productId) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function () {
                Swal.fire({
                    title: 'Saving...',
                    text: 'Please wait while the product is being saved.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            }
        })
        .done(res => {
            closeModal();
            table.ajax.reload(null, false);
            Swal.fire('Success', res.message || 'Saved successfully.', 'success');
        })
        .fail(xhr => {
            Swal.fire('Error', parseError(xhr, 'Failed to save product.'), 'error');
        });
    });

    $(document).on('click', '.edit-product', function () {
        resetForm();
        $('#productModalLabel').text('Edit Product');

        const $btn = $(this);
        const rec = $btn.data('record');
        const id = $btn.data('id') || rec?.id;

        function fillAndOpen(p) {
            $('#productId').val(p.id);
            $('#productCode').val(p.product_code);
            $('#productName').val(p.product_name);
            $('#productDescription').val(p.product_description || '');
            $('#productPrice').val(p.product_price || '');
            $('#averageCost').val(p.average_cost || '');
            $('#stockQuantity').val(p.product_stock_quantity || '');
            $('#packSize').val(p.pack_size || '');
            $('#unit').val(p.unit_id || '');
            $('#manufacturer_id').val(p.manufacturer_id || '');
            $('#isActive').prop('checked', !!Number(p.is_active));
            $('#removeImage').val('0');

            const catIds = (p.category_ids || []).map(String);
            $('#categories').val(catIds).trigger('change');

            if (p.manufacturer_id) {
                loadBrands(p.manufacturer_id, p.brand_id || null);
            } else {
                $('#brand_id').html('<option value="">Select Brand</option>');
            }

            if (p.image_url) {
                setImagePreview(p.image_url);
                $('#existingImageNoteWrap').removeClass('d-none').html(`
                    <div class="mb-2">
                        <strong>Existing Image:</strong> This product already has a primary image.
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeExistingImageBtn">
                        <i class="fas fa-trash"></i> Remove Existing Image
                    </button>
                `);
            } else {
                setImagePreview(null);
                $('#existingImageNoteWrap').addClass('d-none');
            }

            openModal();
        }

        if (rec) {
            fillAndOpen(rec);
        } else if (id) {
            $.get(`{{ url('/admin/inventory/products') }}/${id}`)
                .done(fillAndOpen)
                .fail(() => Swal.fire('Error', 'Failed to fetch product.', 'error'));
        } else {
            Swal.fire('Error', 'No product id found.', 'error');
        }
    });

    $(document).on('click', '.delete-product', function () {
        const id = $(this).data('id');
        if (!id) return;

        Swal.fire({
            title: 'Delete this product?',
            text: 'This will remove the product and related image records.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(res => {
            if (!res.isConfirmed) return;

            $.ajax({
                url: `{{ url('/admin/inventory/products') }}/${id}`,
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: CSRF
                }
            })
            .done(res => {
                table.ajax.reload(null, false);
                Swal.fire('Deleted', res.message || 'Product removed.', 'success');
            })
            .fail(xhr => {
                Swal.fire('Error', parseError(xhr, 'Delete failed.'), 'error');
            });
        });
    });

    $(document).on('change', '#productTable tbody .row-checkbox', updateBulkDeleteVisibility);

    $('#selectAllProducts').on('change', function () {
        const checked = this.checked;
        $('#productTable tbody .row-checkbox').prop('checked', checked);
        updateBulkDeleteVisibility();
    });

    $('#bulkDeleteBtn').on('click', function () {
        const ids = $('#productTable tbody .row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) return;

        Swal.fire({
            title: `Delete ${ids.length} selected product(s)?`,
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(res => {
            if (!res.isConfirmed) return;

            $.post(`{{ route('admin.inventory.products.bulk-delete') }}`, { _token: CSRF, ids })
                .done(resp => {
                    $('#selectAllProducts').prop('checked', false);
                    $('#bulkDeleteBtn').addClass('d-none');
                    table.ajax.reload(null, false);
                    Swal.fire('Deleted', resp.message || 'Products removed.', 'success');
                })
                .fail(xhr => {
                    Swal.fire('Error', parseError(xhr, 'Bulk delete failed.'), 'error');
                });
        });
    });

    $('#cancelModalBtn').on('click', closeModal);
});
</script>
@endpush