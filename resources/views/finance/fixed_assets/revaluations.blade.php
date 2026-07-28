@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Asset Revaluations</h4>
      <div class="text-muted small">Increase/decrease asset cost with reserve or P&L mapping, posting to GL.</div>
    </div>

    @can('finance.fixed_asset_revaluations.create')
      <button class="btn btn-primary" id="btnAdd"><i class="fas fa-plus me-1"></i> New Revaluation</button>
    @endcan
  </div>

  <div class="card">
    <div class="card-body">
      <table class="table table-bordered table-striped w-100" id="tbl">
        <thead>
          <tr>
            <th>ID</th>
            <th>Asset ID</th>
            <th>Date</th>
            <th>Old Cost</th>
            <th>New Cost</th>
            <th>Delta</th>
            <th>Status</th>
            <th style="width:240px;">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="m" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Revaluation</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="f">
        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Asset ID</label>
              <input class="form-control" id="asset_id" required placeholder="finance_fixed_assets.id">
            </div>

            <div class="col-md-6">
              <label class="form-label">Revaluation Date</label>
              <input type="date" class="form-control" id="reval_date" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">New Cost</label>
              <input type="number" step="0.01" class="form-control" id="new_cost" required value="0">
            </div>

            <div class="col-md-6">
              <label class="form-label">Method</label>
              <select class="form-select" id="method" required>
                <option value="reserve">Reserve (Equity)</option>
                <option value="pnl">Profit & Loss</option>
              </select>
              <div class="small text-muted">Reserve uses Revaluation Reserve; P&L uses income/expense account.</div>
            </div>

            <div class="col-md-12">
              <label class="form-label">Revaluation Account (COA)</label>
              <select class="form-select" id="revaluation_account_id" required>
                <option value="">-- select --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Memo</label>
              <input class="form-control" id="memo" maxlength="255">
            </div>

          </div>

          <div class="alert alert-info small mt-3 mb-0">
            GL Posting:<br>
            Increase: Dr Asset / Cr Revaluation Account<br>
            Decrease: Dr Revaluation Account / Cr Asset
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

let dt;
function badge(s){ const map={draft:'secondary',posted:'success',voided:'danger'}; return `<span class="badge bg-${map[s]||'secondary'}">${s}</span>`; }

$(function(){
  dt = $('#tbl').DataTable({
    ajax: "{{ route('admin.finance.fixed_assets.revaluations.datatable') }}",
    columns:[
      {data:'id'},
      {data:'asset_id'},
      {data:'reval_date'},
      {data:'old_cost', render:(d)=>Number(d||0).toFixed(2)},
      {data:'new_cost', render:(d)=>Number(d||0).toFixed(2)},
      {data:'delta', render:(d)=>Number(d||0).toFixed(2)},
      {data:'status', render:(d)=>badge(d)},
      {data:null, orderable:false, searchable:false, render:(row)=>{
        let html = '';
        @can('finance.fixed_asset_revaluations.post')
          if(row.status==='draft') html += `<button class="btn btn-sm btn-success me-1 btnPost" data-id="${row.id}"><i class="fas fa-check"></i> Post</button>`;
        @endcan
        @can('finance.fixed_asset_revaluations.void')
          if(row.status==='posted') html += `<button class="btn btn-sm btn-outline-danger btnVoid" data-id="${row.id}"><i class="fas fa-ban"></i> Void</button>`;
        @endcan
        return html || '-';
      }},
    ]
  });

  $('#btnAdd').on('click', function(){
    $('#f')[0].reset();
    $('#reval_date').val("{{ date('Y-m-d') }}");
    $('#new_cost').val(0);
    $('#m').modal('show');
  });

  $('#f').on('submit', function(e){
    e.preventDefault();
    $.post("{{ route('admin.finance.fixed_assets.revaluations.store') }}", {
      asset_id: $('#asset_id').val(),
      reval_date: $('#reval_date').val(),
      new_cost: $('#new_cost').val(),
      method: $('#method').val(),
      revaluation_account_id: $('#revaluation_account_id').val(),
      memo: $('#memo').val(),
    })
    .done(res=>{ $('#m').modal('hide'); dt.ajax.reload(null,false); Swal.fire('Created', res.message, 'success'); })
    .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
  });

  $(document).on('click','.btnPost', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Post revaluation?', icon:'question', showCancelButton:true, confirmButtonText:'Post'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`{{ url('admin/finance/fixed-assets/revaluations') }}/${id}/post`)
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Posted', res.message, 'success'); })
        .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
  });

  $(document).on('click','.btnVoid', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Void revaluation?', input:'text', inputLabel:'Reason', showCancelButton:true, confirmButtonText:'Void'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`{{ url('admin/finance/fixed-assets/revaluations') }}/${id}/void`, {reason:r.value})
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Voided', res.message, 'success'); })
        .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
  });

});
</script>
@endpush
