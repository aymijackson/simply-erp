@extends('layouts.master')

@section('title','Cash Flow')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .cf-card h6{ margin-bottom:.25rem; }

  .cf-amt{
    font-variant-numeric: tabular-nums;
    font-weight: 600;
  }

  .cf-neg{
    color:#dc3545;
  }

  .cf-pos{
    color:#198754;
  }

  .cf-loading{
    display:none;
    position:fixed;
    inset:0;
    z-index:2000;
    background:rgba(255,255,255,.65);
    align-items:center;
    justify-content:center;
  }
</style>

<div class="cf-loading" id="cfLoading">
  <div class="text-center">
    <div class="spinner-border" role="status"></div>
    <div class="mt-2 text-muted small">Generating cash flow...</div>
  </div>
</div>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Cash Flow Statement</h1>
      <small class="text-muted">Finance / Reports (Indirect Method)</small>
    </div>

    @can('finance.cashflow.manage')
      <a class="btn btn-outline-secondary" href="{{ route('admin.finance.reports.cash_flow.mappings.index') }}">
        <i class="fas fa-sliders-h"></i> Mappings
      </a>
    @endcan
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="text-muted small">From <span class="text-danger">*</span></label>
          <input type="date" class="form-control" id="date_from">
        </div>
        <div class="col-md-3">
          <label class="text-muted small">To <span class="text-danger">*</span></label>
          <input type="date" class="form-control" id="date_to">
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary w-100" id="runBtn">
            <i class="fas fa-play"></i> Run Report
          </button>
        </div>
        <div class="col-md-3">
          <button class="btn btn-outline-secondary w-100" id="resetBtn">
            <i class="fas fa-undo"></i> Reset
          </button>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Uses <b>posted</b> Journal Entries. For best Investing/Financing accuracy, configure Cash Flow Mappings.
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card shadow-sm cf-card">
        <div class="card-body">
          <h6 class="text-muted">Net Profit</h6>
          <div class="h4 cf-amt" id="profitLbl">0.00</div>

          <hr>

          <h6 class="text-muted">Non-cash Adjustments</h6>
          <div class="h5 cf-amt" id="nonCashLbl">0.00</div>

          <hr>

          <h6 class="text-muted">Working Capital Changes</h6>
          <div class="h5 cf-amt" id="wcLbl">0.00</div>

          <hr>

          <h6 class="text-muted">Net Cash From Operating</h6>
          <div class="h4 cf-amt" id="opsLbl">0.00</div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <h6 class="text-muted mb-2">Investing Activities</h6>
              <ul class="list-group" id="investingList"></ul>
              <div class="d-flex justify-content-between mt-2">
                <span class="text-muted small">Net Investing</span>
                <b class="cf-amt" id="invLbl">0.00</b>
              </div>
            </div>

            <div class="col-md-6">
              <h6 class="text-muted mb-2">Financing Activities</h6>
              <ul class="list-group" id="financingList"></ul>
              <div class="d-flex justify-content-between mt-2">
                <span class="text-muted small">Net Financing</span>
                <b class="cf-amt" id="finLbl">0.00</b>
              </div>
            </div>

            <div class="col-12">
              <hr>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Net Change in Cash</span>
                <b class="cf-amt" id="netChangeLbl">0.00</b>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted small">Cash Accounts Movement (sanity check)</span>
                <span class="text-muted small cf-amt" id="cashMoveLbl">0.00</span>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted small">Difference</span>
                <span class="text-muted small cf-amt" id="diffLbl">0.00</span>
              </div>

              <div class="alert alert-warning mt-3 d-none" id="warnBox"></div>
              <div class="text-muted small mt-2" id="notesBox"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mt-3">
        <div class="card-body">
          <h6 class="text-muted mb-2">Working Capital Detail (best-effort)</h6>
          <div class="row g-2">
            <div class="col-md-4 d-flex justify-content-between">
              <span>AR change</span>
              <b class="cf-amt" id="arLbl">0.00</b>
            </div>
            <div class="col-md-4 d-flex justify-content-between">
              <span>Inventory change</span>
              <b class="cf-amt" id="invWCLabel">0.00</b>
            </div>
            <div class="col-md-4 d-flex justify-content-between">
              <span>AP change</span>
              <b class="cf-amt" id="apLbl">0.00</b>
            </div>
          </div>
          <div class="text-muted small mt-2" id="wcNote"></div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
});

const runUrl = "{{ route('admin.finance.reports.cash_flow.run') }}";

