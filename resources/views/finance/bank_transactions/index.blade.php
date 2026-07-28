@extends('layouts.master')

@section('title','Bank Transactions')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Bank Transactions</h1>
      <small class="text-muted">Finance / Bank & Cash</small>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-danger d-none" id="bulkDeleteBtn">
        <i class="fas fa-trash"></i> Delete Selected
      </button>

      <button class="btn btn-primary" id="createBtn">
        <i class="fas fa-plus"></i> New Transaction
      </button>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="text-muted small">Type</label>
          <select class="form-control" id="filterType">
            <option value="">All</option>
            <option value="deposit">Deposit</option>
            <option value="withdrawal">Withdrawal</option>
            <option value="transfer">Transfer</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="text-muted small">Status</label>
          <select class="form-control" id="filterStatus">
            <option value="">All</option>
            <option value="draft">Draft</option>
            <option value="posted">Posted</option>
            <option value="void">Void</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="filterQ" placeholder="txn no, ref, memo...">
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyFiltersBtn">
            <i class="fas fa-filter"></i> Apply
          </button>
          <button class="btn btn-outline-secondary w-100" id="resetFiltersBtn">
            <i class="fas fa-undo"></i> Reset
          </button>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        <b>Always split mode:</b> deposits/withdrawals must have split lines. Transfers move money between bank accounts (no split lines).
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="txTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
              <th style="width:70px;">ID</th>
              <th style="width:120px;">Date</th>
              <th style="width:120px;">Type</th>
              <th>Bank</th>
              <th style="width:90px;">Curr</th>
              <th style="width:140px;">Amount</th>
              <th style="width:110px;">Status</th>
              <th style="width:210px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <small class="text-muted">
        You can only edit/delete draft transactions. Posted transactions can be unposted (subject to period lock rules).
      </small>
    </div>
  </div>
</div>

@include('finance.bank_transactions.partials.modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dtUrl         = "{{ route('admin.finance.bank_transactions.datatable') }}";
const storeUrl      = "{{ route('admin.finance.bank_transactions.store') }}";
const bankAcctUrl   = "{{ route('admin.finance.bank_transactions.bank_accounts') }}";
const glUrl         = "{{ route('admin.finance.bank_transactions.gl_accounts') }}";

// prefer lookups via url (works even if route names differ)
const currencyUrl   = "{{ url('admin/finance/lookups/currencies') }}";
const banksUrl      = "{{ url('admin/finance/lookups/banks') }}";

const baseUrl       = "{{ url('admin/finance/bank-transactions') }}";
const bulkDeleteUrl = "{{ route('admin.finance.bank_transactions.bulk_delete') }}";

let DT = null;

// show swal AFTER modal closes (fix: modal closed but no swal)
window.__pendingSwal = null;
$('#txModal').on('hidden.bs.modal', function(){
  if (window.__pendingSwal) {
    const msg = window.__pendingSwal;
    window.__pendingSwal = null;
    swalOk(msg);
  }
});

/** swal wrapper: supports swal(v1) or Swal.fire(v2) */
function swalWrapper(optsOrTitle, text, icon){
  if (typeof swal === 'function') {
    if (typeof optsOrTitle === 'string') return swal(optsOrTitle, text || '', icon || 'info');
    return swal(optsOrTitle);
  }
  if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
    if (typeof optsOrTitle === 'string') return Swal.fire({title: optsOrTitle, text: text || '', icon: icon || 'info'});
    return Swal.fire(optsOrTitle || {});
  }
  alert((typeof optsOrTitle === 'string') ? (optsOrTitle + ' ' + (text||'')) : 'Done');
}
function swalOk(msg){ return swalWrapper({icon:'success', title:'Success', text: msg || 'Done.'}); }
function swalErr(msg){ return swalWrapper({icon:'error', title:'Error', text: msg || 'Something went wrong.'}); }

