@extends('layouts.master')
@section('title','Bill of Materials')

@section('content')
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 text-primary"><i class="fas fa-sitemap me-1"></i> Bill of Materials</h1>
      <button id="addBtn" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i> New BOM
      </button>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table id="bomTbl" class="table table-bordered w-100">
         <thead class="table-light">
           <tr>
             <th>#</th><th>Product</th><th>Name</th><th class="text-end">Items</th>
             <th class="text-end">Actions</th>
           </tr>
         </thead>
      </table>
    </div>
  </div>

  {{-- include modal --}}
  @include('production.boms.partials.modal')

</div>
@endsection

@push('scripts')
<script>
$(function(){

  /* ---------- DataTable ---------- */
  const tbl = $('#bomTbl').DataTable({
      serverSide:true, responsive:true,
      ajax: "{{ route('admin.production.boms.datatable') }}",
      columns:[
         {data:'id'},
         {data:'product_name'},
         {data:'name'},
         {data:'item_count', className:'text-end'},
         {data:'actions',    orderable:false, searchable:false, className:'text-end'}
      ],
      drawCallback(){
          $('#bomTbl tbody')
            .off('click','.edit-btn')
            .on('click','.edit-btn', e=>{
               $.getJSON(`/admin/production/boms/${$(e.currentTarget).data('id')}`,
                         openBomModal );
            });
      }
  });

  /* ---------- open empty modal ---------- */
  $('#addBtn').click(()=> openBomModal());

  /* ---------- modal helpers in _modal.blade.php will do the heavy lifting ---------- */
});
</script>
@endpush
