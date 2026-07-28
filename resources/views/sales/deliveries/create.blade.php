@extends('layouts.master')

@section('title', 'Create Delivery Note')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

<style>
.select2-container--bootstrap-5 .select2-selection {
    min-height: calc(1.5em + .75rem + 2px) !important;
    padding: .375rem .75rem !important;
    border: 1px solid #d1d3e2 !important;
    border-radius: .35rem !important;
    background-color: #fff !important;
}
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    line-height: 1.5 !important;
    padding-left: 0 !important;
    color: #6e707e;
}
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + .75rem) !important;
    right: .5rem !important;
}
.select2-container { width: 100% !important; }
.select2-container--open { z-index: 9999; }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Create Delivery Note</h1>
            <p class="text-muted mb-0">Sales / Delivery Notes</p>
        </div>
        <a href="{{ route('admin.sales.deliveries.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.sales.deliveries.store') }}" id="deliveryForm">
        @csrf

        <div class="card shadow mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-truck mr-1"></i> Delivery Note Details
                </h6>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-1">Sales Order (optional)</label>
                        <select class="form-control" id="sales_order_id" name="sales_order_id" style="width:100%;">
                            <option value=""></option>
                        </select>
                        <small class="text-muted">Only <strong>confirmed</strong> orders are selectable. Selecting an order auto-loads its lines.</small>
                        @error('sales_order_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-1">Customer</label>
                        <select class="form-control" id="customer_id" name="customer_id" required style="width:100%;">
                            <option value=""></option>
                        </select>
                        <small class="text-muted">If you change customer, order and lines will reset.</small>
                        @error('customer_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-1">Ship Date</label>
                        <input type="date" class="form-control" name="ship_date" value="{{ old('ship_date', date('Y-m-d')) }}" required>
                        @error('ship_date') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label mb-1">Default Store (optional)</label>
                        <select class="form-control" id="default_store_id" name="location_store_id" style="width:100%;">
                            <option value=""></option>
                        </select>
                        <small class="text-muted">If selected, can apply to all lines (each line still editable).</small>
                        @error('location_store_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label mb-1">Remarks (optional)</label>
                        <textarea class="form-control" name="remarks" rows="2">{{ old('remarks') }}</textarea>
                        @error('remarks') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-list mr-1"></i> Delivery Lines
                </h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addLineBtn">
                        <i class="fas fa-plus mr-1"></i> Add Line
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearLinesBtn">
                        <i class="fas fa-eraser mr-1"></i> Clear
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <strong>Stock rule:</strong>
                    If you select a store, quantity is limited by <strong>min(SO remaining, store available)</strong>.
                    If store is blank, we treat as external supply.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="linesTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:34%;">Item (Variant)</th>
                                <th style="width:22%;">Store (optional)</th>
                                <th class="text-center" style="width:10%;">Ordered</th>
                                <th class="text-center" style="width:10%;">Delivered</th>
                                <th class="text-center" style="width:10%;">Remaining</th>
                                <th style="width:10%;">Qty to Deliver</th>
                                <th class="text-center" style="width:4%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="linesBody"></tbody>
                    </table>
                </div>

                @error('lines') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>

            <div class="card-footer bg-white d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-save mr-1"></i> Save Draft
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let prevCustomer = { id: null, text: null };
let ignoreCustomerChangePrompt = false;

let lineIndex = 0;
let syncingCustomerFromOrder = false;

const STORE_SELECT2_URL   = `{{ url('admin/location_stores/fetch') }}`;
const STOCK_AVAILABLE_URL = `{{ url('admin/location_stores/stock/available') }}`;

const rowTimers = new Map();

function getCSRF() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function initSelect2Ajax(selectorOrEl, url, placeholderText, extraDataFn = null, dropdownParentEl = null) {
    const $el = (selectorOrEl instanceof jQuery) ? selectorOrEl : $(selectorOrEl);
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        placeholder: placeholderText,
        allowClear: true,
        width: '100%',
        dropdownParent: dropdownParentEl ? $(dropdownParentEl) : $(document.body),
        ajax: {
            url: url,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                const base = { q: params.term || '', page: params.page || 1 };
                return extraDataFn ? Object.assign(base, extraDataFn(params) || {}) : base;
            },
            processResults: function (data) {
                if (Array.isArray(data)) return { results: data };
                if (data.results) return data;
                if (data.data) return { results: data.data.map(x => ({ id:x.id, text:x.text ?? x.name ?? ('Item #' + x.id) })) };
                return { results: [] };
            },
            cache: true
        }
    });
}

function setSelect2Value($el, id, text) {
    const opt = new Option(text, id, true, true);
    $el.append(opt).trigger('change');
}

