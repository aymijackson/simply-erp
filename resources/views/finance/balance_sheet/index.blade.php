@extends('layouts.master')
@section('title','Balance Sheet')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Balance Sheet</h1>
      <small class="text-muted">From–To report</small>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">

        <div class="col-md-3">
          <label class="text-muted small">From Date</label>
          <input type="date" class="form-control" id="date_from" value="{{ date('Y-01-01') }}">
        </div>

        <div class="col-md-3">
          <label class="text-muted small">To Date</label>
          <input type="date" class="form-control" id="date_to" value="{{ date('Y-m-d') }}">
        </div>

        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="runBtn">
            <i class="fas fa-play"></i> Run
          </button>
          <button class="btn btn-outline-secondary w-100" id="resetBtn" title="Reset">
            <i class="fas fa-undo"></i>
          </button>
        </div>

        <div class="col-md-4 text-end text-muted small">
          Assets: <b id="aTot">0.00</b> |
          Liabilities: <b id="lTot">0.00</b> |
          Equity: <b id="eTot">0.00</b> |
          Check: <b id="chk">0.00</b>
          <span id="balanceBadge" class="badge bg-secondary ms-2">...</span>
        </div>

      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-light"><b>Assets</b></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-bordered" id="assetsTbl">
              <thead>
                <tr>
                  <th>Account</th>
                  <th class="text-end" style="width:160px;">Amount</th>
                </tr>
              </thead>
              <tbody></tbody>
              <tfoot>
                <tr>
                  <th>Total Assets</th>
                  <th class="text-end" id="assetsTot">0.00</th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-light"><b>Liabilities</b></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-bordered" id="liabTbl">
              <thead>
                <tr>
                  <th>Account</th>
                  <th class="text-end" style="width:160px;">Amount</th>
                </tr>
              </thead>
              <tbody></tbody>
              <tfoot>
                <tr>
                  <th>Total Liabilities</th>
                  <th class="text-end" id="liabTot">0.00</th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-light"><b>Equity</b></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-bordered" id="eqTbl">
              <thead>
                <tr>
                  <th>Account</th>
                  <th class="text-end" style="width:160px;">Amount</th>
                </tr>
              </thead>
              <tbody></tbody>
              <tfoot>
                <tr>
                  <th>Total Equity</th>
                  <th class="text-end" id="eqTot">0.00</th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const url = "{{ route('admin.finance.reports.balance_sheets.data') }}";

function swalSafe(optsOrTitle, text, icon){
  if (window.Swal && typeof window.Swal.fire === 'function') {
    if (typeof optsOrTitle === 'string') return Swal.fire({title:optsOrTitle, text:text||'', icon:icon||'info'});
    return Swal.fire(optsOrTitle || {});
  }
  if (typeof window.swal === 'function') {
    if (typeof optsOrTitle === 'string') return swal(optsOrTitle, text||'', icon||'info');
    return swal(optsOrTitle || {});
  }
}
function swalLoading(title='Loading...'){
  if (window.Swal && typeof window.Swal.fire === 'function') {
    return Swal.fire({
      title,
      allowOutsideClick:false,
      allowEscapeKey:false,
      didOpen:()=>Swal.showLoading()
    });
  }
  if (typeof window.swal === 'function') {
    return swal({title, text:'Please wait', buttons:false, closeOnClickOutside:false, closeOnEsc:false});
  }
}
function swalClose(){
  if (window.Swal && typeof window.Swal.close === 'function') return Swal.close();
}

function render($tbl, rows){
  const $b = $tbl.find('tbody');
  $b.html('');

  if (!(rows || []).length) {
    $b.html(`<tr><td colspan="2" class="text-center text-muted">No data</td></tr>`);
    return;
  }

  (rows || []).forEach(r => {
    $b.append(`<tr>
      <td>${(r.code || '')} - ${(r.name || '')}</td>
      <td class="text-end">${Number(r.amount || 0).toFixed(2)}</td>
    </tr>`);
  });
}

async function run(){
  swalLoading('Building balance sheet...');
  try{
    const res = await $.get(url, {
      date_from: $('#date_from').val(),
      date_to: $('#date_to').val()
    });

    swalClose();

    render($('#assetsTbl'), res?.sections?.assets);
    render($('#liabTbl'), res?.sections?.liabilities);
    render($('#eqTbl'), res?.sections?.equity);

    const a = Number(res?.meta?.assets || 0);
    const l = Number(res?.meta?.liabilities || 0);
    const e = Number(res?.meta?.equity || 0);
    const c = Number(res?.meta?.check || 0);
    const balanced = !!res?.meta?.is_balanced;

    $('#assetsTot').text(a.toFixed(2));
    $('#liabTot').text(l.toFixed(2));
    $('#eqTot').text(e.toFixed(2));

    $('#aTot').text(a.toFixed(2));
    $('#lTot').text(l.toFixed(2));
    $('#eTot').text(e.toFixed(2));
    $('#chk').text(c.toFixed(2));

    $('#balanceBadge')
      .removeClass('bg-success bg-danger bg-secondary')
      .addClass(balanced ? 'bg-success' : 'bg-danger')
      .text(balanced ? 'BALANCED' : 'OUT OF BALANCE');

  }catch(e){
    swalClose();
    swalSafe({icon:'error', title:'Error', text:'Failed to load Balance Sheet.'});
  }
}

$('#runBtn').on('click', run);

$('#resetBtn').on('click', ()=>{
  $('#date_from').val("{{ date('Y-01-01') }}");
  $('#date_to').val("{{ date('Y-m-d') }}");
  run();
});

$(function(){ run(); });
</script>
@endpush