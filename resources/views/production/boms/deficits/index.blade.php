@extends('layouts.master')
@section('title','BOM Borrow Deficits')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 text-primary">BOM Borrow Deficits</h1>
    <div class="d-flex gap-2">
      <select id="f_bom" class="form-select">
        <option value="">-- All BOMs --</option>
        @foreach($boms as $b)
          <option value="{{ $b->id }}">#{{ $b->bom_code }} — {{ $b->name }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table id="defTbl" class="table table-bordered w-100">
        <thead class="table-light">
          <tr>
            <th>BOM</th>
            <th>SKU</th>
            <th>Product</th>
            <th class="text-end">Borrowed</th>
            <th class="text-end">Repaid</th>
            <th class="text-end">Outstanding</th>
            <th>Last Txn</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- Txn Modal --}}
<div class="modal fade" id="txnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="txnForm" class="modal-content">
      @csrf
      <input type="hidden" name="bom_id" id="tx_bom_id">
      <input type="hidden" name="product_variant_id" id="tx_variant_id">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="txTitle">Record Transaction</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><strong>Item:</strong> <span id="tx_item_lbl"></span></div>
        <div class="mb-2"><strong>Outstanding:</strong> <span id="tx_out_lbl" class="fw-bold"></span></div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Type *</label>
            <select name="direction" id="tx_direction" class="form-select" required>
              <option value="repay">Repay</option>
              <option value="writeoff">Write-off</option>
              <option value="adjust">Adjust</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Quantity *</label>
            <input type="number" step="0.0001" min="0.0001" name="qty" id="tx_qty" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Unit Cost</label>
            <input type="number" step="0.01" name="unit_cost" id="tx_cost" class="form-control">
          </div>
          <div class="col-md-12">
            <label class="form-label">Note</label>
            <textarea name="note" id="tx_note" rows="2" class="form-control"></textarea>
          </div>
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
<script>
const txnModal = new bootstrap.Modal('#txnModal');

const tbl = $('#defTbl').DataTable({
  serverSide: true, responsive: true,
  dom: 'Blfrtip',
  buttons:[
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
  ],
  ajax: {
    url: "{{ route('admin.production.boms.deficits.datatable') }}",
    data: d => { d.bom_id = $('#f_bom').val(); }
  },
  columns: [
    {data:'bom'},
    {data:'sku'},
    {data:'product'},
    {data:'borrowed', className:'text-end'},
    {data:'repaid', className:'text-end'},
    {data:'outstanding', className:'text-end'},
    {data:'last_txn_at'},
    {data:'actions', orderable:false, searchable:false, className:'text-end'}
  ],
  drawCallback(){
    $('.btn-repay').off().on('click', e => openTxn($(e.currentTarget).data('record'),'repay'));
    $('.btn-writeoff').off().on('click', e => openTxn($(e.currentTarget).data('record'),'writeoff'));
    $('.btn-adjust').off().on('click', e => openTxn($(e.currentTarget).data('record'),'adjust'));
  }
});

$('#f_bom').on('change', ()=> tbl.ajax.reload());

function openTxn(rec, type){
  $('#tx_bom_id').val(rec.bom_id);
  $('#tx_variant_id').val(rec.product_variant_id);
  $('#tx_item_lbl').text(`${rec.sku} — ${rec.product}`);
  $('#tx_out_lbl').text(Number(rec.outstanding).toLocaleString(undefined,{minimumFractionDigits:4, maximumFractionDigits:4}));
  $('#tx_direction').val(type).trigger('change');
  if(type === 'adjust'){
    $('#tx_qty').attr({min:null}).val('0.0000'); // signed allowed
  }else{
    const max = parseFloat(rec.outstanding || 0);
    $('#tx_qty').attr({min:0.0001, max:max}).val(max.toFixed(4));
  }
  $('#tx_cost').val('');
  $('#tx_note').val('');
  txnModal.show();
}

$('#txnForm').on('submit', function(e){
  e.preventDefault();
  $.post("{{ route('admin.production.boms.deficits.transactions.store') }}", $(this).serialize())
    .done(r=>{ txnModal.hide(); tbl.ajax.reload(null,false); Swal.fire('Success', r.message || 'Saved', 'success'); })
    .fail(x=>{
      const errs = x.responseJSON?.errors;
      const msg  = errs ? Object.values(errs).flat().join('<br>') : (x.responseJSON?.message || 'Failed');
      Swal.fire('Error', msg, 'error');
    });
});
</script>
@endpush
