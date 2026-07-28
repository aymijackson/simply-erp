@extends('layouts.master')

@section('title','Expense Categories')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Expense Categories</h1>
      <small class="text-muted">Finance / Expenses</small>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-danger d-none" id="bulkDeleteBtn">
        <i class="fas fa-trash"></i> Delete Selected
      </button>

      <button class="btn btn-primary" id="createBtn">
        <i class="fas fa-plus"></i> New Category
      </button>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="text-muted small">Status</label>
          <select class="form-control" id="filterActive">
            <option value="">All</option>
            <option value="1">Active</option>
            <option value="0">Disabled</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="filterQ" placeholder="category name, GL code/name...">
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
        Map each category to a <b>GL expense account</b> (optional). Posting can use this as a default.
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="catsTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
              <th style="width:80px;">ID</th>
              <th>Name</th>
              <th>GL Account</th>
              <th style="width:110px;">Status</th>
              <th style="width:140px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <small class="text-muted">
        Delete is blocked if the category has been used on any expense. Disable instead.
      </small>
    </div>
  </div>
</div>

@include('finance.expense_categories.partials.modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dtUrl      = "{{ route('admin.finance.expense_categories.datatable') }}";
const storeUrl   = "{{ route('admin.finance.expense_categories.store') }}";
const baseUrl    = "{{ url('admin/finance/expense-categories') }}";
const glUrl      = "{{ route('admin.finance.expense_categories.gl_accounts') }}";
const bulkDelUrl = "{{ route('admin.finance.expense_categories.bulk_delete') }}";

let DT = null;

/**
 * SweetAlert-safe wrapper
 * - SweetAlert v1: window.swal(title, text, icon)
 * - SweetAlert2: window.Swal.fire(...) or window.swal.fire(...)
 * Fixes: "Class constructor ... cannot be invoked without 'new'"
 */
function swalWrapper(optsOrTitle, text, icon){
  // SweetAlert2 (preferred)
  if (window.Swal && typeof window.Swal.fire === 'function') {
    if (typeof optsOrTitle === 'string') return window.Swal.fire({title: optsOrTitle, text: text || '', icon: icon || 'info'});
    return window.Swal.fire(optsOrTitle || {});
  }
  if (window.swal && typeof window.swal.fire === 'function') {
    if (typeof optsOrTitle === 'string') return window.swal.fire({title: optsOrTitle, text: text || '', icon: icon || 'info'});
    return window.swal.fire(optsOrTitle || {});
  }
  // SweetAlert v1
  if (typeof window.swal === 'function') {
    if (typeof optsOrTitle === 'string') return window.swal(optsOrTitle, text || '', icon || 'info');
    // v1 doesn't accept full opts reliably; fallback
    return window.swal((optsOrTitle && optsOrTitle.title) || 'Info', (optsOrTitle && optsOrTitle.text) || '', (optsOrTitle && optsOrTitle.icon) || 'info');
  }
  alert((typeof optsOrTitle === 'string') ? (optsOrTitle + ' ' + (text||'')) : 'Done');
}

function swalOk(msg){ return swalWrapper({icon:'success', title:'Success', text: msg || 'Done.'}); }
function swalErr(msg){ return swalWrapper({icon:'error', title:'Error', text: msg || 'Something went wrong.'}); }

function initDT(){
  DT = $('#catsTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url: dtUrl,
      data: function(d){
        d.active = $('#filterActive').val();
        d.q = $('#filterQ').val();
      }
    },
    columns:[
      {data:'id', orderable:false, searchable:false, render:(id)=>`<input type="checkbox" class="row-check" value="${id}">`},
      {data:'id'},
      {data:'name'},
      {data:'gl', orderable:false},
      {data:'active', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[1,'desc']],
  });
}

function refreshDT(){ DT.ajax.reload(null,false); }

$('#applyFiltersBtn').on('click', ()=> refreshDT());
$('#resetFiltersBtn').on('click', ()=>{
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

  swalWrapper({
    icon:'warning',
    title:'Delete selected?',
    text:'This will delete selected expense categories (unused only).',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  }).then((r)=>{
    const ok = (r && (r.isConfirmed === true || r === true));
    if(!ok) return;

    $.post(bulkDelUrl, {ids})
      .done(res=>{
        swalOk(res.message || 'Deleted.');
        refreshDT();
        $('#bulkDeleteBtn').addClass('d-none');
        $('#checkAll').prop('checked', false);
      })
      .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to delete.'));
  });
});

/** ===== Modal ===== */

function initGLSelect(){
  $('#gl_account_id').select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select GL expense account...',
    allowClear:true,
    dropdownParent: $('#catModal'),
    ajax:{
      url: glUrl,
      dataType:'json',
      delay: 200,
      data: params => ({ q: params.term || '' }),
      processResults: data => data,
      cache:true
    }
  });
}

function resetModal(){
  $('#catForm')[0].reset();
  $('#cat_id').val('');
  $('#is_active').prop('checked', true);

  // reset select2
  $('#gl_account_id').val(null).trigger('change');
}

$('#createBtn').on('click', function(){
  resetModal();
  $('#catModalTitle').text('New Expense Category');
  $('#catModal').modal('show');
});

$('#saveCatBtn').on('click', function(){
  const id = $('#cat_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? (`${baseUrl}/${id}`) : storeUrl;

  $.ajax({url, method, data: $('#catForm').serialize()})
    .done(res=>{
      $('#catModal').modal('hide');
      swalOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(xhr=>{
      swalErr(xhr?.responseJSON?.message || 'Failed to save.');
    });
});

// Edit
$(document).on('click', '.btn-edit-cat', function(){
  resetModal();
  const c = $(this).data('json');

  $('#catModalTitle').text('Edit Expense Category');
  $('#cat_id').val(c.id);
  $('#name').val(c.name || '');
  $('#is_active').prop('checked', !!c.is_active);

  $('#gl_account_id').val(null).trigger('change');
  if (c.gl_account_id) {
    const opt = new Option(c.gl_label || ('#'+c.gl_account_id), c.gl_account_id, true, true);
    $('#gl_account_id').append(opt).trigger('change');
  }

  $('#catModal').modal('show');
});

// Delete
$(document).on('click', '.btn-del-cat', function(){
  const id = $(this).data('id');

  swalWrapper({
    icon:'warning',
    title:'Delete this category?',
    text:'Delete is blocked if it has been used on any expense.',
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

$(function(){
  initGLSelect();
  initDT();
});
</script>
@endpush