@extends('layouts.master')
@section('title', 'Work Orders')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  {{-- Header --------------------------------------------------------------- --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 text-primary mb-0">Work Orders</h1>
    <div class="d-print-none d-flex gap-2">
      <button id="addWoBtn" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> New Work Order
      </button>
    </div>
  </div>

  {{-- Filters -------------------------------------------------------------- --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label mb-1">Status</label>
          <select id="f_status" class="form-select">
            <option value="">-- Any --</option>
            <option value="draft">Draft</option>
            <option value="released">Released</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">From</label>
          <input type="date" id="f_from" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">To</label>
          <input type="date" id="f_to" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1">Variant / Product</label>
          <input type="text" id="f_q" class="form-control" placeholder="Search text">
        </div>
        <div class="col-md-2">
          <button id="f_apply" class="btn btn-primary w-100">
            <i class="fas fa-filter me-1"></i> Apply
          </button>
        </div>
        <div class="col-md-1">
          <button id="f_clear" class="btn btn-outline-secondary w-100">Clear</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Table --------------------------------------------------------------- --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table id="woTbl" class="table table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Status</th>
              <th>SKU</th>
              <th>Product</th>
              <th class="text-end">Qty to Produce</th>
              <th>BOM</th>
              <th>Routing</th>
              <th>Start</th>
              <th>End</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Create / Edit Modal --------------------------------------------------- --}}
<div class="modal fade" id="woModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="woForm" class="modal-content">
      @csrf
      <input type="hidden" id="wo_id">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">New Work Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Work Order #</label>  
                <input type="text" id="work_order_number" name="work_order_number" class="form-control" maxlength="50">
            </div>
        </div>    
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Product Variant *</label>
            <select id="variant_id" name="product_variant_id" class="form-select" required></select>
          </div>
          <div class="col-md-6">
            <label class="form-label">BOM *</label>
            <select id="bom_id" name="bom_header_id" class="form-select" required></select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Routing *</label>
            <select id="routing_id" name="routing_id" class="form-select" required></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Qty to Produce *</label>
            <input type="number" step="0.0001" min="0.0001" id="qty" name="quantity_to_produce" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select id="status" name="status" class="form-select form-control-sm" disabled>
              <option value="draft" selected>Draft</option>
            </select>
            <div class="form-text">New work orders always start as Draft. Use Release / Start / Complete / Close on the work order page to change status.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Start (optional)</label>
            <input type="datetime-local" id="start_date" name="start_date" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">End (optional)</label>
            <input type="datetime-local" id="end_date" name="end_date" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Optional"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="saveWoBtn" class="btn btn-success">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const CSRF = $('meta[name="csrf-token"]').attr('content');

// Endpoints (adjust names if yours differ)
const URLS = {
  dt:        @json(route('admin.production.work-orders.datatable')),
  store:     @json(route('admin.production.work-orders.store')),
  update:    id => @json(route('admin.production.work-orders.update', 0)).replace('/0','/'+id),
  destroy:   id => @json(route('admin.production.work-orders.destroy', 0)).replace('/0','/'+id),
  show:      id => @json(route('admin.production.work-orders.show', 0)).replace('/0','/'+id),

  // select2 feeds
  variants:  "{{ route('admin.inventory.stock_issues.fetch_variants') }}",   // returns [{id,text,sku,...}]
  boms:      "{{ route('admin.production.boms.headers.select2') }}",                 // implement to return [{id,text:bom_code + name}]
  routings:  "{{ route('admin.production.routings.select2') }}"              // returns [{id,text:name (sku/product)}]
};

// Status badge helper (kept in JS for DT render)
function statusBadge(s){
  const map = {draft:'secondary', released:'info', in_progress:'warning', completed:'success', closed:'dark'};
  const c   = map[s] || 'secondary';
  const t   = (s||'').replace('_',' ');
  return `<span class="badge bg-${c} text-white">${t.charAt(0).toUpperCase()+t.slice(1)}</span>`;
}

/** ---------------------- DataTable ---------------------- **/
const tbl = $('#woTbl').DataTable({
  serverSide: true, responsive: true,
  ajax: {
    url: URLS.dt,
    data: d => {
      d.status = $('#f_status').val();
      d.from   = $('#f_from').val();
      d.to     = $('#f_to').val();
      d.q      = $('#f_q').val();
    }
  },
  columns: [
    {data:'id', name:'id'},
    {data:'status', name:'status', render: (v)=> statusBadge(v), orderable:false, searchable:false},
    {data:'variant_sku', name:'variant_sku'},
    {data:'product_name', name:'product_name'},
    {data:'quantity_to_produce', className:'text-end', render:v=> Number(v||0).toLocaleString(undefined,{minimumFractionDigits:4, maximumFractionDigits:4})},
    {data:'bom_code', name:'bom_code', render:v=> v?('#'+v):'—'},
    {data:'routing_name', name:'routing_name'},
    {data:'start_date', name:'start_date', render:v=> v? new Date(v).toLocaleString(): '—'},
    {data:'end_date', name:'end_date', render:v=> v? new Date(v).toLocaleString(): '—'},
    {data:'actions', orderable:false, searchable:false, className:'text-end'}
  ],
  order: [[0,'desc']],
  dom: 'Blfrtip',
  buttons:[
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
  ],
  createdRow: row => row.classList.add('align-middle'),
  drawCallback(){
    // nothing — we use delegated events below (works in responsive child rows)
  }
});

// Filters
$('#f_apply').on('click', ()=> tbl.ajax.reload());
$('#f_clear').on('click', ()=>{
  $('#f_status').val('');
  $('#f_from').val('');
  $('#f_to').val('');
  $('#f_q').val('');
  tbl.ajax.reload();
});

/** ---------------------- Create / Edit Modal ---------------------- **/
const woModal = new bootstrap.Modal('#woModal');

function openCreate(){
  $('#woForm')[0].reset();
  $('#wo_id').val('');
  $('#variant_id, #bom_id, #routing_id').val(null).trigger('change');
  $('.modal-title').text('New Work Order');
  woModal.show();
}
$('#addWoBtn').on('click', openCreate);

// Select2s
$('#variant_id').select2({
  ajax:{ url: URLS.variants, dataType:'json', delay:250, data:p=>({q:p.term}), processResults:d=>({results:d}) },
  dropdownParent: $('#woModal'), width:'100%', placeholder:'-- select variant --', minimumInputLength:0
});
$('#bom_id').select2({
  ajax:{ url: URLS.boms, dataType:'json', delay:250, data:p=>({q:p.term}), processResults:d=>({results:d}) },
  dropdownParent: $('#woModal'), width:'100%', placeholder:'-- select BOM --', minimumInputLength:0
});
$('#routing_id').select2({
  ajax:{ url: URLS.routings, dataType:'json', delay:250, data:p=>({q:p.term}), processResults:d=>({results:d}) },
  dropdownParent: $('#woModal'), width:'100%', placeholder:'-- select routing --', minimumInputLength:0
});

// Save (create/update)
$('#saveWoBtn').on('click', function(e){
  e.preventDefault();

  const id = $('#wo_id').val();
  const url = id ? URLS.update(id) : URLS.store;
  const method = id ? 'PUT' : 'POST';

  const payload = {
    _token: CSRF,
    work_order_number: $('#work_order_number').val(),
    product_variant_id: $('#variant_id').val(),
    bom_header_id: $('#bom_id').val(),
    routing_id: $('#routing_id').val(),
    quantity_to_produce: $('#qty').val(),
    status: $('#status').val(),
    start_date: $('#start_date').val(),
    end_date: $('#end_date').val(),
    notes: $('#notes').val()
  };

  $.ajax({url, type: method, data: payload})
    .done(res=>{
      woModal.hide();
      tbl.ajax.reload(null,false);
      Swal.fire('Saved', res.message || 'Work order saved', 'success');
      // optionally: window.location = URLS.show(res.id);
    })
    .fail(x=>{
      const msg = x.responseJSON?.message || 'Save failed';
      const errs= x.responseJSON?.errors;
      Swal.fire('Error', errs ? Object.values(errs).flat().join('<br>') : msg, 'error');
    });
});

// Edit (if you later add inline Edit; for now index uses View/Delete)
$(document).on('click', '.edit-wo', function(){
  const r = $(this).data('record'); // supply from server if you add an edit button
  $('#woForm')[0].reset();
  $('#wo_id').val(r.id);

  // prime selects with existing values
  if (r.variant_sku && r.product_variant_id) {
    const opt = new Option(r.variant_sku, r.product_variant_id, true, true);
    $('#variant_id').append(opt).trigger('change');
  } else { $('#variant_id').val(null).trigger('change'); }

  if (r.bom_code && r.bom_header_id) {
    const opt2 = new Option('#'+r.bom_code, r.bom_header_id, true, true);
    $('#bom_id').append(opt2).trigger('change');
  } else { $('#bom_id').val(null).trigger('change'); }

  if (r.routing_name && r.routing_id) {
    const opt3 = new Option(r.routing_name, r.routing_id, true, true);
    $('#routing_id').append(opt3).trigger('change');
  } else { $('#routing_id').val(null).trigger('change'); }

  $('#qty').val(r.quantity_to_produce || '');
  $('#status').val(r.status || 'draft');
  $('#start_date').val(r.start_date ? r.start_date.replace(' ', 'T') : '');
  $('#end_date').val(r.end_date ? r.end_date.replace(' ', 'T') : '');
  $('#notes').val(r.notes || '');

  $('.modal-title').text('Edit Work Order');
  woModal.show();
});

// Delete (delegated; works in responsive child)
$(document).on('click', '.del-wo', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Delete this work order?', icon:'warning', showCancelButton:true})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({url: URLS.destroy(id), type:'DELETE', data:{_token:CSRF}})
        .done(()=>{ tbl.ajax.reload(null,false); Swal.fire('Deleted','', 'success'); })
        .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
    });
});
</script>
@endpush
