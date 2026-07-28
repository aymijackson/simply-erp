@extends('layouts.master')
@section('title','Goods Receipts')

@push('styles')
<style>
  .grn-modal-body {
    overflow-x: hidden;
  }

  .grn-lines-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: .25rem;
  }

  .grn-lines-table {
    min-width: 2450px;
    table-layout: fixed;
  }

  .grn-lines-table th,
  .grn-lines-table td {
    vertical-align: middle;
    white-space: normal;
  }

  .grn-lines-table .form-control,
  .grn-lines-table .select2-container {
    min-width: 100%;
  }

  .grn-lines-table .col-product      { min-width: 220px; width: 220px; }
  .grn-lines-table .col-variant      { min-width: 220px; width: 220px; }
  .grn-lines-table .col-description  { min-width: 180px; width: 180px; }
  .grn-lines-table .col-unit         { min-width: 120px; width: 120px; }
  .grn-lines-table .col-ordered      { min-width: 110px; width: 110px; }
  .grn-lines-table .col-prevrecv     { min-width: 130px; width: 130px; }
  .grn-lines-table .col-received     { min-width: 110px; width: 110px; }
  .grn-lines-table .col-remaining    { min-width: 120px; width: 120px; }
  .grn-lines-table .col-unitcost     { min-width: 120px; width: 120px; }
  .grn-lines-table .col-total        { min-width: 120px; width: 120px; }
  .grn-lines-table .col-accepted     { min-width: 110px; width: 110px; }
  .grn-lines-table .col-rejected     { min-width: 110px; width: 110px; }
  .grn-lines-table .col-damaged      { min-width: 110px; width: 110px; }
  .grn-lines-table .col-batch        { min-width: 140px; width: 140px; }
  .grn-lines-table .col-serial       { min-width: 180px; width: 180px; }
  .grn-lines-table .col-expiry       { min-width: 130px; width: 130px; }
  .grn-lines-table .col-remarks      { min-width: 180px; width: 180px; }
  .grn-lines-table .col-action       { min-width: 60px; width: 60px; }

  .grn-lines-table .text-end {
    text-align: right !important;
  }

  .grn-readonly {
    background: #f8f9fa;
  }

  .variant-missing {
    border: 1px solid #dc3545 !important;
    background: #fff5f5 !important;
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
      <h1 class="h3 text-primary mb-0">Goods Receipts</h1>
      <small class="text-muted">Procurement / Goods Receipt Notes</small>
    </div>

    @can('procurement.goods_receipts.create')
    <button class="btn btn-primary" id="createBtn">
      <i class="fas fa-plus"></i> New Goods Receipt
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
            <option value="approved">Approved</option>
            <option value="posted">Posted</option>
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
          <input type="text" class="form-control" id="f_q" placeholder="grn no, po no, supplier, ref...">
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
          Each received line must have a product variant before approval or posting.
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="grnTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:170px;">GRN No</th>
              <th style="width:120px;">Receipt Date</th>
              <th style="width:170px;">PO No</th>
              <th>Supplier</th>
              <th style="width:150px;">Location</th>
              <th style="width:120px;">Status</th>
              <th style="width:140px;" class="text-end">Subtotal</th>
              <th style="width:320px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('procurement.goods_receipts.partials.modal')
@include('procurement.goods_receipts.partials.show_modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({
  headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}
});

const dtUrl              = "{{ route('admin.procurement.goods_receipts.datatable') }}";
const baseUrl            = "{{ url('admin/procurement/goods-receipts') }}";
const purchaseOrdersUrl  = "{{ route('admin.procurement.goods_receipts.lookups.purchase_orders') }}";
const suppliersUrl       = "{{ route('admin.procurement.goods_receipts.lookups.suppliers') }}";
const locationsUrl       = "{{ route('admin.procurement.goods_receipts.lookups.locations') }}";
const storesUrl          = "{{ route('admin.procurement.goods_receipts.lookups.stores') }}";
const productVariantsUrl = "{{ route('admin.procurement.goods_receipts.lookups.product_variants') }}";

let DT = null;

