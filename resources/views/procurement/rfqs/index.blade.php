@extends('layouts.master')
@section('title','Request for Quotations')

@push('styles')
<style>
  .rfq-modal-body { overflow-x: hidden; }
  .rfq-lines-wrap, .rfq-suppliers-wrap { overflow-x: auto; overflow-y: hidden; padding-bottom: .25rem; }
  .rfq-lines-table { min-width: 1280px; table-layout: fixed; }
  .rfq-suppliers-table { min-width: 1180px; table-layout: fixed; }

  .rfq-lines-table th, .rfq-lines-table td,
  .rfq-suppliers-table th, .rfq-suppliers-table td {
    vertical-align: middle;
    white-space: normal;
  }

  .rfq-lines-table .col-product { min-width: 220px; width: 220px; }
  .rfq-lines-table .col-description { min-width: 180px; width: 180px; }
  .rfq-lines-table .col-unit { min-width: 120px; width: 120px; }
  .rfq-lines-table .col-qty { min-width: 90px; width: 90px; }
  .rfq-lines-table .col-unitcost { min-width: 140px; width: 140px; }
  .rfq-lines-table .col-taxcode { min-width: 220px; width: 220px; }
  .rfq-lines-table .col-taxrate { min-width: 90px; width: 90px; }
  .rfq-lines-table .col-taxamt { min-width: 110px; width: 110px; }
  .rfq-lines-table .col-total { min-width: 120px; width: 120px; }
  .rfq-lines-table .col-action { min-width: 60px; width: 60px; }

  .rfq-suppliers-table .col-supplier { min-width: 220px; width: 220px; }
  .rfq-suppliers-table .col-contact { min-width: 220px; width: 220px; }
  .rfq-suppliers-table .col-name { min-width: 180px; width: 180px; }
  .rfq-suppliers-table .col-email { min-width: 220px; width: 220px; }
  .rfq-suppliers-table .col-phone { min-width: 150px; width: 150px; }
  .rfq-suppliers-table .col-notes { min-width: 180px; width: 180px; }
  .rfq-suppliers-table .col-action { min-width: 60px; width: 60px; }

  @media (max-width: 991.98px) {
    .modal-dialog.modal-xl { margin: .5rem; }
  }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Request for Quotations</h1>
      <small class="text-muted">Procurement / RFQ</small>
    </div>

    @can('procurement.rfqs.create')
    <button class="btn btn-primary" id="createBtn">
      <i class="fas fa-plus"></i> New RFQ
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
            <option value="sent">Sent</option>
            <option value="closed">Closed</option>
            <option value="awarded">Awarded</option>
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
          <input type="text" class="form-control" id="f_q" placeholder="rfq no, requisition no, reference...">
        </div>

        <div class="col-md-1">
          <button class="btn btn-outline-primary w-100" id="applyBtn"><i class="fas fa-filter"></i></button>
        </div>
      </div>

      <div class="d-flex gap-2 mt-2">
        <button class="btn btn-outline-secondary" id="resetBtn"><i class="fas fa-undo"></i> Reset</button>
        <div class="alert alert-info mb-0 flex-grow-1">
          <i class="fas fa-info-circle me-1"></i>
          Draft RFQs can be edited. Sent RFQs can be closed. Closed RFQs can be awarded.
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="rfqTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:170px;">RFQ No</th>
              <th style="width:120px;">Date</th>
              <th style="width:120px;">Closing</th>
              <th style="width:120px;">Status</th>
              <th style="width:170px;">Requisition</th>
              <th style="width:100px;">Suppliers</th>
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

@include('procurement.rfqs.partials.modal')
@include('procurement.rfqs.partials.show_modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({
  headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}
});

const dtUrl                = "{{ route('admin.procurement.rfqs.datatable') }}";
const baseUrl              = "{{ url('admin/procurement/rfqs') }}";
const requisitionsUrl      = "{{ route('admin.procurement.rfqs.lookups.requisitions') }}";
const productsUrl          = "{{ route('admin.procurement.rfqs.lookups.products') }}";
const unitsUrl             = "{{ route('admin.procurement.rfqs.lookups.units') }}";
const taxCodesUrl          = "{{ route('admin.procurement.rfqs.lookups.tax_codes') }}";
const suppliersUrl         = "{{ route('admin.procurement.rfqs.lookups.suppliers') }}";
const supplierContactsUrl  = "{{ route('admin.procurement.rfqs.lookups.supplier_contacts') }}";

let DT = null;

