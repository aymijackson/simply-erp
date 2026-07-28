@extends('layouts.master')

@section('title','Customer Statements')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Customer Statements</h1>
      <small class="text-muted">Sales / Reports (AR)</small>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="text-muted small">Customer <span class="text-danger">*</span></label>
          <select class="form-control" id="customer_id" style="width:100%">
            @if($initialCustomer)
              <option value="{{ $initialCustomer->id }}" selected>{{ $initialCustomer->text }}</option>
            @endif
          </select>
        </div>

        <div class="col-md-2">
          <label class="text-muted small">From <span class="text-danger">*</span></label>
          <input type="date" class="form-control" id="date_from">
        </div>

        <div class="col-md-2">
          <label class="text-muted small">To <span class="text-danger">*</span></label>
          <input type="date" class="form-control" id="date_to">
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-primary w-100" id="runBtn">
            <i class="fas fa-play"></i> Run
          </button>
          <button class="btn btn-outline-secondary" id="resetBtn" title="Reset">
            <i class="fas fa-undo"></i>
          </button>
        </div>
      </div>

      <div class="row g-2 mt-3">
        <div class="col-md-3">
          <div class="border rounded p-3">
            <div class="text-muted small">Opening Balance</div>
            <div class="h5 mb-0" id="openingLbl">0.00</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="border rounded p-3">
            <div class="text-muted small">Charges (Invoices)</div>
            <div class="h5 mb-0" id="chargesLbl">0.00</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="border rounded p-3">
            <div class="text-muted small">Credits (Payments/CNs)</div>
            <div class="h5 mb-0" id="creditsLbl">0.00</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="border rounded p-3">
            <div class="text-muted small">Closing Balance</div>
            <div class="h5 mb-0" id="closingLbl">0.00</div>
          </div>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Statement shows invoices (debit) and receipts/credit notes (credit) with running balance.
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="stmtTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:110px;">Date</th>
              <th style="width:140px;">Type</th>
              <th style="width:200px;">Reference</th>
              <th>Description</th>
              <th style="width:140px;" class="text-end">Debit</th>
              <th style="width:140px;" class="text-end">Credit</th>
              <th style="width:160px;" class="text-end">Balance</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const customersUrl = "{{ route('admin.finance.customer_statements.lookup.customers') }}";
const summaryUrl   = "{{ route('admin.finance.customer_statements.summary') }}";
const rowsUrl      = "{{ route('admin.finance.customer_statements.rows') }}";

function fmt(n){
  n = parseFloat(n||0) || 0;
  return n.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2});
}

function swalLoading(title){
  return Swal.fire({
    title: title || 'Loading...',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => Swal.showLoading()
  });
}

function swalError(msg){
  return Swal.fire({icon:'error', title:'Error', text: msg || 'Something went wrong.'});
}

function initCustomerSelect(){
  $('#customer_id').select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select customer...',
    allowClear:true,
    ajax:{
      url: customersUrl,
      dataType:'json',
      delay: 200,
      data: p => ({ q: p.term || '' }),
      processResults: d => d,
      cache:true
    }
  });
}

let DT = null;
function initDT(){
  DT = $('#stmtTable').DataTable({
    processing:true,
    paging:false,
    searching:false,
    info:false,
    ordering:false,
    responsive:true,
    data: [],
    columns:[
      {data:'date'},
      {data:'type'},
      {data:'ref'},
      {data:'description'},
      {data:'debit', className:'text-end', render:(d)=>fmt(d)},
      {data:'credit', className:'text-end', render:(d)=>fmt(d)},
      {data:'balance', className:'text-end', render:(d)=>fmt(d)},
    ]
  });
}

function resetUI(){
  $('#openingLbl,#chargesLbl,#creditsLbl,#closingLbl').text('0.00');
  if(DT) DT.clear().draw();
}

async function runStatement(){
  const customer_id = $('#customer_id').val();
  const date_from = $('#date_from').val();
  const date_to = $('#date_to').val();

  if(!customer_id) return swalError('Customer is required.');
  if(!date_from || !date_to) return swalError('Date range is required.');

  swalLoading('Building statement...');

  try{
    const sum = await $.get(summaryUrl, {customer_id, date_from, date_to});
    $('#openingLbl').text(fmt(sum.opening_balance));
    $('#chargesLbl').text(fmt(sum.charges));
    $('#creditsLbl').text(fmt(sum.credits));
    $('#closingLbl').text(fmt(sum.closing_balance));

    const res = await $.get(rowsUrl, {customer_id, date_from, date_to});

    DT.clear();
    DT.rows.add(res.rows || []);
    DT.draw();

    Swal.close();
  }catch(e){
    Swal.close();
    const msg = e?.responseJSON?.message || 'Failed to load statement.';
    swalError(msg);
  }
}

$('#runBtn').on('click', runStatement);

$('#resetBtn').on('click', ()=>{
  $('#customer_id').val(null).trigger('change');
  $('#date_from,#date_to').val('');
  resetUI();
});

$(function(){
  initCustomerSelect();
  initDT();

  @if($initialCustomer)
    $('#date_from').val('{{ now()->startOfYear()->toDateString() }}');
    $('#date_to').val('{{ now()->toDateString() }}');
    runStatement();
  @endif
});
</script>
@endpush