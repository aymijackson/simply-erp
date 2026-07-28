@extends('layouts.master')

@section('title','Supplier Credits')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .select2-container { width:100% !important; }
  .select2-container--bootstrap-5 .select2-dropdown { z-index: 2056; }
  .modal { overflow: visible; }

  .cr-modal-body {
    overflow-x: hidden;
  }

  .cr-table-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: .25rem;
  }

  .cr-dist-table,
  .cr-app-table {
    min-width: 1500px;
    table-layout: fixed;
  }

  .cr-dist-table th,
  .cr-dist-table td,
  .cr-app-table th,
  .cr-app-table td {
    vertical-align: middle;
    white-space: normal;
  }

  .cr-dist-table .form-control,
  .cr-dist-table .select2-container,
  .cr-app-table .form-control,
  .cr-app-table .select2-container {
    min-width: 100%;
  }

  .cr-dist-table .col-gl         { min-width: 220px; width: 220px; }
  .cr-dist-table .col-desc       { min-width: 220px; width: 220px; }
  .cr-dist-table .col-qty        { min-width: 100px; width: 100px; }
  .cr-dist-table .col-unit       { min-width: 120px; width: 120px; }
  .cr-dist-table .col-taxcode    { min-width: 180px; width: 180px; }
  .cr-dist-table .col-taxrate    { min-width: 180px; width: 180px; }
  .cr-dist-table .col-taxpct     { min-width: 100px; width: 100px; }
  .cr-dist-table .col-line       { min-width: 130px; width: 130px; }
  .cr-dist-table .col-action     { min-width: 60px; width: 60px; }

  .cr-app-table .col-bill        { min-width: 420px; width: 420px; }
  .cr-app-table .col-amt         { min-width: 160px; width: 160px; }
  .cr-app-table .col-app-action  { min-width: 60px; width: 60px; }

  .cr-dist-table .text-end,
  .cr-app-table .text-end {
    text-align: right !important;
  }

  @media (max-width: 991.98px) {
    .modal-dialog.modal-xl {
      margin: .5rem;
    }
  }
</style>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Supplier Credits</h1>
      <small class="text-muted">Finance / Payables</small>
    </div>

    <button class="btn btn-primary" id="createBtn">
      <i class="fas fa-plus"></i> New Credit
    </button>
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
          <input type="text" class="form-control" id="f_q" placeholder="credit no, supplier, reference, memo...">
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
        Draft credits can be edited or deleted. Posting creates a journal entry and applies credit to bills.
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="crTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:170px;">Credit No</th>
              <th style="width:120px;">Date</th>
              <th>Supplier</th>
              <th style="width:80px;">Curr</th>
              <th style="width:140px;" class="text-end">Total</th>
              <th style="width:140px;" class="text-end">Unapplied</th>
              <th style="width:120px;">Status</th>
              <th style="width:240px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <small class="text-muted">
        Posted credits can later be reflected in supplier statements, AP aging, and bill settlement history.
      </small>
    </div>
  </div>
</div>

