@extends('layouts.master')

@section('title','Bank & Cash Accounts')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Bank & Cash Accounts</h1>
      <small class="text-muted">Finance / Setup</small>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-danger d-none" id="bulkDeleteBtn">
        <i class="fas fa-trash"></i> Delete Selected
      </button>

      <button class="btn btn-primary" id="createBtn">
        <i class="fas fa-plus"></i> New Account
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
            <option value="bank">Bank</option>
            <option value="cash">Cash</option>
            <option value="wallet">Wallet</option>
            <option value="mobile_money">Mobile Money</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="text-muted small">Status</label>
          <select class="form-control" id="filterActive">
            <option value="">All</option>
            <option value="1">Active</option>
            <option value="0">Disabled</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="filterQ" placeholder="name, bank, account no, currency, GL...">
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
        Link each bank/cash account to a <b>GL account</b> (e.g., Cash at Bank) so payments & reconciliation can post to the ledger.
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="bankAccountsTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
              <th style="width:80px;">ID</th>
              <th>Name</th>
              <th>Company</th>
              <th style="width:120px;">Type</th>
              <th style="width:160px;">Currency</th>
              <th>GL Account</th>
              <th style="width:140px;">Opening</th>
              <th style="width:110px;">Status</th>
              <th style="width:120px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <small class="text-muted">
        Soft delete is used (deleted_at). Disable instead of deleting once transactions exist.
      </small>
    </div>
  </div>
</div>

{{-- Modal --}}
@include('finance.bank_accounts.partials.modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

// endpoints
const dtUrl       = "{{ route('admin.finance.bank_accounts.datatable') }}";
const storeUrl    = "{{ route('admin.finance.bank_accounts.store') }}";
const glUrl       = "{{ route('admin.finance.bank_accounts.gl_accounts') }}";

// lookups (using url so it works even if route names differ)
const currencyUrl = "{{ url('admin/finance/lookups/currencies') }}";
const banksUrl    = "{{ url('admin/finance/lookups/banks') }}";

// base update/delete url
const updateBase  = "{{ url('admin/finance/bank-accounts') }}";

let DT = null;

/** SweetAlert wrapper: supports swal(v1) OR Swal.fire(v2) */
function swalWrapper(optsOrTitle, text, icon){
  if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
    if (typeof optsOrTitle === 'string') return Swal.fire({title: optsOrTitle, text: text || '', icon: icon || 'info'});
    return Swal.fire(optsOrTitle || {});
  }
  if (typeof swal === 'function') {
    if (typeof optsOrTitle === 'string') return swal(optsOrTitle, text || '', icon || 'info');
    return swal(optsOrTitle);
  }
  alert((typeof optsOrTitle === 'string') ? (optsOrTitle + ' ' + (text||'')) : 'Done');
}

function swalOk(msg){ return swalWrapper({icon:'success', title:'Success', text: msg || 'Done.'}); }
function swalErr(msg){ return swalWrapper({icon:'error', title:'Error', text: msg || 'Something went wrong.'}); }

function resetBulkUI(){
  $('#checkAll').prop('checked', false);
  $('#bulkDeleteBtn').addClass('d-none');
}

function resetModal(){
  $('#bankForm')[0].reset();

  // record id
  $('#bank_account_id').val('');

  // legacy mirror
  $('#bank_name').val('');

  // reset select2
  $('#gl_account_id').val(null).trigger('change');
  $('#currency_code').val(null).trigger('change');
  $('#bank_id_select').val(null).trigger('change');

  $('#is_active').prop('checked', true);
}

function initGLSelect(){
  $('#gl_account_id').select2({
    theme: 'bootstrap-5',
    width: '100%',
    placeholder: 'Select GL account...',
    allowClear: true,
    dropdownParent: $('#bankModal'),
    ajax: {
      url: glUrl,
      dataType: 'json',
      delay: 200,
      data: params => ({ q: params.term || '' }),
      processResults: data => data,
      cache: true
    }
  });
}

function initCurrencySelect(){
  $('#currency_code').select2({
    theme: 'bootstrap-5',
    width: '100%',
    placeholder: 'Select currency...',
    allowClear: true,
    dropdownParent: $('#bankModal'),
    ajax: {
      url: currencyUrl,
      dataType: 'json',
      delay: 200,
      data: params => ({ q: params.term || '' }),
      processResults: data => data,
      cache: true
    }
  });
}

function initBankSelect(){
  $('#bank_id_select').select2({
    theme: 'bootstrap-5',
    width: '100%',
    placeholder: 'Select bank...',
    allowClear: true,
    dropdownParent: $('#bankModal'),
    ajax: {
      url: banksUrl,
      dataType: 'json',
      delay: 200,
      data: params => ({
        q: params.term || '',
        country: ($('#bank_country').val() || 'NG')
      }),
      processResults: data => data,
      cache: true
    }
  });

  // mirror bank name for legacy display
  $('#bank_id_select').on('select2:select', function(e){
    const text = e.params.data.text || '';
    $('#bank_name').val(text);
  });
  $('#bank_id_select').on('select2:clear', function(){
    $('#bank_name').val('');
  });
}

