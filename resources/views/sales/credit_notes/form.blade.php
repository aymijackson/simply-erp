@extends('layouts.master')

@section('title', ($mode==='edit' ? 'Edit' : 'Create').' Credit Note')
@push('styles')
<style>
/* Force select2 to behave inside bootstrap grid/flex */
.select2-container { width: 100% !important; }

/* Match bootstrap input height */
.select2-container .select2-selection--single{
    height: calc(1.5em + .75rem + 2px);
    padding: .375rem .75rem;
    display:flex;
    align-items:center;
}

/* Fix text alignment */
.select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height: 1.5;
    padding-left: 0;
}

/* Fix dropdown arrow vertical alignment */
.select2-container--default .select2-selection--single .select2-selection__arrow{
    height: calc(1.5em + .75rem + 2px);
    top: 0;
    right: .5rem;
}

/* Clear "x" spacing */
.select2-container--default .select2-selection--single .select2-selection__clear{
    margin-right: .5rem;
}

/* Force select2 to look like bootstrap form-control */
.select2-container .select2-selection--single {
    border: 1px solid #ced4da !important;
    border-radius: .375rem !important;
    background-color: #fff !important;
    height: calc(1.5em + .75rem + 2px);
    padding: .375rem .75rem;
}

/* Focus state like bootstrap */
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #86b7fe !important;
    box-shadow: 0 0 0 .2rem rgba(13,110,253,.25) !important;
}

/* Hover */
.select2-container .select2-selection--single:hover {
    border-color: #86b7fe !important;
}

/* Text alignment */
.select2-selection__rendered {
    line-height: 1.5 !important;
    padding-left: 0 !important;
}

/* Arrow alignment */
.select2-selection__arrow {
    height: 100% !important;
    right: 8px !important;
}

