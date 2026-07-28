@extends('layouts.master')
@section('title','Purchase Orders')

@push('styles')
<style>
  .purchase-order-modal-body {
    overflow-x: hidden;
  }

  .po-lines-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: .25rem;
  }

  .po-lines-table {
    min-width: 2350px;
    table-layout: fixed;
  }

  .po-lines-table th,
  .po-lines-table td {
    vertical-align: middle;
    white-space: normal;
  }

  .po-lines-table .form-control,
  .po-lines-table .select2-container {
    min-width: 100%;
  }

  .po-lines-table .col-product       { min-width: 220px; width: 220px; }
  .po-lines-table .col-description   { min-width: 180px; width: 180px; }
  .po-lines-table .col-unit          { min-width: 120px; width: 120px; }
  .po-lines-table .col-location      { min-width: 180px; width: 180px; }
  .po-lines-table .col-store         { min-width: 180px; width: 180px; }
  .po-lines-table .col-qty           { min-width: 90px; width: 90px; }
  .po-lines-table .col-unitprice     { min-width: 130px; width: 130px; }
  .po-lines-table .col-discpct       { min-width: 100px; width: 100px; }
  .po-lines-table .col-discamt       { min-width: 120px; width: 120px; }
  .po-lines-table .col-taxcode       { min-width: 220px; width: 220px; }
  .po-lines-table .col-taxrate       { min-width: 90px; width: 90px; }
  .po-lines-table .col-taxamt        { min-width: 110px; width: 110px; }
  .po-lines-table .col-shipping      { min-width: 120px; width: 120px; }
  .po-lines-table .col-othercharges  { min-width: 130px; width: 130px; }
  .po-lines-table .col-total         { min-width: 120px; width: 120px; }
  .po-lines-table .col-leadtime      { min-width: 100px; width: 100px; }
  .po-lines-table .col-expdelivery   { min-width: 140px; width: 140px; }
  .po-lines-table .col-remarks       { min-width: 180px; width: 180px; }
  .po-lines-table .col-action        { min-width: 60px; width: 60px; }

  .po-lines-table .text-end {
    text-align: right !important;
  }

  .po-lines-table .select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
  }

  .po-readonly {
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
      <h1 class="h3 text-primary mb-0">Purchase Orders</h1>
      <small class="text-muted">Procurement / Purchase Orders</small>
    </div>

    @can('procurement.purchase_orders.create')
    <button class="btn btn-primary" id="createBtn">
      <i class="fas fa-plus"></i> New Purchase Order
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
            <option value="issued">Issued</option>
            <option value="partially_received">Partially Received</option>
            <option value="received">Received</option>
            <option value="partially_billed">Partially Billed</option>
            <option value="billed">Billed</option>
            <option value="closed">Closed</option>
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
          <input type="text" class="form-control" id="f_q" placeholder="po no, supplier, quotation, reference...">
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
          Draft POs can be edited or deleted. Approved POs can be issued. Issued or fulfilled POs can be closed.
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="poTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:170px;">PO No</th>
              <th style="width:120px;">PO Date</th>
              <th style="width:130px;">Expected Delivery</th>
              <th>Supplier</th>
              <th style="width:150px;">Contact</th>
              <th style="width:140px;">Status</th>
              <th style="width:140px;" class="text-end">Total</th>
              <th style="width:280px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('procurement.purchase_orders.partials.modal')
@include('procurement.purchase_orders.partials.show_modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({
  headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}
});

