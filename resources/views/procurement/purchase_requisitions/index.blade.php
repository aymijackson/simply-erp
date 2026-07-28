@extends('layouts.master')
@section('title','Purchase Requisitions')

@push('styles')
<style>
  .procurement-modal-body {
    overflow-x: hidden;
  }

  .requisition-lines-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: .25rem;
  }

  .requisition-lines-table {
    min-width: 1280px;
    table-layout: fixed;
  }

  .requisition-lines-table th,
  .requisition-lines-table td {
    vertical-align: middle;
    white-space: normal;
  }

  .requisition-lines-table .form-control,
  .requisition-lines-table .select2-container {
    min-width: 100%;
  }

  .requisition-lines-table .col-product     { min-width: 240px; width: 240px; }
  .requisition-lines-table .col-description { min-width: 180px; width: 180px; }
  .requisition-lines-table .col-unit        { min-width: 120px; width: 120px; }
  .requisition-lines-table .col-qty         { min-width: 90px;  width: 90px; }
  .requisition-lines-table .col-unitcost    { min-width: 140px; width: 140px; }
  .requisition-lines-table .col-taxcode     { min-width: 220px; width: 220px; }
  .requisition-lines-table .col-taxrate     { min-width: 90px;  width: 90px; }
  .requisition-lines-table .col-taxamt      { min-width: 110px; width: 110px; }
  .requisition-lines-table .col-total       { min-width: 120px; width: 120px; }
  .requisition-lines-table .col-action      { min-width: 60px;  width: 60px; }

  .requisition-lines-table .text-end {
    text-align: right !important;
  }

  .requisition-lines-table .select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
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
      <h1 class="h3 text-primary mb-0">Purchase Requisitions</h1>
      <small class="text-muted">Procurement / Requisitioning</small>
    </div>

    @can('procurement.requisitions.create')
    <button class="btn btn-primary" id="createBtn">
      <i class="fas fa-plus"></i> New Requisition
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
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
            <option value="converted">Converted</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="text-muted small">Priority</label>
          <select class="form-control" id="f_priority">
            <option value="">All</option>
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
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

        <div class="col-md-3">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="f_q" placeholder="req no, reference, requester...">
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
          Draft requisitions can be edited or deleted. Submitted requisitions can be approved or rejected.
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="reqTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:170px;">Req No</th>
              <th style="width:120px;">Date</th>
              <th style="width:120px;">Needed By</th>
              <th style="width:110px;">Priority</th>
              <th style="width:130px;">Status</th>
              <th>Requested By</th>
              <th style="width:140px;" class="text-end">Total</th>
              <th style="width:250px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('procurement.purchase_requisitions.partials.modal')
@include('procurement.purchase_requisitions.partials.show_modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({
  headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
});

const dtUrl        = "{{ route('admin.procurement.purchase_requisitions.datatable') }}";
const baseUrl      = "{{ url('admin/procurement/purchase-requisitions') }}";
const productsUrl  = "{{ route('admin.procurement.purchase_requisitions.lookups.products') }}";
const unitsUrl     = "{{ route('admin.procurement.purchase_requisitions.lookups.units') }}";
const taxCodesUrl  = "{{ route('admin.procurement.purchase_requisitions.lookups.tax_codes') }}";
const locationsUrl = "{{ route('admin.procurement.purchase_requisitions.lookups.locations') }}";
const storesUrl    = "{{ route('admin.procurement.purchase_requisitions.lookups.stores') }}";

let DT = null;

function toastOk(msg){
  return Swal.fire({
    icon:'success',
    title:'Success',
    text: msg || 'Done',
    timer:1200,
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
    confirmButtonText:'Yes',
  }, opts || {}));
}

function initDT(){
  DT = $('#reqTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url: dtUrl,
      data: function(d){
        d.status = $('#f_status').val();
        d.priority = $('#f_priority').val();
        d.date_from = $('#f_from').val();
        d.date_to = $('#f_to').val();
        d.q = $('#f_q').val();
      }
    },
    columns:[
      {data:'id'},
      {data:'requisition_no'},
      {data:'requisition_date'},
      {data:'needed_by_date'},
      {data:'priority', orderable:false, searchable:false},
      {data:'status', orderable:false, searchable:false},
      {data:'requested_by'},
      {data:'total_amount', className:'text-end'},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[0,'desc']]
  });
}

function refreshDT(){
  if (DT) DT.ajax.reload(null,false);
}

$('#applyBtn').on('click', refreshDT);

$('#resetBtn').on('click', function(){
  $('#f_status').val('');
  $('#f_priority').val('');
  $('#f_from').val('');
  $('#f_to').val('');
  $('#f_q').val('');
  refreshDT();
});

function initS2($el, url, placeholder){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder,
    allowClear:true,
    dropdownParent: $('#reqModal'),
    ajax:{
      url,
      dataType:'json',
      delay:200,
      data:p=>({q:p.term||''}),
      processResults:d=>d,
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
    params:{ data:Object.assign({id:id,text:text||id}, extraData) }
  });
}

