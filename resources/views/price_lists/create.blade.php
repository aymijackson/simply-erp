@extends('layouts.master')
@section('title', 'Create Sales Order')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Create Sales Order</h1>
            <p class="text-muted mb-0">Sales / Orders</p>
        </div>
        <a href="{{ route('admin.sales.orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('admin.sales.orders.store') }}" id="orderForm">
        @csrf

        <div class="card shadow mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0 text-primary"><i class="fas fa-file-signature me-1"></i> Order Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-5">
                        <label class="form-label mb-1">Customer <span class="text-danger">*</span></label>
                        <select class="form-control" id="customer_id" name="customer_id" required style="width:100%;">
                            <option value="">-- Select Customer --</option>
                        </select>
                        @error('customer_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-3">
                        {{-- Show assigned price list for info only --}}
                        <label class="form-label mb-1">Price List</label>
                        <div id="priceListBadge" class="form-control-plaintext text-muted small pt-2">
                            <i class="fas fa-tag me-1"></i><span id="priceListName">Select a customer to see their price list</span>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-1">Order Date <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control"
                               value="{{ old('order_date', date('Y-m-d')) }}" required>
                        @error('order_date')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-1">Currency</label>
                        <input type="text" name="currency_code" id="currency_code" class="form-control text-uppercase"
                               value="{{ old('currency_code', 'USD') }}" maxlength="3">
                        @error('currency_code')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label mb-1">Reference</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" maxlength="100">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label mb-1">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="1">{{ old('remarks') }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary"><i class="fas fa-list me-1"></i> Order Lines</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addLineBtn">
                    <i class="fas fa-plus me-1"></i> Add Line
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="linesTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:38%;">Variant</th>
                                <th style="width:22%;">Description</th>
                                <th style="width:10%;">Qty</th>
                                <th style="width:14%;">Unit Price</th>
                                <th style="width:11%;">Line Total</th>
                                <th style="width:5%;" class="text-center">Del</th>
                            </tr>
                        </thead>
                        <tbody id="linesBody"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Grand Total</td>
                                <td class="fw-bold text-end" id="grandTotal">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @error('lines')<small class="text-danger d-block">{{ $message }}</small>@enderror
            </div>

            <div class="card-footer bg-white d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-save me-1"></i> Save Draft
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
let lineIndex = 0;

const RESOLVE_URL = '{{ route('admin.sales.price-lists.resolve') }}';
const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Customer Select2 ────────────────────────────────────────────────────────
function initCustomerSelect2() {
    $('#customer_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Customer --',
        allowClear: true,
        width: '100%',
        ajax: {
            url: "{{ route('admin.customers.select2') }}",
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '' }),
            processResults: data => {
                if (Array.isArray(data)) return { results: data };
                if (data.results) return data;
                return { results: (data.data || []).map(x => ({ id: x.id, text: x.text ?? x.name })) };
            }
        }
    });

    // When customer changes — refresh price list badge + re-fetch all line prices
    $('#customer_id').on('change', function () {
        const customerId = $(this).val();
        updatePriceListBadge(customerId);
        refreshAllLinePrices();
    });
}

// ── Price List Badge ─────────────────────────────────────────────────────────
function updatePriceListBadge(customerId) {
    if (!customerId) {
        $('#priceListName').text('Select a customer to see their price list');
        return;
    }

    // Use resolve endpoint with a dummy variant to check what list would be used
    // (lightweight — just show the customer's list if any)
    $('#priceListName').html('<i class="fas fa-spinner fa-spin me-1"></i> Checking...');

    $.get('/admin/customers/' + customerId + '/price-lists')
        .done(function (data) {
            if (data && data.length) {
                const names = data.map(pl => `<span class="badge bg-info text-dark me-1">${pl.name} (${pl.currency_code})</span>`).join('');
                $('#priceListName').html(names);
            } else {
                $('#priceListName').text('No specific price list — using default/base prices');
            }
        })
        .fail(function () {
            $('#priceListName').text('Using base prices');
        });
}

// ── Variant Select2 ──────────────────────────────────────────────────────────
function initVariantSelect2($el) {
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Search variant --',
        allowClear: true,
        width: '100%',
        minimumInputLength: 1,
        ajax: {
            url: "{{ route('admin.inventory.products.variants.fetch') }}",
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '' }),
            processResults: data => ({ results: data || [] })
        }
    });

    // On variant selected — auto-fetch price
    $el.on('change', function () {
        const variantId = $(this).val();
        const $row      = $(this).closest('tr');
        if (variantId) {
            fetchPrice(variantId, $row);
        }
    });
}

