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
      <a href="{{ route('admin.finance.chart_of_accounts.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-book me-1"></i> Chart of Accounts
      </a>

      <button class="btn btn-primary" id="createBtn">
        <i class="fas fa-plus me-1"></i> New Bank/Cash Account
      </button>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-header fw-bold">
      <i class="fas fa-filter me-1"></i> Filters
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3">
          <label class="text-muted small">Type</label>
          <select class="form-control" id="filterType">
            <option value="">All</option>
            <option value="bank">Bank</option>
            <option value="cash">Cash</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="text-muted small">Status</label>
          <select class="form-control" id="filterActive">
            <option value="">All</option>
            <option value="1">Active only</option>
            <option value="0">Disabled only</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="filterQ" placeholder="name, bank, account no, currency...">
        </div>

        <div class="col-md-2 d-flex align-items-end justify-content-end gap-2">
          <button class="btn btn-outline-primary w-100" id="applyFiltersBtn">
            <i class="fas fa-search me-1"></i> Apply
          </button>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Default Bank GL</strong> is used when posting receipts/payments (unless overridden per transaction).
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <button class="btn btn-danger btn-sm d-none" id="bulkDeleteBtn">
            <i class="fas fa-trash me-1"></i> Delete Selected
          </button>
        </div>
        <div class="text-muted small">
          Company: {{ $companyId }}
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="bankCashTable" style="width:100%;">
          <thead class="bg-light">
            <tr>
              <th style="width:35px;">
                <input type="checkbox" id="checkAll">
              </th>
              <th style="width:70px;">ID</th>
              <th style="width:90px;">Type</th>
              <th>Name</th>
              <th>Bank</th>
              <th>Account No</th>
              <th style="width:90px;">Currency</th>
              <th>GL Account</th>
              <th style="width:110px;">Default</th>
              <th style="width:110px;">Status</th>
              <th style="width:150px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <small class="text-muted">
        Tip: Use <strong>Cash</strong> type for petty cash, tills, float boxes, etc.
      </small>
    </div>
  </div>
</div>

@include('finance.bank_cash_accounts.partials.modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

// ====== swal() compatibility wrapper (SweetAlert v1 or SweetAlert2) ======
function swalCompat(optsOrTitle, text, icon){
  if (typeof swal === 'function') {
    // SweetAlert v1
    if (typeof optsOrTitle === 'string') return swal(optsOrTitle, text || '', icon || 'info');
    return swal(optsOrTitle);
  }
  if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
    // SweetAlert2 fallback
    if (typeof optsOrTitle === 'string') return Swal.fire({ title: optsOrTitle, text: text || '', icon: icon || 'info' });
    return Swal.fire(optsOrTitle || {});
  }
  alert((typeof optsOrTitle === 'string' ? optsOrTitle : (optsOrTitle?.title || 'Alert')) + (text ? (": " + text) : ''));
}
function swalOk(msg){ return swalCompat({icon:'success', title:'Success', text: msg || 'Done.'}); }
function swalErr(msg){ return swalCompat({icon:'error', title:'Error', text: msg || 'Something went wrong.'}); }

const dtUrl     = "{{ route('admin.finance.bank_accounts.datatable') }}";
const storeUrl  = "{{ route('admin.finance.bank_accounts.store') }}";

let DT = null;

function resetModal(){
  $('#bcForm')[0].reset();
  $('#bc_id').val('');
  $('#gl_account_id').val('').trigger('change');
  $('#is_default').prop('checked', false);
  $('#is_active').prop('checked', true);
  $('#opening_balance').val('0.00');
}

function initGlSelect(){
  // expects select2 already loaded globally; if not, it still works as normal select
  if ($.fn.select2) {
    $('#gl_account_id').select2({
      dropdownParent: $('#bcModal'),
      width: '100%',
      placeholder: 'Select GL account...'
    });
  }
}

function initDT(){
  DT = $('#bankCashTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    pageLength: 10,
    ajax: {
      url: dtUrl,
      data: function(d){
        d.type   = $('#filterType').val();
        d.active = $('#filterActive').val();
        d.q      = $('#filterQ').val();
      }
    },
    columns: [
      { data: 'id', orderable:false, searchable:false, render: function(id){
          return `<input type="checkbox" class="row-check" value="${id}">`;
        }
      },
      { data: 'id' },
      { data: 'type' },
      { data: 'name' },
      { data: 'bank_name' },
      { data: 'account_no' },
      { data: 'currency' },
      { data: 'gl_account' },
      { data: 'is_default', orderable:false, searchable:false },
      { data: 'is_active', orderable:false, searchable:false },
      { data: 'actions', orderable:false, searchable:false },
    ],
    order: [[1,'desc']]
  });
}

