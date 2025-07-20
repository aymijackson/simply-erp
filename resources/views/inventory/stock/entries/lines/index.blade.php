@extends('layouts.master')

@section('title','Manage Stock Entry Lines')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 text-primary">Stock Entry Lines <small class="text-muted">Inventory</small></h1>
      <div>
          <button class="btn btn-danger d-none me-2" id="bulkDeleteBtn">
              <i class="fas fa-trash me-1"></i> Delete Selected
          </button>
          <button class="btn btn-primary" id="addLineBtn">
              <i class="fas fa-plus me-1"></i> Add Line
          </button>
      </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table id="lineTable" class="table table-bordered w-100">
          <thead class="thead-light">
            <tr>
              <th><input type="checkbox" id="selectAllLines"></th>
              <th>Entry #</th>
              <th>Store</th>
              <th>Variant</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Unit Cost</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- ───────────────────────────── Modal ───────────────────────────── --}}
<div class="modal fade" id="lineModal" tabindex="-1" aria-labelledby="lineModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="lineForm" class="modal-content">
      @csrf
      <input type="hidden" id="lineId">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="lineModalLabel">Add Line</h5>
        <button class="btn-close text-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">

        <div class="col-md-12">
          <label class="form-label">Entry *</label>
          <select name="stock_entry_id" id="stock_entry_id" class="form-control" required>
             <option value="">-- Select Entry --</option>
             @foreach($entries as $e)
               <option value="{{ $e->id }}">#{{ $e->id }} – {{ $e->store->store_name }} ({{ $e->entry_date }})</option>
             @endforeach
          </select>
        </div>

        <div class="col-md-12">
          <label class="form-label">Variant *</label>
          <select name="product_variant_id" id="product_variant_id" class="form-control" required>
             <option value="">-- Select Variant --</option>
             @foreach($variants as $v)
               <option value="{{ $v->id }}">{{ $v->sku }} – {{ $v->product->product_name }}</option>
             @endforeach
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Qty *</label>
          <input type="number" name="qty" id="qty" class="form-control" min="1" value="1" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Unit Cost</label>
          <input type="number" name="unit_cost" id="unit_cost" class="form-control" step="0.01">
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" id="cancelLineBtn">Cancel</button>
        <button class="btn btn-success" type="submit">Save Line</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<link  href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

/* ───────────── DataTable ───────────── */
const lineTable = $('#lineTable').DataTable({
    serverSide:true, responsive:true,
    ajax:"{{ route('admin.inventory.stock_entries.lines.datatable') }}",
    columns:[
      {data:'checkbox', orderable:false, searchable:false},
      {data:'entry_id'},
      {data:'store'},
      {data:'variant'},
      {data:'qty',        className:'text-end'},
      {data:'unit_cost',  className:'text-end'},
      {data:'actions',    orderable:false, searchable:false, className:'text-end'},
    ],
    drawCallback(){
        $('.edit-line').on('click', e=>{
            $.getJSON(`/admin/inventory/stock-entries/lines/${$(e.currentTarget).data('id')}`, fillModal);
        });
        $('.delete-line').on('click', deleteOne);
        $('.row-checkbox').on('change',toggleBulk);
        $('#selectAllLines').prop('checked',false).on('change',function(){
            $('.row-checkbox').prop('checked',this.checked).trigger('change');
        });
    }
});

/* ───────────── Helpers ───────────── */
function resetForm(){
    $('#lineForm')[0].reset();
    $('#lineId').val('');
}

function fillModal(data){
    resetForm();
    $('#lineModalLabel').text('Edit Line');
    $('#lineId').val(data.id);
    $('#stock_entry_id').val(data.stock_entry_id);
    $('#product_variant_id').val(data.product_variant_id);
    $('#qty').val(data.qty);
    $('#unit_cost').val(data.unit_cost);
    new bootstrap.Modal('#lineModal').show();
}
function toggleBulk(){
    $('#bulkDeleteBtn').toggleClass('d-none', $('.row-checkbox:checked').length===0);
}
/* ───────────── Modal events ───────────── */
$('#addLineBtn').click(()=>{
    resetForm();
    $('#lineModalLabel').text('Add Line');
    new bootstrap.Modal('#lineModal').show();
});
$('#cancelLineBtn').click(()=>bootstrap.Modal.getInstance('#lineModal').hide());

$('#lineForm').submit(function(e){
    e.preventDefault();
    const id  = $('#lineId').val();
    const url = id ? `/admin/inventory/stock-entries/lines/${id}`
                   : `{{ route('admin.inventory.stock_entries.lines.store') }}`;
    const data = $(this).serialize() + (id ? '&_method=PUT':'');
    $.post(url,data)
      .done(()=>{bootstrap.Modal.getInstance('#lineModal').hide(); lineTable.ajax.reload(null,false); Swal.fire('Success', data.message,'success');})
      .fail(x=>Swal.fire('Error',x.responseJSON?.message||'Save failed','error'));
});

/* ───────────── Delete single ───────────── */
function deleteOne(){
  const id=$(this).data('id');
  Swal.fire({title:'Delete?',icon:'warning',showCancelButton:true})
      .then(res=>{
        if(res.isConfirmed){
            $.post(`/admin/inventory/stock/entry-lines/${id}`,{_method:'DELETE'})
              .done(()=>lineTable.ajax.reload(null,false));
        }
      });
}

/* ───────────── Bulk delete ───────────── */
$('#bulkDeleteBtn').click(()=>{
  const ids=$('.row-checkbox:checked').map((_,el)=>el.value).get();
  if(!ids.length) return;
  Swal.fire({title:`Delete ${ids.length} lines?`,icon:'warning',showCancelButton:true})
      .then(res=>{
        if(res.isConfirmed){
          $.post("{{ route('admin.inventory.stock_entries.lines.bulk-delete') }}",{ids})
            .done(()=>{lineTable.ajax.reload(); $('#bulkDeleteBtn').addClass('d-none');});
        }
      });
});
</script>
@endpush
