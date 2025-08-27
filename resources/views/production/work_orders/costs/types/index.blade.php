@extends('layouts.master')
@section('title','Work Order Cost Types')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
@endpush

@section('content')
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 text-primary">Work Order Cost Types</h1>
    <div class="d-print-none">
      <button id="addBtn" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Type</button>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table id="typesTbl" class="table table-bordered w-100">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Code</th>
            <th>Name</th>
            <th>Category</th>
            <th>Default Unit</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="typeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="typeForm" class="modal-content">
      @csrf
      <input type="hidden" id="type_id">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Add Type</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Code *</label>
            <input class="form-control" id="code" name="code" maxlength="50" required>
          </div>
          <div class="col-md-8">
            <label class="form-label">Name *</label>
            <input class="form-control" id="name" name="name" maxlength="120" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Category *</label>
            <select id="category" name="category" class="form-select" required>
              @foreach($categories as $c)
                <option value="{{ $c }}">{{ ucfirst($c) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Default Unit</label>
            <select id="default_unit_id" name="default_unit_id" class="form-select">
              <option value="">— none —</option>
              @foreach($units as $u)
                <option value="{{ $u->id }}">{{ $u->name }}{{ $u->symbol ? ' ('.$u->symbol.')' : '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 form-check mt-2">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
            <label class="form-check-label" for="is_active">Active</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" id="saveBtn">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const BASE   = "{{ route('admin.production.work-orders.cost_types.index') }}".replace(/\/$/, '');
const DT_URL = "{{ route('admin.production.work-orders.cost_types.datatable') }}";
const CSRF   = @json(csrf_token());

const modal  = new bootstrap.Modal('#typeModal');
const $form  = $('#typeForm');
const $id    = $('#type_id');
const $code  = $('#code');
const $name  = $('#name');
const $cat   = $('#category');
const $unit  = $('#default_unit_id');
const $act   = $('#is_active');

const tbl = $('#typesTbl').DataTable({
  serverSide:true, responsive:true,
  ajax:{ url: DT_URL },
  columns:[
    {data:'DT_RowIndex', orderable:false, searchable:false},
    {data:'code', name:'code'},
    {data:'name', name:'name'},
    {data:'category', name:'category'},
    {data:'unit', orderable:false, searchable:false},
    {data:'is_active_badge', orderable:false, searchable:false},
    {data:'actions', orderable:false, searchable:false, className:'text-end'}
  ],
  order:[[1,'asc']],
  dom:'Blfrtip',
  buttons:[
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF'}
  ]
});

// Add
$('#addBtn').on('click', () => {
  $form[0].reset();
  $id.val('');
  $act.prop('checked', true);
  $('.modal-title').text('Add Type');
  modal.show();
});

// Save (create/update)
$('#saveBtn').on('click', function(e){
  e.preventDefault();
  const payload = {
    _token:CSRF,
    code:$code.val(), name:$name.val(),
    category:$cat.val(),
    default_unit_id:$unit.val() || null,
    is_active: $act.is(':checked') ? 1 : 0
  };

  const id = $id.val();
  const url = id ? `${BASE}/${id}` : BASE;
  const type= id ? 'PUT' : 'POST';

  $.ajax({url, type, data: payload})
    .done(res => { modal.hide(); tbl.ajax.reload(null,false); Swal.fire('Done', res.message || 'Saved', 'success'); })
    .fail(x  => {
      const msg  = x.responseJSON?.message || 'Save failed';
      const errs = x.responseJSON?.errors;
      Swal.fire('Error', errs ? Object.values(errs).flat().join('<br>') : msg, 'error');
    });
});

// Edit (delegated – works with responsive rows)
$(document).on('click','.edit-type', function(){
  const r = $(this).data('record');
  $id.val(r.id); $code.val(r.code); $name.val(r.name);
  $cat.val(r.category); $unit.val(r.default_unit_id || '');
  $act.prop('checked', !!r.is_active);
  $('.modal-title').text('Edit Type');
  modal.show();
});

// Delete
$(document).on('click','.del-type', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Delete this type?', icon:'warning', showCancelButton:true})
    .then(res=>{
      if(!res.isConfirmed) return;
      $.ajax({url:`${BASE}/${id}`, type:'DELETE', data:{_token:CSRF}})
        .done(r => { tbl.ajax.reload(null,false); Swal.fire('Deleted', r.message || 'Removed', 'success'); })
        .fail(x => { Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'); });
    });
});
</script>
@endpush
