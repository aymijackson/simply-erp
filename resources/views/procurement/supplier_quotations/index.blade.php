@extends('layouts.master')
@section('title','Supplier Quotations')

@push('styles')
<style>
  .supplier-quotation-modal-body {
    overflow-x: hidden;
  }

  .quotation-lines-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: .25rem;
  }

  .quotation-lines-table {
    min-width: 1900px;
    table-layout: fixed;
  }

  .quotation-lines-table th,
  .quotation-lines-table td {
    vertical-align: middle;
    white-space: normal;
  }

  .quotation-lines-table .form-control,
  .quotation-lines-table .select2-container {
    min-width: 100%;
  }

  .quotation-lines-table .col-product      { min-width: 220px; width: 220px; }
  .quotation-lines-table .col-description  { min-width: 180px; width: 180px; }
  .quotation-lines-table .col-unit         { min-width: 120px; width: 120px; }
  .quotation-lines-table .col-qty          { min-width: 90px;  width: 90px; }
  .quotation-lines-table .col-unitprice    { min-width: 130px; width: 130px; }
  .quotation-lines-table .col-discpct      { min-width: 110px; width: 110px; }
  .quotation-lines-table .col-discamt      { min-width: 120px; width: 120px; }
  .quotation-lines-table .col-taxcode      { min-width: 220px; width: 220px; }
  .quotation-lines-table .col-taxrate      { min-width: 90px;  width: 90px; }
  .quotation-lines-table .col-taxamt       { min-width: 110px; width: 110px; }
  .quotation-lines-table .col-total        { min-width: 120px; width: 120px; }
  .quotation-lines-table .col-leadtime     { min-width: 100px; width: 100px; }
  .quotation-lines-table .col-remarks      { min-width: 180px; width: 180px; }
  .quotation-lines-table .col-action       { min-width: 60px;  width: 60px; }

  .quotation-lines-table .text-end {
    text-align: right !important;
  }

  .quotation-lines-table .select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
  }

  .sq-readonly {
    background: #f8f9fa;
  }

  @media (max-width: 991.98px) {
    .modal-dialog.modal-xl {
      margin: .5rem;
    }
  }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Supplier Quotations</h1>
      <small class="text-muted">Procurement / Supplier Quotations</small>
    </div>

    @can('procurement.supplier_quotations.create')
    <button class="btn btn-primary" id="createBtn">
      <i class="fas fa-plus"></i> New Supplier Quotation
    </button>
    @endcan
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="text-muted small">Status</label>
          <select class="form-control" id="f_status">
            <option value="">All</option>
            <option value="draft">Draft</option>
            <option value="submitted">Submitted</option>
            <option value="reviewed">Reviewed</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="text-muted small">From</label>
          <input type="date" class="form-control" id="f_from">
        </div>

        <div class="col-md-2">
          <label class="text-muted small">To</label>
          <input type="date" class="form-control" id="f_to">
        </div>

        <div class="col-md-5">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="f_q" placeholder="quote no, rfq no, supplier, reference...">
        </div>

        <div class="col-md-1 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyBtn">
            <i class="fas fa-filter"></i>
          </button>
        </div>
      </div>

      <div class="d-flex gap-2 mt-2">
        <button class="btn btn-outline-secondary" id="resetBtn">
          <i class="fas fa-undo"></i> Reset
        </button>

        <div class="alert alert-info mb-0 flex-grow-1">
          <i class="fas fa-info-circle me-1"></i>
          Draft quotations can be edited or deleted. Submitted quotations can be reviewed. Reviewed quotations can be accepted or rejected.
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="quotationTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:170px;">Quotation No</th>
              <th style="width:120px;">Date</th>
              <th style="width:120px;">Valid Until</th>
              <th style="width:160px;">RFQ No</th>
              <th>Supplier</th>
              <th style="width:130px;">Status</th>
              <th style="width:140px;" class="text-end">Total</th>
              <th style="width:260px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('procurement.supplier_quotations.partials.modal')
@include('procurement.supplier_quotations.partials.show_modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({
  headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}
});

const dtUrl           = "{{ route('admin.procurement.supplier_quotations.datatable') }}";
const baseUrl         = "{{ url('admin/procurement/supplier-quotations') }}";
const rfqsUrl         = "{{ route('admin.procurement.supplier_quotations.lookups.rfqs') }}";
const rfqSuppliersUrl = "{{ route('admin.procurement.supplier_quotations.lookups.rfq_suppliers') }}";
const productsUrl     = "{{ route('admin.procurement.supplier_quotations.lookups.products') }}";
const unitsUrl        = "{{ route('admin.procurement.supplier_quotations.lookups.units') }}";
const taxCodesUrl     = "{{ route('admin.procurement.supplier_quotations.lookups.tax_codes') }}";

