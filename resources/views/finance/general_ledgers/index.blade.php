@extends('layouts.master')

@section('title','General Ledger')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .select2-container { z-index: 2050; }
  .select2-container--open { z-index: 99999; }

  .gl-mode-badge {
    font-size: .85rem;
    padding: .35rem .65rem;
    border-radius: 999px;
    display: inline-block;
  }

  .gl-table-wrap {
    overflow-x: auto;
  }

  .gl-summary-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(140px, 1fr));
    gap: 1rem;
    align-items: start;
  }

  .gl-summary-card {
    border: 1px solid #e9ecef;
    border-radius: .5rem;
    padding: .75rem 1rem;
    background: #fff;
  }

  .gl-account-header {
    border: 1px solid #e9ecef;
    border-radius: .5rem;
    padding: .75rem 1rem;
    background: #f8f9fa;
    margin-bottom: 1rem;
  }

  .gl-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0d6efd;
  }

  .gl-subtable {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
  }

  .gl-subtable th,
  .gl-subtable td {
    border: 1px solid #dee2e6;
    padding: .65rem .75rem;
    vertical-align: top;
  }

  .gl-subtable thead th {
    background: #f8f9fa;
    white-space: nowrap;
  }

  .gl-subtable .text-end {
    text-align: right !important;
  }

  .gl-account-row {
    background: #eef4ff;
    font-weight: 700;
  }

  .gl-empty {
    text-align: center;
    color: #6c757d;
    padding: 2rem 1rem;
  }

  @media (max-width: 991.98px) {
    .gl-summary-grid {
      grid-template-columns: repeat(2, minmax(140px, 1fr));
    }
  }

  @media (max-width: 575.98px) {
    .gl-summary-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">General Ledger</h1>
      <small class="text-muted">Finance / Reports</small>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary" id="clearBtn">
        <i class="fas fa-undo"></i> Clear
      </button>
    
      <button class="btn btn-outline-danger" id="pdfBtn">
        <i class="fas fa-file-pdf"></i> PDF
      </button>
    
      <button class="btn btn-outline-success" id="excelBtn">
        <i class="fas fa-file-excel"></i> Excel
      </button>
    
      <button class="btn btn-primary" id="runBtn">
        <i class="fas fa-play"></i> Run Report
      </button>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="text-muted small">Account</label>
          <select class="form-control" id="account_id" style="width:100%"></select>
          <small class="text-muted">Leave blank to run the full General Ledger. Select an account to run a single Account Ledger.</small>
        </div>

        <div class="col-md-2">
          <label class="text-muted small">From</label>
          <input type="date" class="form-control" id="date_from">
        </div>

        <div class="col-md-2">
          <label class="text-muted small">To</label>
          <input type="date" class="form-control" id="date_to">
        </div>

        <div class="col-md-2">
          <label class="text-muted small">Posted Only</label>
          <select class="form-control" id="posted_only">
            <option value="1" selected>Yes</option>
            <option value="0">No</option>
          </select>
        </div>

        <div class="col-md-1"></div>

        <div class="col-md-6 mt-2">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="q" placeholder="entry no, reference, memo...">
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        If an account is selected, the report runs as an <b>Account Ledger</b>. If no account is selected, it runs as a <b>General Ledger</b> across all accounts.
      </div>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <div class="text-muted small">Report Mode</div>
          <div id="modeLbl">
            <span class="gl-mode-badge bg-light text-dark">Not run</span>
          </div>
        </div>

        <div>
          <div class="text-muted small">Account</div>
          <div class="fw-bold" id="accLabel">—</div>
        </div>
      </div>

      <div class="gl-summary-grid">
        <div class="gl-summary-card">
          <div class="text-muted small">Opening</div>
          <div class="fw-bold" id="openingLbl">0.00</div>
        </div>

        <div class="gl-summary-card">
          <div class="text-muted small">Period Debit</div>
          <div class="fw-bold" id="totDrLbl">0.00</div>
        </div>

        <div class="gl-summary-card">
          <div class="text-muted small">Period Credit</div>
          <div class="fw-bold" id="totCrLbl">0.00</div>
        </div>

        <div class="gl-summary-card">
          <div class="text-muted small">Closing</div>
          <div class="fw-bold" id="closingLbl">0.00</div>
        </div>

        <div class="gl-summary-card">
          <div class="text-muted small">Accounts Returned</div>
          <div class="fw-bold" id="accountsCountLbl">0</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Account Ledger view --}}
  <div class="card shadow-sm mb-3" id="accountLedgerCard">
    <div class="card-body">
      <div class="gl-table-wrap">
        <table class="table table-bordered table-hover align-middle" id="glTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:120px;">Date</th>
              <th style="width:170px;">Entry No</th>
              <th style="width:220px;">Reference</th>
              <th>Memo</th>
              <th style="width:140px;" class="text-end">Debit</th>
              <th style="width:140px;" class="text-end">Credit</th>
              <th style="width:160px;" class="text-end">Running Balance</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <small class="text-muted d-block mt-2">
        Source: Journal Entries (<code>finance_journal_entries</code>) + Lines (<code>finance_journal_entry_lines</code>)
      </small>
    </div>
  </div>

  {{-- Full General Ledger view --}}
  <div class="card shadow-sm" id="generalLedgerCard" style="display:none;">
    <div class="card-body">
      <div id="generalLedgerWrap"></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

