@extends('layouts.master')

@section('title', $mode === 'edit' ? 'Edit Delivery' : 'New Delivery')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">{{ $mode === 'edit' ? 'Edit Delivery' : 'New Delivery' }}</h1>
            <small class="text-muted">Sales / Deliveries</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.deliveries.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>

            @if($mode === 'edit' && $delivery->status === 'draft')
                <button class="btn btn-success" id="postBtn"><i class="fas fa-check mr-1"></i> Post</button>
                <button class="btn btn-outline-danger" id="cancelBtn"><i class="fas fa-times mr-1"></i> Cancel</button>
            @endif
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary"><i class="fas fa-info-circle mr-1"></i> Delivery Header</h6>
            @if($mode === 'edit')
                <span class="badge badge-{{ $delivery->status_badge }}">{{ strtoupper($delivery->status) }}</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label mb-1">Delivery No</label>
                    <input class="form-control" id="delivery_no" value="{{ $delivery->delivery_no }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Sales Order <span class="text-danger">*</span></label>
                    <select id="sales_order_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                        @if($delivery->sales_order_id)
                            <option value="{{ $delivery->sales_order_id }}" selected>
                                {{ $delivery->order?->order_no ?? ('Order #'.$delivery->sales_order_id) }}
                            </option>
                        @endif
                    </select>
                    <small class="text-muted">Only confirmed orders are selectable</small>
                </div>

                <div class="col-md-5">
                    <label class="form-label mb-1">Customer <span class="text-danger">*</span></label>
                    <select id="customer_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                        @if($delivery->customer_id)
                            <option value="{{ $delivery->customer_id }}" selected>
                                {{ $delivery->customer?->name ?? ('Customer #'.$delivery->customer_id) }}
                            </option>
                        @endif
                    </select>
                    <small class="text-muted">Changing customer will reset lines</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Ship Date</label>
                    <input type="date" class="form-control" id="ship_date" value="{{ optional($delivery->ship_date)->format('Y-m-d') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Driver</label>
                    <select id="driver_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                        @if($delivery->driver_id)
                            <option value="{{ $delivery->driver_id }}" selected>
                                {{ $delivery->driver?->full_name ?? ('Driver #'.$delivery->driver_id) }}
                            </option>
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Vehicle</label>
                    <select id="vehicle_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                        @if($delivery->vehicle_id)
                            <option value="{{ $delivery->vehicle_id }}" selected>
                                {{ $delivery->vehicle?->registration_no ?? ('Vehicle #'.$delivery->vehicle_id) }}
                            </option>
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Default Store (optional)</label>
                    <select id="header_store_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                        @if($delivery->location_store_id)
                            <option value="{{ $delivery->location_store_id }}" selected>
                                {{ $delivery->store?->name ?? ('Store #'.$delivery->location_store_id) }}
                            </option>
                        @endif
                    </select>
                    <small class="text-muted">Can be overridden per line</small>
                </div>

                <div class="col-md-12">
                    <label class="form-label mb-1">Remarks</label>
                    <textarea class="form-control" id="remarks" rows="2">{{ $delivery->remarks }}</textarea>
                </div>

            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary"><i class="fas fa-list mr-1"></i> Delivery Lines</h6>
            <small class="text-muted">Lines are auto-populated from Sales Order</small>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="linesTable">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Variant</th>
                            <th class="text-end" style="width:140px;">Remaining</th>
                            <th style="width:260px;">Store (optional)</th>
                            <th class="text-end" style="width:160px;">Available</th>
                            <th class="text-end" style="width:180px;">Qty To Deliver</th>
                            <th class="text-end" style="width:180px;">Qty Delivered (Actual)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($mode === 'edit')
                            @foreach($delivery->lines as $i => $ln)
                                <tr data-idx="{{ $i }}">
                                    <td>{{ $i+1 }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $ln->variant?->product?->product_name ?? 'Item' }}</div>
                                        <div class="text-muted small">{{ $ln->variant?->sku ?? ('Variant #'.$ln->product_variant_id) }}</div>
                                        <input type="hidden" class="sales_order_line_id" value="{{ $ln->sales_order_line_id }}">
                                        <input type="hidden" class="product_variant_id" value="{{ $ln->product_variant_id }}">
                                        {{-- IMPORTANT: will be hydrated from order-payload on load --}}
                                        <input type="hidden" class="qty_remaining" value="0">
                                    </td>
                                    <td class="text-end">
                                        <span class="remaining_text">-</span>
                                    </td>
                                    <td>
                                        <select class="form-control line_store_id" style="width:100%;">
                                            <option value=""></option>
                                            @if($ln->location_store_id)
                                                <option value="{{ $ln->location_store_id }}" selected>
                                                    {{ $ln->store?->name ?? ('Store #'.$ln->location_store_id) }}
                                                </option>
                                            @endif
                                        </select>
                                    </td>
                                    <td class="text-end">
                                        <span class="available_text">-</span>
                                        <input type="hidden" class="available_qty" value="0">
                                    </td>
                                    <td class="text-end">
                                        <input type="number" step="0.0001" min="0" class="form-control text-end qty_to_deliver" value="{{ $ln->qty_to_deliver }}">
                                    </td>
                                    <td class="text-end">
                                        <input type="number" step="0.0001" min="0" class="form-control text-end qty_delivered_actual" value="{{ $ln->qty_delivered_actual }}">
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="7" class="text-center text-muted">Select a confirmed Sales Order to load lines.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-primary" id="saveBtn">
                    <i class="fas fa-save mr-1"></i> Save
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const mode = @json($mode);
const deliveryId = @json($delivery->id);

