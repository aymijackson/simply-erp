@extends('layouts.master')

@section('title','Supplier Bills')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Supplier Bills</h1>
      <small class="text-muted">Finance / Accounts Payable</small>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-danger d-none" id="bulkDeleteBtn">
        <i class="fas fa-trash"></i> Delete Selected
      </button>
      <button class="btn btn-primary" id="createBtn">
        <i class="fas fa-plus"></i> New Supplier Bill
      </button>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="text-muted small">Status</label>
          <select class="form-control" id="f_status">
            <option value="">All</option>
            <option value="draft">Draft</option>
            <option value="posted">Posted</option>
            <option value="part_paid">Part Paid</option>
            <option value="paid">Paid</option>
            <option value="voided">Voided</option>
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

        <div class="col-md-4">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="f_q" placeholder="bill no, supplier, reference, memo...">
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
        Draft bills can be edited or deleted. Posted bills can be voided for audit. Payments reduce the balance due.
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="billTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
              <th style="width:70px;">ID</th>
              <th style="width:170px;">Bill No</th>
              <th style="width:120px;">Bill Date</th>
              <th style="width:120px;">Due Date</th>
              <th>Supplier</th>
              <th style="width:70px;">Curr</th>
              <th style="width:140px;" class="text-end">Total</th>
              <th style="width:140px;" class="text-end">Balance</th>
              <th style="width:110px;">Status</th>
              <th style="width:260px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <small class="text-muted">
        Posting creates a Journal Entry and links it via <code>journal_entry_id</code> where posting is implemented.
      </small>
    </div>
  </div>
</div>

@include('finance.supplier_bills.partials.modal')
@include('finance.supplier_bills.partials.show_modal')
@endsection

@push('styles')
<style>
  .select2-container--bootstrap-5 .select2-dropdown { z-index: 2060 !important; }
  .select2-container--open { z-index: 2060 !important; }

  .modal-open .modal { overflow: visible !important; }
  .modal .modal-body { overflow: visible !important; }

  .select2-container--bootstrap-5 .select2-selection--single {
    min-height: 38px;
  }

  .bill-source-box {
    border: 1px dashed #cbd5e1;
    border-radius: .75rem;
    padding: .75rem;
    background: #f8fafc;
  }
</style>
@endpush

@push('scripts')
<script>
$.ajaxSetup({
  headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}
});

const dtUrl           = "{{ url('admin/finance/supplier-bills/datatable') }}";
const storeUrl        = "{{ url('admin/finance/supplier-bills') }}";
const baseUrl         = "{{ url('admin/finance/supplier-bills') }}";
const bulkUrl         = "{{ url('admin/finance/supplier-bills/bulk-delete') }}";

const supplierUrl     = "{{ url('admin/finance/lookups/suppliers') }}";
const glUrl           = "{{ url('admin/finance/lookups/gl-accounts') }}";
const curUrl          = "{{ url('admin/finance/lookups/currencies') }}";
const apControlUrl    = "{{ url('admin/finance/lookups/ap-control-accounts') }}";

const sourceLookupUrl = "{{ url('admin/finance/supplier-bills/lookups/source-records') }}";
const sourceLoadUrl   = "{{ url('admin/finance/supplier-bills/load-source') }}";

let DT = null;

function swalOk(msg){
  if (typeof Swal !== 'undefined' && Swal.fire) {
    return Swal.fire({ icon:'success', title:'Success', text: msg || 'Done.' });
  }
  alert(msg || 'Done.');
}

function swalErr(msg){
  if (typeof Swal !== 'undefined' && Swal.fire) {
    return Swal.fire({ icon:'error', title:'Error', text: msg || 'Something went wrong.' });
  }
  alert(msg || 'Error');
}

function swalAsk(opts){
  if (typeof Swal !== 'undefined' && Swal.fire) {
    return Swal.fire(opts);
  }
  return Promise.resolve({isConfirmed: confirm(opts?.title || 'Confirm?')});
}

