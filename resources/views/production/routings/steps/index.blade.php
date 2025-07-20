@extends('layouts.master')
@section('title','Routing Steps')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h4 class="text-primary">Routing Steps</h4>
        <div>
            <button class="btn btn-danger d-none" id="bulkDeleteBtn"><i class="fas fa-trash-alt"></i> Delete Selected</button>
            <button class="btn btn-primary" id="addBtn"><i class="fas fa-plus me-1"></i> Add Step</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered w-100" id="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Routing</th>
                        <th>Name</th>
                        <th>Seq</th>
                        <th>Duration (min)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="entityModal" tabindex="-1">
 <div class="modal-dialog">
  <form class="modal-content" id="entityForm">@csrf
    <input type="hidden" id="id" name="id">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title" id="modalTitle">Add Step</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="mb-2">
        <label class="form-label">Routing</label>
        <select class="form-select" name="routing_id" id="routing_id" required>
          <option value="">-- select --</option>
          @foreach($routings as $id=>$name) <option value="{{ $id }}">{{ $name }}</option>@endforeach
        </select>
      </div>
      <div class="mb-2">
        <label class="form-label">Step Name</label>
        <input class="form-control" name="step_name" id="step_name" required>
      </div>
      <div class="mb-2">
        <label class="form-label">Description</label>
        <textarea class="form-control" name="description" id="description"></textarea>
      </div>
      <div class="mb-2">
        <label class="form-label">Sequence #</label>
        <input type="number" class="form-control" name="sequence" id="sequence" min="0" value="0">
      </div>
      <div class="mb-2">
        <label class="form-label">Duration (minutes)</label>
        <input type="number" class="form-control" name="duration_minutes" id="duration_minutes" min="0">
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Save</button></div>
  </form>
 </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
 const modal = new bootstrap.Modal('#entityModal');
 const table = $('#table').DataTable({
   ajax: '{{ route("admin.production.routingsteps.datatable") }}',
   columns:[
     {data:'checkbox', orderable:false, searchable:false},
     {data:'id'},
     {data:'routing_name'},
     {data:'step_name'},
     {data:'sequence'},
     {data:'duration_minutes'},
     {data:'actions', orderable:false, searchable:false}
   ]
 });

 $('#selectAll').on('change',()=>$('.row-checkbox').prop('checked',$('#selectAll').prop('checked')));

 $('#addBtn').click(()=>{
   $('#entityForm')[0].reset(); $('#id').val('');
   $('#modalTitle').text('Add Step'); modal.show();
 });

 $('#table').on('click','.edit-btn',function(){
    const r=$(this).data('record');
    $('#id').val(r.id); $('#routing_id').val(r.routing_id);
    $('#step_name').val(r.step_name); $('#description').val(r.description);
    $('#sequence').val(r.sequence); $('#duration_minutes').val(r.duration_minutes);
    $('#modalTitle').text('Edit Step'); modal.show();
 });

 $('#entityForm').submit(function(e){
   e.preventDefault(); const id=$('#id').val();
   const url=id? `/admin/production/routing-steps/${id}` : '{{ route("admin.production.routingsteps.store") }}';
   $.post(url,$(this).serialize()+(id?'&_method=PUT':''),()=>{ table.ajax.reload(null,false); modal.hide(); });
 });

 $('#table').on('click','.delete-btn',function(){
   const id=$(this).data('id');
   $.ajax({url:`/admin/production/routing-steps/${id}`,type:'DELETE',data:{_token:'{{ csrf_token() }}'},success:()=>table.ajax.reload(null,false)});
 });
})();
</script>
@endpush