window.swal = function(opts){
  if (window.Swal && typeof Swal.fire === 'function') return Swal.fire(opts || {});
  alert((opts && (opts.title || opts.text)) ? (opts.title + ' ' + (opts.text||'')) : 'Done');
  return Promise.resolve({isConfirmed:true});
};
function swalOk(msg){ return swal({icon:'success', title:'Success', text: msg || 'Done.'}); }
function swalErr(msg){ return swal({icon:'error', title:'Error', text: msg || 'Something went wrong.'}); }
function swalLoading(title){
  return swal({
    title: title || 'Loading...',
    allowOutsideClick:false,
    allowEscapeKey:false,
    didOpen: () => { if (window.Swal) Swal.showLoading(); }
  });
}

const dataUrl = "{{ route('admin.finance.reports.general_ledger.data') }}";
const accUrl  = "{{ route('admin.finance.reports.general_ledger.accounts') }}";
const pdfUrl   = "{{ route('admin.finance.reports.general_ledger.pdf') }}";
const excelUrl = "{{ route('admin.finance.reports.general_ledger.excel') }}";

let DT = null;

function currentFilters(){
  return {
    account_id: $('#account_id').val() || '',
    date_from: $('#date_from').val(),
    date_to: $('#date_to').val(),
    posted_only: $('#posted_only').val(),
    q: $('#q').val()
  };
}

$('#pdfBtn').on('click', function(){
  const params = new URLSearchParams(currentFilters()).toString();
  window.open(`${pdfUrl}?${params}`, '_blank');
});

$('#excelBtn').on('click', function(){
  const params = new URLSearchParams(currentFilters()).toString();
  window.location.href = `${excelUrl}?${params}`;
});

function initAccountSelect(){
  $('#account_id').select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select an account...',
    allowClear:true,
    ajax:{
      url: accUrl,
      dataType:'json',
      delay:200,
      data: p => ({ q: p.term || '' }),
      processResults: d => d,
      cache:true
    }
  });
}

function initTable(){
  DT = $('#glTable').DataTable({
    responsive:true,
    pageLength:25,
    ordering:false,
    data:[],
    columns:[
      {data:'entry_date'},
      {data:'entry_no'},
      {data:'reference'},
      {data:'memo'},
      {data:'debit', className:'text-end', render:(v)=>fmt(v)},
      {data:'credit', className:'text-end', render:(v)=>fmt(v)},
      {data:'balance', className:'text-end', render:(v)=>fmt(v)},
    ]
  });
}

function setTableRows(rows){
  DT.clear();
  DT.rows.add(rows || []);
  DT.draw();
}