let DT = null;

function toastOk(msg){
  return Swal.fire({
    icon:'success',
    title:'Success',
    text: msg || 'Done',
    timer: 1300,
    showConfirmButton:false
  });
}

function toastErr(msg){
  return Swal.fire({
    icon:'error',
    title:'Error',
    text: msg || 'Something went wrong'
  });
}

function confirmBox(opts){
  return Swal.fire(Object.assign({
    icon:'warning',
    showCancelButton:true,
    confirmButtonText:'Yes'
  }, opts || {}));
}

function initDT(){
  DT = $('#quotationTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url: dtUrl,
      data: function(d){
        d.status    = $('#f_status').val();
        d.date_from = $('#f_from').val();
        d.date_to   = $('#f_to').val();
        d.q         = $('#f_q').val();
      }
    },
    columns:[
      {data:'id'},
      {data:'quotation_no'},
      {data:'quotation_date'},
      {data:'valid_until'},
      {data:'rfq_no'},
      {data:'supplier'},
      {data:'status', orderable:false, searchable:false},
      {data:'total_amount', className:'text-end'},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[0,'desc']]
  });
}

function refreshDT(){
  if(DT) DT.ajax.reload(null,false);
}

$('#applyBtn').on('click', refreshDT);

$('#resetBtn').on('click', function(){
  $('#f_status').val('');
  $('#f_from').val('');
  $('#f_to').val('');
  $('#f_q').val('');
  refreshDT();
});

function initS2($el, url, placeholder, extraDataFn = null){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder,
    allowClear:true,
    dropdownParent: $('#quotationModal'),
    ajax:{
      url,
      dataType:'json',
      delay:200,
      data:function(params){
        let payload = { q: params.term || '' };
        if(typeof extraDataFn === 'function'){
          payload = Object.assign(payload, extraDataFn());
        }
        return payload;
      },
      processResults:function(d){ return d; },
      cache:true
    }
  });
}

function setS2Value($el, id, text, extraData = {}){
  if(!id){
    $el.val(null).trigger('change');
    return;
  }

  const opt = new Option(text || id, id, true, true);
  $el.append(opt).trigger('change');

  $el.trigger({
    type:'select2:select',
    params:{ data:Object.assign({id:id, text:text || id}, extraData) }
  });
}

function resetModal(){
  $('#quotationForm')[0].reset();

  $('#quotation_id').val('');
  $('#quotation_status_badge').html('');

  $('#rfq_id').empty().trigger('change');
  $('#rfq_supplier_id').empty().trigger('change');

  $('#supplier_id').val('');
  $('#supplier_label').val('');
  $('#contact_label').val('');

  $('#linesTbody').html('');
  addLine();
  recalcTotals();
}

