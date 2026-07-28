@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Maintenance Logs</h4>
      <div class="text-muted small">Draft → Post (GL) → Void. Tracks lifecycle costs and service history.</div>
    </div>

    @can('finance.fixed_asset_maintenance.create')
      <button class="btn btn-primary" id="btnAdd"><i class="fas fa-plus me-1"></i> New Log</button>
    @endcan
  </div>

  <div class="card">
    <div class="card-body">
      <table class="table table-bordered table-striped w-100" id="tbl">
        <thead>
          <tr>
            <th>ID</th>
            <th>Asset</th>
            <th>Component</th>
            <th>Date</th>
            <th>Type</th>
            <th class="text-end">Cost</th>
            <th>Status</th>
            <th>Journal</th>
            <th style="width:260px;">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="m" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Maintenance Log</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="f">
        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-4">
              <label class="form-label">Asset ID</label>
              <input class="form-control" id="asset_id" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Component ID (optional)</label>
              <input class="form-control" id="component_id">
            </div>
            <div class="col-md-4">
              <label class="form-label">Service Date</label>
              <input type="date" class="form-control" id="service_date" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Vendor Name</label>
              <input class="form-control" id="vendor_name">
            </div>
            <div class="col-md-6">
              <label class="form-label">Reference No</label>
              <input class="form-control" id="reference_no">
            </div>

            <div class="col-md-6">
              <label class="form-label">Type</label>
              <select class="form-select" id="maintenance_type" required>
                <option value="preventive">Preventive</option>
                <option value="corrective">Corrective</option>
                <option value="inspection">Inspection</option>
                <option value="calibration">Calibration</option>
                <option value="warranty">Warranty</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Expense Account (COA)</label>
              <select class="form-select" id="expense_account_id" required>
                <option value="">-- select --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
              <div class="small text-muted">Posting: Dr Expense / Cr AP (config finance.default_ap_account_id).</div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Cost</label>
              <input type="number" step="0.01" class="form-control" id="cost" required value="0">
            </div>

            <div class="col-md-8">
              <label class="form-label">Description</label>
              <input class="form-control" id="description">
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Create Draft</button>
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
  const map={draft:'secondary',posted:'success',voided:'danger'};
  return `<span class="badge bg-${map[s]||'secondary'}">${s}</span>`;
}
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); }

$(function(){
  const dt = $('#tbl').DataTable({
    ajax: "{{ route('admin.finance.fixed_assets.maintenance.datatable') }}",
    columns: [
      {data:'id'},
      {data:'asset_id'},
      {data:'component_id', defaultContent:'-'},
      {data:'service_date'},
      {data:'maintenance_type'},
      {data:'cost', render:(d)=>`<div class="text-end">${fmt(d)}</div>`},
      {data:'status', render:(d)=>badge(d)},
      {data:'journal_entry_id', defaultContent:'-'},
      {data:null, orderable:false, searchable:false, render:(row)=>{
        let html = '';
        @can('finance.fixed_asset_maintenance.post')
          if(row.status==='draft') html += `<button class="btn btn-sm btn-success me-1 btnPost" data-id="${row.id}"><i class="fas fa-check"></i> Post</button>`;
        @endcan
        @can('finance.fixed_asset_maintenance.void')
          if(row.status==='posted') html += `<button class="btn btn-sm btn-outline-danger btnVoid" data-id="${row.id}"><i class="fas fa-ban"></i> Void</button>`;
        @endcan
        return html || '-';
      }},
    ]
  });

  $('#btnAdd').on('click', ()=>{ $('#f')[0].reset(); $('#service_date').val("{{ date('Y-m-d') }}"); $('#cost').val(0); $('#m').modal('show'); });

  $('#f').on('submit', function(e){
    e.preventDefault();
    $.post("{{ route('admin.finance.fixed_assets.maintenance.store') }}", {
      asset_id: $('#asset_id').val(),
      component_id: $('#component_id').val(),
      service_date: $('#service_date').val(),
      vendor_name: $('#vendor_name').val(),
      reference_no: $('#reference_no').val(),
      maintenance_type: $('#maintenance_type').val(),
      description: $('#description').val(),
      cost: $('#cost').val(),
      expense_account_id: $('#expense_account_id').val(),
    })
    .done(res=>{ $('#m').modal('hide'); dt.ajax.reload(null,false); Swal.fire('Created',res.message,'success'); })
    .fail(xhr=>Swal.fire('Error',xhr.responseJSON?.message || 'Failed','error'));
  });

  $(document).on('click','.btnPost', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Post maintenance?', icon:'warning', showCancelButton:true, confirmButtonText:'Post'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post("{{ route('admin.finance.fixed_assets.maintenance.post', ['id'=>'ID']) }}".replace('ID',id))
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Posted',res.message,'success'); })
        .fail(xhr=>Swal.fire('Error',xhr.responseJSON?.message || 'Failed','error'));
    });
  });

  $(document).on('click','.btnVoid', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Void maintenance?', input:'text', inputLabel:'Reason', showCancelButton:true, confirmButtonText:'Void'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post("{{ route('admin.finance.fixed_assets.maintenance.void', ['id'=>'ID']) }}".replace('ID',id), {reason:r.value})
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Voided',res.message,'success'); })
        .fail(xhr=>Swal.fire('Error',xhr.responseJSON?.message || 'Failed','error'));
    });
  });

});
</script>
@endpush