/* Ensure full width */
.select2-container {
    width: 100% !important;
}
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">
                {{ $mode==='edit' ? 'Edit' : 'New' }} Credit Note
            </h1>
            <small class="text-muted">Sales / Credit Notes</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.credit-notes.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            @if(($creditNote->status ?? 'draft') === 'draft')
                <button class="btn btn-primary" id="saveBtn">
                    <i class="fas fa-save"></i> Save
                </button>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select id="customer_id" class="form-control" {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}>
                        <option value="">Select customer</option>
                        {{-- populate --}}
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Invoice (best practice)</label>
                
                    <div class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            <select id="sales_invoice_id" class="form-control"
                                    {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}>
                                <option value="">Select invoice</option>
                            </select>
                        </div>
                
                        @if(($creditNote->status ?? 'draft') === 'draft')
                            <button class="btn btn-outline-primary flex-shrink-0"
                                    type="button"
                                    id="loadInvoiceLinesBtn"
                                    title="Load invoice lines">
                                <i class="fas fa-download"></i>
                            </button>
                        @endif
                    </div>
                
                    <small class="text-muted">Pick invoice, then click the download button to load lines.</small>
                </div>



                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" id="credit_note_date" class="form-control"
                           value="{{ optional($creditNote->credit_note_date)->format('Y-m-d') ?? now()->format('Y-m-d') }}"
                           {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Currency</label>
                    <input type="text" id="currency_code" class="form-control"
                           value="{{ $creditNote->currency_code ?? 'NGN' }}"
                           {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Reason</label>
                    <input type="text" id="reason" class="form-control"
                           value="{{ $creditNote->reason }}"
                           {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Remarks</label>
                    <input type="text" id="remarks" class="form-control"
                           value="{{ $creditNote->remarks }}"
                           {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}>
                </div>
            </div>
        </div>
    </div>

    {{-- LINES --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="fw-bold"><i class="fas fa-list me-1"></i> Lines</div>

            @if(($creditNote->status ?? 'draft') === 'draft')
                <button class="btn btn-outline-primary btn-sm" id="addLineBtn">
                    <i class="fas fa-plus"></i> Add Line
                </button>
            @endif
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="linesTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-end" style="width:120px;">Qty</th>
                            <th class="text-end" style="width:140px;">Unit Price</th>
                            <th class="text-end" style="width:120px;">Tax Rate</th>
                            <th class="text-end" style="width:160px;">Line Total</th>
                            <th style="width:70px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lines = $creditNote->lines ?? collect(); @endphp

                        @if($lines->count())
                            @foreach($lines as $i => $l)
                                <tr>
                                    <td>
                                        <input type="text" class="form-control line-desc"
                                               value="{{ $l->description }}"
                                               {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}>
                                    </td>
                                    <td><input type="number" step="0.0001" class="form-control text-end line-qty" value="{{ (float)$l->qty }}" {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}></td>
                                    <td><input type="number" step="0.0001" class="form-control text-end line-unit" value="{{ (float)$l->unit_price }}" {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}></td>
                                    <td><input type="number" step="0.0001" class="form-control text-end line-taxrate" value="{{ (float)($l->tax_rate ?? 0) }}" {{ ($creditNote->status ?? 'draft')!=='draft' ? 'disabled':'' }}></td>
                                    <td class="text-end fw-bold line-total-text">0.00</td>
                                    <td class="text-center">
                                        @if(($creditNote->status ?? 'draft') === 'draft')
                                            <button class="btn btn-sm btn-outline-danger removeLineBtn"><i class="fas fa-trash"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr class="no-lines">
                                <td colspan="6" class="text-center text-muted">No lines added yet.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="row justify-content-end mt-3">
                <div class="col-md-5">
                    <table class="table table-sm">
                        <tr>
                            <th class="text-end">Subtotal:</th>
                            <td class="text-end fw-bold" id="subTotalText">0.00</td>
                        </tr>
                        <tr>
                            <th class="text-end">Tax Total:</th>
                            <td class="text-end fw-bold" id="taxTotalText">0.00</td>
                        </tr>
                        <tr>
                            <th class="text-end">Grand Total:</th>
                            <td class="text-end fw-bold" id="grandTotalText">0.00</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if(($creditNote->status ?? 'draft') === 'draft' && $mode==='edit')
                <hr>
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-success" id="postBtn"><i class="fas fa-check"></i> Post</button>
                    <button class="btn btn-danger" id="deleteBtn"><i class="fas fa-trash"></i> Delete</button>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const mode = "{{ $mode }}";
const isDraft = "{{ $creditNote->status ?? 'draft' }}" === "draft";

const storeUrl  = "{{ route('admin.sales.credit-notes.store') }}";
const updateUrl = "{{ $mode==='edit' ? route('admin.sales.credit-notes.update', $creditNote->id) : '' }}";
const postUrl   = "{{ $mode==='edit' ? route('admin.sales.credit-notes.post', $creditNote->id) : '' }}";
const delUrl    = "{{ $mode==='edit' ? route('admin.sales.credit-notes.destroy', $creditNote->id) : '' }}";

function fmt2(n){
    n = parseFloat(n || 0);
    return n.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
}

function recalc(){
    let sub = 0, tax = 0, grand = 0;

    $('#linesTable tbody tr').each(function(){
        if($(this).hasClass('no-lines')) return;

        const qty = parseFloat($(this).find('.line-qty').val() || 0);
        const unit = parseFloat($(this).find('.line-unit').val() || 0);
        const rate = parseFloat($(this).find('.line-taxrate').val() || 0);

        const base = qty * unit;
        const t = rate > 0 ? (base * rate) : 0;
        const total = base + t;

        sub += base;
        tax += t;
        grand += total;

        $(this).find('.line-total-text').text(fmt2(total));
    });

    $('#subTotalText').text(fmt2(sub));
    $('#taxTotalText').text(fmt2(tax));
    $('#grandTotalText').text(fmt2(grand));
}

function addLineRow(){
    const html = `
    <tr>
        <td><input type="text" class="form-control line-desc" placeholder="e.g. Return/Adjustment"></td>
        <td><input type="number" step="0.0001" class="form-control text-end line-qty" value="1"></td>
        <td><input type="number" step="0.0001" class="form-control text-end line-unit" value="0"></td>
        <td><input type="number" step="0.0001" class="form-control text-end line-taxrate" value="0"></td>
        <td class="text-end fw-bold line-total-text">0.00</td>
        <td class="text-center"><button class="btn btn-sm btn-outline-danger removeLineBtn"><i class="fas fa-trash"></i></button></td>
    </tr>`;
    $('#linesTable tbody .no-lines').remove();
    $('#linesTable tbody').append(html);
    recalc();
}

function collectPayload(){
    const lines = [];
    $('#linesTable tbody tr').each(function(){
        if($(this).hasClass('no-lines')) return;

        lines.push({
            description: $(this).find('.line-desc').val(),
            qty: $(this).find('.line-qty').val(),
            unit_price: $(this).find('.line-unit').val(),
            tax_rate: $(this).find('.line-taxrate').val(),
        });
    });

    return {
        customer_id: $('#customer_id').val(),
        sales_invoice_id: $('#sales_invoice_id').val(),
        credit_note_date: $('#credit_note_date').val(),
        currency_code: $('#currency_code').val(),
        reason: $('#reason').val(),
        remarks: $('#remarks').val(),
        lines: lines
    };
}

$(document).on('input', '.line-qty,.line-unit,.line-taxrate', recalc);
$(document).on('click', '.removeLineBtn', function(e){
    e.preventDefault();
    $(this).closest('tr').remove();
    if($('#linesTable tbody tr').length === 0){
        $('#linesTable tbody').html(`<tr class="no-lines"><td colspan="6" class="text-center text-muted">No lines added yet.</td></tr>`);
    }
    recalc();
});

$('#addLineBtn').on('click', function(){
    if(!isDraft) return;
    addLineRow();
});

$('#saveBtn').on('click', function(){
    if(!isDraft) return;

    const payload = collectPayload();

    const url = (mode === 'edit') ? updateUrl : storeUrl;
    const method = (mode === 'edit') ? 'PUT' : 'POST';

    $.ajax({url, method, data: payload})
        .done(function(res){
            Swal.fire({icon:'success', title:'Saved', text: res.message || 'Saved successfully.'});
            if(res.redirect) window.location.href = res.redirect;
        })
        .fail(function(xhr){
            Swal.fire({icon:'error', title:'Error', text: xhr?.responseJSON?.message || 'Failed to save.'});
        });
});

$('#postBtn').on('click', function(){
    if(!isDraft) return;

    Swal.fire({
        icon: 'question',
        title: 'Post this credit note?',
        text: 'You will not be able to edit after posting.',
        showCancelButton: true,
        confirmButtonText: 'Yes, post',
        cancelButtonText: 'Cancel'
    }).then((r)=>{
        if(!r.isConfirmed) return;

        $.post(postUrl)
            .done(function(res){
                Swal.fire({icon:'success', title:'Posted', text: res.message || 'Posted.'})
                    .then(()=> window.location.href = "{{ $mode==='edit' ? route('admin.sales.credit-notes.show',$creditNote->id) : route('admin.sales.credit-notes.index') }}");
            })
            .fail(function(xhr){
                Swal.fire({icon:'error', title:'Error', text: xhr?.responseJSON?.message || 'Failed to post.'});
            });
    });
});

$('#deleteBtn').on('click', function(){
    if(!isDraft) return;

    Swal.fire({
        icon: 'warning',
        title: 'Delete this credit note?',
        text: 'This action cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((r)=>{
        if(!r.isConfirmed) return;

        $.ajax({url: delUrl, method:'DELETE'})
            .done(function(res){
                Swal.fire({icon:'success', title:'Deleted', text: res.message || 'Deleted.'})
                    .then(()=> window.location.href = "{{ route('admin.sales.credit-notes.index') }}");
            })
            .fail(function(xhr){
                Swal.fire({icon:'error', title:'Error', text: xhr?.responseJSON?.message || 'Failed to delete.'});
            });
    });
});

$(function(){ recalc(); });

// second script section

const select2CustomersUrl = "{{ route('admin.customers.select2') }}"; // or your real route
const select2InvoicesUrl  = "{{ route('admin.sales.credit-notes.invoices.select2') }}";

const invoiceLinesUrlBase = "{{ url('admin/sales/credit-notes/invoices') }}"; // we'll build /{id}/lines

function buildInvoiceLinesUrl(invoiceId){
    return `${invoiceLinesUrlBase}/${invoiceId}/lines`;
}

function initSelect2Customer(){
    // If you already have it globally, this won't harm
    $('#customer_id').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Select customer',
        allowClear: true,
        ajax: {
            url: select2CustomersUrl,
            dataType: 'json',
            delay: 250,
            data: function(params){
                return { q: params.term || '' };
            },
            // endpoint should return {results:[{id,text}]}
            processResults: function(data){
                if (data && data.results) return data;
                return { results: Array.isArray(data) ? data : [] };
            },
            cache: true
        }
    });

    // when customer changes, clear invoice and lines
    $('#customer_id').on('change', function(){
        $('#sales_invoice_id').val(null).trigger('change');
        // optional: reset table to placeholder
        $('#linesTable tbody').html(`<tr class="no-lines"><td colspan="6" class="text-center text-muted">No lines added yet.</td></tr>`);
        recalc();
    });
}

function initSelect2Invoice(){
    $('#sales_invoice_id').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Select invoice',
        allowClear: true,
        ajax: {
            url: select2InvoicesUrl,
            dataType: 'json',
            delay: 250,
            data: function(params){
                return {
                    q: params.term || '',
                    customer_id: $('#customer_id').val() || 0
                };
            },
            processResults: function(data){
                if (data && data.results) return data;
                return { results: Array.isArray(data) ? data : [] };
            },
            cache: true
        }
    });
}

function escapeHtml(str){
    return String(str ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}

function setLinesFromInvoice(lines){
    $('#linesTable tbody').empty();

    if(!Array.isArray(lines) || !lines.length){
        $('#linesTable tbody').html(`<tr class="no-lines"><td colspan="6" class="text-center text-muted">Invoice has no lines.</td></tr>`);
        recalc();
        return;
    }

    let html = '';
    lines.forEach(function(l){
        const desc = escapeHtml(l.description || l.item_name || 'Invoice line');
        const qty  = parseFloat(l.qty || l.quantity || 0);
        const unit = parseFloat(l.unit_price || l.price || 0);
        const taxRate = parseFloat(l.tax_rate || 0);

        html += `
        <tr>
            <td><input type="text" class="form-control line-desc" value="${desc}" ${isDraft?'':'disabled'}></td>
            <td><input type="number" step="0.0001" min="0" max="${qty}" class="form-control text-end line-qty" value="${qty}" ${isDraft?'':'disabled'}></td>
            <td><input type="number" step="0.0001" min="0" class="form-control text-end line-unit" value="${unit}" ${isDraft?'':'disabled'}></td>
            <td><input type="number" step="0.0001" min="0" class="form-control text-end line-taxrate" value="${taxRate}" ${isDraft?'':'disabled'}></td>
            <td class="text-end fw-bold line-total-text">0.00</td>
            <td class="text-center">
                ${isDraft ? `<button class="btn btn-sm btn-outline-danger removeLineBtn"><i class="fas fa-trash"></i></button>` : ``}
            </td>
        </tr>`;
    });

    $('#linesTable tbody').html(html);
    recalc();
}

// Load lines button
$('#loadInvoiceLinesBtn').on('click', function(){
    if(!isDraft) return;

    const customerId = $('#customer_id').val();
    const invoiceId  = $('#sales_invoice_id').val();

    if(!customerId){
        Swal.fire({icon:'warning', title:'Select customer', text:'Please select a customer first.'});
        return;
    }
    if(!invoiceId){
        Swal.fire({icon:'warning', title:'Select invoice', text:'Please select an invoice to load lines.'});
        return;
    }

    Swal.fire({
        icon: 'question',
        title: 'Load invoice lines?',
        text: 'This will replace any existing credit note lines.',
        showCancelButton: true,
        confirmButtonText: 'Yes, load',
        cancelButtonText: 'Cancel'
    }).then((r)=>{
        if(!r.isConfirmed) return;

        $.get(buildInvoiceLinesUrl(invoiceId), { customer_id: customerId })
            .done(function(res){
                if(res?.invoice?.currency_code){
                    $('#currency_code').val(res.invoice.currency_code);
                }
                setLinesFromInvoice(res.lines || []);
                Swal.fire({icon:'success', title:'Loaded', text:'Invoice lines loaded.'});
            })
            .fail(function(xhr){
                Swal.fire({icon:'error', title:'Error', text: xhr?.responseJSON?.message || 'Failed to load invoice lines.'});
            });
    });
});

$(function(){
    initSelect2Customer();
    initSelect2Invoice();
});

let linesDirty = false;

$(document).on('input change', '.line-desc,.line-qty,.line-unit,.line-taxrate', function(){
    linesDirty = true;
});

</script>


@endpush