function addLine(line = null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td class="col-product">
        <input type="hidden" class="rfq-line-id" value="${line?.rfq_line_id ?? ''}">
        <input type="hidden" class="product-id" value="${line?.product_id ?? ''}">
        <input type="text" class="form-control sq-readonly product-label" value="${line?.product_label ?? ''}" readonly>
      </td>

      <td class="col-description">
        <input type="text" class="form-control description" value="${line?.description ?? ''}" placeholder="Description">
      </td>

      <td class="col-unit">
        <input type="hidden" class="unit-id" value="${line?.unit_id ?? ''}">
        <input type="text" class="form-control sq-readonly unit-label" value="${line?.unit_label ?? ''}" readonly>
      </td>

      <td class="col-qty">
        <input type="number" step="0.01" class="form-control text-end ln-qty" value="${line?.qty ?? 1}">
      </td>

      <td class="col-unitprice">
        <input type="number" step="0.01" class="form-control text-end ln-unitprice" value="${line?.unit_price ?? 0}">
      </td>

      <td class="col-discpct">
        <input type="number" step="0.01" class="form-control text-end ln-discpct" value="${line?.discount_percent ?? ''}" placeholder="%">
      </td>

      <td class="col-discamt">
        <input type="number" step="0.01" class="form-control text-end ln-discamt" value="${line?.discount_amount ?? 0}" readonly>
      </td>

      <td class="col-taxcode">
        <select class="form-control taxcode-select"></select>
      </td>

      <td class="col-taxrate">
        <input type="number" step="0.01" class="form-control text-end ln-taxrate" value="${line?.tax_rate ?? ''}" readonly>
      </td>

      <td class="col-taxamt">
        <input type="number" step="0.01" class="form-control text-end ln-taxamt" value="${line?.tax_amount ?? 0}" readonly>
      </td>

      <td class="col-total">
        <input type="number" step="0.01" class="form-control text-end ln-total" value="${line?.line_total ?? 0}" readonly>
      </td>

      <td class="col-leadtime">
        <input type="number" step="1" class="form-control text-end ln-leadtime" value="${line?.lead_time_days ?? ''}" placeholder="Days">
      </td>

      <td class="col-remarks">
        <input type="text" class="form-control ln-remarks" value="${line?.remarks ?? ''}" placeholder="Remarks">
      </td>

      <td class="col-action text-center">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-line">
          <i class="fas fa-times"></i>
        </button>
      </td>
    </tr>
  `);

  $('#linesTbody').append(tr);

  const $tax = tr.find('.taxcode-select');
  initS2($tax, taxCodesUrl, 'Tax code...');

  if(line?.tax_code_id && line?.tax_code_label){
    setS2Value($tax, line.tax_code_id, line.tax_code_label, {
      rate_id: line.tax_rate_id || null,
      rate: line.tax_rate || 0,
      is_exempt: line.is_exempt || 0,
      is_out_of_scope: line.is_out_of_scope || 0
    });
  }

  recalcTotals();
}

function recalcTotals(){
  let subtotal = 0;
  let discount = 0;
  let tax = 0;
  let total = 0;

  $('#linesTbody tr').each(function(){
    const $tr = $(this);

    const qty      = parseFloat($tr.find('.ln-qty').val() || '0');
    const unit     = parseFloat($tr.find('.ln-unitprice').val() || '0');
    const discPct  = parseFloat($tr.find('.ln-discpct').val() || '0');
    const taxRate  = parseFloat($tr.find('.ln-taxrate').val() || '0');

    const gross = (isNaN(qty) ? 0 : qty) * (isNaN(unit) ? 0 : unit);
    const discAmt = (isNaN(discPct) || discPct <= 0) ? 0 : (gross * discPct / 100);
    const taxBase = gross - discAmt;
    const taxAmt = (isNaN(taxRate) || taxRate <= 0) ? 0 : (taxBase * taxRate / 100);
    const lineTotal = taxBase + taxAmt;

    $tr.find('.ln-discamt').val(discAmt.toFixed(2));
    $tr.find('.ln-taxamt').val(taxAmt.toFixed(2));
    $tr.find('.ln-total').val(lineTotal.toFixed(2));

    subtotal += gross;
    discount += discAmt;
    tax += taxAmt;
    total += lineTotal;
  });

  $('#subTotalLbl').text(subtotal.toFixed(2));
  $('#discountTotalLbl').text(discount.toFixed(2));
  $('#taxTotalLbl').text(tax.toFixed(2));
  $('#grandTotalLbl').text(total.toFixed(2));
}

$(document).on('input', '.ln-qty,.ln-unitprice,.ln-discpct', recalcTotals);

$(document).on('select2:select', '.taxcode-select', function(e){
  const data = e.params.data || {};
  const $tr = $(this).closest('tr');

  let rate = parseFloat(data.rate || 0);
  if(parseInt(data.is_exempt || 0) === 1 || parseInt(data.is_out_of_scope || 0) === 1){
    rate = 0;
  }

  $tr.find('.ln-taxrate').val(rate.toFixed(2));
  recalcTotals();
});

$(document).on('select2:clear', '.taxcode-select', function(){
  const $tr = $(this).closest('tr');
  $tr.find('.ln-taxrate').val('');
  recalcTotals();
});

$(document).on('click', '.btn-del-line', function(){
  $(this).closest('tr').remove();
  if($('#linesTbody tr').length < 1) addLine();
  recalcTotals();
});

$('#createBtn').on('click', function(){
  resetModal();
  $('#quotationModalTitle').text('New Supplier Quotation');
  $('#quotationModal').modal('show');
});

$('#rfq_id').on('change', function(){
  const rfqId = $(this).val();

  try { $('#rfq_supplier_id').select2('destroy'); } catch(err) {}
  $('#rfq_supplier_id').empty();

  initS2($('#rfq_supplier_id'), rfqSuppliersUrl, 'RFQ supplier...', function(){
    return { rfq_id: rfqId || '' };
  });

  $('#supplier_id').val('');
  $('#supplier_label').val('');
  $('#contact_label').val('');
});

$(document).on('select2:select', '#rfq_supplier_id', function(e){
  const d = e.params.data || {};
  $('#supplier_id').val(d.supplier_id || '');
  $('#supplier_label').val(d.supplier_name || '');
  $('#contact_label').val(d.contact_name || '');
});

$('#loadRfqBtn').on('click', function(){
  const rfqId = $('#rfq_id').val();
  const supplierId = $('#supplier_id').val();

  if(!rfqId) return toastErr('Select an RFQ first.');
  if(!supplierId) return toastErr('Select the RFQ supplier first.');

  $.get(`${baseUrl}/create-from-rfq/${rfqId}`, { supplier_id: supplierId })
    .done(function(res){
      const h = res.header || {};

      $('#quotation_date').val(h.quotation_date || '');
      $('#valid_until').val(h.valid_until || '');
      $('#currency_code').val(h.currency_code || '');
      $('#fx_rate').val(h.fx_rate || '');
      $('#reference').val(h.reference || '');
      $('#notes').val(h.notes || '');

      if(h.rfq_supplier_id){
        setS2Value($('#rfq_supplier_id'), h.rfq_supplier_id, $('#supplier_label').val() || 'RFQ Supplier');
      }

      $('#linesTbody').html('');
      (res.lines || []).forEach(ln => addLine(ln));
      if($('#linesTbody tr').length < 1) addLine();

      recalcTotals();
      toastOk('RFQ lines loaded.');
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load RFQ lines.');
    });
});

$('#saveQuotationBtn').on('click', function(){
  const id = $('#quotation_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : `${baseUrl}`;

  const payload = {
    rfq_id: $('#rfq_id').val(),
    rfq_supplier_id: $('#rfq_supplier_id').val(),
    supplier_id: $('#supplier_id').val(),
    supplier_quote_no: $('#supplier_quote_no').val() || null,
    quotation_date: $('#quotation_date').val(),
    valid_until: $('#valid_until').val() || null,
    currency_code: $('#currency_code').val() || null,
    fx_rate: $('#fx_rate').val() || null,
    reference: $('#reference').val() || null,
    notes: $('#notes').val() || null,
    lines: []
  };

  $('#linesTbody tr').each(function(){
    const $tr = $(this);

    payload.lines.push({
      rfq_line_id: $tr.find('.rfq-line-id').val() || null,
      product_id: $tr.find('.product-id').val() || null,
      description: $tr.find('.description').val() || null,
      unit_id: $tr.find('.unit-id').val() || null,
      qty: $tr.find('.ln-qty').val(),
      unit_price: $tr.find('.ln-unitprice').val(),
      discount_percent: $tr.find('.ln-discpct').val() || null,
      tax_code_id: $tr.find('.taxcode-select').val() || null,
      tax_rate: $tr.find('.ln-taxrate').val() || null,
      lead_time_days: $tr.find('.ln-leadtime').val() || null,
      remarks: $tr.find('.ln-remarks').val() || null
    });
  });

  if(!payload.rfq_id) return toastErr('RFQ is required.');
  if(!payload.rfq_supplier_id) return toastErr('RFQ supplier is required.');
  if(!payload.supplier_id) return toastErr('Supplier is required.');
  if(!payload.quotation_date) return toastErr('Quotation date is required.');
  if(!payload.lines.length) return toastErr('At least one line is required.');

  $.ajax({ url, method, data: payload })
    .done(function(res){
      $('#quotationModal').modal('hide');
      toastOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-quotation', function(){
  const id = $(this).data('id');
  resetModal();

  $.get(`${baseUrl}/${id}`)
    .done(function(res){
      const q = res.quotation || {};

      $('#quotationModalTitle').text('Edit Supplier Quotation');
      $('#quotation_id').val(q.id);
      $('#supplier_id').val(q.supplier_id || '');
      $('#supplier_quote_no').val(q.supplier_quote_no || '');
      $('#quotation_date').val(q.quotation_date || '');
      $('#valid_until').val(q.valid_until || '');
      $('#currency_code').val(q.currency_code || '');
      $('#fx_rate').val(q.fx_rate || '');
      $('#reference').val(q.reference || '');
      $('#notes').val(q.notes || '');
      $('#quotation_status_badge').html(q.status ? `<span class="badge bg-secondary">${String(q.status).toUpperCase()}</span>` : '');

      if(q.rfq_id){
        setS2Value($('#rfq_id'), q.rfq_id, `RFQ #${q.rfq_id}`);
        try { $('#rfq_supplier_id').select2('destroy'); } catch(err) {}
        $('#rfq_supplier_id').empty();
        initS2($('#rfq_supplier_id'), rfqSuppliersUrl, 'RFQ supplier...', function(){
          return { rfq_id: q.rfq_id };
        });
      }

      if(q.rfq_supplier_id){
        setS2Value($('#rfq_supplier_id'), q.rfq_supplier_id, `RFQ Supplier #${q.rfq_supplier_id}`);
      }

      $('#linesTbody').html('');
      (res.lines || []).forEach(ln => addLine(ln));
      if($('#linesTbody tr').length < 1) addLine();

      recalcTotals();
      $('#quotationModal').modal('show');
    })
    .fail(function(){
      toastErr('Could not load supplier quotation.');
    });
});