function initDT(){
  DT = $('#txTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url: dtUrl,
      data: function(d){
        d.type = $('#filterType').val();
        d.status = $('#filterStatus').val();
        d.q = $('#filterQ').val();
      }
    },
    columns:[
      {data:'id', orderable:false, searchable:false, render:(id)=>`<input type="checkbox" class="row-check" value="${id}">`},
      {data:'id'},
      {data:'date'},
      {data:'type'},
      {data:'bank', orderable:false},
      {data:'currency', orderable:false},
      {data:'amount'},
      {data:'status', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[1,'desc']],
  });
}
function refreshDT(){
  if (!DT) return;
  DT.ajax.reload(null,false);
  $('#bulkDeleteBtn').addClass('d-none');
  $('#checkAll').prop('checked', false);
}

$('#applyFiltersBtn').on('click', ()=> refreshDT());
$('#resetFiltersBtn').on('click', ()=>{
  $('#filterType').val('');
  $('#filterStatus').val('');
  $('#filterQ').val('');
  refreshDT();
});

$('#checkAll').on('change', function(){
  $('.row-check').prop('checked', this.checked).trigger('change');
});
$(document).on('change', '.row-check', function(){
  const any = $('.row-check:checked').length > 0;
  $('#bulkDeleteBtn').toggleClass('d-none', !any);
});

$('#bulkDeleteBtn').on('click', function(){
  const ids = $('.row-check:checked').map((i,el)=>$(el).val()).get();

  swalWrapper({
    icon:'warning',
    title:'Delete selected?',
    text:'This will soft-delete the selected bank transactions (draft only recommended).',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  }).then((r)=>{
    const ok = (r && (r.isConfirmed === true || r === true));
    if(!ok) return;

    $.post(bulkDeleteUrl, {ids})
      .done(res=>{
        swalOk(res.message || 'Deleted.');
        refreshDT();
      })
      .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to delete.'));
  });
});

/** ===== Modal + Select2 ===== */

function initSelect2BankAccounts($el){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select bank/cash account...',
    allowClear:true,
    dropdownParent: $('#txModal'),
    ajax:{
      url: bankAcctUrl,
      dataType:'json',
      delay:200,
      data: params => ({ q: params.term || '' }),
      processResults: data => data,
      cache:true
    }
  });
}

function initSelect2Currency(){
  $('#currency_code').select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select currency...',
    allowClear:true,
    dropdownParent: $('#txModal'),
    ajax:{
      url: currencyUrl,
      dataType:'json',
      delay:200,
      data: params => ({ q: params.term || '' }),
      processResults: data => data,
      cache:true
    }
  });
}

function initSelect2Banks(){
  $('#bank_id').select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select bank...',
    allowClear:true,
    dropdownParent: $('#txModal'),
    ajax:{
      url: banksUrl,
      dataType:'json',
      delay:200,
      data: params => ({ q: params.term || '', country: $('#bank_country').val() || 'NG' }),
      processResults: data => data,
      cache:true
    }
  });
}

function initSelect2GL($el){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select GL account...',
    allowClear:true,
    dropdownParent: $('#txModal'),
    ajax:{
      url: glUrl,
      dataType:'json',
      delay:200,
      data: params => ({ q: params.term || '' }),
      processResults: data => data,
      cache:true
    }
  });
}

function resetModal(){
  $('#txForm')[0].reset();
  $('#txn_id').val('');
  $('#linesTbody').html('');
  $('#type').val('deposit');

  // reset select2
  $('#bank_account_id').val(null).trigger('change');
  $('#to_bank_account_id').val(null).trigger('change');
  $('#currency_code').val(null).trigger('change');
  $('#bank_id').val(null).trigger('change');

  toggleTransferUI();
  addLine(); // always split mode
  recalcPreviewTotal();
}

function toggleTransferUI(){
  const t = $('#type').val();
  const isTransfer = (t === 'transfer');

  $('#toBankWrap').toggleClass('d-none', !isTransfer);
  $('#splitWrap').toggleClass('d-none', isTransfer);
  $('#totalTransferWrap').toggleClass('d-none', !isTransfer);

  if (!isTransfer && $('#linesTbody tr').length === 0) addLine();
}