/* -------------------- Select2 helpers -------------------- */
function initSelect2Ajax(selector, url, placeholder, dropdownParent = null) {
    const $el = $(selector);
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder,
        allowClear: true,
        dropdownParent: dropdownParent ? $(dropdownParent) : $(document.body),
        ajax: {
            url,
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '', page: params.page || 1 }),
            processResults: data => data.results ? data : ({ results: Array.isArray(data) ? data : [] })
        }
    });
}

function setSelect2Value($el, id, text) {
    if (!id) return;
    const opt = new Option(text || ('Item #' + id), id, true, true);
    $el.append(opt).trigger('change.select2');
}

/* -------------------- UI helpers -------------------- */
function resetLinesTable(msg = 'Select a confirmed Sales Order to load lines.') {
    $('#linesTable tbody').html(`<tr><td colspan="7" class="text-center text-muted">${msg}</td></tr>`);
}

function fmt(n) {
    const x = Number(n || 0);
    return x.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 4 });
}

function escapeHtml(str) {
    return String(str || '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}

/* -------------------- API -------------------- */
async function fetchOrderPayload(orderId) {
    const res = await fetch(`{{ url('admin/sales/deliveries/order-payload') }}/${orderId}`, {
        headers: { 'Accept': 'application/json' }
    });
    const data = await res.json().catch(()=>({}));
    if (!res.ok) throw new Error(data.message || 'Failed to load order');
    return data;
}

async function storeAvailable(storeId, variantId) {
    const qs = new URLSearchParams({ location_store_id: storeId, product_variant_id: variantId });
    const res = await fetch(`{{ route('admin.location_stores.stock.available') }}?${qs}`, {
        headers: { 'Accept': 'application/json' }
    });
    const data = await res.json().catch(()=>({}));
    if (!res.ok) throw new Error(data.message || 'Failed to load availability');
    return Number(data.available || 0);
}

/* -------------------- Core constraint logic -------------------- */
/**
 * maxAllowed per line:
 * - if store selected => min(remaining, available)
 * - else => remaining
 */
function computeMaxAllowed(remaining, storeSelected, available) {
    remaining = Number(remaining || 0);
    available = Number(available || 0);
    if (storeSelected) return Math.max(0, Math.min(remaining, available));
    return Math.max(0, remaining);
}

/**
 * Auto-fill behaviour (your rule):
 * qty_to_deliver becomes the maximum allowed.
 */
function computeAutoFillQty(remaining, storeSelected, available) {
    return computeMaxAllowed(remaining, storeSelected, available);
}

function clampQty(val, maxAllowed) {
    val = Number(val || 0);
    maxAllowed = Number(maxAllowed || 0);
    if (val < 0) return 0;
    if (val > maxAllowed) return maxAllowed;
    return val;
}

/**
 * Recompute caps & availability for row
 * opts.autoFill === true: overwrite qty_to_deliver with computedAutoFill
 * opts.autoFill === false: preserve user value, only clamp to max
 */
async function recomputeRowCaps($row, opts = {}) {
    const autoFill = opts.autoFill === true;

    const remaining = Number($row.find('.qty_remaining').val() || 0);
    const variantId = Number($row.find('.product_variant_id').val() || 0);
    const storeId = $row.find('.line_store_id').val();
    const storeSelected = !!storeId;

    // Show remaining
    $row.find('.remaining_text').text(fmt(remaining));

    let available = 0;

    if (storeSelected) {
        try {
            available = await storeAvailable(storeId, variantId);
            $row.find('.available_qty').val(available);
            $row.find('.available_text').text(fmt(available));
        } catch (e) {
            available = 0;
            $row.find('.available_qty').val(0);
            $row.find('.available_text').text('0');
        }
    } else {
        $row.find('.available_qty').val(0);
        $row.find('.available_text').text('-');
    }

    const maxAllowed = computeMaxAllowed(remaining, storeSelected, available);

    const $qty = $row.find('.qty_to_deliver');
    $qty.attr('max', String(maxAllowed));

    const current = Number($qty.val() || 0);

    if (autoFill) {
        $qty.val(String(computeAutoFillQty(remaining, storeSelected, available)));
    } else {
        $qty.val(String(clampQty(current, maxAllowed)));
    }

    enforceCaps($row);
}

/**
 * Pure clamp (no auto-fill)
 */
function enforceCaps($row) {
    const remaining = Number($row.find('.qty_remaining').val() || 0);
    const storeId = $row.find('.line_store_id').val();
    const storeSelected = !!storeId;
    const available = Number($row.find('.available_qty').val() || 0);

    const maxAllowed = computeMaxAllowed(remaining, storeSelected, available);

    const $qty = $row.find('.qty_to_deliver');
    const current = Number($qty.val() || 0);

    $qty.attr('max', String(maxAllowed));
    $qty.val(String(clampQty(current, maxAllowed)));
}

/* -------------------- Build lines from order-payload -------------------- */
function buildLines(lines, defaultStoreId = null) {
    if (!Array.isArray(lines) || !lines.length) {
        resetLinesTable('No deliverable lines found on this order.');
        return;
    }

    const tbody = lines.map((ln, i) => {
        return `
        <tr data-idx="${i}">
            <td>${i+1}</td>
            <td>
                <div class="fw-bold">${escapeHtml(ln.variant_text || 'Item')}</div>
                <div class="text-muted small">Variant #${escapeHtml(ln.product_variant_id)}</div>

                <input type="hidden" class="sales_order_line_id" value="${ln.sales_order_line_id || ''}">
                <input type="hidden" class="product_variant_id" value="${ln.product_variant_id}">
                <input type="hidden" class="qty_remaining" value="${Number(ln.qty_remaining || 0)}">
            </td>
            <td class="text-end"><span class="remaining_text">${fmt(ln.qty_remaining)}</span></td>
            <td>
                <select class="form-control line_store_id" style="width:100%;">
                    <option value=""></option>
                </select>
            </td>
            <td class="text-end">
                <span class="available_text">-</span>
                <input type="hidden" class="available_qty" value="0">
            </td>
            <td class="text-end">
                <input type="number" step="0.0001" min="0" class="form-control text-end qty_to_deliver" value="0">
            </td>
            <td class="text-end">
                <input type="number" step="0.0001" min="0" class="form-control text-end qty_delivered_actual" value="0">
            </td>
        </tr>`;
    }).join('');

    $('#linesTable tbody').html(tbody);

    // init select2 + wire events
    $('#linesTable tbody tr').each(function(){
        const $row = $(this);
        const $store = $row.find('.line_store_id');

        initSelect2Ajax(
            $store,
            "{{ route('admin.location_stores.fetch-stores') ?? '' }}",
            'Select store'
        );

        // Apply header store to each line initially (only if provided)
        if (defaultStoreId) {
            setSelect2Value($store, defaultStoreId, '');
        }

        // store change => recompute and autoFill (because store constraint changes)
        $store.on('change', async () => {
            await recomputeRowCaps($row, { autoFill: true });
        });

        // user input => clamp only
        $row.find('.qty_to_deliver').on('input', () => enforceCaps($row));
    });

    // initial compute (autoFill = true): qty_to_deliver becomes maxAllowed
    (async function(){
        const rows = $('#linesTable tbody tr');
        for (const r of rows) {
            await recomputeRowCaps($(r), { autoFill: true });
        }
    })();
}

/* -------------------- Edit-mode hydration of remaining (because edit rows have qty_remaining=0) -------------------- */
async function hydrateEditLinesFromOrderPayload() {
    const orderId = $('#sales_order_id').val();
    if (!orderId) return;

    const payload = await fetchOrderPayload(orderId);

    // map remaining by sales_order_line_id
    const remainingMap = {};
    (payload.lines || []).forEach(ln => {
        if (ln.sales_order_line_id != null) {
            remainingMap[String(ln.sales_order_line_id)] = Number(ln.qty_remaining || 0);
        }
    });

    // update rows
    const rows = $('#linesTable tbody tr');
    for (const r of rows) {
        const $row = $(r);
        const solId = $row.find('.sales_order_line_id').val();

        if (solId && Object.prototype.hasOwnProperty.call(remainingMap, String(solId))) {
            const rem = remainingMap[String(solId)];
            $row.find('.qty_remaining').val(rem);
            $row.find('.remaining_text').text(fmt(rem));
        } else {
            $row.find('.qty_remaining').val(0);
            $row.find('.remaining_text').text('0');
        }

        // recompute caps but preserve existing qty_to_deliver (autoFill false)
        await recomputeRowCaps($row, { autoFill: false });
    }
}

/* -------------------- Collect payload -------------------- */
function collectPayload() {
    const lines = [];

    $('#linesTable tbody tr').each(function(){
        const $row = $(this);
        const variantId = $row.find('.product_variant_id').val();
        if (!variantId) return;

        lines.push({
            sales_order_line_id: $row.find('.sales_order_line_id').val() || null,
            product_variant_id: Number(variantId),
            location_store_id: $row.find('.line_store_id').val() || null,
            qty_remaining: Number($row.find('.qty_remaining').val() || 0),
            qty_to_deliver: Number($row.find('.qty_to_deliver').val() || 0),
            qty_delivered_actual: Number($row.find('.qty_delivered_actual').val() || 0),
            unit_cost: 0,
        });
    });

    return {
        delivery_no: $('#delivery_no').val() || null,
        sales_order_id: $('#sales_order_id').val(),
        customer_id: $('#customer_id').val(),
        driver_id: $('#driver_id').val() || null,
        vehicle_id: $('#vehicle_id').val() || null,
        location_store_id: $('#header_store_id').val() || null,
        ship_date: $('#ship_date').val() || null,
        remarks: $('#remarks').val() || null,
        lines,
    };
}

/* -------------------- Reset dependency (order/customer rules) -------------------- */
function hardResetDependent() {
    $('#driver_id').val(null).trigger('change.select2');
    $('#vehicle_id').val(null).trigger('change.select2');
    $('#header_store_id').val(null).trigger('change.select2');
    resetLinesTable('Lines reset. Select a confirmed Sales Order again.');
}

/* -------------------- Boot -------------------- */
document.addEventListener('DOMContentLoaded', function(){

    // Header select2
    initSelect2Ajax('#sales_order_id', "{{ route('admin.sales.deliveries.select2.orders_confirmed') }}", 'Select confirmed order');
    initSelect2Ajax('#customer_id', "{{ route('admin.customers.select2') ?? '' }}", 'Select customer');
    initSelect2Ajax('#driver_id', "{{ route('admin.drivers.select2') ?? '' }}", 'Select driver');
    initSelect2Ajax('#vehicle_id', "{{ route('admin.vehicles.select2') }}", 'Select vehicle');
    initSelect2Ajax('#header_store_id', "{{ route('admin.location_stores.fetch-stores') ?? '' }}", 'Select store');

    // Order changed: set customer + build lines + reset dependent
    $('#sales_order_id').on('change', async function(){
        const orderId = $(this).val();

        if (!orderId) {
            $('#customer_id').val(null).trigger('change.select2');
            hardResetDependent();
            return;
        }

        try {
            const payload = await fetchOrderPayload(orderId);

            // Auto-populate customer from order-payload
            if (payload?.order?.customer_id) {
                setSelect2Value($('#customer_id'), payload.order.customer_id, payload.order.customer_text);
            }

            const defaultStoreId = $('#header_store_id').val() || null;
            buildLines(payload.lines || [], defaultStoreId);

        } catch (e) {
            Swal.fire({ icon:'error', title:'Error', text: e.message || 'Failed to load order' });
            $('#sales_order_id').val(null).trigger('change.select2');
            $('#customer_id').val(null).trigger('change.select2');
            hardResetDependent();
        }
    });

    // Customer change rule: if customer changed while order selected -> reset order + lines
    $('#customer_id').on('change', function(){
        if (!$('#sales_order_id').val()) return;
        $('#sales_order_id').val(null).trigger('change.select2');
        hardResetDependent();
    });

    // Header store: apply to empty line stores only, then recompute with autoFill=true
    $('#header_store_id').on('change', async function(){
        const storeId = $(this).val();

        const rows = $('#linesTable tbody tr');
        for (const r of rows) {
            const $row = $(r);
            const $lineStore = $row.find('.line_store_id');
            if (!$lineStore.length) continue;

            if (!$lineStore.val() && storeId) {
                setSelect2Value($lineStore, storeId, '');
            }

            await recomputeRowCaps($row, { autoFill: true });
        }
    });

    // Edit mode: hydrate remaining (from your provided order-payload structure)
    if (mode === 'edit') {
        (async function(){
            // init line stores select2 on existing rows, then hydrate
            $('#linesTable tbody tr').each(function(){
                const $row = $(this);
                const $store = $row.find('.line_store_id');

                initSelect2Ajax(
                    $store,
                    "{{ route('admin.location_stores.fetch-stores') ?? '' }}",
                    'Select store'
                );

                $store.on('change', async () => {
                    await recomputeRowCaps($row, { autoFill: true });
                });

                $row.find('.qty_to_deliver').on('input', () => enforceCaps($row));
            });

            try {
                await hydrateEditLinesFromOrderPayload();
            } catch (e) {
                console.warn('hydrateEditLinesFromOrderPayload failed', e);
            }
        })();
    }

    // Save
    $('#saveBtn').on('click', async function(){
        const payload = collectPayload();

        if (!payload.sales_order_id) {
            Swal.fire({ icon:'warning', title:'Missing order', text:'Please select a confirmed sales order.' });
            return;
        }
        if (!payload.customer_id) {
            Swal.fire({ icon:'warning', title:'Missing customer', text:'Customer is required.' });
            return;
        }
        if (!payload.lines.length) {
            Swal.fire({ icon:'warning', title:'No lines', text:'No delivery lines found.' });
            return;
        }

        const url = (mode === 'edit')
            ? `{{ url('admin/sales/deliveries') }}/${deliveryId}`
            : `{{ route('admin.sales.deliveries.store') }}`;

        const method = (mode === 'edit') ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json().catch(()=>({}));

            if (!res.ok) throw new Error(data.message || 'Save failed');

            Swal.fire({ icon:'success', title:'Saved', text:data.message || 'Saved', timer:1200, showConfirmButton:false });

            if (data.redirect) {
                window.location.href = data.redirect;
            }

        } catch (e) {
            Swal.fire({ icon:'error', title:'Error', text:e.message || 'Save failed' });
        }
    });

    // Post/Cancel (edit only)
    $('#postBtn').on('click', async function(){
        try {
            const res = await fetch(`{{ url('admin/sales/deliveries') }}/${deliveryId}/post`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json().catch(()=>({}));
            if (!res.ok) throw new Error(data.message || 'Post failed');
            Swal.fire({ icon:'success', title:'Posted', timer:1200, showConfirmButton:false });
            window.location.reload();
        } catch (e) {
            Swal.fire({ icon:'error', title:'Error', text:e.message || 'Post failed' });
        }
    });

    $('#cancelBtn').on('click', async function(){
        const ok = await Swal.fire({
            icon:'warning',
            title:'Cancel delivery?',
            text:'This will cancel the delivery draft.',
            showCancelButton:true,
            confirmButtonText:'Yes, cancel',
            confirmButtonColor:'#dc3545'
        });

        if (!ok.isConfirmed) return;

        try {
            const res = await fetch(`{{ url('admin/sales/deliveries') }}/${deliveryId}/cancel`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json().catch(()=>({}));
            if (!res.ok) throw new Error(data.message || 'Cancel failed');
            Swal.fire({ icon:'success', title:'Cancelled', timer:1200, showConfirmButton:false });
            window.location.reload();
        } catch (e) {
            Swal.fire({ icon:'error', title:'Error', text:e.message || 'Cancel failed' });
        }
    });

});
</script>
@endpush
