@extends('layouts.master')
@section('title', 'Routing Steps · All')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
@endpush

@section('content')
<div class="container-fluid">

  {{-- Header --------------------------------------------------------------- --}}
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end mb-3">
    <div>
      <h1 class="h4 text-primary mb-2">Routing Steps <small class="text-muted">— All Routings</small></h1>
      <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0">Filter by Routing:</label>
        <select id="routingFilter" class="form-select" style="min-width: 340px"></select>
        <button id="clearRoutingFilter" class="btn btn-outline-secondary">Clear</button>
      </div>
    </div>
    <div class="d-print-none">
      <button id="addBtn" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Add Step
      </button>
      <a href="{{ route('admin.production.routings.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
    </div>
  </div>

  {{-- Table --------------------------------------------------------------- --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table id="stepsTbl" class="table table-bordered w-100">
          <thead class="table-light">
          <tr>
            <th style="width:36px"><input type="checkbox" id="checkAll"></th>
            <th style="width:60px">#</th>
            <th>Routing</th>
            <th>Variant</th>
            <th style="width:120px">Sequence</th>
            <th>Step Name</th>
            <th>Instructions</th>
            <th>Created</th>
            <th style="width:120px" class="text-end">Actions</th>
          </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Modal (Add/Edit) ------------------------------------------------------ --}}
<div class="modal fade" id="stepModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="stepForm" class="modal-content">
      @csrf
      <input type="hidden" id="step_id">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Add Step</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Routing *</label>
          <select id="routing_id" name="routing_id" class="form-select" required></select>
        </div>
        <div class="mb-3">
          <label class="form-label">Sequence</label>
          <input type="number" min="0" step="1" class="form-control" id="sequence" name="sequence" placeholder="e.g. 10">
          <div class="form-text">Use gaps (10, 20, 30) to allow inserts later.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Step Name *</label>
          <input class="form-control" id="step_name" name="step_name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Instructions</label>
          <textarea class="form-control" id="instructions" name="instructions" rows="4"></textarea>
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
/** --------- URLs / constants --------- **/
const CSRF          = @json(csrf_token());
const DT_URL        = @json(route('admin.production.routings.steps.datatable')); // GLOBAL datatable
const STORE_URL     = @json(route('admin.production.routings.steps.store'));     // POST
const UPDATE_URL    = id => @json(route('admin.production.routings.steps.update', ['step' => '__ID__'])).replace('__ID__', id);
const DEL_URL       = id => @json(route('admin.production.routings.steps.destroy', ['step' => '__ID__'])).replace('__ID__', id);
const ROUTING_S2    = @json(route('admin.production.routings.select2'));

/** --------- Select2s --------- **/
// Global filter
$('#routingFilter').select2({
  ajax: {
    url: ROUTING_S2,
    dataType: 'json',
    delay: 250,
    data: params => ({ q: params.term }),
    processResults: d => ({ results: d })
  },
  minimumInputLength: 0,
  width: 'resolve',
  placeholder: '-- any routing --',
  allowClear: true
});

// In-modal routing picker
$('#routing_id').select2({
  ajax: {
    url: ROUTING_S2,
    dataType: 'json',
    delay: 250,
    data: params => ({ q: params.term }),
    processResults: d => ({ results: d })
  },
  dropdownParent: $('#stepModal'),
  minimumInputLength: 0,
  width: '100%',
  placeholder: '-- select routing --'
});

// Clear filter
$('#clearRoutingFilter').on('click', () => {
  $('#routingFilter').val(null).trigger('change');
  tbl.ajax.reload();
});

/** --------- DataTable --------- **/
const tbl = $('#stepsTbl').DataTable({
  serverSide: true,
  responsive: true,
  ajax: {
    url: DT_URL,
    data: d => {
      d.routing_id = $('#routingFilter').val() || ''; // optional filter
    }
  },
  columns: [
    {data:'checkbox', orderable:false, searchable:false},
    {data:'id',  orderable:false},       // display only (server should SELECT it as alias)
    {data:'routing',  orderable:false, searchable:true},       // display only (server should SELECT it as alias)
    {data:'variant',  orderable:false, searchable:true},       // display only (server should SELECT it)
    {data:'sequence', name:'sequence'},                        // real column
    {data:'step_name',name:'step_name'},                       // real column
    {data:'instructions', orderable:false, searchable:true},
    {data:'created_at', name:'created_at'},
    {data:'actions', orderable:false, searchable:false, className:'text-end'}
  ],
  order: [[4,'asc'],[7,'desc']],  // sequence, then created_at
  dom: 'Blfrtip',
  buttons:[
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
  ],
  createdRow: row => row.classList.add('align-middle')
});

// apply filter
$('#routingFilter').on('change', () => tbl.ajax.reload());

/** --------- Modal & form logic --------- **/
const modal  = new bootstrap.Modal(document.getElementById('stepModal'));
const $form  = $('#stepForm');
const $id    = $('#step_id');
const $name  = $('#step_name');
const $seq   = $('#sequence');
const $inst  = $('#instructions');
const $routingSel = $('#routing_id');

// Open Add
$('#addBtn').on('click', () => {
  $form[0].reset();
  $id.val('');
  $routingSel.val(null).trigger('change');
  $('.modal-title').text('Add Step');
  modal.show();
});

// Save (create/update)
$('#saveBtn').on('click', function(e){
  e.preventDefault();

  const payload = {
    _token: CSRF,
    routing_id: $routingSel.val(), // required
    step_name: $name.val(),
    instructions: $inst.val(),
    sequence: $seq.val() || null,
  };

  const id = $id.val();
  const method = id ? 'PUT' : 'POST';
  const url    = id ? UPDATE_URL(id) : STORE_URL;

  $.ajax({ url, type: method, data: payload })
    .done(res => { modal.hide(); tbl.ajax.reload(null,false); Swal.fire('Done', res.message || 'Saved', 'success'); })
    .fail(x  => {
      const msg  = x.responseJSON?.message || 'Save failed';
      const errs = x.responseJSON?.errors;
      Swal.fire('Error', errs ? Object.values(errs).flat().join('<br>') : msg, 'error');
    });
});

// Edit (delegated for responsive rows)
$(document).on('click', '.edit-step', function(){
  const r = $(this).data('record'); // include: id, routing_id, routing_label, step_name, instructions, sequence, variant_sku, product_name
  $id.val(r.id);
  $name.val(r.step_name);
  $seq.val(r.sequence || 0);
  $inst.val(r.instructions || '');

  // preselect routing in select2
  if (r.routing_id && r.routing_label) {
    const opt = new Option(r.routing_label, r.routing_id, true, true);
    $routingSel.append(opt).trigger('change');
  } else {
    $routingSel.val(null).trigger('change');
  }

  $('.modal-title').text('Edit Step');
  modal.show();
});

// Delete (delegated)
$(document).on('click', '.del-step', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Delete this step?', icon:'warning', showCancelButton:true})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({ url: DEL_URL(id), type:'DELETE', data:{ _token: CSRF }})
        .done(res => { tbl.ajax.reload(null,false); Swal.fire('Deleted', res.message || 'Step removed', 'success'); })
        .fail(x  => { Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'); });
    });
});

// Select-all (optional)
$('#checkAll').on('change', function(){
  $('.row-check').prop('checked', this.checked);
});
</script>
@endpush
