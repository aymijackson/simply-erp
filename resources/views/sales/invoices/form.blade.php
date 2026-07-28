@extends('layouts.master')

@section('title', $mode === 'edit' ? 'Edit Invoice' : 'New Invoice')

@push('styles')
<style>
    #linesTable{
        min-width: 1350px;
        table-layout: fixed;
    }
    #linesTable th, #linesTable td{
        vertical-align: middle;
    }

    #linesTable th.action-col,
    #linesTable td.action-col{ width: 80px; }

    #linesTable th.item-col, #linesTable td.item-col{ width: 280px; }
    #linesTable th.desc-col, #linesTable td.desc-col{ width: 320px; }
    #linesTable th.qty-col,  #linesTable td.qty-col { width: 120px; }
    #linesTable th.unit-col, #linesTable td.unit-col{ width: 160px; }
    #linesTable th.tax-col,  #linesTable td.tax-col { width: 220px; }
    #linesTable th.taxamt-col,#linesTable td.taxamt-col{ width: 140px; }
    #linesTable th.total-col,#linesTable td.total-col{ width: 170px; }

    #linesTable .form-control{ width: 100% !important; }
    #linesTable input[type="number"]{ min-width: 110px; }
    #linesTable tfoot th{ background: #fff; }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">{{ $mode === 'edit' ? 'Edit Invoice' : 'New Invoice' }}</h1>
            <small class="text-muted">Sales / Invoices</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.invoices.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>

            @can('sales.invoice.print')
                @if($mode === 'edit')
                    <a href="{{ route('admin.sales.invoices.print', $invoice->id) }}" target="_blank" class="btn btn-outline-dark">
                        <i class="fas fa-print mr-1"></i> Print PDF
                    </a>
                @endif
            @endcan

            @if($mode === 'edit')
                @if(($invoice->status ?? 'draft') === 'draft')
                    @can('sales.invoices.post')
                        <button class="btn btn-success" id="postInvoiceBtn">
                            <i class="fas fa-check mr-1"></i> Post
                        </button>
                    @endcan

                    @can('sales.invoices.cancel')
                        <button class="btn btn-outline-danger" id="cancelInvoiceBtn">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </button>
                    @endcan
                @endif
            @endif
        </div>
    </div>

    {{-- HEADER --}}
    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary"><i class="fas fa-info-circle mr-1"></i> Invoice Header</h6>
            @if($mode === 'edit')
                <span class="badge badge-{{ $invoice->status_badge ?? 'secondary' }}">{{ strtoupper($invoice->status ?? 'draft') }}</span>
            @endif
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label mb-1">Invoice No</label>
                    <input class="form-control" id="invoice_no" value="{{ $invoice->invoice_no }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Invoice Date</label>
                    <input type="date" class="form-control" id="invoice_date" value="{{ optional($invoice->invoice_date)->format('Y-m-d') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Due Date</label>
                    <input type="date" class="form-control" id="due_date" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Currency</label>
                    <input class="form-control" id="currency_code" value="{{ $invoice->currency_code ?? 'NGN' }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Sales Order <span class="text-danger">*</span></label>
                    <select id="sales_order_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                        @if($invoice->sales_order_id)
                            <option value="{{ $invoice->sales_order_id }}" selected>
                                {{ $invoice->order?->order_no ?? ('Order #'.$invoice->sales_order_id) }}
                            </option>
                        @endif
                    </select>
                    <small class="text-muted">Confirmed orders recommended</small>
                </div>

                <div class="col-md-8">
                    <label class="form-label mb-1">Customer <span class="text-danger">*</span></label>
                    <select id="customer_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                        @if($invoice->customer_id)
                            <option value="{{ $invoice->customer_id }}" selected>
                                {{ $invoice->customer?->name ?? ('Customer #'.$invoice->customer_id) }}
                            </option>
                        @endif
                    </select>
                    <small class="text-muted">Changing customer manually resets Sales Order and lines</small>
                </div>

                <div class="col-md-12">
                    <label class="form-label mb-1">Remarks</label>
                    <textarea class="form-control" id="remarks" rows="2">{{ $invoice->remarks }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- LINES --}}
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 text-primary"><i class="fas fa-list mr-1"></i> Invoice Lines</h6>
                <small class="text-muted">Supports product lines + custom charges + % charges + discounts</small>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" id="addProductBtn"><i class="fas fa-plus mr-1"></i> Add Product</button>
                <button class="btn btn-outline-secondary" id="addCustomBtn"><i class="fas fa-plus mr-1"></i> Add Custom</button>
                <button class="btn btn-outline-info" id="addPercentBtn"><i class="fas fa-percent mr-1"></i> Add % Charge</button>
                <button class="btn btn-outline-danger" id="addDiscountBtn"><i class="fas fa-minus mr-1"></i> Add Discount</button>
                <button class="btn btn-outline-dark" id="recalcBtn"><i class="fas fa-sync mr-1"></i> Recalc</button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="linesTable">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th class="item-col">Item / Variant</th>
                            <th class="desc-col">Description</th>
                            <th class="text-end qty-col">Qty</th>
                            <th class="text-end unit-col">Unit Price</th>
                            <th class="tax-col">Tax</th>
                            <th class="text-end taxamt-col">Tax Amt</th>
                            <th class="text-end total-col">Line Total</th>
                            <th class="action-col">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @if($mode === 'edit' && ($invoice->lines?->count() ?? 0))
                            @foreach($invoice->lines as $i => $ln)
                                @php
                                    $raw = strtolower(trim((string)($ln->line_type ?? 'product')));
                                    $map = [
                                        'product' => 'product',
                                        'custom' => 'custom',
                                        'charge' => 'custom',
                                        'fixed' => 'custom',
                                        'fee' => 'custom',
                                        'custom_charge' => 'custom',
                                        'percent' => 'percent',
                                        'percentage' => 'percent',
                                        'percent_charge' => 'percent',
                                        'discount' => 'discount',
                                        'disc' => 'discount',
                                    ];
                                    $t = $map[$raw] ?? 'product';
                                    $basis = $ln->calc_basis ?? 'subtotal';
                                @endphp

                                <tr data-line-type="{{ $t }}">
                                    <td class="row_no">{{ $i + 1 }}</td>

                                    <td class="item-col">
                                        <input type="hidden" class="sales_order_line_id" value="{{ $ln->sales_order_line_id }}">
                                        <input type="hidden" class="product_variant_id" value="{{ $ln->product_variant_id }}">
                                        <input type="hidden" class="line_type" value="{{ $t }}">

                                        <select class="form-control variant_select" style="width:100%; {{ $t !== 'product' ? 'display:none;' : '' }}">
                                            <option value=""></option>
                                            @if($t === 'product' && $ln->product_variant_id)
                                                <option value="{{ $ln->product_variant_id }}" selected>
                                                    {{ $ln->variant_text ?? ($ln->variant?->product?->product_name.' - '.$ln->variant?->sku) }}
                                                </option>
                                            @endif
                                        </select>

                                        <input
                                            type="text"
                                            class="form-control custom_title"
                                            placeholder="e.g. Delivery charge"
                                            value="{{ $t !== 'product' ? ($ln->title ?? ucfirst($t)) : '' }}"
                                            style="{{ $t === 'product' ? 'display:none;' : '' }}"
                                        >

                                        <div class="text-muted small mt-1 line_hint">
                                            {{ $t === 'product' ? '' : ucfirst($t).' line' }}
                                        </div>
                                    </td>

                                    <td class="desc-col">
                                        <input type="text" class="form-control description" placeholder="e.g. Delivery charge / Installation" value="{{ $ln->description }}">

                                        @if($t === 'percent')
                                            <div class="d-flex gap-2 mt-2">
                                                <select class="form-control calc_basis" style="width:50%;">
                                                    <option value="subtotal" {{ $basis === 'subtotal' ? 'selected' : '' }}>Subtotal</option>
                                                    <option value="grand_total" {{ $basis === 'grand_total' ? 'selected' : '' }}>Grand Total</option>
                                                </select>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control calc_percent"
                                                    style="width:50%;"
                                                    value="{{ (float)($ln->calc_percent ?? 0) }}"
                                                    placeholder="% rate"
                                                >
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-end qty-col">
                                        <input
                                            type="number"
                                            step="1"
                                            min="0"
                                            class="form-control text-end qty_to_invoice"
                                            value="{{ (float)($ln->qty_to_invoice ?? ($ln->qty ?? 1)) }}"
                                        >
                                    </td>

                                    <td class="text-end unit-col">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-control text-end unit_price"
                                            value="{{ (float)($ln->unit_price ?? 0) }}"
                                        >
                                    </td>

                                    <td class="tax-col">
                                        <select class="form-control tax_code_id" style="width:100%;">
                                            <option value=""></option>
                                            @if(!empty($ln->tax_code_id))
                                                <option value="{{ $ln->tax_code_id }}" selected>
                                                    {{ $ln->taxCode?->name ?? 'Tax code' }}
                                                </option>
                                            @endif
                                        </select>
                                        <div class="text-muted small tax_rate_text">Rate: {{ number_format((float)($ln->tax_rate ?? 0), 2) }}%</div>
                                        <input type="hidden" class="tax_rate" value="{{ (float)($ln->tax_rate ?? 0) }}">
                                    </td>

                                    <td class="text-end taxamt-col">
                                        <span class="tax_amount_text">{{ number_format((float)($ln->tax_amount ?? 0), 2) }}</span>
                                        <input type="hidden" class="tax_amount" value="{{ (float)($ln->tax_amount ?? 0) }}">
                                    </td>

                                    <td class="text-end total-col">
                                        <span class="line_total_text">{{ number_format((float)($ln->line_total ?? 0), 2) }}</span>
                                        <input type="hidden" class="line_total" value="{{ (float)($ln->line_total ?? 0) }}">
                                    </td>

                                    <td class="text-center action-col">
                                        <button class="btn btn-outline-danger btn-sm removeLineBtn" type="button">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    Select a Sales Order to load product lines, or add custom lines.
                                </td>
                            </tr>
                        @endif
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-end">Subtotal</th>
                            <th class="text-end" colspan="2"><span id="subtotalText">0.00</span></th>
                        </tr>
                        <tr>
                            <th colspan="7" class="text-end">Tax Total</th>
                            <th class="text-end" colspan="2"><span id="taxTotalText">0.00</span></th>
                        </tr>
                        <tr>
                            <th colspan="7" class="text-end">Grand Total</th>
                            <th class="text-end" colspan="2"><span id="grandTotalText">0.00</span></th>
                        </tr>
                    </tfoot>
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
const invoiceId = @json($invoice->id ?? null);