function initDT(){
  DT = $('#bankAccountsTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url: dtUrl,
      data: function(d){
        d.type = $('#filterType').val();
        d.active = $('#filterActive').val();
        d.q = $('#filterQ').val();
      }
    },
    columns:[
      {data:'id', orderable:false, searchable:false, render:(id)=>`<input type="checkbox" class="row-check" value="${id}">`},
      {data:'id'},
      {data:'name'},
      {data:'company_name'},
      {data:'type'},
      {data:'currency'},
      {data:'gl', orderable:false},
      {data:'opening'},
      {data:'active', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[1,'desc']],
    drawCallback: function(){
      // Reset bulk UI after any draw (edit/save reloads too)
      resetBulkUI();
    }
  });
}

function refreshDT(resetPaging=false){
  // resetPaging=true => go back to first page if you want
  DT.ajax.reload(null, !!resetPaging);
}

$('#applyFiltersBtn').on('click', ()=> refreshDT(true));
$('#resetFiltersBtn').on('click', ()=>{
  $('#filterType').val('');
  $('#filterActive').val('');
  $('#filterQ').val('');
  refreshDT(true);
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
    text:'This will delete the selected bank/cash accounts (soft delete).',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  }).then((r)=>{
    const ok = (r && (r.isConfirmed === true || r === true));
    if(!ok) return;

    $.post("{{ route('admin.finance.bank_accounts.bulk_delete') }}", {ids})
      .done(res=>{
        swalOk(res.message || 'Deleted.');
        refreshDT(false);
      })
      .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to delete.'));
  });
});

$('#createBtn').on('click', function(){
  resetModal();
  $('#bankModalTitle').text('New Bank/Cash Account');
  $('#bankModal').modal('show');
});

$('#saveBankBtn').on('click', function(){
  const id = $('#bank_account_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? (`${updateBase}/${id}`) : storeUrl;

  const payload = $('#bankForm').serialize();

  $.ajax({url, method, data: payload})
    .done(res=>{
      // Close modal first, then show swal on top (prevents it hiding behind backdrop)
      $('#bankModal').modal('hide');

      $('#bankModal').one('hidden.bs.modal', function () {
        swalOk(res.message || 'Saved.');
        refreshDT(false); // keep paging + filters
      });
    })
    .fail(xhr=>{
      swalErr(xhr?.responseJSON?.message || 'Failed to save.');
    });
});

$(document).on('click', '.btn-edit-bank', function(){
  resetModal();
  const b = $(this).data('json') || {};

  $('#bankModalTitle').text('Edit Bank/Cash Account');
  $('#bank_account_id').val(b.id || '');

  $('#name').val(b.name || '');
  $('#type').val(b.type || 'bank');

  $('#account_number').val(b.account_number || '');
  $('#sort_code').val(b.sort_code || '');
  $('#iban').val(b.iban || '');
  $('#swift').val(b.swift || '');
  $('#opening_balance').val(b.opening_balance || '0.00');
  $('#opening_balance_date').val(b.opening_balance_date || '');
  $('#notes').val(b.notes || '');
  $('#is_active').prop('checked', !!b.is_active);

  // Currency Select2 set (id = code)
  $('#currency_code').val(null).trigger('change');
  if (b.currency_code) {
    const cText = b.currency_code_label || b.currency_code;
    const opt = new Option(cText, b.currency_code, true, true);
    $('#currency_code').append(opt).trigger('change');
  }

  // Bank Select2 set:
  // - If bank_id exists => set it
  // - Else show bank_name as a display-only option (bank_id remains null; validation allows it)
  $('#bank_id_select').val(null).trigger('change');
  $('#bank_name').val('');

  if (b.bank_id) {
    const label = b.bank_label || b.bank_name || ('Bank #'+b.bank_id);
    const optb = new Option(label, b.bank_id, true, true);
    $('#bank_id_select').append(optb).trigger('change');
    $('#bank_name').val(label);
  } else if (b.bank_name) {
    // show stored name even if not linked to banks table
    const optb = new Option(b.bank_name, '', true, true);
    $('#bank_id_select').append(optb).trigger('change');
    $('#bank_name').val(b.bank_name);
  }

  // GL Select2 set
  $('#gl_account_id').val(null).trigger('change');
  if (b.gl_account_id) {
    const optg = new Option(b.gl_label || ('#'+b.gl_account_id), b.gl_account_id, true, true);
    $('#gl_account_id').append(optg).trigger('change');
  }

  $('#bankModal').modal('show');
});

$(document).on('click', '.btn-del-bank', function(){
  const id = $(this).data('id');

  swalWrapper({
    icon:'warning',
    title:'Delete this account?',
    text:'This cannot be undone (soft delete).',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  }).then((r)=>{
    const ok = (r && (r.isConfirmed === true || r === true));
    if(!ok) return;

    $.ajax({url:`${updateBase}/${id}`, method:'DELETE'})
      .done(res=>{
        swalOk(res.message || 'Deleted.');
        refreshDT(false);
      })
      .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to delete.'));
  });
});

$(function(){
  initGLSelect();
  initCurrencySelect();
  initBankSelect();
  initDT();
});
</script>
@endpush