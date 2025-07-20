@extends('layouts.master')
@section('title','Work-Order Steps')

@section('content')
<div class="container-fluid">
 <div class="d-flex justify-content-between mb-3">
  <h4 class="text-primary">Work-Order Steps</h4>
  <div>
    <button class="btn btn-danger d-none" id="bulkDeleteBtn"><i class="fas fa-trash-alt"></i> Delete</button>
    <button class="btn btn-primary" id="addBtn"><i class="fas fa-plus me-1"></i> Add Step</button>
  </div>
 </div>

 <div class="card"><div class="card-body">
  <table class="table table-bordered w-100" id="table">
    <thead>
     <tr>
       <th><input type="checkbox" id="selectAll"></th>
       <th>#</th><th>Work Order</th><th>Name</th><th>Seq</th><th>Status</th><th>Performed By</th><th>Actions</th>
     </tr>
    </thead>
  </table>
 </div></div>
</div>

{{-- Modal --}}
<div class="modal fade" id="entityModal" tabindex="-1">
 <div class="modal-dialog">
  <form class="modal-content" id="entityForm">@csrf
    <input type="hidden" id="id" name="id">
    <div class="modal-header bg-primary text-white"><h5 id="modalTitle">Add Step</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-2"><label>Work Order</label>
        <select class="form-select" name="work_order_id" id="work_order_id" required>
          <option value="">-- select --</option>
          @foreach($workOrders as $id=>$ref)<option value="{{ $id }}">{{ $ref }}</option>@endforeach
        </select>
      </div>

      <div class="mb-2"><label>Step Name</label><input name="step_name" id="step_name" class="form-control" required></div>
      <div class="mb-2"><label>Description</label><textarea name="description" id="description" class="form-control"></textarea></div>
      <div class="mb-2"><label>Sequence</label><input type="number" class="form-control" name="sequence" id="sequence" value="0"></div>

      <div class="mb-2"><label>Status</label>
        <select class="form-select" name="status" id="status">
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
      </div>

      <div class="mb-2"><label>Performed By (Employee)</label>
        <select class="form-select" name="performed_by" id="performed_by">
          <option value="">-- n/a --</option>
          @foreach($employees as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
        </select>
      </div>

      <div class="mb-2"><label>Start Time</label><input type="datetime-local" class="form-control" name="started_at" id="started_at"></div>
      <div class="mb-2"><label>Completed Time</label><input type="datetime-local" class="form-control" name="completed_at" id="completed_at"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Save</button></div>
  </form>
 </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
 const modal=new bootstrap.Modal('#entityModal');
 const table=$('#table').DataTable({
   ajax:'{{ route("admin.production.workordersteps.datatable") }}',
   columns:[
     {data:'checkbox',orderable:false,searchable:false},{data:'id'},
     {data:'work_order_ref'},{data:'step_name'},{data:'sequence'},
     {data:'status_badge',orderable:false,searchable:false},
     {data:'performer'},{data:'actions',orderable:false,searchable:false}
   ]
 });

 $('#addBtn').click(()=>{ $('#entityForm')[0].reset(); $('#id').val(''); $('#modalTitle').text('Add Step'); modal.show(); });

 $('#table').on('click','.edit-btn',function(){
   const r=$(this).data('record');
   $('#id').val(r.id); $('#work_order_id').val(r.work_order_id);
   $('#step_name').val(r.step_name); $('#description').val(r.description);
   $('#sequence').val(r.sequence); $('#status').val(r.status);
   $('#performed_by').val(r.performed_by); $('#started_at').val(r.started_at); $('#completed_at').val(r.completed_at);
   $('#modalTitle').text('Edit Step'); modal.show();
 });

 $('#entityForm').submit(function(e){
   e.preventDefault();
   const id=$('#id').val();
   const url=id? `/admin/production/work-order-steps/${id}` : '{{ route("admin.production.workordersteps.store") }}';
   $.post(url,$(this).serialize()+(id?'&_method=PUT':''),()=>{table.ajax.reload(null,false);modal.hide();});
 });

 $('#table').on('click','.delete-btn',function(){
   const id=$(this).data('id');
   $.ajax({url:`/admin/production/work-order-steps/${id}`,type:'DELETE',data:{_token:'{{ csrf_token() }}'},success:()=>table.ajax.reload(null,false)});
 });
})();
</script>
@endpush