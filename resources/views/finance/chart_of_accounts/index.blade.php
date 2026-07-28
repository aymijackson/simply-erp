@extends('layouts.master')

@section('title','Chart of Accounts')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Chart of Accounts</h1>
      <small class="text-muted">Finance / Setup</small>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('admin.finance.accounts.mappings.index') ?? '#' }}" class="btn btn-outline-secondary">
        <i class="fas fa-project-diagram me-1"></i> Account Mappings
      </a>

      <button class="btn btn-primary" id="createAccountBtn">
        <i class="fas fa-plus me-1"></i> New Account
      </button>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-header fw-bold">
      <i class="fas fa-filter me-1"></i> Filters
    </div>
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="text-muted small">Type</label>
          <select class="form-control" id="filterType">
            <option value="">All</option>
            @foreach($types as $t)
              <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->category }})</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="text-muted small">Status</label>
          <select class="form-control" id="filterActive">
            <option value="">All</option>
            <option value="1">Active</option>
            <option value="0">Disabled</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="filterQ" placeholder="code or name...">
        </div>

        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyFiltersBtn">
            <i class="fas fa-check me-1"></i> Apply
          </button>
          <button class="btn btn-outline-secondary w-100" id="resetFiltersBtn">
            <i class="fas fa-undo me-1"></i> Reset
          </button>
        </div>
      </div>

      <small class="text-muted d-block mt-2">
        Tip: Use <b>Account Mappings</b> to connect Sales/Payments/Inventory flows to these GL accounts.
      </small>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="coaTable" style="width:100%;">
          <thead class="bg-light">
            <tr>
              <th style="width:40px;">
                <input type="checkbox" id="checkAll">
              </th>
              <th>Code</th>
              <th>Name</th>
              <th>Category</th>
              <th>Type</th>
              <th>Parent</th>
              <th style="width:100px;">Control</th>
              <th style="width:120px;">Manual</th>
              <th style="width:110px;">Status</th>
              <th style="width:140px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <button class="btn btn-danger d-none mt-2" id="bulkDeleteBtn">
        <i class="fas fa-trash me-1"></i> Delete Selected
      </button>
    </div>
  </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="coaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="coaModalTitle">New Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="coaForm">
          <input type="hidden" id="coa_id" name="id">

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="text-muted small">Account Type</label>
              <select class="form-control" name="account_type_id" id="account_type_id" required>
                @foreach($types as $t)
                  <option value="{{ $t->id }}">
                    {{ $t->name }} ({{ strtoupper($t->category) }} / {{ strtoupper($t->normal_balance) }})
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Code</label>
              <input type="text" class="form-control" name="code" id="code" required placeholder="e.g. 1000">
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Status</label>
              <select class="form-control" name="is_active" id="is_active">
                <option value="1">Active</option>
                <option value="0">Disabled</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Name</label>
              <input type="text" class="form-control" name="name" id="name" required placeholder="e.g. Cash and Cash Equivalents">
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Parent Account (optional)</label>
              {{-- Select2 Ajax --}}
              <select class="form-control" name="parent_id" id="parent_id" style="width:100%;">
                <option value="">— None —</option>
              </select>
              <small class="text-muted">Search by code/name.</small>
            </div>

            <div class="col-md-12 mb-3">
              <label class="text-muted small">Description</label>
              <textarea class="form-control" name="description" id="description" rows="2" placeholder="Optional notes..."></textarea>
            </div>

            <div class="col-md-6 mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_control" name="is_control" value="1">
                <label class="form-check-label" for="is_control">
                  Control Account (header/grouping)
                </label>
              </div>
              <small class="text-muted d-block">Control accounts typically should not receive postings.</small>
            </div>

            <div class="col-md-6 mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="allow_manual_posting" name="allow_manual_posting" value="1" checked>
                <label class="form-check-label" for="allow_manual_posting">
                  Allow Manual Posting
                </label>
              </div>
              <small class="text-muted d-block">If disabled, only system flows can post here.</small>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" type="button" id="saveCoaBtn">
          <i class="fas fa-save me-1"></i> Save
        </button>
      </div>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dtUrl      = "{{ route('admin.finance.chart_of_accounts.datatable') }}";
const storeUrl   = "{{ route('admin.finance.chart_of_accounts.store') }}";
const parentsUrl = "{{ route('admin.finance.chart_of_accounts.parents') }}";
const baseUrl    = "{{ url('admin/finance/chart-of-accounts') }}";

let DT = null;

/**
 * ✅ Force a safe swal() function.
 * - If SweetAlert2 exists => use Swal.fire(...)
 * - Else if SweetAlert v1 exists => use original swal(...)
 * - Prevent the "Class constructor Un cannot be invoked without 'new'" issue by overwriting window.swal with a function.
 */
(function(){
  const original = window.swal;

  window.swal = function(a,b,c){
    // SweetAlert2
    if (window.Swal && typeof window.Swal.fire === 'function') {
      if (typeof a === 'string') return window.Swal.fire({ title: a, text: b || '', icon: c || 'info' });
      return window.Swal.fire(a || {});
    }

    // SweetAlert v1
    if (typeof original === 'function') return original(a,b,c);

    // Fallback alert
    if (typeof a === 'string') { alert(a + (b ? ("\n\n"+b) : "")); return; }
    alert((a && (a.title || a.text)) ? ((a.title||'') + "\n\n" + (a.text||'')) : 'Alert');
  };
})();

