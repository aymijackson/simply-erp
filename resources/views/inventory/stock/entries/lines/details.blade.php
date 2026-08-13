@extends('layouts.master')

@section('title', "Entry #{$entry->id} Lines")

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 text-primary mb-0">Stock Entry #{{ $entry->id }}</h1>
      <a href="{{ route('admin.inventory.stock_entries.index') }}" class="btn btn-link">
          <i class="fas fa-arrow-left me-1"></i> Back to Entries
      </a>
  </div>

  {{-- Entry header info --}}
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-bold text-muted">Store</div>
          {{ $entry->store->store_name }}
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-bold text-muted">Entry Date</div>
          {{ $entry->entry_date->toFormattedDateString() }}
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-bold text-muted">Reference #</div>
          {{ $entry->reference ?: '—' }}
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-bold text-muted">Status</div>
          @if($entry->status === 'approved')
             <span class="badge bg-success">Approved</span>
          @else
             <span class="badge bg-secondary">Draft</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Lines table --}}
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
       <h5 class="mb-0">Lines</h5>
       <button class="btn btn-sm btn-primary" id="addLineBtn">
         <i class="fas fa-plus me-1"></i> Add Line
       </button>
    </div>
    <div class="card-body">
      <table id="linesTable" class="table table-bordered w-100">
        <thead class="table-light">
          <tr>
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

{{-- ───────────────────────────── Modal ───────────────────────────── --}}
<div class="modal fade" id="lineModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="lineForm" class="modal-content">
      @csrf
      <input type="hidden" id="lineId">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="lineModalLabel">Add Line</h5>
        <button class="btn-close text-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
          <div class="col-12">
              <label class="form-label">Variant *</label>
              <select id="product_variant_id" name="product_variant_id" class="form-control" required>
                  <option value="">-- Select Variant --</option>
                  @foreach($variants as $v)
                      <option value="{{ $v->id }}">{{ $v->sku }} – {{ $v->product->product_name }}</option>
                  @endforeach
              </select>
          </div>
          <div class="col-md-6">
              <label class="form-label">Qty *</label>
              <input id="qty" name="qty" type="number" class="form-control" min="1" value="1" required>
          </div>
          <div class="col-md-6">
              <label class="form-label">Unit Cost</label>
              <input id="unit_cost" name="unit_cost" type="number" class="form-control" step="0.01">
          </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" id="cancelModalBtn">Cancel</button>
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

const entryId = {{ $entry->id }};

/* DataTable */
const dt = $('#linesTable').DataTable({
    serverSide:true, paging:false, searching:false, info:false, responsive:true,
    ajax:`/admin/inventory/stock/entries/${entryId}/lines/datatable`,
    columns:[
        {data:'variant'},
        {data:'qty',       className:'text-end'},
        {data:'unit_cost', className:'text-end'},
        {data:'actions',   orderable:false, className:'text-end'},
    ],
    drawCallback(){
        $('.edit-line').on('click', e=>$.getJSON(`/admin/inventory/stock/entry-lines/${$(e.currentTarget).data('id')}`, fillModal));
        $('.delete-line').on('click', deleteLine);
    }
});

/* helpers */
function resetForm(){
    $('#lineForm')[0].reset();
    $('#lineId').val('');
}

function fillModal(data){
    resetForm();
    $('#lineModalLabel').text('Edit Line');
    $('#lineId').val(data.id);
    $('#product_variant_id').val(data.product_variant_id);
    $('#qty').val(data.qty);
    $('#unit_cost').val(data.unit_cost);
    new bootstrap.Modal('#lineModal').show();
}

/* open add modal */
$('#addLineBtn').click(()=>{
    resetForm();
    $('#lineModalLabel').text('Add Line');
    new bootstrap.Modal('#lineModal').show();
});
$('#cancelModalBtn').click(()=>bootstrap.Modal.getInstance('#lineModal').hide());

/* save */
$('#lineForm').submit(function(e){
    e.preventDefault();
    const id = $('#lineId').val();
    const url = id ? `/admin/inventory/stock/entry-lines/${id}`
                   : `/admin/inventory/stock/entries/${entryId}/lines`;
    const data = $(this).serialize()+(id ? '&_method=PUT':'');
    $.post(url,data)
      .done(()=>{bootstrap.Modal.getInstance('#lineModal').hide(); dt.ajax.reload();});
});

/* delete */
function deleteLine(){
    const id=$(this).data('id');
    Swal.fire({title:'Delete?',icon:'warning',showCancelButton:true})
        .then(res=>{
          if(res.isConfirmed){
            $.post(`/admin/inventory/stock/entry-lines/${id}`,{_method:'DELETE'})
              .done(()=>dt.ajax.reload());
          }
        });
}
</script>
@endpush