function resetModal(){
  $('#reqForm')[0].reset();
  $('#req_id').val('');
  $('#req_status_badge').html('');
  $('#linesTbody').html('');
  addLine();
  recalcTotals();
}

function addLine(line=null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td class="col-product">
        <select class="form-control product-select" name="lines[${idx}][product_id]"></select>
      </td>
      <td class="col-description">
        <input type="text" class="form-control" name="lines[${idx}][description]" value="${line?.description ?? ''}" placeholder="Description">
      </td>
      <td class="col-unit">
        <select class="form-control unit-select" name="lines[${idx}][unit_id]"></select>
      </td>
      <td class="col-qty">
        <input type="number" step="0.0001" class="form-control text-end ln-qty" name="lines[${idx}][qty]" value="${line?.qty ?? 1}">
      </td>
      <td class="col-unitcost">
        <input type="number" step="0.0001" class="form-control text-end ln-unitcost" name="lines[${idx}][estimated_unit_cost]" value="${line?.estimated_unit_cost ?? 0}">
      </td>
      <td class="col-taxcode">
        <select class="form-control taxcode-select" name="lines[${idx}][tax_code_id]"></select>
      </td>
      <td class="col-taxrate">
        <input type="number" step="0.0001" class="form-control text-end ln-taxrate" name="lines[${idx}][tax_rate]" value="${line?.tax_rate ?? ''}" placeholder="%" readonly>
      </td>
      <td class="col-taxamt">
        <input type="number" step="0.01" class="form-control text-end ln-taxamt" value="${line?.tax_amount ?? 0}" readonly>
      </td>
      <td class="col-total">
        <input type="number" step="0.01" class="form-control text-end ln-total" value="${line?.line_total ?? 0}" readonly>
      </td>
      <td class="col-action text-center">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-line">
          <i class="fas fa-times"></i>
        </button>
      </td>
    </tr>
  `);

  $('#linesTbody').append(tr);

  const $product = tr.find('.product-select');
  initS2($product, productsUrl, 'Product...');
  if(line?.product_id && line?.product_label){
    setS2Value($product, line.product_id, line.product_label);
  }

  const $unit = tr.find('.unit-select');
  initS2($unit, unitsUrl, 'Unit...');
  if(line?.unit_id && line?.unit_label){
    setS2Value($unit, line.unit_id, line.unit_label);
  }

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
  let tax = 0;
  let total = 0;

  $('#linesTbody tr').each(function(){
    const $tr = $(this);
    const qty  = parseFloat($tr.find('.ln-qty').val() || '0');
    const unit = parseFloat($tr.find('.ln-unitcost').val() || '0');
    const rate = parseFloat($tr.find('.ln-taxrate').val() || '0');

    const base = (isNaN(qty) ? 0 : qty) * (isNaN(unit) ? 0 : unit);
    const tx = (isNaN(rate) || rate <= 0) ? 0 : (base * rate / 100);
    const lineTotal = base + tx;

    $tr.find('.ln-taxamt').val(tx.toFixed(2));
    $tr.find('.ln-total').val(lineTotal.toFixed(2));

    subtotal += base;
    tax += tx;
    total += lineTotal;
  });

  $('#subTotalLbl').text(subtotal.toFixed(2));
  $('#taxTotalLbl').text(tax.toFixed(2));
  $('#grandTotalLbl').text(total.toFixed(2));
}

$(document).on('input', '.ln-qty,.ln-unitcost', recalcTotals);

$(document).on('select2:select', '.taxcode-select', function(e){
  const data = e.params.data || {};
  const $tr = $(this).closest('tr');

  let rate = parseFloat(data.rate || 0);
  if (parseInt(data.is_exempt || 0) === 1 || parseInt(data.is_out_of_scope || 0) === 1) {
    rate = 0;
  }

  $tr.find('.ln-taxrate').val(rate.toFixed(4));
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

$('#addLineBtn').on('click', ()=> addLine());

$('#createBtn').on('click', function(){
  resetModal();
  $('#reqModalTitle').text('New Purchase Requisition');
  $('#reqModal').modal('show');
});

$('#saveReqBtn').on('click', function(){
  const id = $('#req_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : `${baseUrl}`;

  const payload = {
    requisition_date: $('#requisition_date').val(),
    needed_by_date: $('#needed_by_date').val() || null,
    priority: $('#priority').val(),
    reference: $('#reference').val() || null,
    notes: $('#notes').val() || null,
    lines: []
  };

  $('#linesTbody tr').each(function(){
    const $tr = $(this);
    payload.lines.push({
      product_id: $tr.find('.product-select').val() || null,
      description: $tr.find('input[name*="[description]"]').val() || null,
      unit_id: $tr.find('.unit-select').val() || null,
      qty: $tr.find('.ln-qty').val(),
      estimated_unit_cost: $tr.find('.ln-unitcost').val(),
      tax_code_id: $tr.find('.taxcode-select').val() || null,
      tax_rate: $tr.find('.ln-taxrate').val() || null,
      memo: null
    });
  });

  if(!payload.requisition_date) return toastErr('Requisition date is required.');
  if(!payload.priority) return toastErr('Priority is required.');
  if(!payload.lines.length) return toastErr('Add at least one line.');

  $.ajax({url, method, data: payload})
    .done(res=>{
      $('#reqModal').modal('hide');
      toastOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Save failed.'));
});

$(document).on('click', '.btn-edit-req', function(){
  const id = $(this).data('id');
  resetModal();

  $.get(`${baseUrl}/${id}`)
    .done(r=>{
      const req = r.requisition || {};
      $('#reqModalTitle').text('Edit Purchase Requisition');
      $('#req_id').val(req.id);
      $('#requisition_date').val(req.requisition_date || '');
      $('#needed_by_date').val(req.needed_by_date || '');
      $('#priority').val(req.priority || 'normal');
      $('#reference').val(req.reference || '');
      $('#notes').val(req.notes || '');
      $('#req_status_badge').html(req.status ? `<span class="badge bg-secondary">${String(req.status).toUpperCase()}</span>` : '');

      $('#linesTbody').html('');
      (r.lines || []).forEach(ln => addLine(ln));
      if($('#linesTbody tr').length < 1) addLine();

      recalcTotals();
      $('#reqModal').modal('show');
    })
    .fail(()=> toastErr('Could not load requisition.'));
});

$(document).on('click', '.btn-del-req', function(){
  const id = $(this).data('id');

  confirmBox({title:'Delete draft requisition?', text:'This will soft-delete the draft requisition.'})
    .then(r=>{
      if(!r.isConfirmed) return;

      $.ajax({url:`${baseUrl}/${id}`, method:'DELETE'})
        .done(res=>{ toastOk(res.message || 'Deleted'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Delete failed.'));
    });
});

$(document).on('click', '.btn-submit-req', function(){
  const id = $(this).data('id');
  confirmBox({title:'Submit requisition?', text:'This will send the requisition for approval.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${baseUrl}/${id}/submit`)
        .done(res=>{ toastOk(res.message || 'Submitted'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Submit failed.'));
    });
});