const salesOrdersUrl = "{{ route('admin.sales.invoices.select2.orders_confirmed') }}";
const customersUrl   = "{{ route('admin.customers.select2') }}";
const variantsUrl    = "{{ route('admin.inventory.products.variants.fetch') }}";
const taxCodesUrl    = "{{ url('admin/finance/expenses/lookups/tax-codes') }}";
const orderPayloadBaseUrl = "{{ url('admin/sales/invoices/order-payload') }}";

let suppressCustomerReset = false;

function num(n){
    n = Number(n);
    return isNaN(n) ? 0 : n;
}

function fmt2(n){
    return (Number(n || 0)).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function normalizeLineType(t){
    t = String(t || '').trim().toLowerCase();
    const map = {
        product: 'product',
        custom: 'custom',
        charge: 'custom',
        fixed: 'custom',
        fee: 'custom',
        custom_charge: 'custom',
        percent: 'percent',
        percentage: 'percent',
        percent_charge: 'percent',
        discount: 'discount',
        disc: 'discount',
    };
    return map[t] || 'custom';
}

function initSelect2Ajax(selector, url, placeholder, dropdownParent = null){
    const $el = $(selector);
    if (!$el.length) return;
    if (!url) return;
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        dropdownParent: dropdownParent ? $(dropdownParent) : $(document.body),
        ajax: {
            url: url,
            dataType: 'json',
            delay: 250,
            data: params => ({
                q: params.term || '',
                page: params.page || 1
            }),
            processResults: function(data){
                if (Array.isArray(data)) {
                    return { results: data };
                }
                if (data && Array.isArray(data.results)) {
                    return data;
                }
                if (data && Array.isArray(data.data)) {
                    return { results: data.data };
                }
                return { results: [] };
            },
            cache: true
        }
    });
}