function initDT(){
  DT = $('#billTable').DataTable({
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
      {
        data:'id',
        orderable:false,
        searchable:false,
        render:(id)=>`<input type="checkbox" class="row-check" value="${id}">`
      },
      {data:'id'},
      {data:'bill_no'},
      {data:'bill_date'},
      {data:'due_date'},
      {data:'vendor'},
      {data:'currency'},
      {data:'total', className:'text-end'},
      {data:'balance', className:'text-end'},
      {data:'status', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[1,'desc']]
  });
}

function refreshDT(){
  if (DT) DT.ajax.reload(null,false);
}

$('#applyBtn').on('click', refreshDT);

$('#resetBtn').on('click', function(){
  $('#f_status').val('');
  $('#f_from').val('');
  $('#f_to').val('');
  $('#f_q').val('');
  refreshDT();
});

$('#checkAll').on('change', function(){
  $('.row-check').prop('checked', this.checked).trigger('change');
});

$(document).on('change', '.row-check', function(){
  const any = $('.row-check:checked').length > 0;
  $('#bulkDeleteBtn').toggleClass('d-none', !any);
});

$('#bulkDeleteBtn').on('click', async function(){
  const ids = $('.row-check:checked').map((i,el)=>$(el).val()).get();

  const r = await swalAsk({
    icon:'warning',
    title:'Delete selected?',
    text:'This will soft-delete selected draft bills.',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  });

  if(!r.isConfirmed) return;

  $.post(bulkUrl, {ids})
    .done(res => {
      swalOk(res.message || 'Deleted.');
      $('#bulkDeleteBtn').addClass('d-none');
      $('#checkAll').prop('checked', false);
      refreshDT();
    })
    .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Failed to delete.'));
});

function s2($el, url, placeholder, extraDataFn = null){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder,
    allowClear:true,
    dropdownParent: $('#billModal'),
    ajax:{
      url,
      dataType:'json',
      delay:200,
      data:function(params){
        let payload = { q: params.term || '' };
        if (typeof extraDataFn === 'function') {
          payload = Object.assign(payload, extraDataFn());
        }
        return payload;
      },
      processResults:function(d){ return d; },
      cache:true,
      error:function(xhr){
        console.error('Select2 lookup failed:', url, xhr);
      }
    }
  });
}

function setSelect2Value($el, id, text){
  if (!id) return;
  const opt = new Option(text || id, id, true, true);
  $el.append(opt).trigger('change');
}

function initModalSelects(){
  s2($('#supplier_id'), supplierUrl, 'Select supplier...');
  s2($('#currency_code'), curUrl, 'Select currency...');
  s2($('#ap_control_account_id'), apControlUrl, 'Select AP control account (optional)...');

  s2(
    $('#source_id'),
    sourceLookupUrl,
    'Select source record...',
    function(){
      return {
        source_type: $('#source_type').val() || ''
      };
    }
  );
}

$('#billModal').on('shown.bs.modal', function(){
  setTimeout(() => {
    $(this).find('select').trigger('change.select2');
  }, 50);
});

$('#supplier_id').on('select2:select', function(e){
  const d = e.params.data || {};
  if (!$('#vendor_name').val().trim()) {
    $('#vendor_name').val(d.text || '');
  }
});

$('#source_type').on('change', function(){
  $('#source_id').empty().trigger('change');
});

function resetModal(){
  $('#billForm')[0].reset();
  $('#bill_id').val('');
  $('#bill_status_badge').html('');
  $('#linesTbody').html('');

  $('#supplier_id').empty().trigger('change');
  $('#currency_code').empty().trigger('change');
  $('#ap_control_account_id').empty().trigger('change');
  $('#source_type').val('');
  $('#source_id').empty().trigger('change');
  $('#vendor_name').val('');

  addLine();
  addLine();
  recalcTotals();
}