function showLoading(on){
  $('#cfLoading').toggle(!!on);
}

function safeText(v, fallback = '0.00'){
  return (v === null || v === undefined || v === '') ? fallback : v;
}

function applyAmount(el, value){
  const n = parseFloat(value || 0);

  let formatted = n.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  if (n < 0) {
    formatted = '(' + Math.abs(n).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ')';

    $(el).removeClass('cf-pos').addClass('cf-neg');
  } else if (n > 0) {
    $(el).removeClass('cf-neg').addClass('cf-pos');
  } else {
    $(el).removeClass('cf-neg cf-pos');
  }

  $(el).text(formatted);
}

function clearAmount(el){
  $(el).removeClass('cf-neg cf-pos').text('0.00');
}

function listItems($ul, items){
  $ul.empty();

  if(!items || !items.length){
    $ul.append(`<li class="list-group-item text-muted small">No items (configure mappings for classification)</li>`);
    return;
  }

  items.forEach(it => {
    const amountRaw = parseFloat(it.amount ?? 0);
    let amountText = safeText(it.amount_formatted, '0.00');
    let amountClass = '';

    if (amountRaw < 0) amountClass = 'cf-neg';
    else if (amountRaw > 0) amountClass = 'cf-pos';

    $ul.append(`
      <li class="list-group-item d-flex justify-content-between">
        <span>${it.label || ''}</span>
        <span class="cf-amt ${amountClass}">${amountText}</span>
      </li>
    `);
  });
}

function resetReport(){
  $('#date_from,#date_to').val('');

  [
    '#profitLbl',
    '#nonCashLbl',
    '#wcLbl',
    '#opsLbl',
    '#invLbl',
    '#finLbl',
    '#netChangeLbl',
    '#cashMoveLbl',
    '#diffLbl',
    '#arLbl',
    '#invWCLabel',
    '#apLbl'
  ].forEach(clearAmount);

  listItems($('#investingList'), []);
  listItems($('#financingList'), []);

  $('#warnBox').addClass('d-none').text('');
  $('#notesBox').text('');
  $('#wcNote').text('');
}

$('#resetBtn').on('click', function(){
  resetReport();
});

$('#runBtn').on('click', function(){
  const date_from = $('#date_from').val();
  const date_to = $('#date_to').val();

  if(!date_from || !date_to){
    return swal('Missing Dates', 'Please select From and To dates.', 'warning');
  }

  showLoading(true);

  $.get(runUrl, { date_from, date_to })
    .done(res => {
      if(!res.ok){
        swal('Error', res.message || 'Failed to run.', 'error');
        return;
      }

      const s  = res.summary || {};
      const d  = res.details || {};
      const df = res.details_formatted || {};
      const wc = d.working_capital || {};

      applyAmount('#profitLbl', s.profit);
      applyAmount('#nonCashLbl', s.non_cash_total);
      applyAmount('#wcLbl', s.working_capital_total);
      applyAmount('#opsLbl', s.net_cash_from_ops);

      applyAmount('#invLbl', s.net_cash_from_investing);
      applyAmount('#finLbl', s.net_cash_from_financing);

      applyAmount('#netChangeLbl', s.net_change_in_cash);
      applyAmount('#cashMoveLbl', s.cash_accounts_movement);
      applyAmount('#diffLbl', s.difference);

      listItems($('#investingList'), df.investing || d.investing || []);
      listItems($('#financingList'), df.financing || d.financing || []);

      applyAmount('#arLbl', wc.ar_change);
      applyAmount('#invWCLabel', wc.inventory_change);
      applyAmount('#apLbl', wc.ap_change);

      $('#wcNote').text(wc.notes || '');

      const diff = parseFloat(s.difference || 0);
      if(Math.abs(diff) > 0.01){
        $('#warnBox')
          .removeClass('d-none')
          .text('Difference detected between indirect totals and cash account movement. Add Cash Flow Mappings and ensure cash/bank accounts are correctly linked to GL.');
      } else {
        $('#warnBox').addClass('d-none').text('');
      }

      const notes = [];
      if ((d.operating || []).length) notes.push('Operating items are derived from mappings.');
      if (d.notes?.length) notes.push(d.notes.join(' '));
      $('#notesBox').text(notes.join(' '));
    })
    .fail(xhr => {
      swal('Error', xhr?.responseJSON?.message || 'Failed to run report.', 'error');
    })
    .always(() => showLoading(false));
});
</script>
@endpush