function setSelect2Value($el, id, text, extraData = {}, triggerSelect = true){
    if(!id){
        return;
    }

    let $opt = $el.find(`option[value="${id}"]`);

    if (!$opt.length) {
        $opt = $(new Option(text || ('Item #' + id), id, true, true));
        $el.append($opt);
    } else {
        $opt.prop('selected', true);
    }

    $el.trigger('change');

    if (triggerSelect) {
        $el.trigger({
            type: 'select2:select',
            params: {
                data: Object.assign({ id, text: text || ('Item #' + id) }, extraData)
            }
        });
    }
}

function renumberRows(){
    $('#linesTable tbody tr').each(function(i){
        $(this).find('.row_no').text(i + 1);
    });
}

function blankBodyIfOnlyPlaceholder(){
    const $trs = $('#linesTable tbody tr');
    return ($trs.length === 1 && $trs.first().find('td').length === 1);
}

function ensureBody(){
    if(blankBodyIfOnlyPlaceholder()){
        $('#linesTable tbody').empty();
    }
}

function makeRow(lineType = 'custom'){
    lineType = normalizeLineType(lineType);

    const showVariant = (lineType === 'product');
    const titleDefault = (lineType === 'custom') ? '' : lineType.toUpperCase();

    return `
    <tr data-line-type="${lineType}">
        <td class="row_no"></td>

        <td class="item-col">
            <input type="hidden" class="sales_order_line_id" value="">
            <input type="hidden" class="product_variant_id" value="">
            <input type="hidden" class="line_type" value="${lineType}">

            <select class="form-control variant_select" style="width:100%; ${showVariant ? '' : 'display:none;'}">
                <option value=""></option>
            </select>

            <input type="text"
                   class="form-control custom_title"
                   placeholder="e.g. Delivery charge"
                   value="${titleDefault}"
                   style="${showVariant ? 'display:none;' : ''}">

            <div class="text-muted small mt-1 line_hint">
                ${showVariant ? '' : (lineType.charAt(0).toUpperCase() + lineType.slice(1)) + ' line'}
            </div>
        </td>

        <td class="desc-col">
            <input type="text" class="form-control description" placeholder="e.g. Delivery charge / Installation" value="">
            ${lineType === 'percent' ? `
                <div class="d-flex gap-2 mt-2">
                    <select class="form-control calc_basis" style="width:50%;">
                        <option value="subtotal" selected>Subtotal</option>
                        <option value="grand_total">Grand Total</option>
                    </select>
                    <input type="number"
                           step="0.0001"
                           min="0"
                           class="form-control calc_percent"
                           style="width:50%;"
                           value="0"
                           placeholder="% rate">
                </div>
            ` : ``}
        </td>

        <td class="text-end qty-col">
            <input type="number"
                   step="0.0001"
                   min="0"
                   class="form-control text-end qty_to_invoice"
                   value="${lineType === 'product' ? 0 : 1}">
        </td>

        <td class="text-end unit-col">
            <input type="number"
                   step="0.0001"
                   min="0"
                   class="form-control text-end unit_price"
                   value="0">
        </td>

        <td class="tax-col">
            <select class="form-control tax_code_id" style="width:100%;">
                <option value=""></option>
            </select>
            <div class="text-muted small tax_rate_text">Rate: 0.00%</div>
            <input type="hidden" class="tax_rate" value="0">
        </td>

        <td class="text-end taxamt-col">
            <span class="tax_amount_text">0.00</span>
            <input type="hidden" class="tax_amount" value="0">
        </td>

        <td class="text-end total-col">
            <span class="line_total_text">0.00</span>
            <input type="hidden" class="line_total" value="0">
        </td>

        <td class="text-center action-col">
            <button class="btn btn-outline-danger btn-sm removeLineBtn" type="button">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>`;
}

