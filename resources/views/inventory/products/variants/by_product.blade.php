@extends('layouts.master')

@section('title', 'Manage Product Variants')

@push('styles')
<style>
    .summary-pill{
        display:inline-block;
        margin:.2rem .35rem .2rem 0;
        padding:.45rem .8rem;
        background:#f8f9fc;
        border:1px solid #dbe3f0;
        border-radius:999px;
        font-size:.85rem;
        font-weight:600;
    }

    .variant-hero-card {
        border: 1px solid #e3e6f0;
        border-radius: .75rem;
        background: #fff;
    }

    .variant-hero-image {
        width: 100%;
        min-height: 220px;
        background: #f8f9fc;
        border: 1px dashed #d1d3e2;
        border-radius: .75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .variant-hero-image img {
        max-width: 100%;
        max-height: 260px;
        object-fit: contain;
    }

    .gallery-card {
        border: 1px solid #e3e6f0;
        border-radius: .75rem;
        overflow: hidden;
        background: #fff;
        height: 100%;
    }

    .gallery-preview {
        height: 130px;
        background: #f8f9fc;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-bottom: 1px solid #e3e6f0;
    }

    .gallery-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .gallery-meta {
        padding: .75rem;
    }

    .upload-dropzone {
        border: 2px dashed #d1d3e2;
        border-radius: .75rem;
        padding: 1rem;
        background: #f8f9fc;
    }

    .empty-state {
        border: 1px dashed #d1d3e2;
        border-radius: .75rem;
        padding: 2rem 1rem;
        text-align: center;
        color: #6c757d;
        background: #fcfcfd;
    }

    .attribute-chip {
        display: inline-block;
        margin: 0 .35rem .35rem 0;
        padding: .35rem .65rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: .78rem;
        font-weight: 600;
    }

    .badge-soft-success {
        background: rgba(28, 200, 138, .12);
        color: #1cc88a;
    }

    .badge-soft-danger {
        background: rgba(231, 74, 59, .12);
        color: #e74a3b;
    }

    .badge-soft-warning {
        background: rgba(246, 194, 62, .18);
        color: #9a6b00;
    }

    .variant-side-label {
        font-size: .8rem;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: .2rem;
        letter-spacing: .02em;
    }

    .variant-side-value {
        font-weight: 600;
        color: #343a40;
        margin-bottom: .9rem;
    }

    .nav-pills .nav-link {
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Variant Management</h1>
            <div class="text-muted">
                {{ $product->product_name }} ({{ $product->product_code }})
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.inventory.products.details', $product->id) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Product
            </a>
            <button class="btn btn-primary btn-sm" id="btn-add-variant">
                <i class="fas fa-plus"></i> Add Variant
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <span class="summary-pill">Brand: {{ $product->brand->brand_name ?? '—' }}</span>
            <span class="summary-pill">Unit: {{ $product->unit->name ?? '—' }}</span>
            <span class="summary-pill">Base Price: {{ $product->product_price !== null ? number_format($product->product_price,2) : '—' }}</span>
            <span class="summary-pill">Product Qty: {{ (int)($product->product_stock_quantity ?? 0) }}</span>
            @foreach($product->categories as $cat)
                <span class="summary-pill">{{ $cat->name }}</span>
            @endforeach
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
            <button class="btn btn-danger btn-sm" id="btn-bulk-delete" disabled>
                <i class="fas fa-trash"></i> Delete Selected
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="variantsTable" width="100%">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="checkAll"></th>
                            <th>#</th>
                            <th>SKU</th>
                            <th>Type</th>
                            <th>Attributes</th>
                            <th>Price</th>
                            <th>Stock Qty</th>
                            <th>Reorder Point</th>
                            <th>Status</th>
                            <th width="110">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Variant Create/Edit Modal --}}
