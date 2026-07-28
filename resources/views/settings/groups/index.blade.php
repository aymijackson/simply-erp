@extends('layouts.master')

@section('title','Setting Groups')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Setting Groups</h1>
            <small class="text-muted">Core / Settings / Groups</small>
        </div>

        <button class="btn btn-primary" id="createGroupBtn">
            <i class="fas fa-plus"></i> New Group
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="filterModule" placeholder="Filter module e.g finance">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" id="filterQ" placeholder="Search code/name/module...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" id="applyFilterBtn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" id="resetFilterBtn">
                        <i class="fas fa-sync"></i> Reset
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="groupsTable" style="width:100%;">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Module</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Sort</th>
                            <th>Active</th>
                            <th style="width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <small class="text-muted">
                Best practice: module is the top-level area (sales/finance/inventory). code is a short slug (receipt, tax, numbering).
            </small>
        </div>
    </div>
</div>

@include('settings.groups.partials.modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dtUrl = "{{ route('admin.setting_groups.datatable') }}";
const storeUrl = "{{ route('admin.setting_groups.store') }}";

let DT = null;

function swalOk(msg){
  Swal.fire({icon:'success', title:'Success', text: msg || 'Done.'});
}
function swalErr(xhr, fallback){
  const msg = xhr?.responseJSON?.message || fallback || 'Something went wrong.';
  Swal.fire({icon:'error', title:'Error', text: msg});
}

function resetModal(){
  $('#groupForm')[0].reset();
  $('#group_id').val('');
  $('#is_active').prop('checked', true);
  $('#sort_order').val(0);
}

function initDT(){
  DT = $('#groupsTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url: dtUrl,
      data: function(d){
        d.module = $('#filterModule').val();
        d.q = $('#filterQ').val();
      }
    },
    columns:[
      {data:'id'},
      {data:'module'},
      {data:'code'},
      {data:'name'},
      {data:'sort_order'},
      {data:'active', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[1,'asc']]
  });
}

function refreshDT(){
  DT.ajax.reload(null,false);
}

$('#applyFilterBtn').on('click', refreshDT);
$('#resetFilterBtn').on('click', function(){
  $('#filterModule').val('');
  $('#filterQ').val('');
  refreshDT();
});

$('#createGroupBtn').on('click', function(){
  resetModal();
  $('#groupModalTitle').text('New Group');
  $('#groupModal').modal('show');
});

$('#saveGroupBtn').on('click', function(){
  const id = $('#group_id').val();
  const url = id ? (`{{ url('admin/setting-groups') }}/${id}`) : storeUrl;
  const method = id ? 'PUT' : 'POST';

  $.ajax({
    url, method,
    data: $('#groupForm').serialize()
  }).done(function(res){
    $('#groupModal').modal('hide');
    swalOk(res.message || 'Saved.');
    refreshDT();
  }).fail(function(xhr){
    swalErr(xhr,'Failed to save.');
  });
});

// Edit
$(document).on('click', '.btn-edit-group', function(){
  resetModal();
  const g = $(this).data('json');

  $('#groupModalTitle').text('Edit Group');
  $('#group_id').val(g.id);
  $('#module').val(g.module);
  $('#code').val(g.code);
  $('#name').val(g.name);
  $('#description').val(g.description || '');
  $('#sort_order').val(g.sort_order ?? 0);
  $('#is_active').prop('checked', !!g.is_active);

  $('#groupModal').modal('show');
});

// Delete
$(document).on('click', '.btn-del-group', function(){
  const id = $(this).data('id');

  Swal.fire({
    icon:'warning',
    title:'Delete this group?',
    text:'This cannot be undone. If the group has settings, deletion will be blocked.',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  }).then((r)=>{
    if(!r.isConfirmed) return;

    $.ajax({url:`{{ url('admin/setting-groups') }}/${id}`, method:'DELETE'})
      .done(res=>{ swalOk(res.message); refreshDT(); })
      .fail(xhr=>swalErr(xhr,'Failed to delete.'));
  });
});

$(function(){
  initDT();
});
</script>
@endpush