function addLine(line = null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const memo = line?.memo || '';
  const amount = line?.amount || '';

  const tr = $(`
    <tr data-row="${idx}">
      <td style="width:52%">
        <select class="form-control gl-select" name="lines[${idx}][account_id]" required></select>
        <small class="text-muted d-block mt-1">Split account</small>
      </td>
      <td style="width:33%">
        <input type="text" class="form-control" name="lines[${idx}][memo]" value="${memo}" placeholder="Memo (optional)">
      </td>
      <td style="width:12%">
        <input type="number" step="0.01" class="form-control line-amount" name="lines[${idx}][amount]" value="${amount}" placeholder="0.00" required>
      </td>
      <td style="width:3%">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-line"><i class="fas fa-times"></i></button>
      </td>
    </tr>
  `);

  $('#linesTbody').append(tr);

  const $sel = tr.find('.gl-select');
  initSelect2GL($sel);

  if (line?.account_id && line?.account_label) {
    const opt = new Option(line.account_label, line.account_id, true, true);
    $sel.append(opt).trigger('change');
  }

  recalcPreviewTotal();
}

function collectLines(){
  const lines = [];
  $('#linesTbody tr').each(function(){
    const $tr = $(this);
    const acct = $tr.find('select.gl-select').val();
    const memo = $tr.find('input[name*="[memo]"]').val();
    const amt  = $tr.find('input.line-amount').val();

    if (!acct) return;
    const f = parseFloat(amt || '0');
    if (isNaN(f) || f <= 0) return;

    lines.push({account_id: acct, memo: memo || null, amount: f});
  });
  return lines;
}

function recalcPreviewTotal(){
  const t = $('#type').val();
  const isTransfer = (t === 'transfer');

  if (isTransfer) {
    const v = parseFloat($('#transfer_amount').val() || '0');
    $('#previewTotal').text((isNaN(v)?0:v).toFixed(2));
    return;
  }

  const lines = collectLines();
  const total = lines.reduce((s,x)=> s + (parseFloat(x.amount)||0), 0);
  $('#previewTotal').text(total.toFixed(2));
}

$(document).on('input', '.line-amount', recalcPreviewTotal);
$(document).on('input', '#transfer_amount', recalcPreviewTotal);

$(document).on('click', '#addLineBtn', function(){ addLine(); });

$(document).on('click', '.btn-del-line', function(){
  $(this).closest('tr').remove();
  if ($('#linesTbody tr').length === 0 && $('#type').val() !== 'transfer') addLine();
  recalcPreviewTotal();
});

$('#type').on('change', function(){
  toggleTransferUI();
  recalcPreviewTotal();
});

$('#createBtn').on('click', function(){
  resetModal();
  $('#txModalTitle').text('New Bank Transaction');
  $('#txModal').modal('show');
});