$(document).on('click', '.btn-approve-req', function(){
  const id = $(this).data('id');
  confirmBox({title:'Approve requisition?', text:'This will mark the requisition as approved.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${baseUrl}/${id}/approve`)
        .done(res=>{ toastOk(res.message || 'Approved'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Approve failed.'));
    });
});

$(document).on('click', '.btn-reject-req', function(){
  const id = $(this).data('id');
  confirmBox({title:'Reject requisition?', text:'This will mark the requisition as rejected.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${baseUrl}/${id}/reject`)
        .done(res=>{ toastOk(res.message || 'Rejected'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Reject failed.'));
    });
});

$(document).on('click', '.btn-view-req', function(){
  const id = $(this).data('id');

  $.get(`${baseUrl}/${id}/details`)
    .done(function(res){
      const h = res.header || {};
      const lines = res.lines || [];

      $('#vw_requisition_no').text(h.requisition_no || '—');
      $('#vw_requisition_date').text(h.requisition_date || '—');
      $('#vw_needed_by_date').text(h.needed_by_date || '—');
      $('#vw_priority').text(h.priority || '—');
      $('#vw_status').text(h.status || '—');
      $('#vw_requested_by').text(h.requested_by || '—');
      $('#vw_approved_by').text(h.approved_by || '—');
      $('#vw_approved_at').text(h.approved_at || '—');
      $('#vw_reference').text(h.reference || '—');
      $('#vw_notes').text(h.notes || '—');

      $('#vw_subtotal').text(Number(h.subtotal || 0).toFixed(2));
      $('#vw_tax_total').text(Number(h.tax_total || 0).toFixed(2));
      $('#vw_total_amount').text(Number(h.total_amount || 0).toFixed(2));
      $('#vw_pdf_link').attr('href', `${baseUrl}/${id}/pdf`);

      let html = '';
      if(!lines.length){
        html = `<tr><td colspan="9" class="text-center text-muted">No lines found</td></tr>`;
      } else {
        lines.forEach(function(l){
          const product = l.product_id
            ? `${l.product_code ? l.product_code + ' - ' : ''}${l.product_name || ''}`
            : '—';

          const unit = l.unit_name
            ? `${l.unit_name}${l.unit_symbol ? ' (' + l.unit_symbol + ')' : ''}`
            : '—';

          const taxCode = l.tax_code_id
            ? `${l.tax_code_code ? l.tax_code_code + ' - ' : ''}${l.tax_code_name || ''}`
            : '—';

          html += `
            <tr>
              <td>${product}</td>
              <td>${l.description || '—'}</td>
              <td>${unit}</td>
              <td class="text-end">${Number(l.qty || 0).toFixed(4)}</td>
              <td class="text-end">${Number(l.estimated_unit_cost || 0).toFixed(4)}</td>
              <td>${taxCode}</td>
              <td class="text-end">${Number(l.tax_rate || 0).toFixed(4)}</td>
              <td class="text-end">${Number(l.tax_amount || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.line_total || 0).toFixed(2)}</td>
            </tr>
          `;
        });
      }

      $('#vw_lines').html(html);
      $('#reqShowModal').modal('show');
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load requisition details.');
    });
});

$(function(){
  initDT();
});
</script>
@endpush