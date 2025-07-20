@extends('layouts.master')

@section('title','Manage Modules')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css"/>
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <h5>Modules</h5>
    <div>
      <button id="bulkDeleteModulesBtn" class="btn btn-danger btn-sm">Delete Selected</button>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModuleModal">
        Add New Module
      </button>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="modulesTable" class="table table-striped table-bordered">
        <thead>
          <tr>
            <th><input type="checkbox" id="select-all-modules"></th>
            <th>Name</th>
            <th>Slug</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody><!-- AJAX --></tbody>
      </table>
    </div>
  </div>
</div>

@include('modules.modals.add')
@include('modules.modals.edit')
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(function(){
  const table = $('#modulesTable').DataTable({
    processing:true, serverSide:true,
    ajax:'{{ route("admin.modules.index") }}',
    dom:'Bfrtip',
    buttons:['excel','pdf','print'],
    columns:[
      { data:'checkbox', orderable:false, searchable:false },
      { data:'name' },
      { data:'slug' },
      { data:'actions', orderable:false, searchable:false }
    ]
  });

  // Select All
  $(document).on('click','#select-all-modules',function(){
    $('input[name="ids[]"]').prop('checked',this.checked);
  });

  // Bulk Delete
  $('#bulkDeleteModulesBtn').click(function(){
    let ids = $('input[name="ids[]"]:checked').map(function(){return this.value}).get();
    if(!ids.length) return Swal.fire('Warning','No modules selected','warning');
    Swal.fire({
      title:'Delete Selected?',
      icon:'warning', showCancelButton:true,
      confirmButtonText:'Yes, delete'
    }).then(({isConfirmed})=>{
      if(!isConfirmed) return;
      $.ajax({
        url:'{{ route("admin.modules.bulkDelete") }}',
        type:'DELETE',
        data:{_token:'{{ csrf_token() }}',ids}
      })
      .done(res=>{
        Swal.fire('Deleted',res.message,'success');
        table.ajax.reload(null,false);
      })
      .fail(()=>Swal.fire('Error','Bulk delete failed','error'));
    });
  });

  // Add Module
  $('#addModuleForm').submit(function(e){
    e.preventDefault();
    $.post('{{ route("admin.modules.store") }}',$(this).serialize())
      .done(res=>{
        $('#addModuleModal').modal('hide');
        Swal.fire('Created',res.message,'success');
        table.ajax.reload(null,false);
        this.reset();
      })
      .fail(xhr=>Swal.fire('Error',Object.values(xhr.responseJSON.errors).join('<br>'),'error'));
  });

  // Open Edit Modal
  $(document).on('click','.edit-module',function(){
    let id = $(this).data('id');
    $.get(`/admin/modules/${id}/edit`,data=>{
      $('#editModuleId').val(id);
      $('#editModuleName').val(data.module.name);
      $('#editModuleSlug').val(data.module.slug);
      $('#editModuleModal').modal('show');
    });
  });

  // Submit Edit
  $('#editModuleForm').submit(function(e){
    e.preventDefault();
    let id = $('#editModuleId').val();
    $.ajax({
      url:`/admin/modules/${id}`,
      type:'PUT',
      data:$(this).serialize()
    })
    .done(res=>{
      $('#editModuleModal').modal('hide');
      Swal.fire('Updated',res.message,'success');
      table.ajax.reload(null,false);
    })
    .fail(xhr=>Swal.fire('Error',Object.values(xhr.responseJSON.errors).join('<br>'),'error'));
  });

  // Single Delete
  $(document).on('click','.delete-module',function(){
    let id = $(this).data('id');
    Swal.fire({
      title:'Are you sure?',
      icon:'warning',showCancelButton:true,
      confirmButtonText:'Delete'
    }).then(({isConfirmed})=>{
      if(!isConfirmed) return;
      $.ajax({
        url:`/admin/modules/${id}`,type:'DELETE',
        data:{_token:'{{ csrf_token() }}'}
      })
      .done(res=>{
        Swal.fire('Deleted',res.message,'success');
        table.ajax.reload(null,false);
      })
      .fail(()=>Swal.fire('Error','Delete failed','error'));
    });
  });

});
</script>
@endpush