function toastOk(msg){
  return Swal.fire({icon:'success', title:'Success', text:msg || 'Done', timer:1200, showConfirmButton:false});
}
function toastErr(msg){
  return Swal.fire({icon:'error', title:'Error', text:msg || 'Something went wrong'});
}
function confirmBox(opts){
  return Swal.fire(Object.assign({icon:'warning', showCancelButton:true, confirmButtonText:'Yes'}, opts || {}));
}

function initDT(){
  DT = $('#rfqTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url:dtUrl,
      data:function(d){
        d.status = $('#f_status').val();
        d.date_from = $('#f_from').val();
        d.date_to = $('#f_to').val();
        d.q = $('#f_q').val();
      }
    },
    columns:[
      {data:'id'},
      {data:'rfq_no'},
      {data:'rfq_date'},
      {data:'closing_date'},
      {data:'status', orderable:false, searchable:false},
      {data:'requisition_no'},
      {data:'supplier_count', className:'text-center'},
      {data:'total_amount', className:'text-end'},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[0,'desc']]
  });
}
function refreshDT(){ if(DT) DT.ajax.reload(null,false); }

$('#applyBtn').on('click', refreshDT);
$('#resetBtn').on('click', function(){
  $('#f_status').val('');
  $('#f_from').val('');
  $('#f_to').val('');
  $('#f_q').val('');
  refreshDT();
});

