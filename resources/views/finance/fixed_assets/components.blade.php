@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Asset Components</h4>
      <div class="text-muted small">Create components under a parent asset, with separate useful life and depreciation.</div>
    </div>

    @can('finance.fixed_asset_components.create')
      <button class="btn btn-primary" id="btnAdd"><i class="fas fa-plus me-1"></i> New Component</button>
    @endcan
  </div>

  <div class="card">
    <div class="card-body">
      <table class="table table-bordered table-striped w-100" id="tbl">
        <thead>
          <tr>
            <th>ID</th>
            <th>Parent Asset</th>
            <th>Code</th>
            <th>Name</th>
            <th class="text-end">Cost</th>
            <th>Method</th>
            <th class="text-end">Life (months)</th>
            <th>Status</th>
            <th style="width:240px;">Actions</th>
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
        <h5 class="modal-title">New Component</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="f">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Parent Asset ID</label>
              <input class="form-control" id="parent_asset_id" required placeholder="finance_fixed_assets.id">
            </div>
            <div class="col-md-4">
              <label class="form-label">Component Code</label>
              <input class="form-control" id="component_code" placeholder="optional">
            </div>
            <div class="col-md-4">
              <label class="form-label">Name</label>
              <input class="form-control" id="name" required>
            </div>

            <div class="col-md-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="description" rows="2"></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Cost</label>
              <input type="number" step="0.01" class="form-control" id="cost" required value="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Salvage Value</label>
              <input type="number" step="0.01" class="form-control" id="salvage_value" value="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Depreciation Method</label>
              <select class="form-select" id="depr_method" required>
                <option value="straight_line">Straight Line</option>
                <option value="reducing_balance">Reducing Balance</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Useful Life (months)</label>
              <input type="number" class="form-control" id="useful_life_months" required value="60">
            </div>
            <div class="col-md-6">
              <label class="form-label">Rate (optional)</label>
              <input type="number" step="0.0001" class="form-control" id="depr_rate" placeholder="optional">
            </div>

          </div>

          <div class="alert alert-info small mt-3 mb-0">
            Components start as <b>Draft</b> → Activate to include them in depreciation runs.
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
  const map={draft:'secondary',active:'success',retired:'danger'};
  return `<span class="badge bg-${map[s]||'secondary'}">${s}</span>`;
}
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); }

$(function(){
  const dt = $('#tbl').DataTable({
    ajax: "{{ route('admin.finance.fixed_assets.components.datatable') }}",
    columns: [
      {data:'id'},
      {data:'parent_asset_id'},
      {data:'component_code', defaultContent:'-'},
      {data:'name'},
      {data:'cost', render:(d)=>`<div class="text-end">${fmt(d)}</div>`},
      {data:'depr_method'},
      {data:'useful_life_months', render:(d)=>`<div class="text-end">${d}</div>`},
      {data:'status', render:(d)=>badge(d)},
      {data:null, orderable:false, searchable:false, render:(row)=>{
        let html = '';
        @can('finance.fixed_asset_components.activate')
          if(row.status==='draft'){
            html += `<button class="btn btn-sm btn-success me-1 btnActivate" data-id="${row.id}"><i class="fas fa-check"></i> Activate</button>`;
          }
        @endcan
        @can('finance.fixed_asset_components.retire')
          if(row.status!=='retired'){
            html += `<button class="btn btn-sm btn-outline-danger btnRetire" data-id="${row.id}"><i class="fas fa-ban"></i> Retire</button>`;
          }
        @endcan
        return html || '-';
      }},
    ]
  });

  $('#btnAdd').on('click', ()=>{ $('#f')[0].reset(); $('#cost').val(0); $('#salvage_value').val(0); $('#useful_life_months').val(60); $('#m').modal('show'); });

  $('#f').on('submit', function(e){
    e.preventDefault();
    $.post("{{ route('admin.finance.fixed_assets.components.store') }}", {
      parent_asset_id: $('#parent_asset_id').val(),
      component_code: $('#component_code').val(),
      name: $('#name').val(),
      description: $('#description').val(),
      cost: $('#cost').val(),
      salvage_value: $('#salvage_value').val(),
      depr_method: $('#depr_method').val(),
      useful_life_months: $('#useful_life_months').val(),
      depr_rate: $('#depr_rate').val(),
    })
    .done(res=>{ $('#m').modal('hide'); dt.ajax.reload(null,false); Swal.fire('Created',res.message,'success'); })
    .fail(xhr=>Swal.fire('Error',xhr.responseJSON?.message || 'Failed','error'));
  });

  $(document).on('click','.btnActivate', function(){
    const id = $(this).data('id');
    $.post("{{ route('admin.finance.fixed_assets.components.activate', ['id'=>'ID']) }}".replace('ID',id))
      .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Done',res.message,'success'); })
      .fail(xhr=>Swal.fire('Error',xhr.responseJSON?.message || 'Failed','error'));
  });

  $(document).on('click','.btnRetire', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Retire component?', icon:'warning', showCancelButton:true, confirmButtonText:'Retire'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post("{{ route('admin.finance.fixed_assets.components.retire', ['id'=>'ID']) }}".replace('ID',id))
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Done',res.message,'success'); })
        .fail(xhr=>Swal.fire('Error',xhr.responseJSON?.message || 'Failed','error'));
    });
  });

});
</script>
@endpush