function fmt(v){
  const n = parseFloat(v || 0);
  return (isNaN(n) ? '0.00' : n.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
}

function resetSummary(){
  $('#accLabel').text('—');
  $('#openingLbl,#totDrLbl,#totCrLbl,#closingLbl').text('0.00');
  $('#accountsCountLbl').text('0');
  $('#modeLbl').html('<span class="gl-mode-badge bg-light text-dark">Not run</span>');
}

function renderMode(mode){
  if (mode === 'account') {
    $('#modeLbl').html('<span class="gl-mode-badge bg-primary text-white">Account Ledger</span>');
  } else if (mode === 'general') {
    $('#modeLbl').html('<span class="gl-mode-badge bg-success text-white">General Ledger</span>');
  } else {
    $('#modeLbl').html('<span class="gl-mode-badge bg-light text-dark">Not run</span>');
  }
}

function renderGeneralLedger(groups){
  let html = '';

  if (!groups || !groups.length) {
    html = `<div class="gl-empty">No ledger entries found for the selected filters.</div>`;
    $('#generalLedgerWrap').html(html);
    return;
  }

  groups.forEach(function(g){
    html += `
      <div class="gl-account-header">
        <div class="gl-section-title">${escapeHtml((g.account?.code ? g.account.code + ' - ' : '') + (g.account?.name || 'Unknown Account'))}</div>
        <div class="row mt-2">
          <div class="col-md-3"><small class="text-muted">Opening</small><div class="fw-bold">${fmt(g.opening_balance || 0)}</div></div>
          <div class="col-md-3"><small class="text-muted">Debit</small><div class="fw-bold">${fmt(g.totals?.debit || 0)}</div></div>
          <div class="col-md-3"><small class="text-muted">Credit</small><div class="fw-bold">${fmt(g.totals?.credit || 0)}</div></div>
          <div class="col-md-3"><small class="text-muted">Closing</small><div class="fw-bold">${fmt(g.totals?.closing_balance || 0)}</div></div>
        </div>
      </div>

      <div class="gl-table-wrap">
        <table class="gl-subtable">
          <thead>
            <tr>
              <th style="width:120px;">Date</th>
              <th style="width:170px;">Entry No</th>
              <th style="width:220px;">Reference</th>
              <th>Memo</th>
              <th style="width:140px;" class="text-end">Debit</th>
              <th style="width:140px;" class="text-end">Credit</th>
              <th style="width:160px;" class="text-end">Running Balance</th>
            </tr>
          </thead>
          <tbody>
    `;

    if (g.rows && g.rows.length) {
      g.rows.forEach(function(r){
        html += `
          <tr>
            <td>${escapeHtml(r.entry_date || '')}</td>
            <td>${escapeHtml(r.entry_no || '')}</td>
            <td>${escapeHtml(r.reference || '')}</td>
            <td>${escapeHtml(r.memo || '')}</td>
            <td class="text-end">${fmt(r.debit || 0)}</td>
            <td class="text-end">${fmt(r.credit || 0)}</td>
            <td class="text-end">${fmt(r.balance || 0)}</td>
          </tr>
        `;
      });
    } else {
      html += `<tr><td colspan="7" class="gl-empty">No rows found for this account.</td></tr>`;
    }

    html += `
          </tbody>
        </table>
      </div>
    `;
  });

  $('#generalLedgerWrap').html(html);
}

function escapeHtml(str){
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

async function runReport(){
  const payload = {
    account_id: $('#account_id').val() || '',
    date_from: $('#date_from').val(),
    date_to: $('#date_to').val(),
    posted_only: $('#posted_only').val(),
    q: $('#q').val()
  };

  swalLoading('Generating Ledger Report...');

  try{
    const res = await $.get(dataUrl, payload);

    if (window.Swal) Swal.close();

    resetSummary();

    if (res.mode === 'account') {
      renderMode('account');
      $('#accountLedgerCard').show();
      $('#generalLedgerCard').hide();

      const acc = res.account || {};
      $('#accLabel').text((acc.code ? (acc.code + ' - ') : '') + (acc.name || '—'));
      $('#openingLbl').text(fmt(res.opening_balance || 0));
      $('#totDrLbl').text(fmt(res.totals?.debit || 0));
      $('#totCrLbl').text(fmt(res.totals?.credit || 0));
      $('#closingLbl').text(fmt(res.totals?.closing_balance || 0));
      $('#accountsCountLbl').text('1');

      setTableRows(res.rows || []);
    } else {
      renderMode('general');
      $('#accountLedgerCard').hide();
      $('#generalLedgerCard').show();

      $('#accLabel').text('All Accounts');
      $('#openingLbl').text(fmt(res.summary?.opening_balance || 0));
      $('#totDrLbl').text(fmt(res.summary?.debit || 0));
      $('#totCrLbl').text(fmt(res.summary?.credit || 0));
      $('#closingLbl').text(fmt(res.summary?.closing_balance || 0));
      $('#accountsCountLbl').text(res.groups_count || 0);

      setTableRows([]);
      renderGeneralLedger(res.groups || []);
    }

    swalOk('Report ready.');
  }catch(e){
    if (window.Swal) Swal.close();
    swalErr(e?.responseJSON?.message || 'Failed to load report.');
  }
}

function clearAll(){
  $('#account_id').val(null).trigger('change');
  $('#date_from').val('');
  $('#date_to').val('');
  $('#posted_only').val('1');
  $('#q').val('');

  resetSummary();
  setTableRows([]);
  $('#generalLedgerWrap').html('');
  $('#accountLedgerCard').show();
  $('#generalLedgerCard').hide();
}

$('#runBtn').on('click', runReport);
$('#clearBtn').on('click', clearAll);

$(function(){
  initAccountSelect();
  initTable();
});
</script>
@endpush