const dtUrl              = "{{ route('admin.procurement.purchase_orders.datatable') }}";
const baseUrl            = "{{ url('admin/procurement/purchase-orders') }}";
const quotationsUrl      = "{{ route('admin.procurement.purchase_orders.lookups.quotations') }}";
const suppliersUrl       = "{{ route('admin.procurement.purchase_orders.lookups.suppliers') }}";
const supplierContactsUrl= "{{ route('admin.procurement.purchase_orders.lookups.supplier_contacts') }}";
const locationsUrl       = "{{ route('admin.procurement.purchase_orders.lookups.locations') }}";
const storesUrl          = "{{ route('admin.procurement.purchase_orders.lookups.stores') }}";
const productsUrl        = "{{ route('admin.procurement.purchase_orders.lookups.products') }}";
const unitsUrl           = "{{ route('admin.procurement.purchase_orders.lookups.units') }}";
const taxCodesUrl        = "{{ route('admin.procurement.purchase_orders.lookups.tax_codes') }}";

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
  DT = $('#poTable').DataTable({
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
      {data:'po_no'},
      {data:'po_date'},
      {data:'expected_delivery_date'},
      {data:'supplier'},
      {data:'contact'},
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
    dropdownParent: $('#poModal'),
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
  $('#poForm')[0].reset();

  $('#po_id').val('');
  $('#po_status_badge').html('');

  $('#quotation_id').empty().trigger('change');
  $('#supplier_id').empty().trigger('change');
  $('#supplier_contact_id').empty().trigger('change');
  $('#delivery_location_id').empty().trigger('change');
  $('#delivery_store_id').empty().trigger('change');
  $('#bill_to_location_id').empty().trigger('change');

  $('#purchase_requisition_id').val('');
  $('#rfq_id').val('');
  $('#supplier_quotation_id').val('');

  $('#linesTbody').html('');
  addLine();
  recalcTotals();
}

function addLine(line = null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td class="col-product">
        <input type="hidden" class="pr-line-id" value="${line?.purchase_requisition_line_id ?? ''}">
        <input type="hidden" class="rfq-line-id" value="${line?.rfq_line_id ?? ''}">
        <input type="hidden" class="sq-line-id" value="${line?.supplier_quotation_line_id ?? ''}">
        <select class="form-control product-select"></select>
      </td>

      <td class="col-description">
        <input type="text" class="form-control description" value="${line?.description ?? ''}" placeholder="Description">
      </td>

      <td class="col-unit">
        <select class="form-control unit-select"></select>
      </td>

      <td class="col-location">
        <select class="form-control location-select"></select>
      </td>

      <td class="col-store">
        <select class="form-control store-select"></select>
      </td>

      <td class="col-qty">
        <input type="number" step="0.0001" class="form-control text-end ln-qty" value="${line?.qty ?? 1}">
      </td>

      <td class="col-unitprice">
        <input type="number" step="0.0001" class="form-control text-end ln-unitprice" value="${line?.unit_price ?? 0}">
      </td>

      <td class="col-discpct">
        <input type="number" step="0.0001" class="form-control text-end ln-discpct" value="${line?.discount_percent ?? ''}" placeholder="%">
      </td>

      <td class="col-discamt">
        <input type="number" step="0.01" class="form-control text-end ln-discamt" value="${line?.discount_amount ?? 0}" readonly>
      </td>

      <td class="col-taxcode">
        <select class="form-control taxcode-select"></select>
      </td>

      <td class="col-taxrate">
        <input type="number" step="0.0001" class="form-control text-end ln-taxrate" value="${line?.tax_rate ?? ''}" readonly>
      </td>

      <td class="col-taxamt">
        <input type="number" step="0.01" class="form-control text-end ln-taxamt" value="${line?.tax_amount ?? 0}" readonly>
      </td>

      <td class="col-shipping">
        <input type="number" step="0.01" class="form-control text-end ln-shipping" value="${line?.shipping_amount ?? 0}">
      </td>

      <td class="col-othercharges">
        <input type="number" step="0.01" class="form-control text-end ln-othercharges" value="${line?.other_charges_amount ?? 0}">
      </td>

      <td class="col-total">
        <input type="number" step="0.01" class="form-control text-end ln-total" value="${line?.line_total ?? 0}" readonly>
      </td>

      <td class="col-leadtime">
        <input type="number" step="1" class="form-control text-end ln-leadtime" value="${line?.lead_time_days ?? ''}" placeholder="Days">
      </td>

      <td class="col-expdelivery">
        <input type="date" class="form-control ln-expdelivery" value="${line?.expected_delivery_date ?? ''}">
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

  const $location = tr.find('.location-select');
  initS2($location, locationsUrl, 'Location...');
  if(line?.location_id && line?.location_label){
    setS2Value($location, line.location_id, line.location_label);
  }

  const $store = tr.find('.store-select');
  initS2($store, storesUrl, 'Store...');
  if(line?.store_id && line?.store_label){
    setS2Value($store, line.store_id, line.store_label);
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
  let discount = 0;
  let tax = 0;
  let shipping = 0;
  let otherCharges = 0;
  let total = 0;

  $('#linesTbody tr').each(function(){
    const $tr = $(this);

    const qty          = parseFloat($tr.find('.ln-qty').val() || '0');
    const unit         = parseFloat($tr.find('.ln-unitprice').val() || '0');
    const discPct      = parseFloat($tr.find('.ln-discpct').val() || '0');
    const taxRate      = parseFloat($tr.find('.ln-taxrate').val() || '0');
    const shipAmt      = parseFloat($tr.find('.ln-shipping').val() || '0');
    const otherAmt     = parseFloat($tr.find('.ln-othercharges').val() || '0');

    const gross = (isNaN(qty) ? 0 : qty) * (isNaN(unit) ? 0 : unit);
    const discAmt = (isNaN(discPct) || discPct <= 0) ? 0 : (gross * discPct / 100);
    const taxBase = gross - discAmt;
    const taxAmt = (isNaN(taxRate) || taxRate <= 0) ? 0 : (taxBase * taxRate / 100);
    const lineTotal = taxBase + taxAmt + (isNaN(shipAmt) ? 0 : shipAmt) + (isNaN(otherAmt) ? 0 : otherAmt);

    $tr.find('.ln-discamt').val(discAmt.toFixed(2));
    $tr.find('.ln-taxamt').val(taxAmt.toFixed(2));
    $tr.find('.ln-total').val(lineTotal.toFixed(2));

    subtotal += gross;
    discount += discAmt;
    tax += taxAmt;
    shipping += (isNaN(shipAmt) ? 0 : shipAmt);
    otherCharges += (isNaN(otherAmt) ? 0 : otherAmt);
    total += lineTotal;
  });

  $('#subTotalLbl').text(subtotal.toFixed(2));
  $('#discountTotalLbl').text(discount.toFixed(2));
  $('#taxTotalLbl').text(tax.toFixed(2));
  $('#shippingTotalLbl').text(shipping.toFixed(2));
  $('#otherChargesTotalLbl').text(otherCharges.toFixed(2));
  $('#grandTotalLbl').text(total.toFixed(2));
}

$(document).on('input', '.ln-qty,.ln-unitprice,.ln-discpct,.ln-shipping,.ln-othercharges', recalcTotals);

$(document).on('select2:select', '.taxcode-select', function(e){
  const data = e.params.data || {};
  const $tr = $(this).closest('tr');

  let rate = parseFloat(data.rate || 0);
  if(parseInt(data.is_exempt || 0) === 1 || parseInt(data.is_out_of_scope || 0) === 1){
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
  $('#poModalTitle').text('New Purchase Order');
  $('#poModal').modal('show');
});

$('#supplier_id').on('change', function(){
  const supplierId = $(this).val();

  try { $('#supplier_contact_id').select2('destroy'); } catch(err) {}
  $('#supplier_contact_id').empty();

  initS2($('#supplier_contact_id'), supplierContactsUrl, 'Supplier contact...', function(){
    return { supplier_id: supplierId || '' };
  });
});

$(document).on('select2:select', '#supplier_id', function(e){
  const d = e.params.data || {};
  if(d.default_currency && !$('#currency_code').val()){
    $('#currency_code').val(d.default_currency);
  }
  if(d.payment_terms && !$('#payment_terms').val()){
    $('#payment_terms').val(d.payment_terms);
  }
});

$(document).on('select2:select', '#quotation_id', function(e){
  const d = e.params.data || {};
  if(d.supplier_id){
    setS2Value($('#supplier_id'), d.supplier_id, d.supplier_name || 'Supplier');
  }
});

$('#loadQuotationBtn').on('click', function(){
  const quotationId = $('#quotation_id').val();
  if(!quotationId) return toastErr('Select a supplier quotation first.');

  $.get(`${baseUrl}/create-from-quotation/${quotationId}`)
    .done(function(res){
      const h = res.header || {};

      $('#purchase_requisition_id').val(h.purchase_requisition_id || '');
      $('#rfq_id').val(h.rfq_id || '');
      $('#supplier_quotation_id').val(h.supplier_quotation_id || '');

      if(h.supplier_id && h.supplier_label){
        setS2Value($('#supplier_id'), h.supplier_id, h.supplier_label);
      }

      $('#po_date').val(h.po_date || '');
      $('#expected_delivery_date').val(h.expected_delivery_date || '');
      $('#currency_code').val(h.currency_code || '');
      $('#fx_rate').val(h.fx_rate || '');
      $('#payment_terms').val(h.payment_terms || '');
      $('#incoterms').val(h.incoterms || '');
      $('#reference').val(h.reference || '');
      $('#notes').val(h.notes || '');
      $('#internal_notes').val(h.internal_notes || '');

      $('#linesTbody').html('');
      (res.lines || []).forEach(ln => addLine(ln));
      if($('#linesTbody tr').length < 1) addLine();

      recalcTotals();
      toastOk('Quotation loaded.');
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load quotation.');
    });
});

$('#savePoBtn').on('click', function(){
  const id = $('#po_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : `${baseUrl}`;

  const payload = {
    purchase_requisition_id: $('#purchase_requisition_id').val() || null,
    rfq_id: $('#rfq_id').val() || null,
    supplier_quotation_id: $('#supplier_quotation_id').val() || null,
    supplier_id: $('#supplier_id').val(),
    supplier_contact_id: $('#supplier_contact_id').val() || null,
    supplier_po_ref: $('#supplier_po_ref').val() || null,
    po_date: $('#po_date').val(),
    expected_delivery_date: $('#expected_delivery_date').val() || null,
    currency_code: $('#currency_code').val() || null,
    fx_rate: $('#fx_rate').val() || null,
    delivery_location_id: $('#delivery_location_id').val() || null,
    delivery_store_id: $('#delivery_store_id').val() || null,
    bill_to_location_id: $('#bill_to_location_id').val() || null,
    payment_terms: $('#payment_terms').val() || null,
    incoterms: $('#incoterms').val() || null,
    reference: $('#reference').val() || null,
    notes: $('#notes').val() || null,
    internal_notes: $('#internal_notes').val() || null,
    lines: []
  };

  $('#linesTbody tr').each(function(){
    const $tr = $(this);

    payload.lines.push({
      purchase_requisition_line_id: $tr.find('.pr-line-id').val() || null,
      rfq_line_id: $tr.find('.rfq-line-id').val() || null,
      supplier_quotation_line_id: $tr.find('.sq-line-id').val() || null,
      product_id: $tr.find('.product-select').val() || null,
      description: $tr.find('.description').val() || null,
      unit_id: $tr.find('.unit-select').val() || null,
      location_id: $tr.find('.location-select').val() || null,
      store_id: $tr.find('.store-select').val() || null,
      qty: $tr.find('.ln-qty').val(),
      unit_price: $tr.find('.ln-unitprice').val(),
      discount_percent: $tr.find('.ln-discpct').val() || null,
      tax_code_id: $tr.find('.taxcode-select').val() || null,
      tax_rate: $tr.find('.ln-taxrate').val() || null,
      shipping_amount: $tr.find('.ln-shipping').val() || null,
      other_charges_amount: $tr.find('.ln-othercharges').val() || null,
      lead_time_days: $tr.find('.ln-leadtime').val() || null,
      expected_delivery_date: $tr.find('.ln-expdelivery').val() || null,
      remarks: $tr.find('.ln-remarks').val() || null
    });
  });

  if(!payload.supplier_id) return toastErr('Supplier is required.');
  if(!payload.po_date) return toastErr('PO date is required.');
  if(!payload.lines.length) return toastErr('At least one line is required.');

  $.ajax({ url, method, data: payload })
    .done(function(res){
      $('#poModal').modal('hide');
      toastOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-po', function(){
  const id = $(this).data('id');
  resetModal();

  $.get(`${baseUrl}/${id}`)
    .done(function(res){
      const po = res.purchase_order || {};

      $('#poModalTitle').text('Edit Purchase Order');
      $('#po_id').val(po.id);
      $('#purchase_requisition_id').val(po.purchase_requisition_id || '');
      $('#rfq_id').val(po.rfq_id || '');
      $('#supplier_quotation_id').val(po.supplier_quotation_id || '');
      $('#supplier_po_ref').val(po.supplier_po_ref || '');
      $('#po_date').val(po.po_date || '');
      $('#expected_delivery_date').val(po.expected_delivery_date || '');
      $('#currency_code').val(po.currency_code || '');
      $('#fx_rate').val(po.fx_rate || '');
      $('#payment_terms').val(po.payment_terms || '');
      $('#incoterms').val(po.incoterms || '');
      $('#reference').val(po.reference || '');
      $('#notes').val(po.notes || '');
      $('#internal_notes').val(po.internal_notes || '');
      $('#po_status_badge').html(po.status ? `<span class="badge bg-secondary">${String(po.status).toUpperCase()}</span>` : '');

      if(po.supplier_id){
        setS2Value($('#supplier_id'), po.supplier_id, `Supplier #${po.supplier_id}`);
      }
      if(po.supplier_contact_id){
        setS2Value($('#supplier_contact_id'), po.supplier_contact_id, `Contact #${po.supplier_contact_id}`);
      }
      if(po.supplier_quotation_id){
        setS2Value($('#quotation_id'), po.supplier_quotation_id, `Quotation #${po.supplier_quotation_id}`);
      }
      if(po.delivery_location_id){
        setS2Value($('#delivery_location_id'), po.delivery_location_id, `Location #${po.delivery_location_id}`);
      }
      if(po.delivery_store_id){
        setS2Value($('#delivery_store_id'), po.delivery_store_id, `Store #${po.delivery_store_id}`);
      }
      if(po.bill_to_location_id){
        setS2Value($('#bill_to_location_id'), po.bill_to_location_id, `Location #${po.bill_to_location_id}`);
      }

      $('#linesTbody').html('');
      (res.lines || []).forEach(ln => addLine(ln));
      if($('#linesTbody tr').length < 1) addLine();

      recalcTotals();
      $('#poModal').modal('show');
    })
    .fail(function(){
      toastErr('Could not load purchase order.');
    });
});

$(document).on('click', '.btn-view-po', function(){
  const id = $(this).data('id');

  $.get(`${baseUrl}/${id}/details`)
    .done(function(res){
      const h = res.header || {};
      const lines = res.lines || [];

      $('#vw_po_no').text(h.po_no || '—');
      $('#vw_supplier_po_ref').text(h.supplier_po_ref || '—');
      $('#vw_po_date').text(h.po_date || '—');
      $('#vw_expected_delivery_date').text(h.expected_delivery_date || '—');
      $('#vw_status').text(h.status || '—');
      $('#vw_supplier').text(h.supplier || '—');
      $('#vw_contact_name').text(h.contact_name || '—');
      $('#vw_contact_email').text(h.contact_email || '—');
      $('#vw_contact_phone').text(h.contact_phone || '—');
      $('#vw_purchase_requisition_no').text(h.purchase_requisition_no || '—');
      $('#vw_rfq_no').text(h.rfq_no || '—');
      $('#vw_quotation_no').text(h.quotation_no || '—');
      $('#vw_currency_code').text(h.currency_code || '—');
      $('#vw_fx_rate').text(h.fx_rate !== null ? Number(h.fx_rate).toFixed(6) : '—');
      $('#vw_payment_terms').text(h.payment_terms || '—');
      $('#vw_incoterms').text(h.incoterms || '—');
      $('#vw_reference').text(h.reference || '—');
      $('#vw_notes').text(h.notes || '—');
      $('#vw_internal_notes').text(h.internal_notes || '—');
      $('#vw_delivery_location').text(h.delivery_location || '—');
      $('#vw_delivery_store').text(h.delivery_store || '—');
      $('#vw_bill_to_location').text(h.bill_to_location || '—');

      $('#vw_subtotal').text(Number(h.subtotal || 0).toFixed(2));
      $('#vw_discount_total').text(Number(h.discount_total || 0).toFixed(2));
      $('#vw_tax_total').text(Number(h.tax_total || 0).toFixed(2));
      $('#vw_shipping_total').text(Number(h.shipping_total || 0).toFixed(2));
      $('#vw_other_charges_total').text(Number(h.other_charges_total || 0).toFixed(2));
      $('#vw_total_amount').text(Number(h.total_amount || 0).toFixed(2));

      $('#vw_pdf_link').attr('href', `${baseUrl}/${id}/pdf`);

      let html = '';
      if(!lines.length){
        html = `<tr><td colspan="15" class="text-center text-muted">No lines found</td></tr>`;
      } else {
        lines.forEach(function(l){
          html += `
            <tr>
              <td>${l.product_code ? l.product_code + ' - ' : ''}${l.product_name || ''}</td>
              <td>${l.description || '—'}</td>
              <td>${l.unit_name ? l.unit_name + (l.unit_symbol ? ' (' + l.unit_symbol + ')' : '') : '—'}</td>
              <td class="text-end">${Number(l.qty || 0).toFixed(4)}</td>
              <td class="text-end">${Number(l.unit_price || 0).toFixed(4)}</td>
              <td class="text-end">${Number(l.discount_percent || 0).toFixed(4)}</td>
              <td class="text-end">${Number(l.discount_amount || 0).toFixed(2)}</td>
              <td>${l.tax_code_code ? l.tax_code_code + ' - ' : ''}${l.tax_code_name || ''}</td>
              <td class="text-end">${Number(l.tax_rate || 0).toFixed(4)}</td>
              <td class="text-end">${Number(l.tax_amount || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.shipping_amount || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.other_charges_amount || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.line_total || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.received_qty || 0).toFixed(4)}</td>
              <td class="text-end">${Number(l.billed_qty || 0).toFixed(4)}</td>
            </tr>
          `;
        });
      }

      $('#vw_lines').html(html);
      $('#poShowModal').modal('show');
    })
    .fail(function(xhr){
      toastErr(xhr?.responseJSON?.message || 'Could not load PO details.');
    });
});

$(document).on('click', '.btn-del-po', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Delete draft purchase order?',
    text:'This will soft-delete the draft purchase order.'
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

$(document).on('click', '.btn-approve-po', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Approve purchase order?',
    text:'This will mark the PO as approved.'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/approve`)
      .done(function(res){
        toastOk(res.message || 'Approved');
        refreshDT();
      })
      .fail(function(xhr){
        toastErr(xhr?.responseJSON?.message || 'Approve failed.');
      });
  });
});

$(document).on('click', '.btn-issue-po', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Issue purchase order?',
    text:'This will mark the PO as issued to supplier.'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/issue`)
      .done(function(res){
        toastOk(res.message || 'Issued');
        refreshDT();
      })
      .fail(function(xhr){
        toastErr(xhr?.responseJSON?.message || 'Issue failed.');
      });
  });
});

$(document).on('click', '.btn-close-po', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Close purchase order?',
    text:'This will close the PO.'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/close`)
      .done(function(res){
        toastOk(res.message || 'Closed');
        refreshDT();
      })
      .fail(function(xhr){
        toastErr(xhr?.responseJSON?.message || 'Close failed.');
      });
  });
});

$(document).on('click', '.btn-cancel-po', function(){
  const id = $(this).data('id');

  confirmBox({
    title:'Cancel purchase order?',
    text:'This will cancel the PO.'
  }).then(function(r){
    if(!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/cancel`)
      .done(function(res){
        toastOk(res.message || 'Cancelled');
        refreshDT();
      })
      .fail(function(xhr){
        toastErr(xhr?.responseJSON?.message || 'Cancel failed.');
      });
  });
});

$(function(){
  initDT();

  initS2($('#quotation_id'), quotationsUrl, 'Accepted/Reviewed quotation...');
  initS2($('#supplier_id'), suppliersUrl, 'Supplier...');
  initS2($('#delivery_location_id'), locationsUrl, 'Delivery location...');
  initS2($('#delivery_store_id'), storesUrl, 'Delivery store...');
  initS2($('#bill_to_location_id'), locationsUrl, 'Bill-to location...');
});
</script>
@endpush