function addLine(line=null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <input type="hidden" name="lines[${idx}][purchase_requisition_line_id]" value="${line?.purchase_requisition_line_id ?? ''}">
      <input type="hidden" name="lines[${idx}][rfq_line_id]" value="${line?.rfq_line_id ?? ''}">
      <input type="hidden" name="lines[${idx}][supplier_quotation_line_id]" value="${line?.supplier_quotation_line_id ?? ''}">
      <input type="hidden" name="lines[${idx}][purchase_order_line_id]" value="${line?.purchase_order_line_id ?? ''}">
      <input type="hidden" name="lines[${idx}][goods_receipt_line_id]" value="${line?.goods_receipt_line_id ?? ''}">

      <td style="width:30%">
        <select class="form-control gl-line" name="lines[${idx}][gl_account_id]" required></select>
      </td>
      <td>
        <input type="text" class="form-control" name="lines[${idx}][description]" value="${line?.description ?? ''}" placeholder="Description">
      </td>
      <td style="width:10%">
        <input type="number" step="0.0001" min="0" class="form-control text-end line-qty" name="lines[${idx}][qty]" value="${line?.qty ?? 1}">
      </td>
      <td style="width:12%">
        <input type="number" step="0.0001" min="0" class="form-control text-end line-unit" name="lines[${idx}][unit_cost]" value="${line?.unit_cost ?? 0}">
      </td>
      <td style="width:10%">
        <input type="number" step="0.0001" min="0" class="form-control text-end line-taxrate" name="lines[${idx}][tax_rate]" value="${line?.tax_rate ?? ''}" placeholder="%">
      </td>
      <td style="width:12%">
        <input type="number" step="0.01" class="form-control text-end line-total" name="lines[${idx}][line_total]" value="${line?.line_total ?? 0}" readonly>
      </td>
      <td style="width:6%">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-line">
          <i class="fas fa-times"></i>
        </button>
      </td>
    </tr>
  `);

  $('#linesTbody').append(tr);

  const $gl = tr.find('select.gl-line');
  s2($gl, glUrl, 'GL account...');

  if (line?.gl_account_id && line?.gl_account_label) {
    setSelect2Value($gl, line.gl_account_id, line.gl_account_label);
  }

  recalcTotals();
}

function recalcTotals(){
  let subtotal = 0;
  let taxTotal = 0;
  let total = 0;

  $('#linesTbody tr').each(function(){
    const $tr = $(this);

    const qty  = parseFloat($tr.find('.line-qty').val() || '0');
    const unit = parseFloat($tr.find('.line-unit').val() || '0');
    const rate = parseFloat($tr.find('.line-taxrate').val() || '0');

    const base = (isNaN(qty)?0:qty) * (isNaN(unit)?0:unit);
    const tax  = base * ((isNaN(rate)?0:rate)/100);
    const lineTotal = base + tax;

    $tr.find('.line-total').val(lineTotal.toFixed(2));

    subtotal += base;
    taxTotal += tax;
    total    += lineTotal;
  });

  $('#subTotalLbl').text(subtotal.toFixed(2));
  $('#taxTotalLbl').text(taxTotal.toFixed(2));
  $('#grandTotalLbl').text(total.toFixed(2));
}

$(document).on('input', '.line-qty,.line-unit,.line-taxrate', recalcTotals);
$(document).on('click', '#addLineBtn', () => addLine());

$(document).on('click', '.btn-del-line', function(){
  $(this).closest('tr').remove();
  if ($('#linesTbody tr').length < 1) addLine();
  recalcTotals();
});

$('#createBtn').on('click', function(){
  resetModal();
  $('#billModalTitle').text('New Supplier Bill');
  $('#billModal').modal('show');
});

$('#loadSourceBtn').on('click', function(){
  const sourceType = $('#source_type').val();
  const sourceId   = $('#source_id').val();

  if (!sourceType || !sourceId) {
    return swalErr('Please select a source type and source record.');
  }

  $.get(sourceLoadUrl, {
    source_type: sourceType,
    source_id: sourceId
  })
  .done(function(res){
    const h = res.header || {};
    const lines = res.lines || [];

    $('#supplier_id').empty().trigger('change');
    $('#currency_code').empty().trigger('change');
    $('#ap_control_account_id').empty().trigger('change');

    if (h.supplier_id && h.supplier_label) {
      setSelect2Value($('#supplier_id'), h.supplier_id, h.supplier_label);
      $('#vendor_name').val(h.supplier_label || '');
    } else {
      $('#vendor_name').val(h.vendor_name || '');
    }

    if (h.currency_code) {
      setSelect2Value($('#currency_code'), h.currency_code, h.currency_code);
    }

    if (h.ap_control_account_id && h.ap_control_account_label) {
      setSelect2Value($('#ap_control_account_id'), h.ap_control_account_id, h.ap_control_account_label);
    }

    $('#bill_date').val(h.bill_date || '');
    $('#due_date').val(h.due_date || '');
    $('#reference').val(h.reference || '');
    $('#memo').val(h.memo || '');
    $('#fx_rate').val(h.fx_rate || 1);

    $('#linesTbody').html('');
    (lines || []).forEach(line => addLine(line));

    if ($('#linesTbody tr').length < 1) addLine();

    recalcTotals();
    swalOk('Source loaded successfully.');
  })
  .fail(function(xhr){
    swalErr(xhr?.responseJSON?.message || 'Could not load source record.');
  });
});

$('#saveBillBtn').on('click', function(){
  const id = $('#bill_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : storeUrl;

  if (!$('#bill_date').val()) return swalErr('Bill date is required.');
  if (!$('#supplier_id').val() && !$('#vendor_name').val().trim()) return swalErr('Supplier or Vendor Name is required.');
  if ($('#linesTbody tr').length < 1) return swalErr('Add at least one line.');

  $.ajax({
    url,
    method,
    data: $('#billForm').serialize()
  })
  .done(res => {
    $('#billModal').modal('hide');
    swalOk(res.message || 'Saved.');
    refreshDT();
  })
  .fail(xhr => {
    swalErr(xhr?.responseJSON?.message || 'Failed to save.');
  });
});

$(document).on('click', '.btn-edit-bill', function(){
  resetModal();

  const bill = $(this).data('json');

  $('#billModalTitle').text('Edit Supplier Bill');
  $('#bill_id').val(bill.id);

  $('#bill_no').val(bill.bill_no || '');
  $('#bill_date').val(bill.bill_date || '');
  $('#due_date').val(bill.due_date || '');
  $('#reference').val(bill.reference || '');
  $('#memo').val(bill.memo || '');
  $('#fx_rate').val(bill.fx_rate || '');
  $('#vendor_name').val(bill.vendor_name || '');

  $('#bill_status_badge').html(
    bill.status ? `<span class="badge bg-secondary">${String(bill.status).toUpperCase()}</span>` : ''
  );

  if (bill.source_type) {
    $('#source_type').val(bill.source_type);
  }

  if (bill.source_id && bill.source_label) {
    setSelect2Value($('#source_id'), bill.source_id, bill.source_label);
  }

  if (bill.supplier_id && bill.supplier_label) {
    setSelect2Value($('#supplier_id'), bill.supplier_id, bill.supplier_label);
    if (!$('#vendor_name').val().trim()) {
      $('#vendor_name').val(bill.supplier_label || '');
    }
  }

  if (bill.currency_code) {
    setSelect2Value($('#currency_code'), bill.currency_code, bill.currency_code);
  }

  if (bill.ap_control_account_id && bill.ap_control_account_label) {
    setSelect2Value($('#ap_control_account_id'), bill.ap_control_account_id, bill.ap_control_account_label);
  }

  $.get(`${baseUrl}/${bill.id}/lines`)
    .done(r => {
      $('#linesTbody').html('');
      (r.lines || []).forEach(ln => addLine(ln));
      if ($('#linesTbody tr').length < 1) addLine();
      recalcTotals();
      $('#billModal').modal('show');
    })
    .fail(() => swalErr('Could not load bill lines.'));
});

$(document).on('click', '.btn-del-bill', async function(){
  const id = $(this).data('id');

  const r = await swalAsk({
    icon:'warning',
    title:'Delete bill?',
    text:'Draft only recommended. This is a soft delete.',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  });

  if(!r.isConfirmed) return;

  $.ajax({url:`${baseUrl}/${id}`, method:'DELETE'})
    .done(res => {
      swalOk(res.message || 'Deleted.');
      refreshDT();
    })
    .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Delete failed.'));
});

$(document).on('click', '.btn-post-bill', async function(){
  const id = $(this).data('id');

  const r = await swalAsk({
    icon:'warning',
    title:'Post bill?',
    text:'This will create a journal entry and lock the bill.',
    showCancelButton:true,
    confirmButtonText:'Yes, post'
  });

  if(!r.isConfirmed) return;

  $.post(`${baseUrl}/${id}/post`)
    .done(res => {
      swalOk(res.message || 'Posted.');
      refreshDT();
    })
    .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Post failed.'));
});

$(document).on('click', '.btn-void-bill', async function(){
  const id = $(this).data('id');

  const r = await swalAsk({
    icon:'warning',
    title:'Void bill?',
    text:'Voided bills remain for audit.',
    showCancelButton:true,
    confirmButtonText:'Yes, void'
  });

  if(!r.isConfirmed) return;

  $.post(`${baseUrl}/${id}/void`)
    .done(res => {
      swalOk(res.message || 'Voided.');
      refreshDT();
    })
    .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Void failed.'));
});

$(document).on('click', '.btn-view-bill', function(){
  const id = $(this).data('id');

  $.get(`${baseUrl}/${id}`)
    .done(function(res){
      const b = res.bill || {};
      const lines = res.lines || [];

      $('#vw_bill_no').text(b.bill_no || '—');
      $('#vw_bill_date').text(b.bill_date || '—');
      $('#vw_due_date').text(b.due_date || '—');
      $('#vw_supplier').text(b.supplier_name || b.vendor_name || '—');
      $('#vw_vendor_name').text(b.vendor_name || '—');
      $('#vw_currency').text(b.currency_code || '—');
      $('#vw_fx_rate').text(b.fx_rate ?? '—');
      $('#vw_reference').text(b.reference || '—');
      $('#vw_memo').text(b.memo || '—');
      $('#vw_status').text((b.status || '—').toUpperCase());
      $('#vw_source').text(
        b.source_type
          ? `${String(b.source_type).replaceAll('_', ' ')}${b.source_id ? ' #' + b.source_id : ''}`
          : 'Manual'
      );
      $('#vw_payable_account').text(b.payable_account_label || '—');
      $('#vw_journal_entry').text(b.journal_entry_id || '—');
      $('#vw_subtotal').text(Number(b.subtotal || 0).toFixed(2));
      $('#vw_tax_total').text(Number(b.tax_total || 0).toFixed(2));
      $('#vw_total_amount').text(Number(b.total_amount || 0).toFixed(2));
      $('#vw_amount_paid').text(Number(b.amount_paid || 0).toFixed(2));
      $('#vw_balance_due').text(Number(b.balance_due || 0).toFixed(2));
      $('#vw_pdf_link').attr('href', `${baseUrl}/${id}/pdf`);

      let html = '';
      if (!lines.length) {
        html = `<tr><td colspan="7" class="text-center text-muted">No lines found</td></tr>`;
      } else {
        lines.forEach(function(l){
          html += `
            <tr>
              <td>${l.gl_account || '—'}</td>
              <td>${l.description || '—'}</td>
              <td class="text-end">${Number(l.qty || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.unit_cost || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.tax_amount || 0).toFixed(2)}</td>
              <td class="text-end">${Number(l.line_total || 0).toFixed(2)}</td>
              <td>${l.memo || '—'}</td>
            </tr>
          `;
        });
      }

      $('#vw_lines').html(html);
      $('#billShowModal').modal('show');
    })
    .fail(function(xhr){
      swalErr(xhr?.responseJSON?.message || 'Could not load supplier bill.');
    });
});
$(function(){
  initModalSelects();
  initDT();
});
</script>
@endpush