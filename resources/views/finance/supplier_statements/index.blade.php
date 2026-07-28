@extends('layouts.master')

@section('title','Supplier Statements')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Supplier Statements</h1>
      <small class="text-muted">Finance / Reports</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary" id="printBtn" disabled>
        <i class="fas fa-print"></i> Print
      </button>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="text-muted small">Supplier <span class="text-danger">*</span></label>
          <select class="form-control" id="supplier_id" style="width:100%"></select>
          <small class="text-muted">Uses suppliers.name only.</small>
        </div>

        <div class="col-md-2">
          <label class="text-muted small">From</label>
          <input type="date" class="form-control" id="date_from">
        </div>

        <div class="col-md-2">
          <label class="text-muted small">To</label>
          <input type="date" class="form-control" id="date_to">
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-primary w-100" id="runBtn">
            <i class="fas fa-play"></i> Run
          </button>
          <button class="btn btn-outline-secondary" id="resetBtn">
            <i class="fas fa-undo"></i>
          </button>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Statement includes: <b>Bills</b> (debit), <b>Payments</b> (credit), <b>Credits</b> (credit), with running balance.
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
        <div>
          <div class="fw-semibold" id="supName">—</div>
          <div class="text-muted small" id="rangeLbl">—</div>
        </div>
        <div class="text-muted small text-end">
          Opening: <b id="openLbl">0.00</b> |
          Closing: <b id="closeLbl">0.00</b>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="stmtTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:120px;">Date</th>
              <th style="width:110px;">Type</th>
              <th style="width:180px;">Ref</th>
              <th>Memo</th>
              <th style="width:140px;" class="text-end">Debit</th>
              <th style="width:140px;" class="text-end">Credit</th>
              <th style="width:140px;" class="text-end">Balance</th>
            </tr>
          </thead>
          <tbody id="stmtBody">
            <tr><td colspan="7" class="text-center text-muted">Run a statement to view transactions.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const suppliersUrl = "{{ url('admin/finance/reports/supplier-statements/lookups/suppliers') }}";
const dataUrl      = "{{ url('admin/finance/reports/supplier-statements/data') }}";
const printUrl     = "{{ url('admin/finance/reports/supplier-statements/print') }}";

function swalErr(msg){ return (window.Swal?.fire) ? Swal.fire({icon:'error',title:'Error',text:msg||'Error'}) : alert(msg||'Error'); }

function s2($el, url, placeholder){
  $el.select2({
    theme:'bootstrap-5', width:'100%', placeholder, allowClear:true,
    ajax:{
      url, dataType:'json', delay:200,
      data: p => ({ q: p.term || '' }),
      processResults: d => d, cache:true
    }
  });
}

function resetUI(){
  $('#supplier_id').val(null).trigger('change');
  $('#date_from,#date_to').val('');
  $('#supName').text('—');
  $('#rangeLbl').text('—');
  $('#openLbl,#closeLbl').text('0.00');
  $('#printBtn').prop('disabled', true);

  $('#stmtBody').html(`<tr><td colspan="7" class="text-center text-muted">Run a statement to view transactions.</td></tr>`);
}

function renderRows(lines){
  if(!lines || !lines.length){
    $('#stmtBody').html(`<tr><td colspan="7" class="text-center text-muted">No transactions found for this range.</td></tr>`);
    return;
  }
  let html = '';
  lines.forEach(r=>{
    html += `
      <tr>
        <td>${escapeHtml(r.date||'')}</td>
        <td>${escapeHtml(r.type||'')}</td>
        <td>${escapeHtml(r.ref||'')}</td>
        <td>${escapeHtml(r.memo||'')}</td>
        <td class="text-end">${escapeHtml(r.debit||'')}</td>
        <td class="text-end">${escapeHtml(r.credit||'')}</td>
        <td class="text-end fw-semibold">${escapeHtml(r.balance||'')}</td>
      </tr>
    `;
  });
  $('#stmtBody').html(html);
}

function escapeHtml(s){
  return String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
  }[m]));
}

$('#runBtn').on('click', function(){
  const supplierId = $('#supplier_id').val();
  if(!supplierId) return swalErr('Supplier is required.');

  const params = {
    supplier_id: supplierId,
    date_from: $('#date_from').val(),
    date_to: $('#date_to').val()
  };

  $('#stmtBody').html(`<tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>`);

  $.get(dataUrl, params)
    .done(res=>{
      if(!res.ok) return swalErr(res.message || 'Failed.');

      $('#supName').text(res.summary?.supplier_name || '—');

      const from = res.summary?.from || '';
      const to   = res.summary?.to || '';
      $('#rangeLbl').text((from || to) ? `Range: ${from || '…'} to ${to || '…'}` : 'Range: All time');

      $('#openLbl').text(res.summary?.opening_balance || '0.00');
      $('#closeLbl').text(res.summary?.closing_balance || '0.00');

      renderRows(res.lines);

      // enable print
      $('#printBtn').prop('disabled', false).off('click').on('click', function(){
        const qs = new URLSearchParams(params).toString();
        window.open(`${printUrl}?${qs}`, '_blank');
      });
    })
    .fail(xhr=>{
      swalErr(xhr?.responseJSON?.message || 'Failed to load statement.');
    });
});

$('#resetBtn').on('click', resetUI);

$(function(){
  s2($('#supplier_id'), suppliersUrl, 'Select supplier...');
  resetUI();
});
</script>
@endpush