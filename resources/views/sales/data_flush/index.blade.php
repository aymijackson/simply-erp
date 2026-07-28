@extends('layouts.master')

@section('title','Flush Sales Data')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-danger mb-0">Flush Sales Data</h1>
            <small class="text-muted">Sales → Utilities → Data Flush</small>
        </div>
    </div>

    <div class="alert alert-warning">
        <b>Warning:</b> This tool permanently deletes sales records based on your scope.
        Always run <b>Preview</b> before flushing. Every flush is logged.
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Scope</label>
                    <select id="scope" class="form-control">
                        <option value="draft_only">Draft Only (recommended)</option>
                        <option value="date_range">Date Range</option>
                        <option value="customer">By Customer</option>
                        <option value="full_reset">Full Reset (dangerous)</option>
                    </select>
                </div>

                <div class="col-md-4 scope-date d-none">
                    <label class="form-label">From</label>
                    <input type="date" id="from" class="form-control">
                </div>

                <div class="col-md-4 scope-date d-none">
                    <label class="form-label">To</label>
                    <input type="date" id="to" class="form-control">
                </div>

                <div class="col-md-6 scope-customer d-none">
                    <label class="form-label">Customer</label>
                    <select id="customer_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Include Posted?</label>
                    <select id="include_posted" class="form-control">
                        <option value="0" selected>No (recommended)</option>
                        <option value="1">Yes (dangerous)</option>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Sales modules to flush</label>
                    <div class="d-flex flex-wrap gap-3">
                        <label><input type="checkbox" class="mod" value="orders" checked> Orders</label>
                        <label><input type="checkbox" class="mod" value="deliveries" checked> Deliveries</label>
                        <label><input type="checkbox" class="mod" value="invoices" checked> Invoices</label>
                        <label><input type="checkbox" class="mod" value="payments" checked> Payments</label>
                        <label><input type="checkbox" class="mod" value="credit_notes" checked> Credit Notes</label>
                        <label><input type="checkbox" class="mod" value="allocations" checked> Payment Allocations</label>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Confirmation phrase</label>
                    <input type="text" id="confirm_phrase" class="form-control" placeholder="Type: FLUSH SALES">
                    <small class="text-muted">Must match exactly <b>FLUSH SALES</b> to run.</small>
                </div>

            </div>

            <hr>

            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" id="previewBtn">
                    <i class="fas fa-search me-1"></i> Preview
                </button>

                <button class="btn btn-danger" id="runBtn">
                    <i class="fas fa-broom me-1"></i> Run Flush
                </button>
            </div>

            <div class="mt-3 d-none" id="previewBox">
                <div class="alert alert-info mb-0">
                    <div class="fw-bold mb-2">Preview Summary</div>
                    <pre class="mb-0" id="previewJson" style="white-space:pre-wrap;"></pre>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const previewUrl = "{{ route('admin.sales.data-flush.preview') }}";
const runUrl     = "{{ route('admin.sales.data-flush.run') }}";
const customersSelect2Url = "{{ route('admin.customers.select2') ?? '' }}";

function getModules(){
    return $('.mod:checked').map(function(){ return $(this).val(); }).get();
}

function payload(){
    return {
        scope: $('#scope').val(),
        include_posted: Number($('#include_posted').val() || 0),
        from: $('#from').val() || null,
        to: $('#to').val() || null,
        customer_id: $('#customer_id').val() || null,
        modules: getModules(),
        confirm_phrase: $('#confirm_phrase').val() || ''
    };
}

function toggleScopeUI(){
    const s = $('#scope').val();
    $('.scope-date').toggleClass('d-none', s !== 'date_range');
    $('.scope-customer').toggleClass('d-none', s !== 'customer');
}
$('#scope').on('change', toggleScopeUI);
toggleScopeUI();

if(customersSelect2Url){
    $('#customer_id').select2({
        theme:'bootstrap4',
        width:'100%',
        placeholder:'Select customer',
        allowClear:true,
        ajax:{
            url: customersSelect2Url,
            dataType:'json',
            delay:250,
            data: params => ({ q: params.term || '' }),
            processResults: data => data.results ? data : ({ results: Array.isArray(data) ? data : [] })
        }
    });
}

async function doPreview(){
    const data = payload();

    if(!data.modules.length){
        Swal.fire({icon:'warning', title:'Select modules', text:'Pick at least one module.'});
        return;
    }

    try{
        const res = await $.post(previewUrl, data);
        $('#previewBox').removeClass('d-none');
        $('#previewJson').text(JSON.stringify(res.summary, null, 2));
        return res.summary;
    }catch(xhr){
        Swal.fire({icon:'error', title:'Preview failed', text: xhr?.responseJSON?.message || 'Could not preview.'});
        return null;
    }
}

$('#previewBtn').on('click', function(e){
    e.preventDefault();
    doPreview();
});

$('#runBtn').on('click', async function(e){
    e.preventDefault();
    const data = payload();

    if(!data.modules.length){
        Swal.fire({icon:'warning', title:'Select modules', text:'Pick at least one module.'});
        return;
    }
    if(data.confirm_phrase !== 'FLUSH SALES'){
        Swal.fire({icon:'error', title:'Wrong confirmation', text:'Type exactly: FLUSH SALES'});
        return;
    }

    const summary = await doPreview();
    const html = summary ? `<pre style="text-align:left; white-space:pre-wrap;">${escapeHtml(JSON.stringify(summary.counts, null, 2))}</pre>`
                         : `<div class="text-muted">No preview available.</div>`;

    Swal.fire({
        icon: 'warning',
        title: 'Run Sales Flush?',
        html: `<div class="mb-2">This will permanently delete selected sales data.</div>${html}`,
        showCancelButton: true,
        confirmButtonText: 'Yes, flush now',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33'
    }).then(async (r)=>{
        if(!r.isConfirmed) return;

        try{
            const res = await $.post(runUrl, data);
            Swal.fire({icon:'success', title:'Done', text: res.message || 'Flush completed.'})
                .then(()=> window.location.reload());
        }catch(xhr){
            Swal.fire({icon:'error', title:'Flush failed', text: xhr?.responseJSON?.message || 'Could not flush.'});
        }
    });
});

function escapeHtml(str){
    return String(str)
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}
</script>
@endpush