function initS2($el, url, placeholder, parent = '#rfqModal'){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder,
    allowClear:true,
    dropdownParent: $(parent),
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

function initSupplierContactS2($el, supplierId){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Contact...',
    allowClear:true,
    dropdownParent: $('#rfqModal'),
    ajax:{
      url:supplierContactsUrl,
      dataType:'json',
      delay:200,
      data:p=>({
        q:p.term || '',
        supplier_id:supplierId || ''
      }),
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
  $el.trigger({type:'select2:select', params:{data:Object.assign({id:id, text:text || id}, extraData)}});
}

function resetModal(){
  $('#rfqForm')[0].reset();
  $('#rfq_id').val('');
  $('#rfq_status_badge').html('');
  $('#linesTbody').html('');
  $('#suppliersTbody').html('');
  $('#requisition_id').val(null).trigger('change');
  addLine();
  addSupplier();
  recalcTotals();
}

function addLine(line=null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td class="col-product">
        <select class="form-control product-select" name="lines[${idx}][product_id]"></select>
        <input type="hidden" class="req-line-id" value="${line?.requisition_line_id ?? ''}">
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
        <input type="number" step="0.0001" class="form-control text-end ln-taxrate" name="lines[${idx}][tax_rate]" value="${line?.tax_rate ?? ''}" readonly>
      </td>
      <td class="col-taxamt">
        <input type="number" step="0.01" class="form-control text-end ln-taxamt" value="${line?.tax_amount ?? 0}" readonly>
      </td>
      <td class="col-total">
        <input type="number" step="0.01" class="form-control text-end ln-total" value="${line?.line_total ?? 0}" readonly>
      </td>
      <td class="col-action text-center">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-line"><i class="fas fa-times"></i></button>
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

function addSupplier(sp=null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td class="col-supplier">
        <select class="form-control supplier-select"></select>
      </td>
      <td class="col-contact">
        <select class="form-control supplier-contact-select"></select>
      </td>
      <td class="col-name">
        <input type="text" class="form-control sp-contact-name" value="${sp?.contact_name ?? ''}" placeholder="Contact name">
      </td>
      <td class="col-email">
        <input type="email" class="form-control sp-contact-email" value="${sp?.contact_email ?? ''}" placeholder="Contact email">
      </td>
      <td class="col-phone">
        <input type="text" class="form-control sp-contact-phone" value="${sp?.contact_phone ?? ''}" placeholder="Phone">
      </td>
      <td class="col-notes">
        <input type="text" class="form-control sp-notes" value="${sp?.notes ?? ''}" placeholder="Notes">
      </td>
      <td class="col-action text-center">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-supplier"><i class="fas fa-times"></i></button>
      </td>
    </tr>
  `);

  $('#suppliersTbody').append(tr);

  const $supplier = tr.find('.supplier-select');
  initS2($supplier, suppliersUrl, 'Supplier...');
  if(sp?.supplier_id && sp?.supplier_label){
    setS2Value($supplier, sp.supplier_id, sp.supplier_label);
  }

  const $contact = tr.find('.supplier-contact-select');
  initSupplierContactS2($contact, sp?.supplier_id || null);
  if(sp?.supplier_contact_id && sp?.supplier_contact_label){
    setS2Value($contact, sp.supplier_contact_id, sp.supplier_contact_label, {
      name: sp.contact_name || '',
      email: sp.contact_email || '',
      phone: sp.contact_phone || ''
    });
  }
}

function recalcTotals(){
  let subtotal = 0, tax = 0, total = 0;

  $('#linesTbody tr').each(function(){
    const $tr = $(this);
    const qty = parseFloat($tr.find('.ln-qty').val() || '0');
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

$(document).on('select2:select', '.supplier-select', function(e){
  const data = e.params.data || {};
  const $tr = $(this).closest('tr');
  const supplierId = data.id || null;

  const $contact = $tr.find('.supplier-contact-select');
  try { $contact.select2('destroy'); } catch(err) {}
  $contact.empty();
  initSupplierContactS2($contact, supplierId);

  $tr.find('.sp-contact-name').val('');
  $tr.find('.sp-contact-email').val('');
  $tr.find('.sp-contact-phone').val('');
});

$(document).on('select2:select', '.supplier-contact-select', function(e){
  const data = e.params.data || {};
  const $tr = $(this).closest('tr');

  $tr.find('.sp-contact-name').val(data.name || '');
  $tr.find('.sp-contact-email').val(data.email || '');
  $tr.find('.sp-contact-phone').val(data.phone || '');
});

$(document).on('click', '.btn-del-line', function(){
  $(this).closest('tr').remove();
  if($('#linesTbody tr').length < 1) addLine();
  recalcTotals();
});

$(document).on('click', '.btn-del-supplier', function(){
  $(this).closest('tr').remove();
  if($('#suppliersTbody tr').length < 1) addSupplier();
});

$('#addLineBtn').on('click', ()=> addLine());
$('#addSupplierBtn').on('click', ()=> addSupplier());

$('#createBtn').on('click', function(){
  resetModal();
  $('#rfqModalTitle').text('New RFQ');
  $('#rfqModal').modal('show');
});

$('#loadReqBtn').on('click', function(){
  const reqId = $('#requisition_id').val();
  if(!reqId) return toastErr('Select an approved requisition first.');

  $.get(`${baseUrl}/create-from-requisition/${reqId}`)
    .done(function(res){
      const h = res.header || {};
      $('#rfq_date').val(h.rfq_date || '');
      $('#closing_date').val(h.closing_date || '');
      $('#reference').val(h.reference || '');
      $('#notes').val(h.notes || '');

      $('#linesTbody').html('');
      (res.lines || []).forEach(ln => addLine(ln));
      if($('#linesTbody tr').length < 1) addLine();
      recalcTotals();
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load requisition lines.');
    });
});

$('#saveRfqBtn').on('click', function(){
  const id = $('#rfq_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : `${baseUrl}`;

  const payload = {
    requisition_id: $('#requisition_id').val() || null,
    rfq_date: $('#rfq_date').val(),
    closing_date: $('#closing_date').val() || null,
    currency_code: $('#currency_code').val() || null,
    fx_rate: $('#fx_rate').val() || null,
    reference: $('#reference').val() || null,
    notes: $('#notes').val() || null,
    lines: [],
    suppliers: [],
  };

  $('#linesTbody tr').each(function(){
    const $tr = $(this);
    payload.lines.push({
      requisition_line_id: $tr.find('.req-line-id').val() || null,
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

  $('#suppliersTbody tr').each(function(){
    const $tr = $(this);
    const supplierId = $tr.find('.supplier-select').val();
    if(!supplierId) return;

    payload.suppliers.push({
      supplier_id: supplierId,
      supplier_contact_id: $tr.find('.supplier-contact-select').val() || null,
      contact_name: $tr.find('.sp-contact-name').val() || null,
      contact_email: $tr.find('.sp-contact-email').val() || null,
      contact_phone: $tr.find('.sp-contact-phone').val() || null,
      notes: $tr.find('.sp-notes').val() || null
    });
  });

  if(!payload.rfq_date) return toastErr('RFQ date is required.');
  if(!payload.lines.length) return toastErr('Add at least one line.');
  if(!payload.suppliers.length) return toastErr('Add at least one supplier.');

  $.ajax({url, method, data: payload})
    .done(res=>{
      $('#rfqModal').modal('hide');
      toastOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Save failed.'));
});

$(document).on('click', '.btn-edit-rfq', function(){
  const id = $(this).data('id');
  resetModal();

  $.get(`${baseUrl}/${id}`)
    .done(function(res){
      const rfq = res.rfq || {};

      $('#rfqModalTitle').text('Edit RFQ');
      $('#rfq_id').val(rfq.id);
      $('#rfq_date').val(rfq.rfq_date || '');
      $('#closing_date').val(rfq.closing_date || '');
      $('#currency_code').val(rfq.currency_code || '');
      $('#fx_rate').val(rfq.fx_rate || '');
      $('#reference').val(rfq.reference || '');
      $('#notes').val(rfq.notes || '');
      $('#rfq_status_badge').html(rfq.status ? `<span class="badge bg-secondary">${String(rfq.status).toUpperCase()}</span>` : '');

      if(rfq.requisition_id){
        setS2Value($('#requisition_id'), rfq.requisition_id, `Requisition #${rfq.requisition_id}`);
      }

      $('#linesTbody').html('');
      (res.lines || []).forEach(ln => addLine(ln));
      if($('#linesTbody tr').length < 1) addLine();

      $('#suppliersTbody').html('');
      (res.suppliers || []).forEach(sp => addSupplier(sp));
      if($('#suppliersTbody tr').length < 1) addSupplier();

      recalcTotals();
      $('#rfqModal').modal('show');
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load RFQ.');
    });
});

