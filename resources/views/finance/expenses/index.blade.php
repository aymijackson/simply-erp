@extends('layouts.master')
@section('title','Expenses')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Expenses</h1>
      <small class="text-muted">Finance / AP</small>
    </div>

    <button class="btn btn-primary" id="createBtn">
      <i class="fas fa-plus"></i> New Expense
    </button>
  </div>

  {{-- Filters --}}
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
          <label class="text-muted small">Payment Mode</label>
          <select class="form-control" id="f_payment_mode">
            <option value="">All</option>
            <option value="cash">Cash</option>
            <option value="bank">Bank</option>
            <option value="credit">Credit</option>
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
          <input type="text" class="form-control" id="f_q" placeholder="exp no, vendor, ref...">
        </div>

        <div class="col-md-1 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyBtn"><i class="fas fa-filter"></i></button>
        </div>
      </div>

      <div class="d-flex gap-2 mt-2">
        <button class="btn btn-outline-secondary" id="resetBtn"><i class="fas fa-undo"></i> Reset</button>

        <div class="alert alert-info mb-0 flex-grow-1">
          <i class="fas fa-info-circle me-1"></i>
          Draft can be edited/deleted. Posting creates a Journal Entry. Void keeps audit trail.
        </div>
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="expTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:120px;">Date</th>
              <th style="width:160px;">Expense No</th>
              <th>Category</th>
              <th>Vendor</th>
              <th style="width:90px;">Curr</th>
              <th style="width:140px;" class="text-end">Total</th>
              <th style="width:120px;">Status</th>
              <th style="width:240px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('finance.expenses.partials.modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

// URLs
const dtUrl       = "{{ url('admin/finance/expenses/datatable') }}";
const storeUrl    = "{{ url('admin/finance/expenses') }}";
const baseUrl     = "{{ url('admin/finance/expenses') }}";

// lookups
const suppliersUrl = "{{ url('admin/finance/expenses/lookups/suppliers') }}";
const categoriesUrl= "{{ url('admin/finance/expenses/lookups/categories') }}";
const currenciesUrl= "{{ url('admin/finance/expenses/lookups/currencies') }}";
const bankUrl      = "{{ url('admin/finance/expenses/lookups/bank-accounts') }}";
const payableUrl   = "{{ url('admin/finance/expenses/lookups/payable-accounts') }}";
const glUrl        = "{{ url('admin/finance/expenses/lookups/gl-accounts') }}";
const taxCodesUrl  = "{{ url('admin/finance/expenses/lookups/tax-codes') }}";

// SweetAlert2 only
function toastOk(msg){
  return Swal.fire({icon:'success', title:'Success', text: msg || 'Done', timer:1200, showConfirmButton:false});
}
function toastErr(msg){
  return Swal.fire({icon:'error', title:'Error', text: msg || 'Something went wrong'});
}
function confirmBox(opts){
  return Swal.fire(Object.assign({
    icon:'warning',
    showCancelButton:true,
    confirmButtonText:'Yes',
  }, opts || {}));
}

let DT = null;

function initDT(){
  DT = $('#expTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url: dtUrl,
      data: function(d){
        d.status = $('#f_status').val();
        d.payment_mode = $('#f_payment_mode').val();
        d.date_from = $('#f_from').val();
        d.date_to = $('#f_to').val();
        d.q = $('#f_q').val();
      }
    },
    columns:[
      {data:'id'},
      {data:'expense_date'},
      {data:'expense_no'},
      {data:'category'},
      {data:'vendor'},
      {data:'currency'},
      {data:'total', className:'text-end'},
      {data:'status', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[0,'desc']]
  });
}
function refreshDT(){ DT.ajax.reload(null,false); }

$('#applyBtn').on('click', refreshDT);
$('#resetBtn').on('click', function(){
  $('#f_status').val('');
  $('#f_payment_mode').val('');
  $('#f_from').val('');
  $('#f_to').val('');
  $('#f_q').val('');
  refreshDT();
});

/** ===== Select2 helpers ===== */
function initS2($el, url, placeholder){
  $el.select2({
    theme: 'bootstrap-5',
    width: '100%',
    placeholder,
    allowClear: true,
    dropdownParent: $('#expModal .modal-body'),
    ajax: {
      url, dataType:'json', delay:200,
      data: p => ({ q: p.term || '' }),
      processResults: d => d,
      cache: true
    }
  });
}
function setS2Value($el, id, text, extraData = {}){
  if(!id) { $el.val(null).trigger('change'); return; }
  const opt = new Option(text || id, id, true, true);
  $el.append(opt).trigger('change');

  // store extra data for select2 selected item if needed
  const data = Object.assign({ id:id, text:text || id }, extraData);
  $el.trigger({
    type: 'select2:select',
    params: { data: data }
  });
}