function initTaxSelect($row){
    const $tax = $row.find('.tax_code_id');
    if(!$tax.length) return;

    initSelect2Ajax($tax, taxCodesUrl, 'Tax code');

    $tax.off('select2:select.tax').on('select2:select.tax', function(e){
        const t = e.params.data || {};
        let rate = num(t.rate || 0);

        if (parseInt(t.is_exempt || 0) === 1 || parseInt(t.is_out_of_scope || 0) === 1) {
            rate = 0;
        }

        $row.find('.tax_rate').val(rate);
        $row.find('.tax_rate_text').text(`Rate: ${fmt2(rate)}%`);
        recalcRow($row);
    });

    $tax.off('select2:clear.tax').on('select2:clear.tax', function(){
        $row.find('.tax_rate').val(0);
        $row.find('.tax_rate_text').text('Rate: 0.00%');
        recalcRow($row);
    });

    $tax.off('change.tax').on('change.tax', function(){
        if(!$(this).val()){
            $row.find('.tax_rate').val(0);
            $row.find('.tax_rate_text').text('Rate: 0.00%');
            recalcRow($row);
        }
    });
}

function initRowPlugins($row){
    const type = normalizeLineType($row.data('line-type'));
    $row.attr('data-line-type', type);
    $row.find('.line_type').val(type);

    if(type === 'product'){
        initSelect2Ajax($row.find('.variant_select'), variantsUrl, 'Search variant');

        $row.find('.variant_select').off('select2:select.variant').on('select2:select.variant', function(e){
            const v = e.params.data || {};
            $row.find('.product_variant_id').val(v.id || '');
            recalcRow($row);
        });
    }

    initTaxSelect($row);

    $row.find('.removeLineBtn').off('click.remove').on('click.remove', function(){
        $row.remove();

        if($('#linesTable tbody tr').length === 0){
            $('#linesTable tbody').html(`<tr><td colspan="9" class="text-center text-muted">No lines. Add a line.</td></tr>`);
        }

        renumberRows();
        recalcTotals();
    });
}