// ── Price Fetch (single line) ────────────────────────────────────────────────
function fetchPrice(variantId, $row) {
    const customerId   = $('#customer_id').val();
    const currencyCode = $('#currency_code').val() || 'USD';
    const qty          = parseFloat($row.find('.line-qty').val()) || 1;

    $.get(RESOLVE_URL, {
        variant_id:    variantId,
        qty:           qty,
        customer_id:   customerId || undefined,
        currency_code: currencyCode,
    }).done(function (res) {
        const $priceInput = $row.find('.line-price');
        $priceInput.val(parseFloat(res.unit_price).toFixed(4));

        // Show source as small tooltip
        const sourceLabels = {
            'customer_price_list' : 'Customer price list',
            'default_price_list'  : 'Default price list',
            'variant_base_price'  : 'Base price',
            'not_found'           : 'No price found',
        };
        const badge = res.discount_applied
            ? ' <span class="badge bg-warning text-dark">Discount applied</span>'
            : '';
        $row.find('.price-source').html(
            `<small class="text-muted">${sourceLabels[res.source] ?? res.source}${badge}</small>`
        );

        updateLineTotals($row);
    }).fail(function () {
        // Silently fall back — user can enter manually
        $row.find('.price-source').html('<small class="text-danger">Price lookup failed</small>');
    });
}

// ── Refresh all lines when customer/currency changes ─────────────────────────
function refreshAllLinePrices() {
    $('#linesBody tr').each(function () {
        const $row     = $(this);
        const variantId = $row.find('.variant-select').val();
        if (variantId) fetchPrice(variantId, $row);
    });
}

// ── Line totals ──────────────────────────────────────────────────────────────
function updateLineTotals($row) {
    const qty   = parseFloat($row.find('.line-qty').val())   || 0;
    const price = parseFloat($row.find('.line-price').val()) || 0;
    const total = qty * price;
    $row.find('.line-total').text(total.toFixed(2));
    updateGrandTotal();
}

function updateGrandTotal() {
    let grand = 0;
    $('#linesBody .line-total').each(function () {
        grand += parseFloat($(this).text()) || 0;
    });
    $('#grandTotal').text(grand.toFixed(2));
}

// ── Add line row ─────────────────────────────────────────────────────────────
function addLineRow() {
    const idx = lineIndex++;

    const row = `
        <tr data-row="${idx}">
            <td>
                <select class="form-control variant-select" name="lines[${idx}][product_variant_id]"
                        required style="width:100%;"></select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm"
                       name="lines[${idx}][description]" placeholder="optional">
            </td>
            <td>
                <input type="number" step="0.01" min="0.01"
                       class="form-control form-control-sm line-qty"
                       name="lines[${idx}][qty_ordered]" value="1" required>
            </td>
            <td>
                <input type="number" step="0.0001" min="0"
                       class="form-control form-control-sm line-price"
                       name="lines[${idx}][unit_price]" value="0.0000" required>
                <div class="price-source mt-1"></div>
            </td>
            <td class="text-end align-middle line-total fw-semibold">0.00</td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger removeLineBtn">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;

    $('#linesBody').append(row);
    const $row = $(`tr[data-row="${idx}"]`);
    initVariantSelect2($row.find('.variant-select'));

    // Recalculate on qty change
    $row.on('input', '.line-qty', function () {
        const variantId = $row.find('.variant-select').val();
        if (variantId) fetchPrice(variantId, $row);
        else updateLineTotals($row);
    });

    // Recalculate on manual price change
    $row.on('input', '.line-price', function () {
        updateLineTotals($row);
    });
}

// ── Remove line ──────────────────────────────────────────────────────────────
$(document).on('click', '.removeLineBtn', function () {
    $(this).closest('tr').remove();
    updateGrandTotal();
});

// ── Also refresh prices when currency changes ─────────────────────────────────
$('#currency_code').on('change keyup', function () {
    refreshAllLinePrices();
});

// ── Init ─────────────────────────────────────────────────────────────────────
$(document).ready(function () {
    initCustomerSelect2();
    addLineRow();
    $('#addLineBtn').on('click', addLineRow);
});
</script>
@endpush