<div class="modal fade" id="variantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form id="variantForm">
            @csrf
            <input type="hidden" id="variant_id" name="variant_id">
            <input type="hidden" id="product_id" name="product_id" value="{{ $product->id }}">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Variant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        {{-- Left --}}
                        <div class="col-lg-7">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="sku" id="sku" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Item Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="item_type" id="item_type" required>
                                        <option value="">Select Type</option>
                                        <option value="raw">Raw Material</option>
                                        <option value="wip">Work In Progress</option>
                                        <option value="fg">Finished Goods</option>
                                        <option value="tool">Tool</option>
                                        <option value="service">Service</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="price" id="price">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                    <input type="number" min="0" class="form-control" name="stock_quantity" id="stock_quantity" value="0" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Reorder Point</label>
                                    <input type="number" min="0" class="form-control" name="reorder_point" id="reorder_point" value="0">
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="font-weight-bold text-primary mb-0">Variant Attributes</h6>
                            </div>

                            <div id="attributeMatrixWrap">
                                <div class="text-muted">Loading attributes...</div>
                            </div>
                        </div>

                        {{-- Right --}}
                        <div class="col-lg-5">
                            <div class="variant-hero-card p-3 mb-3">
                                <div class="variant-side-label">Product</div>
                                <div class="variant-side-value">{{ $product->product_name }}</div>

                                <div class="variant-side-label">Product Code</div>
                                <div class="variant-side-value">{{ $product->product_code }}</div>

                                <div class="variant-side-label">Configured Attributes</div>
                                <div class="variant-side-value">
                                    @forelse($product->attributes as $attr)
                                        <span class="attribute-chip">{{ $attr->type->name ?? 'Attribute' }}</span>
                                    @empty
                                        <span class="text-muted">No attributes configured.</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="variant-hero-card p-3">
                                <ul class="nav nav-pills mb-3" id="variantSideTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#variantImagesPane" type="button">
                                            Images
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#variantDocumentsPane" type="button">
                                            Documents
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    {{-- Images Pane --}}
                                    <div class="tab-pane fade show active" id="variantImagesPane">
                                        <div id="variantImagesEmptyState" class="empty-state">
                                            <i class="fas fa-images fa-2x mb-3"></i>
                                            <div class="mb-2">Save the variant first to manage its images.</div>
                                        </div>

                                        <div id="variantImagesManager" style="display:none;">
                                            <div class="upload-dropzone mb-3">
                                                <form id="variantImagesUploadForm" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="mb-2">
                                                        <label class="form-label font-weight-bold">Upload Variant Images</label>
                                                        <input type="file" name="images[]" id="variantImagesInput" class="form-control" multiple accept="image/*">
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-upload"></i> Upload Images
                                                    </button>
                                                </form>
                                            </div>

                                            <div id="variantGalleryGrid" class="row"></div>
                                        </div>
                                    </div>

                                    {{-- Documents Pane --}}
                                    <div class="tab-pane fade" id="variantDocumentsPane">
                                        <div id="variantDocumentsEmptyState" class="empty-state">
                                            <i class="fas fa-file-alt fa-2x mb-3"></i>
                                            <div class="mb-2">Save the variant first to manage linked documents.</div>
                                        </div>

                                        <div id="variantDocumentsManager" style="display:none;">
                                            <div class="alert alert-info mb-3">
                                                After the variant is saved, documents can be attached through the shared ERP document linking workflow.
                                            </div>

                                            <div id="variantDocumentsDynamicWrap"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>{{-- right --}}
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveVariantBtn">
                        Save Variant
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Dynamic Variant Documents Modal/Section Loader Target --}}
<div id="variantDocumentPartialHost" class="d-none"></div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const productId = {{ $product->id }};
    let currentEditId = null;
    const variantModalEl = document.getElementById('variantModal');
    const variantModal = new bootstrap.Modal(variantModalEl);

    function showError(xhr, fallback = 'An error occurred.') {
        let msg = fallback;

        if (xhr.responseJSON?.message) {
            msg = xhr.responseJSON.message;
        }

        if (xhr.responseJSON?.errors) {
            msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
        }

        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: msg
        });
    }

    const table = $('#variantsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.inventory.products.variants.datatable", $product->id) }}',
        columns: [
            { data: 'checkbox', orderable:false, searchable:false },
            { data: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'sku', name:'sku' },
            { data: 'item_type', name:'item_type' },
            { data: 'attributes', name:'attributes', orderable:false, searchable:false },
            { data: 'price', name:'price' },
            { data: 'qty_on_hand', name:'stock_quantity' },
            { data: 'reorder_point', name:'reorder_point' },
            { data: 'stock_status', name:'stock_status', orderable:false, searchable:false },
            { data: 'action', orderable:false, searchable:false }
        ],
        order: [[2, 'asc']],
        drawCallback: function () {
            toggleBulkDelete();
        }
    });

    function toggleBulkDelete() {
        $('#btn-bulk-delete').prop('disabled', $('.row-checkbox:checked').length === 0);
    }

    $('#checkAll').on('change', function () {
        $('.row-checkbox').prop('checked', this.checked);
        toggleBulkDelete();
    });

    $(document).on('change', '.row-checkbox', function () {
        toggleBulkDelete();
    });

    function resetVariantForm() {
        currentEditId = null;
        $('#variantForm')[0].reset();
        $('#variant_id').val('');
        $('#product_id').val(productId);
        $('#attributeMatrixWrap').html('<div class="text-muted">Loading attributes...</div>');
        $('#variantImagesInput').val('');
        $('#variantGalleryGrid').html('');
        $('#variantImagesManager, #variantDocumentsManager').hide();
        $('#variantImagesEmptyState, #variantDocumentsEmptyState').show();
        $('#variantDocumentsDynamicWrap').html('');
    }

    function loadAttributeMatrix(selectedMap = {}) {
        $.get('{{ route("admin.inventory.products.attribute-matrix", $product->id) }}', function (res) {
            let html = '';

            if (!res.length) {
                html = '<div class="alert alert-info mb-0">No attributes/values configured for this product yet.</div>';
                $('#attributeMatrixWrap').html(html);
                return;
            }

            res.forEach(function (group) {
                html += `
                    <div class="border rounded p-3 mb-3">
                        <label class="form-label font-weight-bold d-block mb-2">${group.type_name}</label>
                        <select class="form-select variant-attr-select" name="attribute_values[${group.type_id}]">
                            <option value="">Select ${group.type_name}</option>
                `;

                group.values.forEach(function (val) {
                    const selected = selectedMap[group.type_id] && parseInt(selectedMap[group.type_id]) === parseInt(val.id)
                        ? 'selected'
                        : '';
                    html += `<option value="${val.id}" ${selected}>${val.value}</option>`;
                });

                html += `
                        </select>
                    </div>
                `;
            });

            $('#attributeMatrixWrap').html(html);
        });
    }

    function renderVariantGallery(images) {
        let html = '';

        if (!images.length) {
            html = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-images fa-2x mb-3"></i>
                        <div>No variant images uploaded yet.</div>
                    </div>
                </div>
            `;
            $('#variantGalleryGrid').html(html);
            return;
        }

        images.forEach(function (image) {
            html += `
                <div class="col-md-6 mb-3">
                    <div class="gallery-card">
                        <div class="gallery-preview">
                            <img src="${image.file_url}" alt="${image.display_title}">
                        </div>
                        <div class="gallery-meta">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="font-weight-bold small">${image.display_title ?? 'Image'}</div>
                                    <div class="text-muted small">${image.human_file_size ?? ''}</div>
                                </div>
                                ${image.is_primary ? `<span class="badge badge-soft-success">Primary</span>` : ``}
                            </div>

                            <div class="btn-group btn-group-sm w-100" role="group">
                                ${!image.is_primary ? `
                                    <button type="button" class="btn btn-outline-primary btn-set-primary-variant-image" data-id="${image.id}">
                                        <i class="fas fa-star"></i>
                                    </button>` : ``}
                                <a href="${image.file_url}" target="_blank" class="btn btn-outline-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-delete-variant-image" data-id="${image.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#variantGalleryGrid').html(html);
    }

    function loadVariantImages(variantId) {
        $.get('{{ route("admin.inventory.products.variants.images.index", "__ID__") }}'.replace('__ID__', variantId), function (res) {
            $('#variantImagesEmptyState').hide();
            $('#variantImagesManager').show();
            renderVariantGallery(Array.isArray(res) ? res : []);
        });
    }

    function enableVariantSidePanels(variantId) {
        $('#variantImagesEmptyState, #variantDocumentsEmptyState').hide();
        $('#variantImagesManager, #variantDocumentsManager').show();

        loadVariantImages(variantId);

        $('#variantDocumentsDynamicWrap').html(`
            <div class="border rounded p-2 bg-light small text-muted">
                Link documents to this variant from the shared document module view or variant-specific linked document partial.
            </div>
        `);
    }

    $('#btn-add-variant').on('click', function () {
        resetVariantForm();
        loadAttributeMatrix();
        variantModal.show();
    });

    $(document).on('click', '.edit-variant', function () {
        const id = $(this).data('id');
        resetVariantForm();
        currentEditId = id;

        $.get('{{ route("admin.inventory.products.variants.show", "__ID__") }}'.replace('__ID__', id), function (res) {
            $('#variant_id').val(res.id);
            $('#product_id').val(res.product_id);
            $('#sku').val(res.sku);
            $('#item_type').val(res.item_type);
            $('#price').val(res.price);
            $('#stock_quantity').val(res.stock_quantity);
            $('#reorder_point').val(res.reorder_point);

            let selectedMap = {};
            (res.selected_attrs || []).forEach(function (row) {
                selectedMap[row.type_id] = row.value_id;
            });

            loadAttributeMatrix(selectedMap);
            enableVariantSidePanels(res.id);
            variantModal.show();
        }).fail(function (xhr) {
            showError(xhr, 'Could not load variant.');
        });
    });

    $('#variantForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#variant_id').val();
        const url = id
            ? '{{ route("admin.inventory.products.variants.update", "__ID__") }}'.replace('__ID__', id)
            : '{{ route("admin.inventory.products.variants.store") }}';

        const formData = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            beforeSend: function () {
                Swal.fire({
                    title: 'Saving...',
                    text: 'Please wait.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                Swal.fire('Success', res.message || 'Saved successfully.', 'success');
                if (!id && res.variant && res.variant.id) {
                    $('#variant_id').val(res.variant.id);
                    currentEditId = res.variant.id;
                    enableVariantSidePanels(res.variant.id);
                } else if (id) {
                    enableVariantSidePanels(id);
                }
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                showError(xhr, 'Could not save variant.');
            }
        });
    });

    $('#variantImagesUploadForm').on('submit', function (e) {
        e.preventDefault();

        const variantId = $('#variant_id').val();
        if (!variantId) {
            Swal.fire('Save first', 'Please save the variant before uploading images.', 'warning');
            return;
        }

        const files = $('#variantImagesInput')[0].files;
        if (!files.length) {
            Swal.fire('No file selected', 'Please choose one or more images.', 'warning');
            return;
        }

        let formData = new FormData(this);

        $.ajax({
            url: '{{ route("admin.inventory.products.variants.images.upload", "__ID__") }}'.replace('__ID__', variantId),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            beforeSend: function () {
                Swal.fire({
                    title: 'Uploading...',
                    text: 'Please wait while images are uploaded.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                Swal.fire('Success', res.message || 'Images uploaded successfully.', 'success');
                $('#variantImagesInput').val('');
                loadVariantImages(variantId);
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                showError(xhr, 'Variant image upload failed.');
            }
        });
    });

    $(document).on('click', '.btn-set-primary-variant-image', function () {
        const id = $(this).data('id');
        const variantId = $('#variant_id').val();

        $.ajax({
            url: '{{ route("admin.inventory.products.variants.images.primary", "__ID__") }}'.replace('__ID__', id),
            type: 'POST',
            data: {
                _token: csrfToken
            },
            success: function () {
                Swal.fire('Updated', 'Primary image updated successfully.', 'success');
                loadVariantImages(variantId);
            },
            error: function (xhr) {
                showError(xhr, 'Could not set primary image.');
            }
        });
    });

    $(document).on('click', '.btn-delete-variant-image', function () {
        const id = $(this).data('id');
        const variantId = $('#variant_id').val();

        Swal.fire({
            title: 'Delete image?',
            text: 'This image will be removed permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route("admin.inventory.products.variants.images.destroy", "__ID__") }}'.replace('__ID__', id),
                type: 'POST',
                data: {
                    _token: csrfToken,
                    _method: 'DELETE'
                },
                success: function () {
                    Swal.fire('Deleted', 'Variant image deleted successfully.', 'success');
                    loadVariantImages(variantId);
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    showError(xhr, 'Could not delete image.');
                }
            });
        });
    });

    $(document).on('click', '.delete-variant', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete variant?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route("admin.inventory.products.variants.destroy", "__ID__") }}'.replace('__ID__', id),
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: csrfToken
                },
                success: function (res) {
                    Swal.fire('Deleted', res.message || 'Deleted successfully.', 'success');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    showError(xhr, 'Failed to delete variant.');
                }
            });
        });
    });

    $('#btn-bulk-delete').on('click', function () {
        const ids = $('.row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) return;

        Swal.fire({
            title: 'Delete selected variants?',
            text: `You are about to delete ${ids.length} variant(s).`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route("admin.inventory.products.variants.bulk-delete") }}',
                type: 'POST',
                data: {
                    _token: csrfToken,
                    ids: ids
                },
                success: function (res) {
                    Swal.fire('Deleted', res.message || 'Deleted successfully.', 'success');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    showError(xhr, 'Bulk delete failed.');
                }
            });
        });
    });
});
</script>
@endpush