$(document).on('click', '.btn-view-rfq', function(){
  const id = $(this).data('id');

  $.get(`${baseUrl}/${id}/details`)
    .done(function(res){
      const h = res.header || {};
      const lines = res.lines || [];
      const suppliers = res.suppliers || [];

      $('#vw_rfq_no').text(h.rfq_no || '—');
      $('#vw_rfq_date').text(h.rfq_date || '—');
      $('#vw_closing_date').text(h.closing_date || '—');
      $('#vw_status').text(h.status || '—');
      $('#vw_requisition_no').text(h.requisition_no || '—');
      $('#vw_created_by').text(h.created_by || '—');
      $('#vw_reference').text(h.reference || '—');
      $('#vw_notes').text(h.notes || '—');
      $('#vw_subtotal').text(Number(h.subtotal || 0).toFixed(2));
      $('#vw_tax_total').text(Number(h.tax_total || 0).toFixed(2));
      $('#vw_total_amount').text(Number(h.total_amount || 0).toFixed(2));
      $('#vw_pdf_link').attr('href', `${baseUrl}/${id}/pdf`);

      let linesHtml = '';
      if(!lines.length){
        linesHtml = `<tr><td colspan="9" class="text-center text-muted">No lines found</td></tr>`;
      } else {
        lines.forEach(function(l){
          const product = l.product_id ? `${l.product_code ? l.product_code + ' - ' : ''}${l.product_name || ''}` : '—';
          const unit = l.unit_name ? `${l.unit_name}${l.unit_symbol ? ' (' + l.unit_symbol + ')' : ''}` : '—';
          const taxCode = l.tax_code_id ? `${l.tax_code_code ? l.tax_code_code + ' - ' : ''}${l.tax_code_name || ''}` : '—';

          linesHtml += `
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
      $('#vw_lines').html(linesHtml);

      let supHtml = '';
      if(!suppliers.length){
        supHtml = `<tr><td colspan="5" class="text-center text-muted">No suppliers found</td></tr>`;
      } else {
        suppliers.forEach(function(s){
          supHtml += `
            <tr>
              <td>${s.supplier_name || '—'}</td>
              <td>${s.supplier_contact_name || s.contact_name || '—'}</td>
              <td>${s.contact_email || '—'}</td>
              <td>${s.contact_phone || '—'}</td>
              <td>${s.response_status || '—'}</td>
            </tr>
          `;
        });
      }
      $('#vw_suppliers').html(supHtml);

      $('#rfqShowModal').modal('show');
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load RFQ details.');
    });
});

$(document).on('click', '.btn-del-rfq', function(){
  const id = $(this).data('id');
  confirmBox({title:'Delete draft RFQ?', text:'This will soft-delete the draft RFQ.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({url:`${baseUrl}/${id}`, method:'DELETE'})
        .done(res=>{ toastOk(res.message || 'Deleted'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Delete failed.'));
    });
});

$(document).on('click', '.btn-send-rfq', function(){
  const id = $(this).data('id');
  confirmBox({title:'Send RFQ?', text:'This will mark the RFQ as sent.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${baseUrl}/${id}/send`)
        .done(res=>{ toastOk(res.message || 'Sent'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Send failed.'));
    });
});

$(document).on('click', '.btn-close-rfq', function(){
  const id = $(this).data('id');
  confirmBox({title:'Close RFQ?', text:'This will close the RFQ.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${baseUrl}/${id}/close`)
        .done(res=>{ toastOk(res.message || 'Closed'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Close failed.'));
    });
});

$(document).on('click', '.btn-award-rfq', function(){
  const id = $(this).data('id');
  confirmBox({title:'Award RFQ?', text:'This will mark the RFQ as awarded.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${baseUrl}/${id}/award`)
        .done(res=>{ toastOk(res.message || 'Awarded'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Award failed.'));
    });
});

$(function(){
  initDT();
  initS2($('#requisition_id'), requisitionsUrl, 'Approved requisition...');
});
</script>
@endpush