function recalcRow($row){
    const type = normalizeLineType($row.data('line-type'));

    let qty = num($row.find('.qty_to_invoice').val());
    let unit = num($row.find('.unit_price').val());
    let base = qty * unit;

    if(type === 'percent') base = 0;
    if(type === 'discount') base = base * -1;

    const rate = num($row.find('.tax_rate').val());
    const taxAmt = (base * rate) / 100;
    const lineTotal = base + taxAmt;

    $row.find('.tax_amount').val(taxAmt);
    $row.find('.tax_amount_text').text(fmt2(taxAmt));

    $row.find('.line_total').val(lineTotal);
    $row.find('.line_total_text').text(fmt2(lineTotal));

    recalcTotals();
}

function recalcTotals(){
    let subtotal = 0;
    let taxTotal = 0;

    $('#linesTable tbody tr').each(function(){
        const $row = $(this);
        if($row.find('td').length === 1) return;

        const type = normalizeLineType($row.data('line-type'));
        if(type === 'percent') return;

        subtotal += num($row.find('.line_total').val()) - num($row.find('.tax_amount').val());
        taxTotal += num($row.find('.tax_amount').val());
    });

    let grandBeforePercent = subtotal + taxTotal;

    $('#linesTable tbody tr').each(function(){
        const $row = $(this);
        if($row.find('td').length === 1) return;

        const type = normalizeLineType($row.data('line-type'));
        if(type !== 'percent') return;

        const basis = ($row.find('.calc_basis').val() || 'subtotal');
        const pct = num($row.find('.calc_percent').val());

        const baseValue = (basis === 'grand_total') ? grandBeforePercent : subtotal;
        const amount = (baseValue * pct) / 100;

        $row.find('.qty_to_invoice').val(1);
        $row.find('.unit_price').val(amount);

        const rate = num($row.find('.tax_rate').val());
        const taxAmt = (amount * rate) / 100;
        const lineTotal = amount + taxAmt;

        $row.find('.tax_amount').val(taxAmt);
        $row.find('.tax_amount_text').text(fmt2(taxAmt));
        $row.find('.line_total').val(lineTotal);
        $row.find('.line_total_text').text(fmt2(lineTotal));

        subtotal += amount;
        taxTotal += taxAmt;

        grandBeforePercent = subtotal + taxTotal;
    });

    const grandTotal = subtotal + taxTotal;

    $('#subtotalText').text(fmt2(subtotal));
    $('#taxTotalText').text(fmt2(taxTotal));
    $('#grandTotalText').text(fmt2(grandTotal));
}

async function fetchOrderPayload(orderId){
    const res = await fetch(`${orderPayloadBaseUrl}/${orderId}`, {
        headers: { 'Accept': 'application/json' }
    });

    const data = await res.json().catch(() => ({}));

    if(!res.ok){
        throw new Error(data.message || 'Failed to load order');
    }

    return data;
}