function toastOk(msg){
  return Swal.fire({
    icon:'success',
    title:'Success',
    text: msg || 'Done',
    timer: 1400,
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

function showLoading(title = 'Processing...'){
  Swal.fire({
    title: title,
    text: 'Please wait',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading()
  });
}

function initDT(){
  DT = $('#grnTable').DataTable({
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
      {data:'grn_no'},
      {data:'receipt_date'},
      {data:'po_no'},
      {data:'supplier'},
      {data:'location'},
      {data:'status', orderable:false, searchable:false},
      {data:'subtotal', className:'text-end'},
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

function initS2($el, url, placeholder, extraDataFn = null, dropdownParent = $('#grnModal')){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder,
    allowClear:true,
    dropdownParent: dropdownParent,
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
  $('#grnForm')[0].reset();

  $('#grn_id').val('');
  $('#grn_status_badge').html('');

  $('#purchase_order_id').empty().trigger('change');
  $('#supplier_id').empty().trigger('change');
  $('#delivery_location_id').empty().trigger('change');
  $('#delivery_store_id').empty().trigger('change');

  $('#linesTbody').html('');
  $('#subTotalLbl').text('0.00');
}

function addLine(line = null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td class="col-product">
        <input type="hidden" class="po-line-id" value="${line?.purchase_order_line_id ?? ''}">
        <input type="hidden" class="product-id" value="${line?.product_id ?? ''}">
        <input type="hidden" class="unit-id" value="${line?.unit_id ?? ''}">
        <input type="text" class="form-control grn-readonly product-label" value="${line?.product_label ?? ''}" readonly>
      </td>

      <td class="col-variant">
        <select class="form-control variant-id" style="width:100%">
          <option value="">Select variant</option>
        </select>
      </td>

      <td class="col-description">
        <input type="text" class="form-control grn-readonly description" value="${line?.description ?? ''}" readonly>
      </td>

      <td class="col-unit">
        <input type="text" class="form-control grn-readonly unit-label" value="${line?.unit_label ?? ''}" readonly>
      </td>

      <td class="col-ordered">
        <input type="number" step="0.0001" class="form-control text-end grn-readonly ln-ordered" value="${line?.ordered_qty ?? 0}" readonly>
      </td>

      <td class="col-prevrecv">
        <input type="number" step="0.0001" class="form-control text-end grn-readonly ln-prevrecv" value="${line?.previously_received_qty ?? 0}" readonly>
      </td>

      <td class="col-received">
        <input type="number" step="0.0001" class="form-control text-end ln-received" value="${line?.received_qty ?? 0}">
      </td>

      <td class="col-remaining">
        <input type="number" step="0.0001" class="form-control text-end grn-readonly ln-remaining" value="${line?.remaining_qty ?? 0}" readonly>
      </td>

      <td class="col-unitcost">
        <input type="number" step="0.0001" class="form-control text-end grn-readonly ln-unitcost" value="${line?.unit_cost ?? 0}" readonly>
      </td>

      <td class="col-total">
        <input type="number" step="0.01" class="form-control text-end grn-readonly ln-total" value="${line?.line_total ?? 0}" readonly>
      </td>

      <td class="col-accepted">
        <input type="number" step="0.0001" class="form-control text-end ln-accepted" value="${line?.accepted_qty ?? 0}">
      </td>

      <td class="col-rejected">
        <input type="number" step="0.0001" class="form-control text-end ln-rejected" value="${line?.rejected_qty ?? 0}">
      </td>

      <td class="col-damaged">
        <input type="number" step="0.0001" class="form-control text-end ln-damaged" value="${line?.damage_qty ?? 0}">
      </td>

      <td class="col-batch">
        <input type="text" class="form-control ln-batch" value="${line?.batch_no ?? ''}" placeholder="Batch no">
      </td>

      <td class="col-serial">
        <input type="text" class="form-control ln-serial" value="${line?.serial_no ?? ''}" placeholder="Serial no">
      </td>

      <td class="col-expiry">
        <input type="date" class="form-control ln-expiry" value="${line?.expiry_date ?? ''}">
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
  initVariantSelect(tr, line);
  recalcLine(tr);
  recalcTotals();
}

function initVariantSelect($tr, line = null){
  const productId = $tr.find('.product-id').val();
  const $variant = $tr.find('.variant-id');

  $variant.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select variant...',
    allowClear:true,
    dropdownParent: $('#grnModal'),
    ajax:{
      url: productVariantsUrl,
      dataType:'json',
      delay:200,
      data:function(params){
        return {
          q: params.term || '',
          product_id: productId
        };
      },
      processResults:function(data){
        return data;
      },
      cache:true
    }
  });

  if(line?.product_variant_id){
    const opt = new Option(
      line.product_variant_label || ('Variant #' + line.product_variant_id),
      line.product_variant_id,
      true,
      true
    );
    $variant.append(opt).trigger('change');
  }
}

function recalcLine(row){
  const $tr = $(row);

  const ordered   = parseFloat($tr.find('.ln-ordered').val() || '0');
  const prevRecv  = parseFloat($tr.find('.ln-prevrecv').val() || '0');
  const received  = parseFloat($tr.find('.ln-received').val() || '0');
  const unitCost  = parseFloat($tr.find('.ln-unitcost').val() || '0');
  const accepted  = parseFloat($tr.find('.ln-accepted').val() || '0');
  const rejected  = parseFloat($tr.find('.ln-rejected').val() || '0');
  const damaged   = parseFloat($tr.find('.ln-damaged').val() || '0');

  const maxRemaining = Math.max(0, ordered - prevRecv);
  const effectiveReceived = Math.max(0, Math.min(received, maxRemaining));

  if (effectiveReceived !== received) {
    $tr.find('.ln-received').val(effectiveReceived.toFixed(4));
  }

  const total = effectiveReceived * unitCost;
  const remaining = Math.max(0, maxRemaining - effectiveReceived);

  $tr.find('.ln-total').val(total.toFixed(2));
  $tr.find('.ln-remaining').val(remaining.toFixed(4));

  const sumQuality = (isNaN(accepted) ? 0 : accepted) + (isNaN(rejected) ? 0 : rejected) + (isNaN(damaged) ? 0 : damaged);
  if (sumQuality > effectiveReceived + 0.0001) {
    $tr.addClass('table-warning');
  } else {
    $tr.removeClass('table-warning');
  }

  const variantId = $tr.find('.variant-id').val();
  if (effectiveReceived > 0 && !variantId) {
    $tr.find('.variant-id').next('.select2-container').find('.select2-selection').addClass('variant-missing');
  } else {
    $tr.find('.variant-id').next('.select2-container').find('.select2-selection').removeClass('variant-missing');
  }
}

function recalcTotals(){
  let subtotal = 0;

  $('#linesTbody tr').each(function(){
    recalcLine(this);
    subtotal += parseFloat($(this).find('.ln-total').val() || '0');
  });

  $('#subTotalLbl').text(subtotal.toFixed(2));
}

$(document).on('input', '.ln-received,.ln-accepted,.ln-rejected,.ln-damaged', function(){
  recalcTotals();
});

$(document).on('change', '.variant-id', function(){
  recalcTotals();
});

$(document).on('click', '.btn-del-line', function(){
  $(this).closest('tr').remove();
  recalcTotals();
});

$('#createBtn').on('click', function(){
  resetModal();
  $('#grnModalTitle').text('New Goods Receipt');
  $('#grnModal').modal('show');
});

$(document).on('select2:select', '#purchase_order_id', function(e){
  const d = e.params.data || {};
  if(d.supplier_id){
    setS2Value($('#supplier_id'), d.supplier_id, d.supplier_name || 'Supplier');
  }
});

$('#delivery_location_id').on('change', function(){
  $('#delivery_store_id').empty().trigger('change');
});

$('#loadPoBtn').on('click', function(){
  const poId = $('#purchase_order_id').val();
  if(!poId) return toastErr('Select a purchase order first.');

  $.get(`${baseUrl}/create-from-purchase-order/${poId}`)
    .done(function(res){
      const h = res.header || {};

      if(h.supplier_id && h.supplier_label){
        setS2Value($('#supplier_id'), h.supplier_id, h.supplier_label);
      }

      $('#receipt_date').val(h.receipt_date || '');
      $('#supplier_delivery_note_no').val(h.supplier_delivery_note_no || '');
      $('#reference').val(h.reference || '');
      $('#notes').val(h.notes || '');

      if(h.delivery_location_id && h.delivery_location_label){
        setS2Value($('#delivery_location_id'), h.delivery_location_id, h.delivery_location_label);
      }

      if(h.delivery_store_id && h.delivery_store_label){
        setS2Value($('#delivery_store_id'), h.delivery_store_id, h.delivery_store_label);
      }

      $('#linesTbody').html('');
      (res.lines || []).forEach(ln => addLine(ln));

      recalcTotals();
      toastOk('PO lines loaded.');
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load PO lines.');
    });
});

$('#saveGrnBtn').on('click', function(){
  const id = $('#grn_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : `${baseUrl}`;

  const payload = {
    purchase_order_id: $('#purchase_order_id').val(),
    supplier_id: $('#supplier_id').val(),
    receipt_date: $('#receipt_date').val(),
    supplier_delivery_note_no: $('#supplier_delivery_note_no').val() || null,
    delivery_location_id: $('#delivery_location_id').val() || null,
    delivery_store_id: $('#delivery_store_id').val() || null,
    reference: $('#reference').val() || null,
    notes: $('#notes').val() || null,
    lines: []
  };

  let hasQualityError = false;
  let missingVariant = false;

  $('#linesTbody tr').each(function(){
    const $tr = $(this);

    const received = parseFloat($tr.find('.ln-received').val() || '0');
    const accepted = parseFloat($tr.find('.ln-accepted').val() || '0');
    const rejected = parseFloat($tr.find('.ln-rejected').val() || '0');
    const damaged = parseFloat($tr.find('.ln-damaged').val() || '0');
    const variantId = $tr.find('.variant-id').val();

    if ((accepted + rejected + damaged) > (received + 0.0001)) {
      hasQualityError = true;
    }

    if (received > 0 && !variantId) {
      missingVariant = true;
    }

    payload.lines.push({
      purchase_order_line_id: $tr.find('.po-line-id').val(),
      product_id: $tr.find('.product-id').val() || null,
      product_variant_id: variantId || null,
      description: $tr.find('.description').val() || null,
      unit_id: $tr.find('.unit-id').val() || null,
      ordered_qty: $tr.find('.ln-ordered').val(),
      previously_received_qty: $tr.find('.ln-prevrecv').val(),
      received_qty: $tr.find('.ln-received').val(),
      accepted_qty: $tr.find('.ln-accepted').val() || 0,
      rejected_qty: $tr.find('.ln-rejected').val() || 0,
      damage_qty: $tr.find('.ln-damaged').val() || 0,
      unit_cost: $tr.find('.ln-unitcost').val(),
      batch_no: $tr.find('.ln-batch').val() || null,
      serial_no: $tr.find('.ln-serial').val() || null,
      expiry_date: $tr.find('.ln-expiry').val() || null,
      remarks: $tr.find('.ln-remarks').val() || null
    });
  });

  if(!payload.purchase_order_id) return toastErr('Purchase order is required.');
  if(!payload.supplier_id) return toastErr('Supplier is required.');
  if(!payload.receipt_date) return toastErr('Receipt date is required.');
  if(!payload.lines.length) return toastErr('At least one line is required.');
  if(missingVariant) return toastErr('Please select a product variant for every line with received quantity.');
  if(hasQualityError) return toastErr('Accepted + Rejected + Damaged cannot exceed Received Qty on any line.');

  $.ajax({ url, method, data: payload })
    .done(function(res){
      $('#grnModal').modal('hide');
      toastOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-grn', function(){
  const id = $(this).data('id');
  resetModal();

  $.get(`${baseUrl}/${id}`)
    .done(function(res){
      const gr = res.goods_receipt || {};

      $('#grnModalTitle').text('Edit Goods Receipt');
      $('#grn_id').val(gr.id);
      $('#receipt_date').val(gr.receipt_date || '');
      $('#supplier_delivery_note_no').val(gr.supplier_delivery_note_no || '');
      $('#reference').val(gr.reference || '');
      $('#notes').val(gr.notes || '');
      $('#grn_status_badge').html(gr.status ? `<span class="badge bg-secondary">${String(gr.status).toUpperCase()}</span>` : '');

      if(gr.purchase_order_id){
        setS2Value($('#purchase_order_id'), gr.purchase_order_id, `PO #${gr.purchase_order_id}`);
      }
      if(gr.supplier_id){
        setS2Value($('#supplier_id'), gr.supplier_id, `Supplier #${gr.supplier_id}`);
      }
      if(gr.delivery_location_id){
        setS2Value($('#delivery_location_id'), gr.delivery_location_id, `Location #${gr.delivery_location_id}`);
      }
      if(gr.delivery_store_id){
        setS2Value($('#delivery_store_id'), gr.delivery_store_id, `Store #${gr.delivery_store_id}`);
      }

      $('#linesTbody').html('');
      (res.lines || []).forEach(ln => addLine(ln));

      recalcTotals();
      $('#grnModal').modal('show');
    })
    .fail(function(){
      toastErr('Could not load goods receipt.');
    });
});

$(document).on('click', '.btn-view-grn', function(){
  const id = $(this).data('id');

  $.get(`${baseUrl}/${id}/details`)
    .done(function(res){
      const h = res.header || {};
      const lines = res.lines || [];

      $('#vw_grn_no').text(h.grn_no || '—');
      $('#vw_receipt_date').text(h.receipt_date || '—');
      $('#vw_supplier_delivery_note_no').text(h.supplier_delivery_note_no || '—');
      $('#vw_po_no').text(h.po_no || '—');
      $('#vw_supplier').text(h.supplier || '—');
      $('#vw_delivery_location').text(h.delivery_location || '—');
      $('#vw_delivery_store').text(h.delivery_store || '—');
      $('#vw_reference').text(h.reference || '—');
      $('#vw_notes').text(h.notes || '—');
      $('#vw_status').text(h.status || '—');
      $('#vw_subtotal').text(Number(h.subtotal || 0).toFixed(2));
      $('#vw_received_by').text(h.received_by || '—');
      $('#vw_posted_by').text(h.posted_by || '—');
      $('#vw_posted_at').text(h.posted_at || '—');
      $('#vw_pdf_link').attr('href', `${baseUrl}/${id}/pdf`);

      let html = '';
      if(!lines.length){
        html = `<tr><td colspan="13" class="text-center text-muted">No lines found</td></tr>`;
      } else {
        lines.forEach(function(l){
          html += `
            <tr>
              <td>${l.product_code ? l.product_code + ' - ' : ''}${l.product_name || ''}</td>
              <td>${l.variant_sku ? l.variant_sku + (l.variant_type ? ' (' + String(l.variant_type).toUpperCase() + ')' : '') : '—'}</td>
              <td>${l.description || '—'}</td>
              <td>${l.unit_name ? l.unit_name + (l.unit_symbol ? ' (' + l.unit_symbol + ')' : '') : '—'}</td>
              <td class="text-end">${Number(l.ordered_qty || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.previously_received_qty || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.received_qty || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.remaining_qty || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.accepted_qty || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.rejected_qty || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.damage_qty || 0).toFixed(2)}</td>
              <td>${l.batch_no || '—'}</td>
              <td>${l.serial_no || '—'}</td>
            </tr>
          `;
        });
      }

      $('#vw_lines').html(html);
      $('#grnShowModal').modal('show');
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load GRN details.');
    });
});

$(document).on('click', '.btn-del-grn', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Delete draft goods receipt?',
    text:'This will soft-delete the draft receipt.'
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

$(document).on('click', '.btn-approve-grn', function(){
  const id = $(this).data('id');

  Swal.fire({
    title:'Approve Goods Receipt?',
    text:'This will move the GRN to approved status.',
    icon:'warning',
    showCancelButton:true,
    confirmButtonText:'Yes, approve'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.ajax({
      url:`${baseUrl}/${id}/approve`,
      method:'POST',
      beforeSend:function(){ showLoading('Approving...'); }
    })
    .done(function(res){
      Swal.close();
      toastOk(res.message || 'Approved');
      refreshDT();
    })
    .fail(function(xhr){
      Swal.close();
      toastErr(xhr?.responseJSON?.message || 'Approval failed.');
    });
  });
});

$(document).on('click', '.btn-post-grn', function(){
  const id = $(this).data('id');

  Swal.fire({
    title:'Post Goods Receipt?',
    text:'This will create stock entry records and update inventory.',
    icon:'warning',
    showCancelButton:true,
    confirmButtonText:'Yes, post'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.ajax({
      url:`${baseUrl}/${id}/post`,
      method:'POST',
      beforeSend:function(){ showLoading('Posting...'); }
    })
    .done(function(res){
      Swal.close();
      toastOk(res.message || 'Posted');
      refreshDT();
    })
    .fail(function(xhr){
      Swal.close();
      toastErr(xhr?.responseJSON?.message || 'Posting failed.');
    });
  });
});

$(document).on('click', '.btn-cancel-grn', function(){
  const id = $(this).data('id');

  Swal.fire({
    title:'Cancel Goods Receipt?',
    text:'Only draft or approved GRNs can be cancelled.',
    icon:'warning',
    showCancelButton:true,
    confirmButtonText:'Yes, cancel'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.ajax({
      url:`${baseUrl}/${id}/cancel`,
      method:'POST',
      beforeSend:function(){ showLoading('Cancelling...'); }
    })
    .done(function(res){
      Swal.close();
      toastOk(res.message || 'Cancelled');
      refreshDT();
    })
    .fail(function(xhr){
      Swal.close();
      toastErr(xhr?.responseJSON?.message || 'Cancellation failed.');
    });
  });
});

$(function(){
  initDT();

  initS2($('#purchase_order_id'), purchaseOrdersUrl, 'Purchase order...');
  initS2($('#supplier_id'), suppliersUrl, 'Supplier...');
  initS2($('#delivery_location_id'), locationsUrl, 'Delivery location...');
  initS2(
    $('#delivery_store_id'),
    storesUrl,
    'Delivery store...',
    function(){
      return { location_id: $('#delivery_location_id').val() || '' };
    }
  );
});
</script>
@endpush