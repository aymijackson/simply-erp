@extends('layouts.master')
@section('title','Work-Order Materials')

@section('content')
<div class="container-fluid">
 <div class="d-flex justify-content-between mb-3">
  <h4 class="text-primary">Work-Order Materials</h4>
  <div>
   <button class="btn btn-danger d-none" id="bulkDeleteBtn"><i class="fas fa-trash-alt"></i> Delete Selected</button>
   <button class="btn btn-primary" id="addBtn"><i class="fas fa-plus me-1"></i> Add Material</button>
  </div>
 </div>

 <div class="card"><div class="card-body">
  <table class="table table-bordered w-100" id="table">
    <thead>
     <tr>
        <th><input type="checkbox" id="selectAll"></th>
        <th>#</th><th>Work Order</th><th>Material</th>
        <th>Qty Required</th><th>Qty Issued</th><th>Unit</th><th>Actions</th>
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
      <div class="modal-header bg-primary text-white"><h5 id="modalTitle">Add Material</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2">
          <label>Work Order</label>
          <select class="form-select" name="work_order_id" id="work_order_id" required>
            <option value="">-- select --</option>
            @foreach($workOrders as $id=>$ref)<option value="{{ $id }}">{{ $ref }}</option>@endforeach
          </select>
        </div>
        <div class="mb-2">
          <label>Raw Material</label>
          <select class="form-select" name="material_id" id="material_id" required>
            <option value="">-- select --</option>
            @foreach($materials as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
          </select>
        </div>
        <div class="mb-2"><label>Quantity Required</label><input type="number" step="0.0001" name="quantity_required" id="quantity_required" class="form-control" required></div>
        <div class="mb-2"><label>Quantity Issued</label><input type="number" step="0.0001" name="quantity_issued" id="quantity_issued" class="form-control"></div>
        <div class="mb-2"><label>Unit</label><input name="unit" id="unit" class="form-control"></div>
        <div class="mb-2"><label>Remarks</label><textarea name="remarks" id="remarks" class="form-control"></textarea></div>
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
   ajax:'{{ route("admin.production.workordermaterials.datatable") }}',
   columns:[
     {data:'checkbox',orderable:false,searchable:false},{data:'id'},
     {data:'work_order_ref'},{data:'material_name'},
     {data:'quantity_required'},{data:'quantity_issued'},
     {data:'unit'},{data:'actions',orderable:false,searchable:false}
   ]
 });

 $('#selectAll').on('change',()=>$('.row-checkbox').prop('checked',$('#selectAll').prop('checked')));

 $('#addBtn').click(()=>{ $('#entityForm')[0].reset(); $('#id').val(''); $('#modalTitle').text('Add Material'); modal.show(); });

 $('#table').on('click','.edit-btn',function(){
   const r=$(this).data('record');
   $('#id').val(r.id); $('#work_order_id').val(r.work_order_id);
   $('#material_id').val(r.material_id); $('#quantity_required').val(r.quantity_required);
   $('#quantity_issued').val(r.quantity_issued); $('#unit').val(r.unit); $('#remarks').val(r.remarks);
   $('#modalTitle').text('Edit Material'); modal.show();
 });

 $('#entityForm').submit(function(e){
   e.preventDefault();
   const id=$('#id').val();
   const url=id? `/admin/production/work-order-materials/${id}` : '{{ route("admin.production.workordermaterials.store") }}';
   $.post(url,$(this).serialize()+(id?'&_method=PUT':''),()=>{table.ajax.reload(null,false);modal.hide();});
 });

 $('#table').on('click','.delete-btn',function(){
   const id=$(this).data('id');
   $.ajax({url:`/admin/production/work-order-materials/${id}`,type:'DELETE',data:{_token:'{{ csrf_token() }}'},success:()=>table.ajax.reload(null,false)});
 });
})();
</script>
@endpush