<div class="modal fade" id="crModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="crModalTitle">New Supplier Credit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body cr-modal-body">
        <form id="crForm">
          <input type="hidden" id="credit_id" name="id">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div id="cr_status_badge"></div>
            <div class="text-muted small">
              Subtotal: <b id="subLbl">0.00</b> |
              Tax: <b id="taxLbl">0.00</b> |
              Total: <b id="totLbl">0.00</b> |
              Unapplied: <b id="unappliedLbl">0.00</b>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-3">
              <label class="text-muted small">Credit No</label>
              <input type="text" class="form-control" id="credit_no" name="credit_no" placeholder="Auto">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Credit Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="credit_date" name="credit_date" required>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Supplier <span class="text-danger">*</span></label>
              <select class="form-control" id="supplier_id" name="supplier_id" style="width:100%"></select>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">AP Control Account (optional)</label>
              <select class="form-control" id="ap_control_account_id" name="ap_control_account_id" style="width:100%"></select>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Currency</label>
              <select class="form-control" id="currency_code" name="currency_code" style="width:100%"></select>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">FX Rate</label>
              <input type="number" step="0.000001" class="form-control" id="fx_rate" name="fx_rate" placeholder="1.000000">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Reference</label>
              <input type="text" class="form-control" id="reference" name="reference" placeholder="Credit note no...">
            </div>

            <div class="col-md-12">
              <label class="text-muted small">Memo</label>
              <textarea class="form-control" id="memo" name="memo" rows="2"></textarea>
            </div>

            <div class="col-md-12">
              <div class="d-flex justify-content-between align-items-center mt-2">
                <h6 class="mb-0">Distribution (What is being credited)</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addDistBtn">
                  <i class="fas fa-plus"></i> Add Line
                </button>
              </div>

              <div class="cr-table-wrap mt-2">
                <table class="table table-bordered align-middle mb-0 cr-dist-table">
                  <thead class="bg-light">
                    <tr>
                      <th class="col-gl">GL Account</th>
                      <th class="col-desc">Description</th>
                      <th class="col-qty text-end">Qty</th>
                      <th class="col-unit text-end">Unit Cost</th>
                      <th class="col-taxcode">Tax Code</th>
                      <th class="col-taxrate">Tax Rate</th>
                      <th class="col-taxpct text-end">Tax %</th>
                      <th class="col-line text-end">Line Total</th>
                      <th class="col-action"></th>
                    </tr>
                  </thead>
                  <tbody id="distTbody"></tbody>
                </table>
              </div>
            </div>

            <div class="col-md-12">
              <div class="d-flex justify-content-between align-items-center mt-3">
                <h6 class="mb-0">Applications (Optional: apply credit to bills)</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addAppBtn">
                  <i class="fas fa-plus"></i> Add Bill
                </button>
              </div>

              <div class="cr-table-wrap mt-2">
                <table class="table table-bordered align-middle mb-0 cr-app-table">
                  <thead class="bg-light">
                    <tr>
                      <th class="col-bill">Bill</th>
                      <th class="col-amt text-end">Amount Applied</th>
                      <th class="col-app-action"></th>
                    </tr>
                  </thead>
                  <tbody id="appTbody"></tbody>
                </table>
              </div>

              <small class="text-muted d-block mt-2">
                Only posted bills with balance due will show. Applied total cannot exceed credit total.
              </small>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveCrBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dtUrl       = "{{ url('admin/finance/supplier-credits/datatable') }}";
const storeUrl    = "{{ url('admin/finance/supplier-credits') }}";
const baseUrl     = "{{ url('admin/finance/supplier-credits') }}";

const supplierUrl = "{{ url('admin/finance/supplier-credits/lookups/suppliers') }}";
const billsUrl    = "{{ url('admin/finance/supplier-credits/lookups/open-supplier-bills') }}";
const apUrl       = "{{ url('admin/finance/supplier-credits/lookups/ap-control-accounts') }}";
const glUrl       = "{{ url('admin/finance/lookups/gl-accounts') }}";
const curUrl      = "{{ url('admin/finance/supplier-credits/lookups/currencies') }}";
const taxCodesUrl = "{{ url('admin/finance/supplier-credits/lookups/tax-codes') }}";
const taxRatesUrl = "{{ url('admin/finance/supplier-credits/lookups/tax-rates') }}";

function swalOk(msg){ return (window.Swal?.fire) ? Swal.fire({icon:'success',title:'Success',text:msg||'Done.'}) : alert(msg||'Done'); }
function swalErr(msg){ return (window.Swal?.fire) ? Swal.fire({icon:'error',title:'Error',text:msg||'Error'}) : alert(msg||'Error'); }
function swalAsk(opts){ return (window.Swal?.fire) ? Swal.fire(opts) : Promise.resolve({isConfirmed: confirm(opts?.title||'Confirm?')}); }

let DT = null;

function initDT(){
  DT = $('#crTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url:dtUrl,
      data:(d)=>{
        d.status = $('#f_status').val();
        d.date_from = $('#f_from').val();
        d.date_to = $('#f_to').val();
        d.q = $('#f_q').val();
      }
    },
    columns:[
      {data:'id'},
      {data:'credit_no'},
      {data:'credit_date'},
      {data:'supplier'},
      {data:'currency'},
      {data:'total', className:'text-end'},
      {data:'unapplied', className:'text-end'},
      {data:'status', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[0,'desc']]
  });
}

function refreshDT(){
  if (DT) DT.ajax.reload(null,false);
}

$('#applyBtn').on('click', refreshDT);

$('#resetBtn').on('click', ()=>{
  $('#f_status,#f_from,#f_to,#f_q').val('');
  refreshDT();
});