/** ===== Modal + Lines ===== */
function resetModal(){
  $('#expForm')[0].reset();
  $('#expense_id').val('');
  $('#exp_status_badge').html('');

  $('#supplier_id').val(null).trigger('change');
  $('#category_id').val(null).trigger('change');
  $('#currency_code').val(null).trigger('change');
  $('#bank_account_id').val(null).trigger('change');
  $('#payable_account_id').val(null).trigger('change');

  $('#linesTbody').html('');
  addLine();
  recalcTotals();
  togglePaymentUI();
}

function togglePaymentUI(){
  const mode = $('#payment_mode').val();
  const isBank = (mode === 'bank');
  $('#bankWrap').toggleClass('d-none', !isBank);
}

function addLine(line=null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td style="width:20%">
        <select class="form-control gl-select" name="lines[${idx}][gl_account_id]" required></select>
      </td>
      <td style="width:16%">
        <input type="text" class="form-control" name="lines[${idx}][description]" value="${line?.description ?? ''}" placeholder="Description">
      </td>
      <td style="width:8%">
        <input type="number" step="0.0001" class="form-control text-end ln-qty" name="lines[${idx}][qty]" value="${line?.qty ?? 1}">
      </td>
      <td style="width:10%">
        <input type="number" step="0.0001" class="form-control text-end ln-unit" name="lines[${idx}][unit_cost]" value="${line?.unit_cost ?? 0}">
      </td>
      <td style="width:18%">
        <select class="form-control taxcode-select" name="lines[${idx}][tax_code_id]"></select>
      </td>
      <td style="width:8%">
        <input type="number" step="0.0001" class="form-control text-end ln-taxrate" name="lines[${idx}][tax_rate]" value="${line?.tax_rate ?? ''}" placeholder="%" readonly>
      </td>
      <td style="width:8%">
        <input type="number" step="0.01" class="form-control text-end ln-taxamt" value="${line?.tax_amount ?? 0}" readonly>
      </td>
      <td style="width:10%">
        <input type="number" step="0.01" class="form-control text-end ln-total" value="${line?.line_total ?? 0}" readonly>
      </td>
      <td style="width:4%">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-line"><i class="fas fa-times"></i></button>
      </td>
    </tr>
  `);

  $('#linesTbody').append(tr);

  const $gl = tr.find('.gl-select');
  initS2($gl, glUrl, 'GL account...');
  if (line?.gl_account_id && line?.gl_account_label) {
    setS2Value($gl, line.gl_account_id, line.gl_account_label);
  }

  const $tax = tr.find('.taxcode-select');
  initS2($tax, taxCodesUrl, 'Tax code...');
  if (line?.tax_code_id && line?.tax_code_label) {
    setS2Value($tax, line.tax_code_id, line.tax_code_label, {
      rate_id: line.tax_rate_id || null,
      rate: line.tax_rate || 0,
      is_reverse_charge: line.is_reverse_charge || 0,
      is_exempt: line.is_exempt || 0,
      is_out_of_scope: line.is_out_of_scope || 0
    });
  }

  recalcTotals();
}

function recalcTotals(){
  let subtotal = 0, tax = 0, total = 0;

  $('#linesTbody tr').each(function(){
    const $tr = $(this);
    const qty = parseFloat($tr.find('.ln-qty').val() || '0');
    const unit = parseFloat($tr.find('.ln-unit').val() || '0');
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

$(document).on('input', '.ln-qty,.ln-unit', recalcTotals);

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
  if ($('#linesTbody tr').length < 1) addLine();
  recalcTotals();
});

$('#addLineBtn').on('click', ()=> addLine());

$('#payment_mode').on('change', togglePaymentUI);
$('#supplier_id').on('change', function(){
  const t = $('#supplier_id').select2('data')?.[0]?.text || '';
  if (!$('#vendor_name').val()) $('#vendor_name').val(t);
});

/** ===== Actions ===== */
$('#createBtn').on('click', function(){
  resetModal();
  $('#expModalTitle').text('New Expense');
  $('#expModal').modal('show');
});

// Save (create/update)
$('#saveExpBtn').on('click', function(){
  const id = $('#expense_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : storeUrl;

  const payload = {
    expense_no: $('#expense_no').val() || null,
    expense_date: $('#expense_date').val(),
    category_id: $('#category_id').val(),
    supplier_id: $('#supplier_id').val() || null,
    vendor_name: $('#vendor_name').val() || null,
    reference: $('#reference').val() || null,
    memo: $('#memo').val() || null,
    currency_code: $('#currency_code').val() || null,
    fx_rate: $('#fx_rate').val() || null,
    payment_mode: $('#payment_mode').val(),
    bank_account_id: $('#bank_account_id').val() || null,
    payable_account_id: $('#payable_account_id').val() || null,
    lines: []
  };

  $('#linesTbody tr').each(function(){
    const $tr = $(this);
    payload.lines.push({
      gl_account_id: $tr.find('select.gl-select').val(),
      description: $tr.find('input[name*="[description]"]').val() || null,
      qty: $tr.find('.ln-qty').val(),
      unit_cost: $tr.find('.ln-unit').val(),
      tax_code_id: $tr.find('select.taxcode-select').val() || null,
      tax_rate: $tr.find('.ln-taxrate').val() || null,
      memo: null
    });
  });

  if(!payload.expense_date) return toastErr('Expense date is required.');
  if(!payload.category_id) return toastErr('Category is required.');
  if(!payload.payment_mode) return toastErr('Payment mode is required.');
  if(payload.payment_mode === 'bank' && !payload.bank_account_id) return toastErr('Bank account is required for bank payment.');
  if(!payload.lines.length) return toastErr('Add at least 1 line.');

  $.ajax({url, method, data: payload})
    .done(res=>{
      $('#expModal').modal('hide');
      toastOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Save failed.'));
});

// Edit
$(document).on('click', '.btn-edit-exp', function(){
  const id = $(this).data('id');
  resetModal();

  $.get(`${baseUrl}/${id}`)
    .done(r=>{
      const e = r.expense || {};
      $('#expModalTitle').text('Edit Expense');
      $('#expense_id').val(e.id);

      $('#expense_no').val(e.expense_no || '');
      $('#expense_date').val(e.expense_date || '');
      $('#vendor_name').val(e.vendor_name || '');
      $('#reference').val(e.reference || '');
      $('#memo').val(e.memo || '');
      $('#fx_rate').val(e.fx_rate || '');
      $('#payment_mode').val(e.payment_mode || 'bank').trigger('change');

      setS2Value($('#supplier_id'), e.supplier_id, e.supplier_label);
      setS2Value($('#category_id'), e.category_id, e.category_label);
      setS2Value($('#currency_code'), e.currency_code, e.currency_code);
      setS2Value($('#bank_account_id'), e.bank_account_id, e.bank_account_label);
      setS2Value($('#payable_account_id'), e.payable_account_id, e.payable_account_label);

      $('#exp_status_badge').html(e.status ? `<span class="badge bg-secondary">${String(e.status).toUpperCase()}</span>` : '');

      $('#linesTbody').html('');
      (r.lines || []).forEach(ln => addLine(ln));
      if ($('#linesTbody tr').length < 1) addLine();

      recalcTotals();
      $('#expModal').modal('show');
    })
    .fail(()=> toastErr('Could not load expense.'));
});

// Delete
$(document).on('click', '.btn-del-exp', function(){
  const id = $(this).data('id');
  confirmBox({title:'Delete draft expense?', text:'This will soft-delete the draft expense.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({url:`${baseUrl}/${id}`, method:'DELETE'})
        .done(res=>{ toastOk(res.message||'Deleted'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Delete failed.'));
    });
});

// Post
$(document).on('click', '.btn-post-exp', function(){
  const id = $(this).data('id');
  confirmBox({title:'Post expense?', text:'This will create a journal entry and lock the expense.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${baseUrl}/${id}/post`)
        .done(res=>{ toastOk(res.message||'Posted'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Post failed.'));
    });
});

// Void
$(document).on('click', '.btn-void-exp', function(){
  const id = $(this).data('id');
  confirmBox({title:'Void expense?', text:'Voided expenses remain for audit.'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${baseUrl}/${id}/void`)
        .done(res=>{ toastOk(res.message||'Voided'); refreshDT(); })
        .fail(xhr=> toastErr(xhr?.responseJSON?.message || 'Void failed.'));
    });
});

$(function(){
  initDT();

  initS2($('#supplier_id'), suppliersUrl, 'Select supplier...');
  initS2($('#category_id'), categoriesUrl, 'Select category...');
  initS2($('#currency_code'), currenciesUrl, 'Select currency...');
  initS2($('#bank_account_id'), bankUrl, 'Select bank account...');
  initS2($('#payable_account_id'), payableUrl, 'Select payable/control account...');
});
</script>
@endpush