$(document).on('click', '.btn-del-quotation', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Delete draft quotation?',
    text:'This will soft-delete the draft quotation.'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.ajax({url:`${baseUrl}/${id}`, method:'DELETE'})
      .done(function(res){
        toastOk(res.message || 'Deleted');
        refreshDT();
      })
      .fail(function(xhr){
        toastErr(xhr?.responseJSON?.message || 'Delete failed.');
      });
  });
});

$(document).on('click', '.btn-submit-quotation', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Submit quotation?',
    text:'This will submit the quotation for review.'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/submit`)
      .done(function(res){
        toastOk(res.message || 'Submitted');
        refreshDT();
      })
      .fail(function(xhr){
        toastErr(xhr?.responseJSON?.message || 'Submit failed.');
      });
  });
});

$(document).on('click', '.btn-review-quotation', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Mark as reviewed?',
    text:'This will move the quotation to reviewed status.'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/review`)
      .done(function(res){
        toastOk(res.message || 'Reviewed');
        refreshDT();
      })
      .fail(function(xhr){
        toastErr(xhr?.responseJSON?.message || 'Review failed.');
      });
  });
});

$(document).on('click', '.btn-accept-quotation', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Accept quotation?',
    text:'This quotation will become the preferred quotation for PO conversion.'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/accept`)
      .done(function(res){
        toastOk(res.message || 'Accepted');
        refreshDT();
      })
      .fail(function(xhr){
        toastErr(xhr?.responseJSON?.message || 'Accept failed.');
      });
  });
});

$(document).on('click', '.btn-reject-quotation', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Reject quotation?',
    text:'This will reject the quotation.'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/reject`)
      .done(function(res){
        toastOk(res.message || 'Rejected');
        refreshDT();
      })
      .fail(function(xhr){
        toastErr(xhr?.responseJSON?.message || 'Reject failed.');
      });
  });
});

