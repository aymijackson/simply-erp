@extends('layouts.master')
@section('title','Routings')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-primary">Routings</h1>
    <div>
      <button class="btn btn-primary" id="addRoutingBtn">
        <i class="fas fa-plus me-1"></i> New Routing
      </button>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table id="routingTbl" class="table table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Variant (SKU)</th>
              <th>Product</th>
              <th>Name</th>
              <th>Description</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

</div>

{{-- Modal --}}
<div class="modal fade" id="routingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="routingForm" class="modal-content">
      @csrf
      <input type="hidden" id="routingId">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">New Routing</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Variant *</label>
          <select id="product_variant_id" name="product_variant_id" class="form-select" required></select>
        </div>
        <div class="mb-3">
          <label class="form-label">Name *</label>
          <input id="name" name="name" class="form-control" required>
        </div>
        <div class="mb-0">
          <label class="form-label">Description</label>
          <textarea id="description" name="description" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-success" type="submit">Save</button>
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
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const modal = new bootstrap.Modal('#routingModal');
const $form = $('#routingForm');
const $variant = $('#product_variant_id');

// Variant select2 (reuse your working endpoint)
$variant.select2({
  ajax: {
    url: "{{ route('admin.inventory.stock_issues.fetch_variants') }}",
    dataType: 'json', delay: 250,
    data: p => ({ q: p.term }),
    processResults: d => ({ results: d })
  },
  placeholder: '-- select variant --',
  dropdownParent: $('#routingModal'),
  width: '100%',
  minimumInputLength: 0
});

const tbl = $('#routingTbl').DataTable({
  serverSide: true, responsive: true,
  dom: 'Blfrtip',
  buttons: [
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
  ],
  ajax: "{{ route('admin.production.routings.datatable') }}",
  columns: [
    {data:'id'},
    {data:'variant',      name:'v.sku'},
    {data:'product',      name:'p.product_name'},
    {data:'name'},
    {data:'description', defaultContent:'—'},
    {data:'actions', orderable:false, searchable:false, className:'text-end'}
  ],
  drawCallback() {
    $('.edit-routing').off().on('click', function() {
      const d = $(this).data('record');
      $('#routingId').val(d.id);
      $('.modal-title').text('Edit Routing');
      $('#name').val(d.name || '');
      $('#description').val(d.description || '');

      // preset select2
      if (d.variant) {
        const opt = new Option(d.variant.sku + ' — ' + (d.product?.name || ''), d.variant.id, true, true);
        $variant.append(opt).trigger('change');
      } else {
        $variant.val(null).trigger('change');
      }

      modal.show();
    });

    // Delegated delete
$('#routingTbl').on('click', '.delete-routing', function (e) {
  e.preventDefault();

  // Prefer the data-id on the button, fallback to row().data()
  let id = $(this).data('id');

  // If button is inside a Responsive child row, hop to its parent
  let $tr = $(this).closest('tr');
  if ($tr.hasClass('child')) $tr = $tr.prev();

  if (!id) {
    id = routingDT.row($tr).data()?.id;
  }
  if (!id) return Swal.fire('Error','Could not resolve row id','error');

  Swal.fire({title:'Delete routing?',icon:'warning',showCancelButton:true})
    .then(r=>{
      if (!r.isConfirmed) return;
      $.ajax({
        url: "{{ route('admin.production.routings.destroy', ':id') }}".replace(':id', id),
        type: 'DELETE',
        data: {_token: '{{ csrf_token() }}'}
      })
      .done(()=>{ routingDT.ajax.reload(null,false); Swal.fire('Deleted','','success'); })
      .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Delete failed','error'));
    });
    });
  }
});

// New
$('#addRoutingBtn').on('click', ()=>{
  $form[0].reset();
  $('#routingId').val('');
  $variant.val(null).trigger('change');
  $('.modal-title').text('New Routing');
  modal.show();
});

// Save (create/update)
$form.on('submit', function(e){
  e.preventDefault();
  const id = $('#routingId').val();
  const payload = {
    product_variant_id: $variant.val(),
    name: $('#name').val(),
    description: $('#description').val()
  };
  const ajax = id
    ? $.ajax({url:`{{ route('admin.production.routings.update', ':id') }}`.replace(':id', id), type:'PUT', data:payload})
    : $.post(`{{ route('admin.production.routings.store') }}`, payload);

  ajax.done(res=>{
      modal.hide();
      tbl.ajax.reload(null,false);
      Swal.fire('Success', res.message || 'Saved', 'success');
    })
    .fail(x=>{
      const errs = x.responseJSON?.errors;
      Swal.fire('Error', errs ? Object.values(errs).flat().join('<br>') : (x.responseJSON?.message || 'Save failed'), 'error');
    });
});
</script>
@endpush