function s2($el, url, placeholder, extraDataFn){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder,
    allowClear:true,
    dropdownParent: $('#crModal'),
    ajax:{
      url,
      dataType:'json',
      delay:200,
      data: p => Object.assign({ q: p.term || '' }, (extraDataFn ? extraDataFn() : {})),
      processResults: d => d,
      cache:true
    }
  });
}

function resetModal(){
  $('#crForm')[0].reset();
  $('#credit_id').val('');
  $('#distTbody').html('');
  $('#appTbody').html('');
  $('#cr_status_badge').html('');
  $('#subLbl,#taxLbl,#totLbl,#unappliedLbl').text('0.00');

  $('#supplier_id,#currency_code,#ap_control_account_id').val(null).trigger('change');

  addDistLine();
  addAppLine();
  recalcTotals();
}

function recalcTotals(){
  let subtotal = 0, tax = 0, total = 0, applied = 0;

  $('#distTbody tr').each(function(){
    const $tr = $(this);
    const qty  = parseFloat($tr.find('.ln-qty').val() || '0') || 0;
    const unit = parseFloat($tr.find('.ln-unit').val() || '0') || 0;
    const rate = parseFloat($tr.find('.ln-taxrate').val() || '0') || 0;

    const base = qty * unit;
    const t = base * (rate / 100);
    const line = base + t;

    $tr.find('.ln-line').val(line.toFixed(2));

    subtotal += base;
    tax += t;
    total += line;
  });

  $('#appTbody tr').each(function(){
    applied += (parseFloat($(this).find('.app-amt').val() || '0') || 0);
  });

  const unapplied = total - applied;

  $('#subLbl').text(subtotal.toFixed(2));
  $('#taxLbl').text(tax.toFixed(2));
  $('#totLbl').text(total.toFixed(2));
  $('#unappliedLbl').text(unapplied.toFixed(2));
}

function addDistLine(line=null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td class="col-gl">
        <select class="form-control gl-select" name="distribution[${idx}][gl_account_id]" required></select>
      </td>
      <td class="col-desc">
        <input type="text" class="form-control" name="distribution[${idx}][description]" value="${line?.description ?? ''}" placeholder="Description">
      </td>
      <td class="col-qty">
        <input type="number" step="0.0001" min="0" class="form-control text-end ln-qty" name="distribution[${idx}][qty]" value="${line?.qty ?? 1}">
      </td>
      <td class="col-unit">
        <input type="number" step="0.0001" min="0" class="form-control text-end ln-unit" name="distribution[${idx}][unit_cost]" value="${line?.unit_cost ?? 0}">
      </td>
      <td class="col-taxcode">
        <select class="form-control tax-code-select" name="distribution[${idx}][tax_code_id]"></select>
      </td>
      <td class="col-taxrate">
        <select class="form-control tax-rate-select" name="distribution[${idx}][tax_rate_id]"></select>
      </td>
      <td class="col-taxpct">
        <input type="number" step="0.0001" min="0" class="form-control text-end ln-taxrate" name="distribution[${idx}][tax_rate]" value="${line?.tax_rate ?? ''}" placeholder="%">
      </td>
      <td class="col-line">
        <input type="number" step="0.01" class="form-control text-end ln-line" name="distribution[${idx}][line_total]" value="${line?.line_total ?? 0}" readonly>
      </td>
      <td class="col-action">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-dist">
          <i class="fas fa-times"></i>
        </button>
      </td>
    </tr>
  `);

  $('#distTbody').append(tr);

  const $gl = tr.find('.gl-select');
  const $taxCode = tr.find('.tax-code-select');
  const $taxRate = tr.find('.tax-rate-select');
  const $taxRateInput = tr.find('.ln-taxrate');

  s2($gl, glUrl, 'Select GL...');
  s2($taxCode, taxCodesUrl, 'Tax code...');
  s2($taxRate, taxRatesUrl, 'Tax rate...');

  $taxCode.on('select2:select', function(e){
    const d = e.params.data || {};

    if (d.rate_id) {
      const rateText = d.rate_text || `Rate #${d.rate_id}`;
      const opt = new Option(rateText, d.rate_id, true, true);

      $taxRate.empty().append(opt).trigger('change');

      if (d.rate !== undefined && d.rate !== null) {
        $taxRateInput.val(parseFloat(d.rate).toFixed(4));
        recalcTotals();
      }
    }
  });

  $taxCode.on('select2:clear', function(){
    $taxRate.val(null).trigger('change');
    $taxRateInput.val('');
    recalcTotals();
  });

  $taxRate.on('select2:select', function(e){
    const d = e.params.data || {};
    if (d.rate !== undefined && d.rate !== null) {
      $taxRateInput.val(parseFloat(d.rate).toFixed(4));
      recalcTotals();
    }
  });

  $taxRate.on('select2:clear', function(){
    $taxRateInput.val('');
    recalcTotals();
  });

  if (line?.gl_account_id && line?.gl_account_label) {
    const opt = new Option(line.gl_account_label, line.gl_account_id, true, true);
    $gl.append(opt).trigger('change');
  }

  if (line?.tax_code_id && line?.tax_code_label) {
    const opt = new Option(line.tax_code_label, line.tax_code_id, true, true);
    $taxCode.append(opt).trigger('change');
  }

  if (line?.tax_rate_id && line?.tax_rate_label) {
    const opt = new Option(line.tax_rate_label, line.tax_rate_id, true, true);
    $taxRate.append(opt).trigger('change');
  }

  recalcTotals();
}