$('#saveTxBtn').on('click', function(){
  const id = $('#txn_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? (`${baseUrl}/${id}`) : storeUrl;

  const type = $('#type').val();
  const payload = {
    txn_no: $('#txn_no').val() || null,
    txn_date: $('#txn_date').val(),
    type: type,
    bank_account_id: $('#bank_account_id').val(),
    to_bank_account_id: $('#to_bank_account_id').val() || null,
    currency_code: $('#currency_code').val() || null,
    exchange_rate: $('#exchange_rate').val() || null,
    reference: $('#reference').val() || null,
    description: $('#description').val() || null,
    bank_id: $('#bank_id').val() || null,
    bank_name: $('#bank_name').val() || null,
    lines: (type === 'transfer') ? [] : collectLines(),
  };

  if (!payload.txn_date) { swalErr('Transaction date is required.'); return; }
  if (!payload.bank_account_id) { swalErr('Select the bank/cash account.'); return; }

  if (type !== 'transfer' && (!payload.lines || payload.lines.length < 1)) {
    swalErr('Add at least one split line with amount.');
    return;
  }

  if (type === 'transfer') {
    const v = parseFloat($('#transfer_amount').val() || '0');
    if (isNaN(v) || v <= 0) { swalErr('Transfer amount must be > 0.'); return; }
    if (!payload.to_bank_account_id) { swalErr('Select the destination bank account.'); return; }
    payload.total_amount = v;
  }

  $.ajax({url, method, data: payload})
    .done(res=>{
      // show swal AFTER modal closes
      window.__pendingSwal = res.message || 'Saved.';
      $('#txModal').modal('hide');
      refreshDT();
    })
    .fail(xhr=>{
      swalErr(xhr?.responseJSON?.message || 'Failed to save.');
    });
});

// Edit
$(document).on('click', '.btn-edit-tx', function(){
  resetModal();
  const t = $(this).data('json');
  const lines = $(this).data('lines') || [];

  $('#txModalTitle').text('Edit Bank Transaction');
  $('#txn_id').val(t.id);

  $('#txn_no').val(t.txn_no || '');
  $('#txn_date').val(t.txn_date || '');
  $('#type').val(t.type || 'deposit').trigger('change');

  // bank account select2
  $('#bank_account_id').val(null).trigger('change');
  if (t.bank_account_id && t.bank_account_label) {
    const opt = new Option(t.bank_account_label, t.bank_account_id, true, true);
    $('#bank_account_id').append(opt).trigger('change');
  }

  // to bank
  $('#to_bank_account_id').val(null).trigger('change');
  if (t.to_bank_account_id && t.to_bank_account_label) {
    const opt2 = new Option(t.to_bank_account_label, t.to_bank_account_id, true, true);
    $('#to_bank_account_id').append(opt2).trigger('change');
  }

  // currency select2
  $('#currency_code').val(null).trigger('change');
  if (t.currency_code) {
    const optC = new Option(t.currency_code, t.currency_code, true, true);
    $('#currency_code').append(optC).trigger('change');
  }

  // bank select2 (optional)
  $('#bank_id').val(null).trigger('change');
  if (t.bank_id && t.bank_label) {
    const optB = new Option(t.bank_label, t.bank_id, true, true);
    $('#bank_id').append(optB).trigger('change');
  }
  $('#bank_name').val(t.bank_name || '');

  $('#exchange_rate').val(t.exchange_rate || '1.00');
  $('#reference').val(t.reference || '');
  $('#description').val(t.description || '');

  // lines
  $('#linesTbody').html('');
  if (t.type === 'transfer') {
    $('#transfer_amount').val(t.total_amount || '0.00');
  } else {
    (lines || []).forEach(ln=> addLine(ln));
    if ($('#linesTbody tr').length === 0) addLine();
  }

  recalcPreviewTotal();
  $('#txModal').modal('show');
});

// Delete
$(document).on('click', '.btn-del-tx', function(){
  const id = $(this).data('id');

  swalWrapper({
    icon:'warning',
    title:'Delete this transaction?',
    text:'This will soft delete the transaction (draft only recommended).',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  }).then((r)=>{
    const ok = (r && (r.isConfirmed === true || r === true));
    if(!ok) return;

    $.ajax({url:`${baseUrl}/${id}`, method:'DELETE'})
      .done(res=>{ swalOk(res.message || 'Deleted.'); refreshDT(); })
      .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to delete.'));
  });
});

// Post
$(document).on('click', '.btn-post-tx', function(){
  const id = $(this).data('id');

  swalWrapper({
    icon:'warning',
    title:'Post transaction?',
    text:'This will create a journal entry and affect the ledger.',
    showCancelButton:true,
    confirmButtonText:'Yes, post'
  }).then((r)=>{
    const ok = (r && (r.isConfirmed === true || r === true));
    if(!ok) return;

    $.post(`${baseUrl}/${id}/post`)
      .done(res=>{ swalOk(res.message || 'Posted.'); refreshDT(); })
      .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to post.'));
  });
});

// Unpost
$(document).on('click', '.btn-unpost-tx', function(){
  const id = $(this).data('id');

  swalWrapper({
    icon:'warning',
    title:'Unpost transaction?',
    text:'This will remove the linked journal entry.',
    showCancelButton:true,
    confirmButtonText:'Yes, unpost'
  }).then((r)=>{
    const ok = (r && (r.isConfirmed === true || r === true));
    if(!ok) return;

    $.post(`${baseUrl}/${id}/unpost`)
      .done(res=>{ swalOk(res.message || 'Unposted.'); refreshDT(); })
      .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to unpost.'));
  });
});

$(function(){
  initSelect2BankAccounts($('#bank_account_id'));
  initSelect2BankAccounts($('#to_bank_account_id'));
  initSelect2Currency();
  initSelect2Banks();

  initDT();
});
</script>
@endpush