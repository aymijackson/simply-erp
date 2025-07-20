@extends('layouts.master')

@section('title','Manage Stock Transactions')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 text-primary">Stock Transactions <small class="text-muted">Inventory</small></h1>
      <div>
          <button class="btn btn-danger d-none me-2" id="bulkDeleteBtn">
              <i class="fas fa-trash me-1"></i> Delete Selected
          </button>
          <button class="btn btn-primary" id="addTransactionBtn">
              <i class="fas fa-plus me-1"></i> Add Transaction
          </button>
      </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table id="transactionTable" class="table table-bordered w-100">
          <thead class="thead-light">
            <tr>
              <th><input type="checkbox" id="selectAllTransactions"></th>
              <th>Product Variant</th>
              <th>Store</th>
              <th>Tx Type</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Unit Cost</th>
              <th class="text-end">Tx Type</th>
              <th class="text-end">Tx ID</th>
              <th class="text-end">Tx Date</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- ───────────────────────────── Modal ───────────────────────────── --}}
<div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="transactionForm" class="modal-content">
      @csrf
      <input type="hidden" id="transactionId">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="transactionModalLabel">Add Transaction</h5>
        <button class="btn-close text-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">

        <div class="col-md-12">
          <label class="form-label">Location Stores*</label>
          <select name="location_store_id" id="location_store_id" class="form-control" required>
             <option value="">-- Select Location Store --</option>
             @foreach($stores as $store)
               <option value="{{ $store->id }}">{{ $store->name }}</option>
             @endforeach
          </select>
        </div>

        <div class="col-md-12">
          <label class="form-label">Product Variant*</label>
          <select name="stock_entry_id" id="stock_entry_id" class="form-control" required>
             <option value="">-- Select Product Variant --</option>
             @foreach($product_variants as $variant)
               <option value="{{ $variant->id }}">#{{ $variant->sku }} – {{ $variant->product->product_name }}</option>
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
        <button class="btn btn-secondary" type="button" id="cancelTransactionBtn">Cancel</button>
        <button class="btn btn-success" type="submit">Save Transaction</button>
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
const transactionTable = $('#transactionTable').DataTable({
    serverSide:true, 
    responsive:true,
    processing:true,
    ajax:"{{ route('admin.inventory.stock_entries.transactions.datatable') }}",
    columns:[
      {data:'checkbox', orderable:false, searchable:false},
      {data:'variant'},
      {data:'store'},
      {data:'tx_type'},
      {data:'qty'},
      {data:'unit_cost'},
      {data:'txable_type'},
      {data:'txable_id'},
      {data:'tx_date'},
      {data:'actions',    orderable:false, searchable:false, className:'text-end'},
    ],
    drawCallback(){
        $('.edit-transaction').on('click', e=>{
            $.getJSON(`/admin/inventory/stock-entries/transactions/${$(e.currentTarget).data('id')}`, fillModal);
        });
        $('.delete-transaction').on('click', deleteOne);
        $('.row-checkbox').on('change',toggleBulk);
        $('#selectAllTransactions').prop('checked',false).on('change',function(){
            $('.row-checkbox').prop('checked',this.checked).trigger('change');
        });
    }
});

/* ───────────── Helpers ───────────── */
function resetForm(){
    $('#transactionForm')[0].reset();
    $('#transactionId').val('');
}

function fillModal(data){
    resetForm();
    $('#transactionModalLabel').text('Edit Transaction');
    $('#transactionId').val(data.id);
    $('#stock_entry_id').val(data.stock_entry_id);
    $('#product_variant_id').val(data.product_variant_id);
    $('#qty').val(data.qty);
    $('#unit_cost').val(data.unit_cost);
    new bootstrap.Modal('#transactionModal').show();
}
function toggleBulk(){
    $('#bulkDeleteBtn').toggleClass('d-none', $('.row-checkbox:checked').length===0);
}
/* ───────────── Modal events ───────────── */
$('#addTransactionBtn').click(()=>{
    resetForm();
    $('#transactionModalLabel').text('Add Transaction');
    new bootstrap.Modal('#transactionModal').show();
});
$('#cancelTransactionBtn').click(()=>bootstrap.Modal.getInstance('#transactionModal').hide());

$('#transactionForm').submit(function(e){
    e.preventDefault();
    const id  = $('#transactionId').val();
    const url = id ? `/admin/inventory/stock-entries/transactions/${id}`
                   : `{{ route('admin.inventory.stock_entries.transactions.store') }}`;
    const data = $(this).serialize() + (id ? '&_method=PUT':'');
    $.post(url,data)
      .done(()=>{bootstrap.Modal.getInstance('#transactionModal').hide(); transactionTable.ajax.reload(null,false); Swal.fire('Success', data.message,'success');})
      .fail(x=>Swal.fire('Error',x.responseJSON?.message||'Save failed','error'));
});

/* ───────────── Delete single ───────────── */
function deleteOne(){
  const id=$(this).data('id');
  Swal.fire({title:'Delete?',icon:'warning',showCancelButton:true})
      .then(res=>{
        if(res.isConfirmed){
            $.post(`/admin/inventory/stock/entry-transactions/${id}`,{_method:'DELETE'})
              .done(()=>transactionTable.ajax.reload(null,false));
        }
      });
}

/* ───────────── Bulk delete ───────────── */
$('#bulkDeleteBtn').click(()=>{
  const ids=$('.row-checkbox:checked').map((_,el)=>el.value).get();
  if(!ids.length) return;
  Swal.fire({title:`Delete ${ids.length} transactions?`,icon:'warning',showCancelButton:true})
      .then(res=>{
        if(res.isConfirmed){
          $.post("{{ route('admin.inventory.stock_entries.transactions.bulk-delete') }}",{ids})
            .done(()=>{transactionTable.ajax.reload(); $('#bulkDeleteBtn').addClass('d-none');});
        }
      });
});
</script>
@endpush