function swalOk(msg){
  swal({ icon:'success', title:'Success', text: msg || 'Done.' });
}
function swalErr(xhrOrMsg, fallback){
  let msg = fallback || 'Something went wrong.';
  if (typeof xhrOrMsg === 'string') msg = xhrOrMsg;
  else msg = xhrOrMsg?.responseJSON?.message || xhrOrMsg?.responseText || msg;
  swal({ icon:'error', title:'Error', text: msg });
}

function resetModal(){
  $('#coaForm')[0].reset();
  $('#coa_id').val('');

  // reset select2
  $('#parent_id').val(null).trigger('change');

  // defaults
  $('#allow_manual_posting').prop('checked', true);
  $('#is_control').prop('checked', false);
  $('#is_active').val('1');
}

function initParentSelect2(){
  if (!$.fn.select2) return;

  $('#parent_id').select2({
    theme: 'bootstrap-5',
    dropdownParent: $('#coaModal'),
    placeholder: '— None —',
    allowClear: true,
    ajax: {
      url: parentsUrl,
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term || '' }),
      processResults: data => data
    }
  });
}

function initDT(){
  DT = $('#coaTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    pageLength: 10,
    ajax: {
      url: dtUrl,
      data: function(d){
        d.type_id = $('#filterType').val();
        d.active  = $('#filterActive').val();
        d.q       = $('#filterQ').val();
      }
    },
    columns: [
      { data: 'id', orderable:false, searchable:false, render: function(id){
          return `<input type="checkbox" class="row-check" value="${id}">`;
      }},
      { data: 'code' },
      { data: 'name' },
      { data: 'category' },
      { data: 'type' },
      { data: 'parent', orderable:false },
      { data: 'control', orderable:false, searchable:false },
      { data: 'manual', orderable:false, searchable:false },
      { data: 'active', orderable:false, searchable:false },
      { data: 'actions', orderable:false, searchable:false },
    ],
    order: [[1,'asc']]
  });
}

function refreshDT(){
  if (DT) DT.ajax.reload(null,false);
}

$('#applyFiltersBtn').on('click', function(){ refreshDT(); });
$('#resetFiltersBtn').on('click', function(){
  $('#filterType').val('');
  $('#filterActive').val('');
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
  if(!ids.length) return;

  swal({
    icon:'warning',
    title:'Delete selected?',
    text:'Only accounts with no activity and no children will be deleted.',
    buttons: { cancel:true, confirm:{ text:'Yes, delete' } },
    dangerMode:true
  }).then((ok)=>{
    if(!ok) return;

    $.post("{{ route('admin.finance.chart_of_accounts.bulk_delete') }}", { ids })
      .done(res=>{
        swalOk(res.message || 'Deleted.');
        $('#bulkDeleteBtn').addClass('d-none');
        $('#checkAll').prop('checked',false);
        refreshDT();
      })
      .fail(xhr=>swalErr(xhr,'Failed to delete.'));
  });
});

$('#createAccountBtn').on('click', function(){
  resetModal();
  $('#coaModalTitle').text('New Account');
  $('#coaModal').modal('show');
});

$('#saveCoaBtn').on('click', function(){
  const id = $('#coa_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : storeUrl;

  const payload = $('#coaForm').serialize();

  $.ajax({ url, method, data: payload })
    .done(function(res){
      $('#coaModal').modal('hide');
      swalOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(function(xhr){
      swalErr(xhr,'Failed to save.');
    });
});

// edit
$(document).on('click', '.btn-edit-coa', function(){
  resetModal();
  const a = $(this).data('json');

  $('#coaModalTitle').text('Edit Account');
  $('#coa_id').val(a.id);
  $('#account_type_id').val(a.account_type_id);
  $('#code').val(a.code);
  $('#name').val(a.name);
  $('#description').val(a.description || '');
  $('#is_active').val(String(a.is_active || 0));

  $('#is_control').prop('checked', !!a.is_control);
  $('#allow_manual_posting').prop('checked', !!a.allow_manual_posting);

  // Set select2 parent option
  if (a.parent_id) {
    const opt = new Option(a.parent_label || 'Parent', a.parent_id, true, true);
    $('#parent_id').append(opt).trigger('change');
  } else {
    $('#parent_id').val(null).trigger('change');
  }

  $('#coaModal').modal('show');
});

// delete
$(document).on('click', '.btn-del-coa', function(){
  const id = $(this).data('id');

  swal({
    icon:'warning',
    title:'Delete this account?',
    text:'This cannot be undone. If it has activity, deletion will be blocked.',
    buttons: { cancel:true, confirm:{ text:'Yes, delete' } },
    dangerMode:true
  }).then((ok)=>{
    if(!ok) return;

    $.ajax({ url:`${baseUrl}/${id}`, method:'DELETE' })
      .done(res=>{
        swalOk(res.message || 'Deleted.');
        refreshDT();
      })
      .fail(xhr=>swalErr(xhr,'Failed to delete.'));
  });
});

$(function(){
  initParentSelect2();
  initDT();
});
</script>
@endpush