function initVariantSelect2($el, dropdownParentEl = null) {
    if ($el.hasClass('select2-hidden-accessible')) return;
    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search variant...',
        allowClear: true,
        minimumInputLength: 1,
        dropdownParent: dropdownParentEl ? $(dropdownParentEl) : $(document.body),
        ajax: {
            url: "{{ route('admin.inventory.products.variants.fetch') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term || '', page: params.page || 1 }; },
            processResults: function (data) {
                if (Array.isArray(data)) return { results: data };
                if (data.results) return data;
                return { results: [] };
            },
            cache: true
        }
    });
}

function initStoreSelect2($el, dropdownParentEl = null) {
    initSelect2Ajax($el, STORE_SELECT2_URL, 'Select store...', null, dropdownParentEl);
}

function clearLines(){ $('#linesBody').empty(); lineIndex = 0; }

function resetEverythingBecauseCustomerChanged() {
    // Clear Sales Order select2 properly
    $('#sales_order_id').val(null).trigger('change.select2'); // ensures select2 UI resets
    $('#sales_order_id').trigger('change'); // triggers your existing clearLines logic

    // Clear lines
    clearLines();

    // Optional: clear default store too (keep if desired)
    $('#default_store_id').val(null).trigger('change.select2');
}

/**
 * ✅ MAIN FIX (Store selection):
 * cap = SO remaining ? min(remaining, available) : available
 * if store selected: set qty_to_deliver = cap and max = cap
 * if cap is 0 => qty_to_deliver becomes 0
 */
async function applyStoreCap($row) {
    const storeId  = $row.find('.line-store-select').val();
    const qtyInput = $row.find('.qty-input');

    // SO remaining saved on SO-linked rows
    const remainingAttr = qtyInput.attr('data-remaining');
    const remaining = (remainingAttr !== undefined && remainingAttr !== null && remainingAttr !== '')
        ? parseFloat(remainingAttr)
        : null;
    const hasRemaining = Number.isFinite(remaining);

    // No store: cap stays at remaining for SO rows, or unlimited for manual
    if (!storeId) {
        $row.find('.stock-hint').remove();
        if (hasRemaining) {
            qtyInput.attr('max', remaining);
            qtyInput.val(remaining);
            qtyInput.after(`<div class="text-muted small stock-hint mt-1">SO remaining: ${remaining}</div>`);
        } else {
            qtyInput.removeAttr('max');
        }
        return;
    }

    // Need variant id
    const variantId = $row.find('input[name*="[product_variant_id]"]').val()
        || $row.find('.variant-select').val();

    if (!variantId) return;

    const url = `${STOCK_AVAILABLE_URL}?location_store_id=${encodeURIComponent(storeId)}&product_variant_id=${encodeURIComponent(variantId)}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCSRF() } });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) return;

    const available = parseFloat(data.available ?? '0');
    const cap = hasRemaining ? Math.min(remaining, available) : available;

    qtyInput.attr('max', cap);
    qtyInput.val(cap);

    // hint
    const cell = qtyInput.closest('td');
    cell.find('.stock-hint').remove();
    const hint = hasRemaining
        ? `SO remaining: ${remaining} | Available: ${available} | Qty/Max set to: ${cap}`
        : `Available: ${available} | Qty/Max set to: ${cap}`;
    cell.append(`<div class="text-muted small stock-hint mt-1">${hint}</div>`);
}

function scheduleApplyStoreCap($row, delay=150){
    const key = $row.attr('data-row') || String(Math.random());
    if (rowTimers.has(key)) clearTimeout(rowTimers.get(key));
    rowTimers.set(key, setTimeout(() => applyStoreCap($row), delay));
}

function addManualLineRow(){
    const idx = lineIndex++;
    const row = `
        <tr data-row="${idx}">
            <td>
                <input type="hidden" name="lines[${idx}][sales_order_line_id]" value="">
                <select class="form-control variant-select" name="lines[${idx}][product_variant_id]" required style="width:100%;">
                    <option value=""></option>
                </select>
                <small class="text-muted">Manual line</small>
            </td>
            <td>
                <select class="form-control line-store-select" name="lines[${idx}][location_store_id]" style="width:100%;">
                    <option value=""></option>
                </select>
                <small class="text-muted">Leave blank for external supply</small>
            </td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td>
                <input type="number" step="1" min="0"
                       class="form-control qty-input"
                       name="lines[${idx}][qty_to_deliver]" value="0">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger removeLineBtn"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `;
    $('#linesBody').append(row);
    const $newRow = $('#linesBody tr:last');
    initVariantSelect2($newRow.find('.variant-select'), document.body);
    initStoreSelect2($newRow.find('.line-store-select'), document.body);

    const defStoreId = $('#default_store_id').val();
    const defStoreText = $('#default_store_id').select2('data')?.[0]?.text;
    if (defStoreId) {
        setSelect2Value($newRow.find('.line-store-select'), defStoreId, defStoreText || 'Selected Store');
        scheduleApplyStoreCap($newRow, 50);
    }
}