$(document).on('click', '.btn-view-quotation', function(){
  const id = $(this).data('id');

  $.get(`${baseUrl}/${id}/details`)
    .done(function(res){
      const h = res.header || {};
      const lines = res.lines || [];

      $('#vw_quotation_no').text(h.quotation_no || '—');
      $('#vw_quotation_date').text(h.quotation_date || '—');
      $('#vw_valid_until').text(h.valid_until || '—');
      $('#vw_rfq_no').text(h.rfq_no || '—');
      $('#vw_supplier').text(h.supplier || '—');
      $('#vw_supplier_quote_no').text(h.supplier_quote_no || '—');
      $('#vw_status').text(h.status || '—');
      $('#vw_reference').text(h.reference || '—');
      $('#vw_notes').text(h.notes || '—');
      $('#vw_subtotal').text(Number(h.subtotal || 0).toFixed(2));
      $('#vw_discount_total').text(Number(h.discount_total || 0).toFixed(2));
      $('#vw_tax_total').text(Number(h.tax_total || 0).toFixed(2));
      $('#vw_total_amount').text(Number(h.total_amount || 0).toFixed(2));

      $('#vw_pdf_link').attr('href', `${baseUrl}/${id}/pdf`);

      let html = '';
      if(!lines.length){
        html = `<tr><td colspan="11" class="text-center text-muted">No lines found</td></tr>`;
      } else {
        lines.forEach(function(l){
          html += `
            <tr>
              <td>${l.product_code ? l.product_code + ' - ' : ''}${l.product_name || ''}</td>
              <td>${l.description || '—'}</td>
              <td>${l.unit_name ? l.unit_name + (l.unit_symbol ? ' (' + l.unit_symbol + ')' : '') : '—'}</td>
              <td class="text-end">${Number(l.qty || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.unit_price || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.discount_percent || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.discount_amount || 0).toFixed(2)}</td>
              <td>${l.tax_code_code ? l.tax_code_code + ' - ' : ''}${l.tax_code_name || ''}</td>
              <td class="text-end">${Number(l.tax_rate || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.tax_amount || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.line_total || 0).toFixed(2)}</td>
            </tr>
          `;
        });
      }

      $('#vw_lines').html(html);
      $('#quotationShowModal').modal('show');
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load quotation details.');
    });
});

$(function(){
  initDT();
  initS2($('#rfq_id'), rfqsUrl, 'Select RFQ...');
});
</script>
@endpush