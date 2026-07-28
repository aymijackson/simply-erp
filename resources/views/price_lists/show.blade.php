@extends('layouts.master')
@section('title', 'Price List: ' . $priceList->name)

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">{{ $priceList->name }}</h1>
            <small class="text-muted">
                {{ strtoupper($priceList->type) }} &bull; {{ $priceList->currency_code }}
                @if($priceList->is_default)
                    &bull; <span class="badge bg-primary">Default</span>
                @endif
                {!! $priceList->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>' !!}
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.price-lists.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            @can('sales.price_lists.edit')
            <button class="btn btn-warning btn-sm" id="btnEditList">
                <i class="fas fa-edit me-1"></i> Edit
            </button>
            @endcan
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Items</div>
                    <div class="fw-bold h5 mb-0">{{ $priceList->items->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Customers</div>
                    <div class="fw-bold h5 mb-0">{{ $priceList->customers->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Valid From</div>
                    <div class="fw-bold">{{ $priceList->valid_from?->format('d M Y') ?? '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Valid To</div>
                    <div class="fw-bold">{{ $priceList->valid_to?->format('d M Y') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs" id="plTabs">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabItems">
                        <i class="fas fa-list me-1"></i> Price Items
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabInfo">
                        <i class="fas fa-info-circle me-1"></i> Details
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">

                {{-- ITEMS TAB --}}
                <div class="tab-pane fade show active" id="tabItems">
                    <div class="d-flex justify-content-end mb-2">
                        @can('sales.price_lists.items.manage')
                        <button class="btn btn-primary btn-sm" id="btnAddItem">
                            <i class="fas fa-plus me-1"></i> Add Item
                        </button>
                        @endcan
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm w-100" id="tblItems">
                            <thead class="table-light">
                                <tr>
                                    <th>Variant</th>
                                    <th>Min Qty</th>
                                    <th>Unit Price ({{ $priceList->currency_code }})</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- DETAILS TAB --}}
                <div class="tab-pane fade" id="tabInfo">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tr><th width="160">Name</th><td>{{ $priceList->name }}</td></tr>
                                <tr><th>Code</th><td>{{ $priceList->code ?? '-' }}</td></tr>
                                <tr><th>Type</th><td>{{ ucfirst($priceList->type) }}</td></tr>
                                <tr><th>Currency</th><td>{{ $priceList->currency_code }}</td></tr>
                                <tr><th>Default</th><td>{!! $priceList->is_default ? '<span class="badge bg-primary">Yes</span>' : 'No' !!}</td></tr>
                                <tr><th>Status</th><td>{!! $priceList->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' !!}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tr><th width="160">Valid From</th><td>{{ $priceList->valid_from?->format('d M Y') ?? '—' }}</td></tr>
                                <tr><th>Valid To</th><td>{{ $priceList->valid_to?->format('d M Y') ?? '—' }}</td></tr>
                                <tr><th>Notes</th><td>{{ $priceList->notes ?? '-' }}</td></tr>
                                <tr><th>Created</th><td>{{ $priceList->created_at?->format('d M Y H:i') }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ===================== ITEM MODAL ===================== --}}
<div class="modal fade" id="modalItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="itemModalLabel">Add Price Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmItem" novalidate>
                    @csrf
                    <input type="hidden" id="itemId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Product Variant <span class="text-danger">*</span></label>
                            <select class="form-select" id="item_variant_id" name="product_variant_id" required>
                                <option value="">-- Search variant --</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Min Qty</label>
                            <input type="number" class="form-control" id="item_min_qty"
                                   name="min_qty" min="0.0001" step="0.0001" value="1">
                            <div class="form-text">Lowest qty at which this price applies.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Unit Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $priceList->currency_code }}</span>
                                <input type="number" class="form-control" id="item_unit_price"
                                       name="unit_price" min="0" step="0.0001" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSaveItem">
                    <i class="fas fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const CSRF   = $('meta[name="csrf-token"]').attr('content');
    const PL_ID  = {{ $priceList->id }};
    const URLS = {
        itemsDT    : '{{ route('admin.sales.price-lists.items.datatable', $priceList) }}',
        storeItem  : '{{ route('admin.sales.price-lists.items.store', $priceList) }}',
        updateItem : (id) => `/admin/sales/price-lists/${PL_ID}/items/${id}`,
        destroyItem: (id) => `/admin/sales/price-lists/${PL_ID}/items/${id}`,
        variantSearch: '{{ route('admin.inventory.products.variants.fetch') }}',
    };

    // ── Items DataTable ──────────────────────────────────────────────────────
    const itemsDT = $('#tblItems').DataTable({
        processing: true, serverSide: true,
        ajax: { url: URLS.itemsDT, dataSrc: 'data' },
        columns: [
            { data: 'variant_name' },
            { data: 'min_qty', render: v => parseFloat(v).toFixed(4) },
            { data: 'unit_price', render: v => parseFloat(v).toFixed(4) },
            { data: 'actions', orderable: false, searchable: false },
        ],
        responsive: true,
    });

    // ── Variant Select2 ──────────────────────────────────────────────────────
    $('#item_variant_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Search variant --',
        allowClear: true,
        dropdownParent: $('#modalItem'),
        minimumInputLength: 1,
        ajax: {
            url: URLS.variantSearch,
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data || [] }),
        },
    });

    // ── Modal open/close ─────────────────────────────────────────────────────
    const $itemModal = new bootstrap.Modal(document.getElementById('modalItem'));

    $('#btnAddItem').on('click', function () {
        $('#frmItem')[0].reset();
        $('#itemId').val('');
        $('#item_variant_id').val(null).trigger('change');
        $('#item_min_qty').val(1);
        $('#itemModalLabel').text('Add Price Item');
        $itemModal.show();
    });

    $('#tblItems').on('click', '.btn-edit-item', function () {
        const r = $(this).data('record');
        $('#itemId').val(r.id);
        // Set select2 with existing variant
        const opt = new Option(r.variant_name, r.product_variant_id, true, true);
        $('#item_variant_id').append(opt).trigger('change');
        $('#item_min_qty').val(r.min_qty);
        $('#item_unit_price').val(r.unit_price);
        $('#itemModalLabel').text('Edit Price Item');
        $itemModal.show();
    });

    // ── Save item ────────────────────────────────────────────────────────────
    $('#btnSaveItem').on('click', function () {
        const id   = $('#itemId').val();
        const url  = id ? URLS.updateItem(id) : URLS.storeItem;
        const data = $('#frmItem').serialize() + (id ? '&_method=PUT' : '');

        $.post(url, data)
            .done(() => {
                $itemModal.hide();
                itemsDT.ajax.reload();
                Swal.fire({ icon:'success', title:'Saved', timer:1400, showConfirmButton:false });
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error saving.');
                Swal.fire('Error', msg, 'error');
            });
    });

    // ── Delete item ──────────────────────────────────────────────────────────
    $('#tblItems').on('click', '.btn-delete-item', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Remove item?', icon:'warning',
                    showCancelButton:true, confirmButtonColor:'#e74a3b' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.destroyItem(id), { _token:CSRF, _method:'DELETE' })
                    .done(() => itemsDT.ajax.reload());
            });
    });
})();
</script>
@endpush