function resetLines(msg = 'Lines reset. Select a Sales Order again.'){
    $('#linesTable tbody').html(`<tr><td colspan="9" class="text-center text-muted">${msg}</td></tr>`);
    recalcTotals();
}

function buildProductLinesFromOrder(lines){
    ensureBody();
    $('#linesTable tbody').empty();

    if(!Array.isArray(lines) || !lines.length){
        resetLines('No lines found on selected order.');
        return;
    }

    lines.forEach((ln) => {
        const $row = $(makeRow('product'));
        $('#linesTable tbody').append($row);

        $row.find('.sales_order_line_id').val(ln.sales_order_line_id || '');
        $row.find('.product_variant_id').val(ln.product_variant_id || '');

        initRowPlugins($row);

        const $variant = $row.find('.variant_select');
        const text = ln.variant_text || ('Variant #' + ln.product_variant_id);
        setSelect2Value($variant, ln.product_variant_id, text);

        const remaining = num(ln.qty_remaining || 0);
        $row.find('.qty_to_invoice').val(remaining);
        $row.find('.unit_price').val(num(ln.unit_price ?? 0));

        recalcRow($row);
    });

    renumberRows();
    recalcTotals();
}

function collectPayload(){
    const lines = [];

    $('#linesTable tbody tr').each(function(){
        const $row = $(this);
        if($row.find('td').length === 1) return;

        const line_type = normalizeLineType($row.data('line-type'));

        lines.push({
            line_type,
            sales_order_line_id: $row.find('.sales_order_line_id').val() || null,
            product_variant_id: $row.find('.product_variant_id').val() || null,
            title: $row.find('.custom_title').val() || null,
            description: $row.find('.description').val() || null,
            qty_to_invoice: num($row.find('.qty_to_invoice').val()),
            unit_price: num($row.find('.unit_price').val()),
            tax_code_id: $row.find('.tax_code_id').val() || null,
            tax_rate: num($row.find('.tax_rate').val()),
            tax_amount: num($row.find('.tax_amount').val()),
            line_total: num($row.find('.line_total').val()),
            calc_basis: $row.find('.calc_basis').length ? ($row.find('.calc_basis').val() || 'subtotal') : null,
            calc_percent: $row.find('.calc_percent').length ? num($row.find('.calc_percent').val()) : null,
        });
    });

    return {
        invoice_no: $('#invoice_no').val() || null,
        invoice_date: $('#invoice_date').val() || null,
        due_date: $('#due_date').val() || null,
        currency_code: $('#currency_code').val() || 'NGN',
        sales_order_id: $('#sales_order_id').val(),
        customer_id: $('#customer_id').val(),
        remarks: $('#remarks').val() || null,
        lines
    };
}

