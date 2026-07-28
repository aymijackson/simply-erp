@extends('layouts.master')
@section('title','Customer Returns')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 text-primary"><i class="fas fa-undo me-1"></i> Customer Returns</h1>
      <button id="addBtn" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Return</button>
  </div>

  <div class="card shadow-sm">
     <div class="card-body">
       <table id="retTbl" class="table table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Store</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Posted at</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
       </table>
     </div>
  </div>
</div>

@include('inventory.returns.customer.partials.modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const BASE_URL = `{{ url('admin/inventory/returns/customer') }}`;

window.customerReturnsTable = $('#retTbl').DataTable({
   serverSide:true,
   responsive:true,
   processing:true,
   ajax:"{{ route('admin.inventory.returns.customer.datatable') }}",
   columns:[
     {data:'id'}, // or return_no if you output it from datatable
     {data:'location_store', defaultContent:'—'},
     {data:'customer_name', defaultContent:'—'},
     {data:'status_badge', orderable:false, searchable:false},
     {data:'posted_info', defaultContent:'Not Posted/ Info. not available'},
     {data:'actions', orderable:false, searchable:false, className:'text-end'},
   ]
});

window.resetAndReloadCustomerReturnsTable = function(){
  if(!window.customerReturnsTable) return;

  // reset search + go to page 1
  window.customerReturnsTable.search('');
  window.customerReturnsTable.page('first');

  // reload
  window.customerReturnsTable.ajax.reload(null, true);
};

$('#addBtn').on('click', function(){
  window.openCustomerReturnModal(null);
});

/**
 * ✅ FIX: Edit must fetch JSON from server (do NOT use data-json)
 * We call: GET /admin/inventory/returns/customer/{id}/json
 */
$(document).on('click','.edit-btn', function(){
  const id = $(this).data('id');
  if(!id) return;

  $.get(`${BASE_URL}/${id}/json`)
    .done(payload => window.openCustomerReturnModal(payload))
    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed to load record', 'error'));
});

$(document).on('click','.approve-btn', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Approve?',icon:'question',showCancelButton:true})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${BASE_URL}/${id}/approve`)
        .done(()=>{ resetAndReloadCustomerReturnsTable(); Swal.fire('Approved','','success'); })
        .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
    });
});

$(document).on('click','.post-btn', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Post?',icon:'question',showCancelButton:true})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`${BASE_URL}/${id}/post`)
        .done(()=>{ resetAndReloadCustomerReturnsTable(); Swal.fire('Posted','','success'); })
        .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
    });
});

$(document).on('click','.delete-btn', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Delete draft?',icon:'warning',showCancelButton:true,confirmButtonText:'Delete'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({
        url: `${BASE_URL}/${id}`,
        method:'DELETE'
      })
      .done(()=>{ resetAndReloadCustomerReturnsTable(); Swal.fire('Deleted','','success'); })
      .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
    });
});
</script>
@endpush