function renderOrderLines(lines){
    clearLines();
    if (!Array.isArray(lines) || !lines.length) return;

    lines.forEach(l => {
        const ordered   = parseFloat(l.qty_ordered ?? 0);
        const delivered = parseFloat(l.qty_delivered ?? 0);
        const remaining = Math.max(0, ordered - delivered);
        const idx = lineIndex++;

        const label = (l.variant_label ?? ('Variant #' + l.product_variant_id));
        const desc  = (l.description ?? '');

        const row = `
            <tr data-row="${idx}">
                <td>
                    <input type="hidden" name="lines[${idx}][sales_order_line_id]" value="${l.id}">
                    <input type="hidden" name="lines[${idx}][product_variant_id]" value="${l.product_variant_id}">
                    <div class="font-weight-bold">${label}</div>
                    ${desc ? `<div class="text-muted small">${desc}</div>` : ``}
                </td>
                <td>
                    <select class="form-control line-store-select" name="lines[${idx}][location_store_id]" style="width:100%;">
                        <option value=""></option>
                    </select>
                    <small class="text-muted">Leave blank for external supply</small>
                </td>
                <td class="text-center">${ordered.toFixed(0)}</td>
                <td class="text-center">${delivered.toFixed(0)}</td>
                <td class="text-center">${remaining.toFixed(0)}</td>
                <td>
                    <input type="number" step="1" min="0"
                           class="form-control qty-input"
                           name="lines[${idx}][qty_to_deliver]"
                           data-remaining="${remaining}"
                           max="${remaining}"
                           value="${remaining}">
                    <div class="text-muted small stock-hint mt-1">SO remaining: ${remaining}</div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger removeLineBtn"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
        $('#linesBody').append(row);
    });

    $('#linesBody .line-store-select').each(function(){ initStoreSelect2($(this), document.body); });
}

document.addEventListener('DOMContentLoaded', function(){
    initSelect2Ajax('#customer_id', "{{ route('admin.customers.select2') }}", '-- Select Customer --');
    initSelect2Ajax('#sales_order_id', "{{ route('admin.sales.orders.select2ByStatus') }}", '-- Select Sales Order --', () => ({ status: 'confirmed' }));
    initStoreSelect2($('#default_store_id'), document.body);

    $('#customer_id').on('select2:select', function(){
        prevCustomer = { id: $(this).val(), text: $(this).select2('data')?.[0]?.text || null };
    });
    $('#customer_id').on('select2:clear', function(){ prevCustomer = { id:null, text:null }; });

    document.addEventListener('click', function(e){
        if (e.target.closest('.removeLineBtn')) e.target.closest('tr').remove();
    });

    document.getElementById('addLineBtn').addEventListener('click', addManualLineRow);

    document.getElementById('clearLinesBtn').addEventListener('click', function(){
        Swal.fire({ icon:'warning', title:'Clear all lines?', showCancelButton:true })
            .then(r => { if (r.isConfirmed) clearLines(); });
    });

    $('#sales_order_id').on('change', async function(){
        const orderId = $(this).val();
        if (!orderId) { clearLines(); return; }

        const url = `{{ url('admin/sales/orders') }}/${orderId}/lines`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCSRF() } });
        if (!res.ok) return;

        const payload = await res.json();
        if (payload.customer_id) {
            syncingCustomerFromOrder = true;
            setSelect2Value($('#customer_id'), payload.customer_id, payload.customer_name ?? ('Customer #' + payload.customer_id));
            syncingCustomerFromOrder = false;
        }
        renderOrderLines(payload.lines || []);
    });

    // ✅ When store changes: APPLY CAP and SET VALUE
    $(document).on('change', '.line-store-select', function(){
        scheduleApplyStoreCap($(this).closest('tr'), 50);
    });

    // qty typing: clamp only
    $(document).on('input', '.qty-input', function(){
        const max = parseFloat($(this).attr('max') || '0');
        const v = parseFloat($(this).val() || '0');
        if (Number.isFinite(max) && v > max) $(this).val(max);
    });

    // manual variant change: if store selected, re-apply cap
    $(document).on('change', '.variant-select', function(){
        scheduleApplyStoreCap($(this).closest('tr'), 80);
    });
});
</script>
@endpush
