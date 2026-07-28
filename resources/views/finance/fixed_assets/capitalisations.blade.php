@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Capitalisation Queue</h4>
      <div class="text-muted small">Queue items from Procurement/Inventory/AP, then Convert to Fixed Asset.</div>
    </div>

    @can('finance.asset_capitalisations.create')
      <button class="btn btn-primary" id="btnAdd"><i class="fas fa-plus me-1"></i> New Queue Item</button>
    @endcan
  </div>

  <div class="card">
    <div class="card-body">
      <table class="table table-bordered table-striped w-100" id="tbl">
        <thead>
          <tr>
            <th>ID</th>
            <th>Source</th>
            <th>Ref</th>
            <th>Category</th>
            <th>Name</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Total</th>
            <th>Status</th>
            <th>Converted Asset</th>
            <th style="width:280px;">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="m" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Capitalisation Item</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="f">
        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Source Module</label>
              <input class="form-control" id="source_module" value="procurement" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Source Table</label>
              <input class="form-control" id="source_table" value="purchase_invoices" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Source ID</label>
              <input class="form-control" id="source_id" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Reference No</label>
              <input class="form-control" id="reference_no">
            </div>

            <div class="col-md-4">
              <label class="form-label">Asset Category</label>
              <select class="form-select" id="asset_category_id" required>
                <option value="">-- select --</option>
                @foreach($categories as $c)
                  <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-8">
              <label class="form-label">Asset Name</label>
              <input class="form-control" id="asset_name" required>
            </div>

            <div class="col-md-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="asset_description" rows="2"></textarea>
            </div>

            <div class="col-md-2">
              <label class="form-label">Qty</label>
              <input type="number" class="form-control" id="quantity" required value="1" min="1">
            </div>

            <div class="col-md-3">
              <label class="form-label">Unit Cost</label>
              <input type="number" step="0.01" class="form-control" id="unit_cost" required value="0">
            </div>

            <div class="col-md-3">
              <label class="form-label">Total Cost</label>
              <input type="number" step="0.01" class="form-control" id="total_cost" value="0">
              <div class="small text-muted">If 0, system uses Qty × Unit Cost.</div>
            </div>

            <div class="col-md-2">
              <label class="form-label">Purchase Date</label>
              <input type="date" class="form-control" id="purchase_date" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="col-md-2">
              <label class="form-label">In Service Date</label>
              <input type="date" class="form-control" id="in_service_date" value="{{ date('Y-m-d') }}">
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Create Pending</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

function badge(s){
  const map={pending:'secondary',converted:'success',voided:'danger'};
  return `<span class="badge bg-${map[s]||'secondary'}">${s}</span>`;
}
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); }

$(function(){
  const dt = $('#tbl').DataTable({
    ajax: "{{ route('admin.finance.fixed_assets.capitalisations.datatable') }}",
    columns: [
      {data:'id'},
      {data:null, render:(row)=>`${row.source_module}.${row.source_table} #${row.source_id}`},
      {data:'reference_no', defaultContent:'-'},
      {data:'asset_category_id'},
      {data:'asset_name'},
      {data:'quantity', render:(d)=>`<div class="text-end">${d}</div>`},
      {data:'total_cost', render:(d)=>`<div class="text-end">${fmt(d)}</div>`},
      {data:'status', render:(d)=>badge(d)},
      {data:'converted_asset_id', defaultContent:'-'},
      {data:null, orderable:false, searchable:false, render:(row)=>{
        let html = '';
        @can('finance.asset_capitalisations.convert')
          if(row.status==='pending'){
            html += `<button class="btn btn-sm btn-success me-1 btnConvert" data-id="${row.id}"><i class="fas fa-check"></i> Convert</button>`;
          }
        @endcan
        @can('finance.asset_capitalisations.void')
          if(row.status!=='voided'){
            html += `<button class="btn btn-sm btn-outline-danger btnVoid" data-id="${row.id}"><i class="fas fa-ban"></i> Void</button>`;
          }
        @endcan
        return html || '-';
      }},
    ]
  });

  $('#btnAdd').on('click', ()=>{
    $('#f')[0].reset();
    $('#source_module').val('procurement');
    $('#source_table').val('purchase_invoices');
    $('#quantity').val(1);
    $('#unit_cost').val(0);
    $('#total_cost').val(0);
    $('#purchase_date').val("{{ date('Y-m-d') }}");
    $('#in_service_date').val("{{ date('Y-m-d') }}");
    $('#m').modal('show');
  });

  $('#f').on('submit', function(e){
    e.preventDefault();
    $.post("{{ route('admin.finance.fixed_assets.capitalisations.store') }}", {
      source_module: $('#source_module').val(),
      source_table: $('#source_table').val(),
      source_id: $('#source_id').val(),
      reference_no: $('#reference_no').val(),
      asset_category_id: $('#asset_category_id').val(),
      asset_name: $('#asset_name').val(),
      asset_description: $('#asset_description').val(),
      quantity: $('#quantity').val(),
      unit_cost: $('#unit_cost').val(),
      total_cost: $('#total_cost').val(),
      purchase_date: $('#purchase_date').val(),
      in_service_date: $('#in_service_date').val(),
    })
    .done(res=>{ $('#m').modal('hide'); dt.ajax.reload(null,false); Swal.fire('Created',res.message,'success'); })
    .fail(xhr=>Swal.fire('Error',xhr.responseJSON?.message || 'Failed','error'));
  });

  $(document).on('click','.btnConvert', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Convert to Fixed Asset?', icon:'question', showCancelButton:true, confirmButtonText:'Convert'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post("{{ route('admin.finance.fixed_assets.capitalisations.convert', ['id'=>'ID']) }}".replace('ID',id))
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Converted',res.message + ` (Asset #${res.asset_id})`,'success'); })
        .fail(xhr=>Swal.fire('Error',xhr.responseJSON?.message || 'Failed','error'));
    });
  });

  $(document).on('click','.btnVoid', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Void item?', input:'text', inputLabel:'Reason', showCancelButton:true, confirmButtonText:'Void'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post("{{ route('admin.finance.fixed_assets.capitalisations.void', ['id'=>'ID']) }}".replace('ID',id), {reason:r.value})
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Voided',res.message,'success'); })
        .fail(xhr=>Swal.fire('Error',xhr.responseJSON?.message || 'Failed','error'));
    });
  });

});
</script>
@endpush