function refreshDT(){ DT.ajax.reload(null,false); }

$('#applyFiltersBtn').on('click', refreshDT);

$('#checkAll').on('change', function(){
  $('.row-check').prop('checked', this.checked).trigger('change');
});

$(document).on('change', '.row-check', function(){
  const any = $('.row-check:checked').length > 0;
  $('#bulkDeleteBtn').toggleClass('d-none', !any);
});

$('#bulkDeleteBtn').on('click', function(){
  const ids = $('.row-check:checked').map((i,el)=>$(el).val()).get();
  swalCompat({
    icon:'warning',
    title:'Delete selected?',
    text:'This will permanently delete the selected bank/cash accounts.',
    buttons: {
      cancel: { text:'Cancel', visible:true },
      confirm: { text:'Yes, delete', closeModal:true }
    },
    dangerMode:true
  }).then((ok)=>{
    if(!ok) return;
    $.post("{{ route('admin.finance.bank_accounts.bulk_delete') }}", {ids})
      .done(res=>{
        swalOk(res.message || 'Deleted.');
        $('#bulkDeleteBtn').addClass('d-none');
        $('#checkAll').prop('checked', false);
        refreshDT();
      })
      .fail(xhr=>{
        swalErr(xhr?.responseJSON?.message || 'Failed to delete.');
      });
  });
});

$('#createBtn').on('click', function(){
  resetModal();
  $('#bcModalTitle').text('New Bank/Cash Account');
  $('#bcModal').modal('show');
});

$('#saveBcBtn').on('click', function(){
  const id = $('#bc_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? (`{{ url('admin/finance/bank-cash-accounts') }}/${id}`) : storeUrl;

  $.ajax({
    url, method,
    data: $('#bcForm').serialize()
  }).done(res=>{
    $('#bcModal').modal('hide');
    swalOk(res.message || 'Saved.');
    refreshDT();
  }).fail(xhr=>{
    swalErr(xhr?.responseJSON?.message || 'Failed to save.');
  });
});

// edit
$(document).on('click', '.btn-edit-bc', function(){
  resetModal();
  const s = $(this).data('json');

  $('#bcModalTitle').text('Edit Bank/Cash Account');
  $('#bc_id').val(s.id);

  $('#type').val(s.type);
  $('#name').val(s.name);
  $('#bank_name').val(s.bank_name || '');
  $('#account_no').val(s.account_no || '');
  $('#currency').val(s.currency || 'NGN');
  $('#opening_balance').val(s.opening_balance ?? '0.00');

  $('#gl_account_id').val(s.gl_account_id).trigger('change');

  $('#is_default').prop('checked', !!s.is_default);
  $('#is_active').prop('checked', !!s.is_active);

  $('#bcModal').modal('show');
});

// delete
$(document).on('click', '.btn-del-bc', function(){
  const id = $(this).data('id');

  swalCompat({
    icon:'warning',
    title:'Delete this account?',
    text:'This cannot be undone.',
    buttons: {
      cancel: { text:'Cancel', visible:true },
      confirm: { text:'Yes, delete', closeModal:true }
    },
    dangerMode:true
  }).then((ok)=>{
    if(!ok) return;

    $.ajax({url:`{{ url('admin/finance/bank-cash-accounts') }}/${id}`, method:'DELETE'})
      .done(res=>{ swalOk(res.message || 'Deleted.'); refreshDT(); })
      .fail(xhr=>{ swalErr(xhr?.responseJSON?.message || 'Failed to delete.'); });
  });
});

// set default
$(document).on('click', '.btn-default-bc', function(){
  const id = $(this).data('id');
  const name = $(this).data('name');

  swalCompat({
    icon:'warning',
    title:'Set as default?',
    text:`"${name}" will be used as default bank GL for postings.`,
    buttons: {
      cancel: { text:'Cancel', visible:true },
      confirm: { text:'Yes, set default', closeModal:true }
    }
  }).then((ok)=>{
    if(!ok) return;

    $.post("{{ route('admin.finance.bank_accounts.set_default') }}", {id})
      .done(res=>{ swalOk(res.message || 'Default set.'); refreshDT(); })
      .fail(xhr=>{ swalErr(xhr?.responseJSON?.message || 'Failed to set default.'); });
  });
});

$(function(){
  initGlSelect();
  initDT();
});
</script>
@endpush