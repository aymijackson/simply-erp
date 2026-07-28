@extends('layouts.master')

@section('title','Trial Balance')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h1 class="h3 text-primary mb-0">Trial Balance</h1>
      <small class="text-muted">Finance / Reports</small>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-outline-danger" id="pdfBtn">
        <i class="fas fa-file-pdf"></i> PDF
      </button>
      <button class="btn btn-outline-success" id="excelBtn">
        <i class="fas fa-file-excel"></i> Excel
      </button>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="text-muted small">Status</label>
          <select class="form-control" id="f_status">
            <option value="posted" selected>Posted</option>
            <option value="draft">Draft</option>
            <option value="voided">Voided</option>
            <option value="reversed">Reversed</option>
            <option value="">All</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="text-muted small">From</label>
          <input type="date" class="form-control" id="f_from">
        </div>

        <div class="col-md-3">
          <label class="text-muted small">To</label>
          <input type="date" class="form-control" id="f_to">
        </div>

        <div class="col-md-3">
          <label class="text-muted small">Non-zero only</label>
          <select class="form-control" id="f_nonzero">
            <option value="1" selected>Yes</option>
            <option value="0">No</option>
          </select>
        </div>

        <div class="col-md-8">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="f_q" placeholder="account code or account name...">
        </div>

        <div class="col-md-4 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyBtn">
            <i class="fas fa-filter"></i> Apply
          </button>
          <button class="btn btn-outline-secondary w-100" id="resetBtn">
            <i class="fas fa-undo"></i> Reset
          </button>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Shows totals grouped by account for the selected period and status. Balanced means total debits equal total credits within rounding tolerance.
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="small text-muted">
          Totals:
          Debit <b id="sumDebit">0.00</b>
          |
          Credit <b id="sumCredit">0.00</b>
          |
          Difference <b id="sumDiff">0.00</b>
        </div>
        <div id="balBadge"></div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="tbTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th>Account</th>
              <th style="width:170px;" class="text-end">Debit</th>
              <th style="width:170px;" class="text-end">Credit</th>
              <th style="width:170px;" class="text-end">Net</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <small class="text-muted d-block mt-2">
        Source: <code>finance_journal_entries</code> and <code>finance_journal_entry_lines</code>
      </small>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({
  headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
});

const dtUrl    = "{{ route('admin.finance.reports.trial_balance.datatable') }}";
const pdfUrl   = "{{ route('admin.finance.reports.trial_balance.pdf') }}";
const excelUrl = "{{ route('admin.finance.reports.trial_balance.excel') }}";

let DT = null;

function swalLoading(title = 'Loading...') {
  if (window.Swal?.fire) {
    Swal.fire({
      title,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => Swal.showLoading()
    });
  }
}

function swalClose() {
  if (window.Swal?.close) Swal.close();
}

function swalErr(msg) {
  swalClose();
  if (window.Swal?.fire) {
    return Swal.fire({
      icon: 'error',
      title: 'Error',
      text: msg || 'Something went wrong.'
    });
  }
  alert(msg || 'Something went wrong.');
}

function fmt(v) {
  const n = parseFloat(v || 0);
  return isNaN(n)
    ? '0.00'
    : n.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function filters() {
  return {
    status: $('#f_status').val(),
    date_from: $('#f_from').val(),
    date_to: $('#f_to').val(),
    nonzero: $('#f_nonzero').val(),
    q: $('#f_q').val()
  };
}

function queryString() {
  return new URLSearchParams(filters()).toString();
}

function updateMeta(meta) {
  const debit = parseFloat(meta.sum_debit || 0);
  const credit = parseFloat(meta.sum_credit || 0);
  const diff = parseFloat(meta.diff || 0);

  $('#sumDebit').text(fmt(debit));
  $('#sumCredit').text(fmt(credit));
  $('#sumDiff').text(fmt(diff));

  $('#balBadge').html(
    meta.balanced
      ? '<span class="badge bg-success">BALANCED</span>'
      : '<span class="badge bg-danger">NOT BALANCED</span>'
  );
}

function initDT() {
  DT = $('#tbTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    pageLength: 25,
    searching: true,
    ordering: true,
    ajax: {
      url: dtUrl,
      data: function(d) {
        const f = filters();
        d.status = f.status;
        d.date_from = f.date_from;
        d.date_to = f.date_to;
        d.nonzero = f.nonzero;
        d.q = f.q;
      },
      beforeSend: function() {
        swalLoading('Loading trial balance...');
      },
      complete: function() {
        swalClose();
      },
      error: function(xhr) {
        swalErr(xhr?.responseJSON?.message || 'Failed to load trial balance.');
      }
    },
    columns: [
      {data: 'account', name: 'account'},
      {data: 'debit', name: 'debit', className: 'text-end'},
      {data: 'credit', name: 'credit', className: 'text-end'},
      {data: 'net', name: 'net', className: 'text-end'}
    ],
    order: [[0, 'asc']],
    drawCallback: function(settings) {
      updateMeta(settings.json?.meta || {});
    }
  });
}

function refreshDT() {
  if (DT) DT.ajax.reload(null, false);
}

$('#applyBtn').on('click', function() {
  refreshDT();
});

$('#resetBtn').on('click', function() {
  $('#f_status').val('posted');
  $('#f_from').val('');
  $('#f_to').val('');
  $('#f_nonzero').val('1');
  $('#f_q').val('');
  refreshDT();
});

$('#pdfBtn').on('click', function() {
  window.open(`${pdfUrl}?${queryString()}`, '_blank');
});

$('#excelBtn').on('click', function() {
  window.location.href = `${excelUrl}?${queryString()}`;
});

$(function() {
  initDT();
});
</script>
@endpush