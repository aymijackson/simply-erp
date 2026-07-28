@extends('layouts.master')

@section('title','AR Ageing')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">AR Ageing Report</h1>
      <small class="text-muted">Finance / Reports</small>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="text-muted small">As of</label>
          <input type="date" class="form-control" id="as_of" value="{{ date('Y-m-d') }}">
        </div>

        <div class="col-md-5">
          <label class="text-muted small">Customer (optional)</label>
          <select class="form-control" id="customer_id" style="width:100%"></select>
        </div>

        <div class="col-md-2">
          <label class="text-muted small">Currency (optional)</label>
          <input type="text" class="form-control" id="currency_code" placeholder="e.g. GBP">
        </div>

        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyBtn">
            <i class="fas fa-filter"></i> Apply
          </button>
          <button class="btn btn-outline-secondary w-100" id="resetBtn">
            <i class="fas fa-undo"></i>
          </button>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Shows open invoice balances grouped into ageing buckets based on <b>Due Date</b>.
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="ageTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th>Customer</th>
              <th style="width:80px;">Curr</th>
              <th class="text-end">Not Due</th>
              <th class="text-end">0-30</th>
              <th class="text-end">31-60</th>
              <th class="text-end">61-90</th>
              <th class="text-end">91+</th>
              <th class="text-end">Total</th>
              <th style="width:140px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      {{-- Drilldown Modal --}}
      <div class="modal fade" id="drillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="drillTitle">Customer Invoices</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th>Invoice</th>
                      <th style="width:120px;">Inv Date</th>
                      <th style="width:120px;">Due Date</th>
                      <th style="width:120px;" class="text-end">Days</th>
                      <th style="width:90px;">Curr</th>
                      <th style="width:140px;" class="text-end">Total</th>
                      <th style="width:140px;" class="text-end">Paid</th>
                      <th style="width:140px;" class="text-end">Balance</th>
                    </tr>
                  </thead>
                  <tbody id="drillTbody"></tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
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

const dtUrl = "{{ route('admin.finance.reports.ar_ageing.datatable') }}";
const custUrl = "{{ route('admin.finance.reports.ar_ageing.lookups.customers') }}";
const drillBase = "{{ url('admin/finance/reports/ar-ageing/customer') }}";

function swalLoading(title){
  if(!window.Swal) return;
  Swal.fire({
    title: title || 'Loading...',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });
}
function swalClose(){ if(window.Swal) Swal.close(); }
function swalErr(msg){
  if(window.Swal) return Swal.fire({icon:'error', title:'Error', text: msg || 'Something went wrong'});
  alert(msg || 'Error');
}

function initCustomerSelect(){
  $('#customer_id').select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select customer...',
    allowClear:true,
    ajax:{
      url: custUrl,
      dataType:'json',
      delay:200,
      data: p => ({ q: p.term || '' }),
      processResults: d => d,
      cache:true
    }
  });
}

let DT=null;
function initDT(){
  DT = $('#ageTable').DataTable({
    processing:true, serverSide:true, responsive:true, pageLength:10,
    ajax:{
      url: dtUrl,
      data: (d) => {
        d.as_of = $('#as_of').val();
        d.customer_id = $('#customer_id').val();
        d.currency_code = ($('#currency_code').val()||'').trim().toUpperCase();
      }
    },
    columns:[
      {data:'customer'},
      {data:'currency'},
      {data:'not_due', className:'text-end'},
      {data:'b0_30', className:'text-end'},
      {data:'b31_60', className:'text-end'},
      {data:'b61_90', className:'text-end'},
      {data:'b91_plus', className:'text-end'},
      {data:'total', className:'text-end'},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[7,'desc']]
  });
}

function refreshDT(){ DT.ajax.reload(null,false); }

$('#applyBtn').on('click', () => {
  swalLoading('Refreshing report...');
  refreshDT();
  setTimeout(swalClose, 400);
});

$('#resetBtn').on('click', () => {
  $('#as_of').val("{{ date('Y-m-d') }}");
  $('#customer_id').val(null).trigger('change');
  $('#currency_code').val('');
  swalLoading('Resetting...');
  refreshDT();
  setTimeout(swalClose, 400);
});

// Drilldown
$(document).on('click', '.btn-drill', function(){
  const j = $(this).data('json');
  const asOf = $('#as_of').val();
  const currency = ($('#currency_code').val()||'').trim().toUpperCase();

  $('#drillTitle').text(`Invoices — ${j.customer_name} (${j.currency_code || ''})`);
  $('#drillTbody').html('');

  swalLoading('Loading invoices...');
  $.get(`${drillBase}/${j.customer_id}/invoices`, {as_of: asOf, currency_code: currency || j.currency_code})
    .done(res => {
      const rows = (res.invoices || []).map(r => {
        const days = (r.days_past_due === null || typeof r.days_past_due === 'undefined') ? '' : r.days_past_due;
        return `
          <tr>
            <td>${r.invoice_no}</td>
            <td>${r.invoice_date || ''}</td>
            <td>${r.due_date || ''}</td>
            <td class="text-end">${days}</td>
            <td>${r.currency_code || ''}</td>
            <td class="text-end">${Number(r.grand_total||0).toFixed(2)}</td>
            <td class="text-end">${Number(r.paid||0).toFixed(2)}</td>
            <td class="text-end">${Number(r.balance_due||0).toFixed(2)}</td>
          </tr>
        `;
      }).join('');

      $('#drillTbody').html(rows || `<tr><td colspan="8" class="text-center text-muted">No open invoices</td></tr>`);
      $('#drillModal').modal('show');
    })
    .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Failed to load invoices'))
    .always(() => swalClose());
});

$(function(){
  initCustomerSelect();
  initDT();
});
</script>
@endpush