@extends('layouts.master')

@section('title', 'Product Details')

@push('styles')
<style>
    .info-label {
        font-size: 0.82rem;
        color: #6c757d;
        margin-bottom: .2rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .info-value {
        font-weight: 600;
        color: #343a40;
        word-break: break-word;
    }

    .hero-image-box {
        width: 100%;
        min-height: 280px;
        border: 1px dashed #ced4da;
        border-radius: .75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fc;
        overflow: hidden;
    }

    .hero-image-box img {
        max-width: 100%;
        max-height: 340px;
        object-fit: contain;
    }

    .thumb-card {
        border: 1px solid #e3e6f0;
        border-radius: .75rem;
        overflow: hidden;
        background: #fff;
        height: 100%;
    }

    .thumb-preview {
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        overflow: hidden;
    }

    .thumb-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .thumb-meta {
        padding: .75rem;
    }

    .attr-badge,
    .cat-badge {
        display: inline-block;
        margin: 0 .35rem .35rem 0;
        padding: .4rem .65rem;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 600;
    }

    .attr-badge {
        background: #eef2ff;
        color: #3730a3;
    }

    .cat-badge {
        background: #ecfeff;
        color: #155e75;
    }

    .stat-tile {
        border: 1px solid #e3e6f0;
        border-radius: .75rem;
        background: #fff;
        padding: 1rem;
        text-align: center;
        height: 100%;
    }

    .stat-tile .stat-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .stat-tile .stat-label {
        font-size: .8rem;
        color: #6c757d;
        margin-top: .25rem;
    }

    .nav-tabs .nav-link {
        font-weight: 600;
    }

    .tab-pane {
        padding-top: 1rem;
    }

    .empty-state {
        border: 1px dashed #d1d3e2;
        border-radius: .75rem;
        padding: 2rem 1rem;
        text-align: center;
        color: #6c757d;
        background: #fcfcfd;
    }

    .upload-dropzone {
        border: 2px dashed #d1d3e2;
        border-radius: .75rem;
        padding: 1rem;
        background: #f8f9fc;
    }

    .variant-mini-table th,
    .variant-mini-table td {
        vertical-align: middle;
    }

    .badge-soft-primary {
        background: rgba(78, 115, 223, .12);
        color: #4e73df;
    }

    .badge-soft-success {
        background: rgba(28, 200, 138, .12);
        color: #1cc88a;
    }

    .badge-soft-warning {
        background: rgba(246, 194, 62, .18);
        color: #9a6b00;
    }

    .badge-soft-danger {
        background: rgba(231, 74, 59, .12);
        color: #e74a3b;
    }

    .badge-soft-secondary {
        background: rgba(133, 135, 150, .12);
        color: #858796;
    }

    .stock-summary-box {
        border: 1px solid #e3e6f0;
        border-radius: .75rem;
        padding: 1rem;
        background: #fff;
    }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    $primaryGalleryImage = $product->primaryImage ?? $product->images->where('is_primary', true)->first() ?? $product->images->first();
    $galleryImages = $product->images ?? collect();
    $linkedDocuments = optional($product->documentLinks)->count() ? $product->documentLinks : collect();

    $variantIds = $product->variants->pluck('id')->filter()->values()->all();

    $variantStockMap = collect();
    if (!empty($variantIds)) {
        $variantStoreRows = \Illuminate\Support\Facades\DB::table('v_stock_levels as vsl')
            ->whereIn('vsl.product_variant_id', $variantIds)
            ->selectRaw('
                vsl.product_variant_id,
                SUM(COALESCE(vsl.qty_on_hand,0)) as qty_on_hand,
                SUM(COALESCE(vsl.value_on_hand,0)) as value_on_hand
            ')
            ->groupBy('vsl.product_variant_id')
            ->get();

        $variantStockMap = $variantStoreRows->keyBy('product_variant_id');
    }
@endphp

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">{{ $product->product_name }}</h1>
            <div class="text-muted">
                Code: <strong>{{ $product->product_code }}</strong>
                @if($product->is_active)
                    <span class="badge badge-soft-success ms-2">Active</span>
                @else
                    <span class="badge badge-soft-secondary ms-2">Inactive</span>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.inventory.products.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            <button class="btn btn-warning btn-sm edit-product" data-id="{{ $product->id }}">
                <i class="fas fa-edit"></i> Edit Product
            </button>

            <a href="{{ route('admin.inventory.products.variants.page', $product->id) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-cubes"></i> Manage Variants
            </a>
        </div>
    </div>

    <div class="row">

        <div class="col-lg-4">

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Primary Product Image</h6>
                </div>
                <div class="card-body">
                    <div class="hero-image-box mb-3" id="primaryImageBox">
                        @if($primaryGalleryImage)
                            <img src="{{ $primaryGalleryImage->file_url }}" alt="{{ $product->product_name }}">
                        @else
                            <div class="text-center text-muted">
                                <i class="fas fa-image fa-3x mb-2"></i>
                                <div>No Image</div>
                            </div>
                        @endif
                    </div>

                    <div class="small text-muted">
                        Product image gallery supports multiple images and one primary display image.
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Stats</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 mb-3">
                            <div class="stat-tile">
                                <div class="stat-value text-primary">{{ $variantCount }}</div>
                                <div class="stat-label">Variants</div>
                            </div>
                        </div>

                        <div class="col-6 mb-3">
                            <div class="stat-tile">
                                <div class="stat-value text-success">{{ number_format((float)($stockTotals->total_qty ?? 0), 2) }}</div>
                                <div class="stat-label">Qty On Hand</div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="stat-tile">
                                <div class="stat-value text-info">
                                    {{ $product->product_price !== null ? number_format($product->product_price, 2) : '—' }}
                                </div>
                                <div class="stat-label">Selling Price</div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="stat-tile">
                                <div class="stat-value text-dark">
                                    {{ $product->average_cost !== null ? number_format($product->average_cost, 2) : '—' }}
                                </div>
                                <div class="stat-label">Average Cost</div>
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            <div class="stat-tile">
                                <div class="stat-value text-primary">
                                    {{ number_format((float)($stockTotals->total_value ?? 0), 2) }}
                                </div>
                                <div class="stat-label">Stock Value</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-8">

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <ul class="nav nav-tabs card-header-tabs border-bottom-0" id="productDetailTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-pane" type="button" role="tab">
                                Overview
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="variants-tab" data-bs-toggle="tab" data-bs-target="#variants-pane" type="button" role="tab">
                                Variants
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="attributes-tab" data-bs-toggle="tab" data-bs-target="#attributes-pane" type="button" role="tab">
                                Attributes
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images-pane" type="button" role="tab">
                                Images
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents-pane" type="button" role="tab">
                                Documents
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock-pane" type="button" role="tab">
                                Stock by Store
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="productDetailTabsContent">

                        <div class="tab-pane fade show active" id="overview-pane" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Product Name</div>
                                    <div class="info-value">{{ $product->product_name }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Product Code</div>
                                    <div class="info-value">{{ $product->product_code }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Brand</div>
                                    <div class="info-value">{{ $product->brand->brand_name ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Manufacturer</div>
                                    <div class="info-value">{{ $product->brand->manufacturer->manufacturer_name ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Unit</div>
                                    <div class="info-value">
                                        {{ $product->unit->name ?? '—' }}
                                        @if($product->unit?->symbol)
                                            ({{ $product->unit->symbol }})
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Pack Size</div>
                                    <div class="info-value">{{ $product->pack_size ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Selling Price</div>
                                    <div class="info-value">
                                        {{ $product->product_price !== null ? number_format($product->product_price, 2) : '—' }}
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label">Average Cost</div>
                                    <div class="info-value">
                                        {{ $product->average_cost !== null ? number_format($product->average_cost, 2) : '—' }}
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="info-label">Description</div>
                                <div class="info-value" style="font-weight: 400;">
                                    {{ $product->product_description ?: 'No description provided.' }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="info-label">Categories</div>
                                <div>
                                    @forelse($product->categories as $cat)
                                        <span class="cat-badge">{{ $cat->name }}</span>
                                    @empty
                                        <span class="text-muted">No categories assigned.</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="stock-summary-box">
                                <div class="row">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="info-label">Total Qty On Hand</div>
                                        <div class="info-value">{{ number_format((float)($stockTotals->total_qty ?? 0), 2) }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Total Stock Value</div>
                                        <div class="info-value">{{ number_format((float)($stockTotals->total_value ?? 0), 2) }}</div>
                                    </div>
                                </div>
                                <div class="small text-muted mt-3">
                                    These balances are derived from stock transactions through <strong>v_stock_levels</strong>.
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="variants-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="font-weight-bold text-primary mb-0">Product Variants</h6>
                                <a href="{{ route('admin.inventory.products.variants.page', $product->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-cubes"></i> Open Variant Manager
                                </a>
                            </div>

                            @if($product->variants->count())
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover variant-mini-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>SKU</th>
                                                <th>Type</th>
                                                <th>Attributes</th>
                                                <th class="text-end">Price</th>
                                                <th class="text-end">Qty On Hand</th>
                                                <th class="text-end">Stock Value</th>
                                                <th class="text-end">Reorder Point</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($product->variants as $variant)
                                                @php
                                                    $variantLabel = $variant->attributeValues->map(function ($value) {
                                                        $typeName = $value->attribute?->type?->name;
                                                        return $typeName ? ($typeName . ': ' . $value->value) : $value->value;
                                                    })->filter()->implode(' | ');

                                                    $stockRow = $variantStockMap->get($variant->id);
                                                    $qtyOnHand = (float) ($stockRow->qty_on_hand ?? 0);
                                                    $valueOnHand = (float) ($stockRow->value_on_hand ?? 0);
                                                    $reorderPoint = (float) ($variant->reorder_point ?? 0);
                                                @endphp
                                                <tr>
                                                    <td>{{ $variant->sku }}</td>
                                                    <td>{{ strtoupper($variant->item_type ?? '-') }}</td>
                                                    <td>{{ $variantLabel ?: '—' }}</td>
                                                    <td class="text-end">{{ $variant->price !== null ? number_format($variant->price, 2) : '—' }}</td>
                                                    <td class="text-end">{{ number_format($qtyOnHand, 2) }}</td>
                                                    <td class="text-end">{{ number_format($valueOnHand, 2) }}</td>
                                                    <td class="text-end">{{ number_format($reorderPoint, 2) }}</td>
                                                    <td>
                                                        @if($qtyOnHand <= 0)
                                                            <span class="badge badge-soft-danger">Out of Stock</span>
                                                        @elseif($reorderPoint > 0 && $qtyOnHand <= $reorderPoint)
                                                            <span class="badge badge-soft-warning">Low Stock</span>
                                                        @else
                                                            <span class="badge badge-soft-success">In Stock</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-cubes fa-2x mb-3"></i>
                                    <div class="mb-2">No variants created yet.</div>
                                    <a href="{{ route('admin.inventory.products.variants.page', $product->id) }}" class="btn btn-sm btn-primary">
                                        Create First Variant
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="attributes-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="font-weight-bold text-primary mb-0">Attribute Configuration</h6>
                                <a href="{{ route('admin.inventory.products.variants.page', $product->id) }}" class="btn btn-sm btn-outline-primary">
                                    Manage Variants
                                </a>
                            </div>

                            @forelse($product->attributes as $attr)
                                <div class="border rounded p-3 mb-3">
                                    <div class="font-weight-bold text-dark mb-2">
                                        {{ $attr->type->name ?? 'Attribute Type' }}
                                    </div>
                                    <div>
                                        @forelse($attr->values as $val)
                                            <span class="attr-badge">{{ $val->value }}</span>
                                        @empty
                                            <span class="text-muted">No values added yet.</span>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <i class="fas fa-sliders-h fa-2x mb-3"></i>
                                    <div>No product attributes configured yet.</div>
                                </div>
                            @endforelse
                        </div>

                        <div class="tab-pane fade" id="images-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="font-weight-bold text-primary mb-0">Product Gallery</h6>
                            </div>

                            <div class="upload-dropzone mb-4">
                                <form id="productImagesUploadForm" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row align-items-end">
                                        <div class="col-md-9 mb-3 mb-md-0">
                                            <label class="form-label font-weight-bold">Upload Images</label>
                                            <input type="file" name="images[]" id="productImagesInput" class="form-control" multiple accept="image/*">
                                            <small class="text-muted">You can upload multiple product images at once.</small>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-upload"></i> Upload
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div id="productGalleryWrapper">
                                @if($galleryImages->count())
                                    <div class="row" id="productGalleryGrid">
                                        @foreach($galleryImages as $image)
                                            <div class="col-md-6 col-xl-4 mb-4 gallery-item" data-id="{{ $image->id }}">
                                                <div class="thumb-card">
                                                    <div class="thumb-preview">
                                                        <img src="{{ $image->file_url }}" alt="{{ $image->display_title }}">
                                                    </div>
                                                    <div class="thumb-meta">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <div class="font-weight-bold small">{{ $image->display_title }}</div>
                                                                <div class="text-muted small">{{ $image->human_file_size }}</div>
                                                            </div>
                                                            @if($image->is_primary)
                                                                <span class="badge badge-soft-success">Primary</span>
                                                            @endif
                                                        </div>

                                                        <div class="btn-group btn-group-sm w-100" role="group">
                                                            @if(!$image->is_primary)
                                                                <button type="button"
                                                                        class="btn btn-outline-primary btn-set-primary-image"
                                                                        data-id="{{ $image->id }}">
                                                                    <i class="fas fa-star"></i> Set Primary
                                                                </button>
                                                            @endif

                                                            <a href="{{ $image->file_url }}" target="_blank" class="btn btn-outline-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>

                                                            <button type="button"
                                                                    class="btn btn-outline-danger btn-delete-product-image"
                                                                    data-id="{{ $image->id }}">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="empty-state" id="productGalleryEmpty">
                                        <i class="fas fa-images fa-2x mb-3"></i>
                                        <div>No gallery images uploaded yet.</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="tab-pane fade" id="documents-pane" role="tabpanel">
                            @include('documents.partials.linked-documents-tab', ['model' => $product])
                        </div>

                        <div class="tab-pane fade" id="stock-pane" role="tabpanel">
                            @if($storeStock->count())
                                <div class="mb-3">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="stat-tile">
                                                <div class="stat-value text-success">{{ number_format((float)($stockTotals->total_qty ?? 0), 2) }}</div>
                                                <div class="stat-label">Total Qty On Hand</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="stat-tile">
                                                <div class="stat-value text-primary">{{ number_format((float)($stockTotals->total_value ?? 0), 2) }}</div>
                                                <div class="stat-label">Total Stock Value</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Store</th>
                                                <th class="text-end">Qty On Hand</th>
                                                <th class="text-end">Value On Hand</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($storeStock as $row)
                                                <tr>
                                                    <td>{{ $row->store_name }}</td>
                                                    <td class="text-end">{{ number_format((float)$row->qty_on_hand, 2) }}</td>
                                                    <td class="text-end">{{ number_format((float)$row->value_on_hand, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="font-weight-bold">
                                                <td>Total</td>
                                                <td class="text-end">{{ number_format((float)($stockTotals->total_qty ?? 0), 2) }}</td>
                                                <td class="text-end">{{ number_format((float)($stockTotals->total_value ?? 0), 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="small text-muted mt-2">
                                    Stock balances are derived from stock transactions via <strong>v_stock_levels</strong>.
                                </div>
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-warehouse fa-2x mb-3"></i>
                                    <div>No stock-by-store data available yet.</div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const productId = @json($product->id);

    function showErrorMessage(xhr, fallback = 'An error occurred.') {
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

    $('#productImagesUploadForm').on('submit', function (e) {
        e.preventDefault();

        const files = $('#productImagesInput')[0].files;
        if (!files.length) {
            Swal.fire('No file selected', 'Please select one or more images to upload.', 'warning');
            return;
        }

        let formData = new FormData(this);

        $.ajax({
            url: '{{ route("admin.inventory.products.images.upload", $product->id) }}',
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
                    text: 'Please wait while the images are uploaded.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (res) {
                Swal.fire('Success', res.message || 'Images uploaded successfully.', 'success')
                    .then(() => window.location.reload());
            },
            error: function (xhr) {
                showErrorMessage(xhr, 'Image upload failed.');
            }
        });
    });

    $(document).on('click', '.btn-set-primary-image', function () {
        let id = $(this).data('id');

        $.ajax({
            url: '{{ route("admin.inventory.products.images.primary", "__ID__") }}'.replace('__ID__', id),
            type: 'POST',
            data: {
                _token: csrfToken
            },
            success: function () {
                Swal.fire('Updated', 'Primary image updated successfully.', 'success')
                    .then(() => window.location.reload());
            },
            error: function (xhr) {
                showErrorMessage(xhr, 'Could not update primary image.');
            }
        });
    });

    $(document).on('click', '.btn-delete-product-image', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'Delete image?',
            text: 'This image will be removed permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route("admin.inventory.products.images.destroy", "__ID__") }}'.replace('__ID__', id),
                type: 'POST',
                data: {
                    _token: csrfToken,
                    _method: 'DELETE'
                },
                success: function () {
                    Swal.fire('Deleted', 'Image deleted successfully.', 'success')
                        .then(() => window.location.reload());
                },
                error: function (xhr) {
                    showErrorMessage(xhr, 'Could not delete image.');
                }
            });
        });
    });
});
</script>
@endpush