document.addEventListener('DOMContentLoaded', function(){

    initSelect2Ajax('#sales_order_id', salesOrdersUrl, 'Select order');
    initSelect2Ajax('#customer_id', customersUrl, 'Select customer');

    $('#linesTable').on('input change', '.qty_to_invoice, .unit_price, .description, .calc_basis, .calc_percent', function(){
        const $row = $(this).closest('tr');
        if($row.find('td').length === 1) return;
        recalcRow($row);
    });

    $('#linesTable tbody tr').each(function(){
        const $row = $(this);
        if($row.find('td').length === 1) return;
        initRowPlugins($row);
        recalcRow($row);
    });

    renumberRows();
    recalcTotals();

    $('#sales_order_id').on('change', async function(){
        const orderId = $(this).val();

        if(!orderId){
            if (!suppressCustomerReset) {
                $('#customer_id').val(null).trigger('change.select2');
                resetLines('Select a Sales Order to load product lines, or add custom lines.');
            }
            return;
        }

        try{
            const payload = await fetchOrderPayload(orderId);

            if(payload?.order?.customer_id){
                suppressCustomerReset = true;
                setSelect2Value(
                    $('#customer_id'),
                    payload.order.customer_id,
                    payload.order.customer_text,
                    {},
                    false
                );
                setTimeout(() => {
                    suppressCustomerReset = false;
                }, 0);
            }

            buildProductLinesFromOrder(payload.lines || []);
        }catch(e){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: e.message || 'Failed to load order'
            });

            $('#sales_order_id').val(null).trigger('change.select2');
            $('#customer_id').val(null).trigger('change.select2');
            resetLines('Select a Sales Order to load product lines, or add custom lines.');
        }
    });

    $('#customer_id').on('change', function(){
        if (suppressCustomerReset) return;
        if (!$('#sales_order_id').val()) return;

        $('#sales_order_id').val(null).trigger('change');
        resetLines('Customer changed. Please re-select Sales Order.');
    });

    $('#addProductBtn').on('click', function(e){
        e.preventDefault();
        ensureBody();
        const $row = $(makeRow('product'));
        $('#linesTable tbody').append($row);
        initRowPlugins($row);
        renumberRows();
        recalcRow($row);
    });

    $('#addCustomBtn').on('click', function(e){
        e.preventDefault();
        ensureBody();
        const $row = $(makeRow('custom'));
        $('#linesTable tbody').append($row);
        initRowPlugins($row);
        renumberRows();
        recalcRow($row);
    });

    $('#addPercentBtn').on('click', function(e){
        e.preventDefault();
        ensureBody();
        const $row = $(makeRow('percent'));
        $('#linesTable tbody').append($row);
        initRowPlugins($row);
        renumberRows();
        recalcRow($row);
    });

    $('#addDiscountBtn').on('click', function(e){
        e.preventDefault();
        ensureBody();
        const $row = $(makeRow('discount'));
        $('#linesTable tbody').append($row);
        initRowPlugins($row);
        renumberRows();
        recalcRow($row);
    });

    $('#recalcBtn').on('click', function(e){
        e.preventDefault();

        $('#linesTable tbody tr').each(function(){
            const $row = $(this);
            if($row.find('td').length === 1) return;
            recalcRow($row);
        });

        recalcTotals();
    });

    $('#saveBtn').on('click', async function(){
        const payload = collectPayload();

        if(!payload.customer_id){
            Swal.fire({
                icon: 'warning',
                title: 'Missing customer',
                text: 'Customer is required.'
            });
            return;
        }

        if(!payload.lines.length){
            Swal.fire({
                icon: 'warning',
                title: 'No lines',
                text: 'Add at least one invoice line.'
            });
            return;
        }

        const url = (mode === 'edit')
            ? `{{ url('admin/sales/invoices') }}/${invoiceId}`
            : `{{ route('admin.sales.invoices.store') }}`;

        const method = (mode === 'edit') ? 'PUT' : 'POST';

        try{
            const res = await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json().catch(() => ({}));

            if(!res.ok){
                throw new Error(data.message || 'Save failed');
            }

            Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: data.message || 'Invoice saved',
                timer: 1200,
                showConfirmButton: false
            });

            if(data.redirect){
                window.location.href = data.redirect;
            }
        }catch(e){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: e.message || 'Save failed'
            });
        }
    });

    const postInvoiceUrl = "{{ $mode === 'edit' ? route('admin.sales.invoices.post', $invoice->id) : '' }}";
    const cancelInvoiceUrl = "{{ $mode === 'edit' ? route('admin.sales.invoices.cancel', $invoice->id) : '' }}";

    $('#postInvoiceBtn').on('click', function(e){
        e.preventDefault();

        Swal.fire({
            icon: 'question',
            title: 'Post this invoice?',
            text: 'After posting, editing should be restricted.',
            showCancelButton: true,
            confirmButtonText: 'Yes, Post',
            cancelButtonText: 'Cancel'
        }).then((r) => {
            if(!r.isConfirmed) return;

            $.post(postInvoiceUrl)
                .done(function(res){
                    Swal.fire({
                        icon: 'success',
                        title: 'Posted',
                        text: res.message || 'Invoice posted.'
                    }).then(() => window.location.reload());
                })
                .fail(function(xhr){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr?.responseJSON?.message || 'Failed to post.'
                    });
                });
        });
    });

    $('#cancelInvoiceBtn').on('click', function(e){
        e.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: 'Cancel this invoice?',
            text: 'This action can be restricted by policy.',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel',
            cancelButtonText: 'Keep'
        }).then((r) => {
            if(!r.isConfirmed) return;

            $.post(cancelInvoiceUrl)
                .done(function(res){
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled',
                        text: res.message || 'Invoice cancelled.'
                    }).then(() => window.location.reload());
                })
                .fail(function(xhr){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr?.responseJSON?.message || 'Failed to cancel.'
                    });
                });
        });
    });
});
</script>
@endpush