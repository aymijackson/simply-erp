@extends('layouts.master')
@section('title','Supplier Returns')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 text-primary"><i class="fas fa-undo me-1"></i> Supplier Returns</h1>
      <button id="addBtn" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Return</button>
  </div>

  <div class="card shadow-sm">
     <div class="card-body">
       <table id="retTbl" class="table table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>No</th><th>Store</th><th>Supplier</th>
              <th>Status</th><th>Posted at</th><th class="text-end">Actions</th>
            </tr>
          </thead>
       </table>
     </div>
  </div>
</div>

@include('inventory.returns.suppliers.partials.modal', ['request_uuid'=>$request_uuid])

@endsection

@push('scripts')
<script>
$(function () {
  $.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

  // ✅ Prevent double-init (very common cause of “reload not working”)
  if ($.fn.dataTable.isDataTable('#retTbl')) {
    window.retTbl = $('#retTbl').DataTable();
  } else {
    window.retTbl = $('#retTbl').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,

      // if your project has stateSave enabled globally, this will still be overridden by global defaults.
      // Our reset function below clears state anyway, so you're safe.
      // stateSave: false,

      ajax: "{{ route('admin.inventory.returns.supplier.datatable') }}",
      columns: [
        {data:'id'},
        {data:'location_store', defaultContent:'-'},
        {data:'supplier', defaultContent:'-'},
        {data:'status_badge', orderable:false, searchable:false},
        {data:'posted_info', defaultContent:'Not Posted/ Info. not available'},
        {data:'actions', orderable:false, searchable:false, className:'text-end'}
      ],
      order: [[0,'desc']],
      drawCallback: function(){
        // edit
        $('#retTbl tbody').off('click','.edit-btn').on('click','.edit-btn', function(){
          const payload = $(this).data('json');
          window.openReturnModal(payload);
        });

        // approve/post/delete
        $('#retTbl tbody').off('click','.approve-btn').on('click','.approve-btn', doApprove);
        $('#retTbl tbody').off('click','.post-btn').on('click','.post-btn', doPost);
        $('#retTbl tbody').off('click','.delete-btn').on('click','.delete-btn', doDelete);
      }
    });
  }

  // ✅ HARD RESET (works even when stateSave is on)
  window.resetAndReloadReturnsTable = function(){
    if(!window.retTbl) return;

    // clear saved state (critical if stateSave is true anywhere)
    if (window.retTbl.state && typeof window.retTbl.state.clear === 'function') {
      window.retTbl.state.clear();
    }

    window.retTbl.search('');
    window.retTbl.columns().search('');
    window.retTbl.order([[0,'desc']]);

    // reset paging + reload
    window.retTbl.page('first');
    window.retTbl.ajax.reload(null, true);
  };

  // New return
  $('#addBtn').on('click', function(){
    window.openReturnModal(null);
  });

  function doApprove(){
    const id = $(this).data('id');
    Swal.fire({title:'Approve?',icon:'question',showCancelButton:true})
      .then(r=>{
        if(!r.isConfirmed) return;
        $.post(`/admin/inventory/returns/supplier/${id}/approve`)
          .done(()=>{ window.resetAndReloadReturnsTable(); Swal.fire('Approved','','success'); })
          .fail(xhr=> Swal.fire('Error', xhr.responseJSON?.message || 'Approve failed', 'error'));
      });
  }

  function doPost(){
    const id = $(this).data('id');
    Swal.fire({title:'Post?',icon:'question',showCancelButton:true})
      .then(r=>{
        if(!r.isConfirmed) return;
        $.post(`/admin/inventory/returns/supplier/${id}/post`)
          .done(()=>{ window.resetAndReloadReturnsTable(); Swal.fire('Posted','','success'); })
          .fail(xhr=> Swal.fire('Error', xhr.responseJSON?.message || 'Post failed', 'error'));
      });
  }

  function doDelete(){
    const id = $(this).data('id');
    Swal.fire({
      title:'Delete draft?',
      text:'This will permanently remove the draft return.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Yes, delete',
    }).then(r=>{
      if(!r.isConfirmed) return;

      $.ajax({
        url: `/admin/inventory/returns/supplier/${id}`,
        type: 'DELETE',
      })
      .done(()=>{ window.resetAndReloadReturnsTable(); Swal.fire('Deleted','','success'); })
      .fail(xhr=> Swal.fire('Error', xhr.responseJSON?.message || 'Delete failed', 'error'));
    });
  }
});
</script>
@endpush
