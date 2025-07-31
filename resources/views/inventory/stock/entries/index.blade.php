@extends('layouts.master')

@section('title', 'Manage Stock Entries')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">Stock Entries <small class="text-muted">Inventory</small></h1>
        <div>
            <button class="btn btn-danger me-2 d-none" id="bulkDeleteBtn">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addEntryBtn">
                <i class="fas fa-plus me-1"></i> Add Stock Entry
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="entryTable" class="table table-bordered w-100">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="selectAllEntries"></th>
                            <th>Reference #</th>
                            <th>Store</th>
                            <th>Entry Date</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ─────────────────────────────── Modal ─────────────────────────────── --}}
<div class="modal fade" id="entryModal" tabindex="-1" aria-labelledby="entryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form id="entryForm" class="modal-content">
      @csrf
      <input type="hidden" id="entryId">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="entryModalLabel">Add Stock Entry</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Store *</label>
                <select name="store_id" id="store_id" class="form-control" required>
                    <option value="">-- Select Store --</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Entry Date *</label>
                <input type="date" name="entry_date" id="entry_date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Reference #</label>
                <input type="text" name="reference" id="reference" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" id="supplier_id" class="form-select form-control">
                    <option value="">-- optional --</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="draft">Draft</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Lines</h5>
            <button type="button" id="addLineBtn" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Add Line
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="linesTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:35%">Variant</th>
                        <th style="width:15%">Qty</th>
                        <th style="width:20%">Unit Cost</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" id="cancelEntryBtn" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-success">Save Entry</button>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

/* template row for Lines table */
const lineTemplate = variantOptions => `
<tr>
  <td>
    <select name="lines[variant_id][]" class="form-control variant-select">
        <option value="">-- Select Variant --</option>
        ${variantOptions}
    </select>
  </td>
  <td><input type="number" name="lines[qty][]" class="form-control" min="1" value="1" required></td>
  <td><input type="number" name="lines[unit_cost][]" class="form-control" step="0.01"></td>
  <td class="text-center">
    <button type="button" class="btn btn-sm btn-link text-danger remove-line"><i class="fas fa-times"></i></button>
  </td>
</tr>`;

/* variant dropdown cached HTML */
let variantOptionHTML = `
@foreach($variants as $v)
  <option value="{{ $v->id }}">{{ $v->sku }} – {{ $v->product->product_name }}</option>
@endforeach
`;

function addLineRow(selected = {}) {
    $('#linesTable tbody').append(lineTemplate(variantOptionHTML));
    if(selected.variant_id){
        $('#linesTable tbody tr:last .variant-select').val(selected.variant_id);
        $('#linesTable tbody tr:last input[name="lines[qty][]"]').val(selected.qty);
        $('#linesTable tbody tr:last input[name="lines[unit_cost][]"]').val(selected.unit_cost);
    }
}

/* reset modal */
function resetEntryForm(){
    $('#entryForm')[0].reset();
    $('#entryId').val('');
    $('#linesTable tbody').empty();
}

/* fill modal */
function fillEntryModal(data){
    resetEntryForm();
    $('#entryModalLabel').text('Edit Stock Entry');
    $('#entryId').val(data.id);
    $('#store_id').val(data.store_id);
    $('#entry_date').val(data.entry_date);
    $('#reference').val(data.reference);
    data.lines.forEach(l=>{
        addLineRow({variant_id:l.product_variant_id, qty:l.qty, unit_cost:l.unit_cost});
    });
    new bootstrap.Modal('#entryModal').show();
}

$(function(){

  /* DataTable */
  const table = $('#entryTable').DataTable({
      serverSide:true, responsive:true,
      ajax:"{{ route('admin.inventory.stock_entries.datatable') }}",
      columns:[
        {data:'checkbox', orderable:false, searchable:false},
        {data:'reference'},
        {data:'store_name'},
        {data:'entry_date'},
        {data:'supplier'},
        {data:'status', render:d=> d==='draft'
              ? '<span class="badge bg-secondary text-white">Draft</span>'
              : '<span class="badge bg-success text-white">Approved</span>'},
        {data:'actions', orderable:false, searchable:false, className:'text-end'},
      ],
      drawCallback(){
        $('.edit-entry').on('click', e=>{
            $.getJSON(`/admin/inventory/stock-entries/${$(e.currentTarget).data('id')}`, fillEntryModal);
        });
        $('.delete-entry').on('click', deleteOne);
      }
  });

  /* open modal: create */
  $('#addEntryBtn').click(()=>{
      resetEntryForm();
      $('#entryModalLabel').text('Add Stock Entry');
      addLineRow();                    // at least one line
      new bootstrap.Modal('#entryModal').show();
  });

  /* add / remove line rows */
  $('#addLineBtn').click(()=>addLineRow());
  $('#linesTable').on('click','.remove-line', function(){
      $(this).closest('tr').remove();
  });

  /* cancel btn */
  $('#cancelEntryBtn').click(()=>bootstrap.Modal.getInstance('#entryModal').hide());

  /* store form */
  $('#entryForm').submit(function(e){
      e.preventDefault();
      const id  = $('#entryId').val();
      const url = id ? `/admin/inventory/stock-entries/${id}` 
                     : `{{ route('admin.inventory.stock_entries.store') }}`;
      const data = $(this).serialize() + (id ? '&_method=PUT' : '');
      $.post(url, data)
        .done(r=>{
           bootstrap.Modal.getInstance('#entryModal').hide();
           table.ajax.reload(null,false);
           Swal.fire('Success', r.message,'success');
        })
        .fail(x=>Swal.fire('Error', x.responseJSON?.message || 'Save failed','error'));
  });

  /* delete single */
  function deleteOne(){
      const id = $(this).data('id');
      Swal.fire({title:'Delete?',icon:'warning',showCancelButton:true})
          .then(res=>{
              if(res.isConfirmed){
                  $.post(`/admin/inventory/stock-entries/${id}`,{_method:'DELETE'})
                    .done(()=>table.ajax.reload(null,false));
              }
          });
  }

  /* ---- Supplier live‑search ---- */
    $('#supplier_id').select2({
        ajax: {
            url: "{{ route('admin.inventory.suppliers.select2') }}",   // sample route
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data })      // expects [{id,text}]
        },
        placeholder: '-- optional --',
        minimumInputLength: 2,
        dropdownParent: $('#entryModal'),  // keep dropdown inside the modal
        width: '100%'
    });
});
</script>
@endpush