function addAppLine(line = null) {
  const idx = Date.now() + Math.floor(Math.random() * 1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td class="col-bill">
        <select class="form-control bill-select" name="applications[${idx}][bill_id]"></select>
      </td>
      <td class="col-amt">
        <input
          type="number"
          step="0.01"
          min="0"
          class="form-control text-end app-amt"
          name="applications[${idx}][amount_applied]"
          value="${line?.amount_applied ?? ''}"
          placeholder="0.00">
      </td>
      <td class="col-app-action">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-app">
          <i class="fas fa-times"></i>
        </button>
      </td>
    </tr>
  `);

  $('#appTbody').append(tr);

  const $bill = tr.find('.bill-select');
  const $amt  = tr.find('.app-amt');

  s2($bill, billsUrl, 'Select bill (optional)...', () => ({
    supplier_id: $('#supplier_id').val()
  }));

  $bill.on('select2:select', function (e) {
    const d = e.params.data || {};
    const bal = parseFloat(d.balance_due || 0) || 0;

    let totalCredit = parseFloat($('#totLbl').text() || '0') || 0;
    let alreadyAppliedElsewhere = 0;

    $('#appTbody tr').not($(this).closest('tr')).each(function () {
      alreadyAppliedElsewhere += parseFloat($(this).find('.app-amt').val() || '0') || 0;
    });

    let suggested = 0;

    if (totalCredit <= 0) {
      suggested = bal;
    } else {
      const remainingCredit = Math.max(0, totalCredit - alreadyAppliedElsewhere);
      suggested = Math.min(bal, remainingCredit);
    }

    $amt.val(suggested.toFixed(2));
    recalcTotals();
  });

  $bill.on('select2:clear', function () {
    $amt.val('');
    recalcTotals();
  });

  if (line?.bill_id && line?.bill_label) {
    const opt = new Option(line.bill_label, line.bill_id, true, true);
    $bill.append(opt).trigger('change');

    if (
      line?.amount_applied !== undefined &&
      line?.amount_applied !== null &&
      line?.amount_applied !== ''
    ) {
      $amt.val(parseFloat(line.amount_applied).toFixed(2));
    }
  }

  recalcTotals();
}

$(document).on('input', '.ln-qty,.ln-unit,.ln-taxrate,.app-amt', recalcTotals);
$(document).on('click', '#addDistBtn', ()=> addDistLine());
$(document).on('click', '#addAppBtn', ()=> addAppLine());

$(document).on('click', '.btn-del-dist', function(){
  $(this).closest('tr').remove();
  if($('#distTbody tr').length < 1) addDistLine();
  recalcTotals();
});

$(document).on('click', '.btn-del-app', function(){
  $(this).closest('tr').remove();
  recalcTotals();
});

function initModalSelects(){
  s2($('#supplier_id'), supplierUrl, 'Select supplier...');
  s2($('#currency_code'), curUrl, 'Select currency...');
  s2($('#ap_control_account_id'), apUrl, 'Select AP control account (optional)...');
}

$('#supplier_id').on('change', function(){
  $('#appTbody tr').each(function(){
    $(this).find('.bill-select').val(null).trigger('change');
    $(this).find('.app-amt').val('');
  });
  recalcTotals();
});

$('#createBtn').on('click', ()=>{
  resetModal();
  $('#crModalTitle').text('New Supplier Credit');
  $('#crModal').modal('show');
});

$('#saveCrBtn').on('click', function(){
  const id = $('#credit_id').val();
  const url = id ? `${baseUrl}/${id}` : storeUrl;
  const method = id ? 'PUT' : 'POST';

  if(!$('#credit_date').val()) return swalErr('Credit date is required.');
  if(!$('#supplier_id').val()) return swalErr('Supplier is required.');

  const total = parseFloat($('#totLbl').text() || '0');
  const unapplied = parseFloat($('#unappliedLbl').text() || '0');

  if(total <= 0) return swalErr('Credit total must be > 0.');
  if(unapplied < -0.009) return swalErr('Applied total cannot exceed credit total.');

  $.ajax({url, method, data: $('#crForm').serialize()})
    .done(res=>{
      $('#crModal').modal('hide');
      swalOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to save.'));
});

$(document).on('click', '.btn-edit-cr', function(){
  resetModal();
  const cr = $(this).data('json');

  $('#crModalTitle').text('Edit Supplier Credit');
  $('#credit_id').val(cr.id);
  $('#credit_no').val(cr.credit_no || '');
  $('#credit_date').val(cr.credit_date || '');
  $('#reference').val(cr.reference || '');
  $('#memo').val(cr.memo || '');
  $('#fx_rate').val(cr.fx_rate || '');
  $('#cr_status_badge').html(cr.status ? `<span class="badge bg-secondary">${String(cr.status).toUpperCase()}</span>` : '');

  if(cr.supplier_id && cr.supplier_label){
    const opt = new Option(cr.supplier_label, cr.supplier_id, true, true);
    $('#supplier_id').append(opt).trigger('change');
  }

  if(cr.currency_code){
    const opt = new Option(cr.currency_code, cr.currency_code, true, true);
    $('#currency_code').append(opt).trigger('change');
  }

  if(cr.ap_control_account_id && cr.ap_control_account_label){
    const opt = new Option(cr.ap_control_account_label, cr.ap_control_account_id, true, true);
    $('#ap_control_account_id').append(opt).trigger('change');
  }

  $.get(`${baseUrl}/${cr.id}/lines`)
    .done(r=>{
      $('#distTbody').html('');
      (r.distribution || []).forEach(ln => addDistLine(ln));

      $('#appTbody').html('');
      (r.applications || []).forEach(ln => addAppLine(ln));

      if($('#distTbody tr').length < 1) addDistLine();
      if($('#appTbody tr').length < 1) addAppLine();

      recalcTotals();
      $('#crModal').modal('show');
    })
    .fail(()=>swalErr('Could not load credit details.'));
});

$(document).on('click', '.btn-del-cr', async function(){
  const id = $(this).data('id');
  const r = await swalAsk({
    icon:'warning',
    title:'Delete credit?',
    text:'Draft only. Soft delete.',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  });
  if(!r.isConfirmed) return;

  $.ajax({url:`${baseUrl}/${id}`, method:'DELETE'})
    .done(res=>{ swalOk(res.message || 'Deleted.'); refreshDT(); })
    .fail(xhr=>swalErr(xhr?.responseJSON?.message || 'Delete failed.'));
});

$(document).on('click', '.btn-post-cr', async function(){
  const id = $(this).data('id');
  const r = await swalAsk({
    icon:'warning',
    title:'Post credit?',
    text:'Creates a journal entry and applies credit to bills.',
    showCancelButton:true,
    confirmButtonText:'Yes, post'
  });
  if(!r.isConfirmed) return;

  $.post(`${baseUrl}/${id}/post`)
    .done(res=>{ swalOk(res.message || 'Posted.'); refreshDT(); })
    .fail(xhr=>swalErr(xhr?.responseJSON?.message || 'Post failed.'));
});

$(document).on('click', '.btn-void-cr', async function(){
  const id = $(this).data('id');
  const r = await swalAsk({
    icon:'warning',
    title:'Void credit?',
    text:'Voided credits remain for audit.',
    showCancelButton:true,
    confirmButtonText:'Yes, void'
  });
  if(!r.isConfirmed) return;

  $.post(`${baseUrl}/${id}/void`)
    .done(res=>{ swalOk(res.message || 'Voided.'); refreshDT(); })
    .fail(xhr=>swalErr(xhr?.responseJSON?.message || 'Void failed.'));
});

$(function(){
  initModalSelects();
  initDT();
});
</script>
@endpush