{{-- resources/views/sales/orders/edit.blade.php --}}
@extends('layouts.master')

@section('title', 'Edit Sales Order')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Edit Sales Order</h1>
            <p class="text-muted mb-0">Sales / Orders</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.orders.show', $order->id) }}" class="btn btn-outline-primary">
                <i class="fas fa-eye mr-1"></i> View
            </a>

            <a href="{{ route('admin.sales.orders.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.sales.orders.update', $order->id) }}" id="salesOrderForm">
        @csrf
        @method('PUT')

        {{-- Order Details --}}
        <div class="card shadow mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-clipboard-list mr-1"></i> Order Details
                </h6>

                @php
                    $badgeClass = match($order->status) {
                        'confirmed' => 'badge-info',
                        'partial'   => 'badge-warning',
                        'delivered' => 'badge-success',
                        'cancelled' => 'badge-danger',
                        default     => 'badge-secondary',
                    };
                @endphp

                <span class="badge {{ $badgeClass }}">{{ strtoupper($order->status ?? 'DRAFT') }}</span>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    {{-- Order No (read-only) --}}
                    <div class="col-md-3">
                        <label class="form-label mb-1">Order No</label>
                        <input type="text" class="form-control" value="{{ $order->order_no }}" readonly>
                    </div>

                    {{-- Customer --}}
                    <div class="col-md-5">
                        <label class="form-label mb-1">Customer <span class="text-danger">*</span></label>
                        <select id="customer_id" name="customer_id" class="form-control" style="width:100%;" required>
                            {{-- Preselected option for Select2 --}}
                            @if($order->customer_id)
                                <option value="{{ $order->customer_id }}" selected>
                                    {{ $order->customer->name ?? ('Customer #'.$order->customer_id) }}
                                </option>
                            @endif
                        </select>
                        @error('customer_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    {{-- Order Date --}}
                    <div class="col-md-2">
                        <label class="form-label mb-1">Order Date <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            name="order_date"
                            class="form-control"
                            value="{{ old('order_date', optional($order->order_date)->format('Y-m-d') ?? date('Y-m-d')) }}"
                            required
                        >
                        @error('order_date') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    {{-- Currency --}}
                    <div class="col-md-2">
                        <label class="form-label mb-1">Currency</label>
                        <input
                            type="text"
                            name="currency_code"
                            class="form-control"
                            value="{{ old('currency_code', $order->currency_code ?? 'USD') }}"
                        >
                        @error('currency_code') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    {{-- Reference --}}
                    <div class="col-md-6">
                        <label class="form-label mb-1">Reference (optional)</label>
                        <input
                            type="text"
                            name="reference"
                            class="form-control"
                            value="{{ old('reference', $order->reference) }}"
                            placeholder="e.g. Customer PO / Ref"
                        >
                        @error('reference') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    {{-- Remarks --}}
                    <div class="col-md-6">
                        <label class="form-label mb-1">Remarks (optional)</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Any notes...">{{ old('remarks', $order->remarks) }}</textarea>
                        @error('remarks') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Order Lines --}}
        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-list mr-1"></i> Order Lines
                </h6>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addLineBtn">
                        <i class="fas fa-plus mr-1"></i> Add Line
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="recalcBtn">
                        <i class="fas fa-calculator mr-1"></i> Recalculate
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <strong>Tip:</strong> Use the variant search to add items quickly. Line total is auto-calculated (Qty × Unit Price).
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="linesTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:34%;">Variant</th>
                                <th>Description</th>
                                <th class="text-right" style="width:140px;">Qty Ordered</th>
                                <th class="text-right" style="width:160px;">Unit Price</th>
                                <th class="text-right" style="width:160px;">Line Total</th>
                                <th class="text-center" style="width:80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="linesBody">
                            @php $idx = 0; @endphp

                            @foreach(($order->lines ?? []) as $ln)
                                <tr data-row="{{ $idx }}">
                                    <td>
                                        <input type="hidden" name="lines[{{ $idx }}][id]" value="{{ $ln->id }}">
                                        <select class="form-control variant-select" name="lines[{{ $idx }}][product_variant_id]" style="width:100%;" required>
                                            <option value="{{ $ln->product_variant_id }}" selected>
                                                {{ $ln->variant->product->product_name ?? 'Item' }} — {{ $ln->variant->sku ?? ('Variant #'.$ln->product_variant_id) }}
                                            </option>
                                        </select>
                                        @error("lines.$idx.product_variant_id") <small class="text-danger d-block">{{ $message }}</small> @enderror
                                    </td>

                                    <td>
                                        <input type="text" name="lines[{{ $idx }}][description]" class="form-control"
                                               value="{{ old("lines.$idx.description", $ln->description) }}"
                                               placeholder="Optional description">
                                        @error("lines.$idx.description") <small class="text-danger d-block">{{ $message }}</small> @enderror
                                    </td>

                                    <td>
                                        <input type="number" step="1" min="0"
                                               name="lines[{{ $idx }}][qty_ordered]"
                                               class="form-control text-right qty-input"
                                               value="{{ old("lines.$idx.qty_ordered", (float)$ln->qty_ordered) }}">
                                        @error("lines.$idx.qty_ordered") <small class="text-danger d-block">{{ $message }}</small> @enderror
                                    </td>

                                    <td>
                                        <input type="number" step="1" min="0"
                                               name="lines[{{ $idx }}][unit_price]"
                                               class="form-control text-right price-input"
                                               value="{{ old("lines.$idx.unit_price", (float)$ln->unit_price) }}">
                                        @error("lines.$idx.unit_price") <small class="text-danger d-block">{{ $message }}</small> @enderror
                                    </td>

                                    <td class="text-right">
                                        <span class="line-total">
                                            {{ number_format((float)($ln->qty_ordered ?? 0) * (float)($ln->unit_price ?? 0), 4) }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger removeLineBtn" title="Remove line">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @php $idx++; @endphp
                            @endforeach
                        </tbody>

                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="4" class="text-right">Subtotal</th>
                                <th class="text-right"><span id="subtotalCell">0</span></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @error('lines') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>

            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Last updated: {{ optional($order->updated_at)->format('d M Y, h:i A') }}
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Update Order
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
{{-- Select2 + SweetAlert2 (if not already globally included in master layout) --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ========= CONFIG =========
    const customerSelectUrl = "{{ route('admin.customers.select2') }}";
    const variantSelectUrl  = "{{ route('admin.inventory.products.variants.fetch') }}";

    let lineIndex = {{ isset($idx) ? (int)$idx : 0 }};

    // ========= HELPERS =========
    function money4(n){ return (isFinite(n) ? n : 0).toFixed(); }

    function initCustomerSelect2() {
        const $el = $('#customer_id');
        if ($el.hasClass('select2-hidden-accessible')) return;

        $el.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select customer',
            allowClear: true,
            ajax: {
                url: customerSelectUrl,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '', page: params.page || 1 }),
                processResults: function (data, params) {
                    params.page = params.page || 1;

                    if (Array.isArray(data)) return { results: data };
                    if (data.results) return data;

                    if (data.data) {
                        return {
                            results: data.data.map(x => ({
                                id: x.id,
                                text: x.text ?? x.name ?? x.company_name ?? ('Customer #' + x.id)
                            })),
                            pagination: { more: !!data.next_page_url }
                        };
                    }
                    return { results: [] };
                },
                cache: true
            }
        });
    }

    function initVariantSelect2($el) {
        if ($el.hasClass('select2-hidden-accessible')) return;

        $el.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Search variant...',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: variantSelectUrl,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '', page: params.page || 1 }),
                processResults: function (data) {
                    if (Array.isArray(data)) return { results: data };
                    if (data.results) return data;
                    return { results: [] };
                },
                cache: true
            }
        });
    }

    function recalcTotals() {
        let subtotal = 0;

        $('#linesBody tr').each(function(){
            const qty   = parseFloat($(this).find('.qty-input').val() || '0');
            const price = parseFloat($(this).find('.price-input').val() || '0');
            const total = qty * price;

            $(this).find('.line-total').text(money4(total));
            subtotal += total;
        });

        $('#subtotalCell').text(money4(subtotal));
    }

    function addLineRow() {
        const idx = lineIndex++;

        const rowHtml = `
            <tr data-row="${idx}">
                <td>
                    <input type="hidden" name="lines[${idx}][id]" value="">
                    <select class="form-control variant-select" name="lines[${idx}][product_variant_id]" style="width:100%;" required>
                        <option value=""></option>
                    </select>
                </td>
                <td>
                    <input type="text" name="lines[${idx}][description]" class="form-control" placeholder="Optional description">
                </td>
                <td>
                    <input type="number" step="1" min="0" name="lines[${idx}][qty_ordered]" class="form-control text-right qty-input" value="1">
                </td>
                <td>
                    <input type="number" step="1" min="0" name="lines[${idx}][unit_price]" class="form-control text-right price-input" value="0">
                </td>
                <td class="text-right">
                    <span class="line-total">0</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger removeLineBtn" title="Remove line">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#linesBody').append(rowHtml);

        const $newSelect = $('#linesBody tr:last .variant-select');
        initVariantSelect2($newSelect);

        recalcTotals();
    }

    // ========= INIT =========
    document.addEventListener('DOMContentLoaded', function () {

        // Optional: block edits when not draft (UX)
        const status = "{{ $order->status }}";
        if (status && status !== 'draft') {
            // still allow viewing but warn; server already blocks update
            // You can hard-disable fields if you want.
        }

        initCustomerSelect2();

        // init existing variant selects
        $('.variant-select').each(function(){
            initVariantSelect2($(this));
        });

        // buttons
        $('#addLineBtn').on('click', addLineRow);
        $('#recalcBtn').on('click', recalcTotals);

        // remove line
        $(document).on('click', '.removeLineBtn', function(){
            $(this).closest('tr').remove();
            recalcTotals();
        });

        // auto calc
        $(document).on('input', '.qty-input, .price-input', function(){
            recalcTotals();
        });

        // first calc
        recalcTotals();
    });
</script>
@endpush
