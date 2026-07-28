@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Asset Write-Offs</h4>
      <div class="text-muted small">Mark assets as written off (posts NBV loss, proceeds = 0).</div>
    </div>

    @can('finance.fixed_asset_writeoffs.create')
      <button class="btn btn-primary" id="btnAdd"><i class="fas fa-plus me-1"></i> New Write-off</button>
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
            <th>Status</th>
            <th>Journal</th>
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
        <h5 class="modal-title">New Write-off</h5>
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
              <label class="form-label">Write-off Date</label>
              <input type="date" class="form-control" id="writeoff_date" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="col-md-12">
              <label class="form-label">Memo</label>
              <input class="form-control" id="memo" maxlength="255">
            </div>
          </div>

          <div class="alert alert-info small mt-3 mb-0">
            GL Posting removes asset cost, reverses accum dep, and books NBV as loss (requires disposal loss mapping).
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
    ajax: "{{ route('admin.finance.fixed_assets.writeoffs.datatable') }}",
    columns:[
      {data:'id'},
      {data:'asset_id'},
      {data:'writeoff_date'},
      {data:'status', render:(d)=>badge(d)},
      {data:'journal_entry_id', defaultContent:'-'},
      {data:null, orderable:false, searchable:false, render:(row)=>{
        let html = '';
        @can('finance.fixed_asset_writeoffs.post')
          if(row.status==='draft') html += `<button class="btn btn-sm btn-success me-1 btnPost" data-id="${row.id}"><i class="fas fa-check"></i> Post</button>`;
        @endcan
        @can('finance.fixed_asset_writeoffs.void')
          if(row.status==='posted') html += `<button class="btn btn-sm btn-outline-danger btnVoid" data-id="${row.id}"><i class="fas fa-ban"></i> Void</button>`;
        @endcan
        return html || '-';
      }},
    ]
  });

  $('#btnAdd').on('click', function(){
    $('#f')[0].reset();
    $('#writeoff_date').val("{{ date('Y-m-d') }}");
    $('#m').modal('show');
  });

  $('#f').on('submit', function(e){
    e.preventDefault();
    $.post("{{ route('admin.finance.fixed_assets.writeoffs.store') }}", {
      asset_id: $('#asset_id').val(),
      writeoff_date: $('#writeoff_date').val(),
      memo: $('#memo').val(),
    })
    .done(res=>{ $('#m').modal('hide'); dt.ajax.reload(null,false); Swal.fire('Created', res.message, 'success'); })
    .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
  });

  $(document).on('click','.btnPost', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Post write-off?', icon:'question', showCancelButton:true, confirmButtonText:'Post'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`{{ url('admin/finance/fixed-assets/writeoffs') }}/${id}/post`)
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Posted', res.message, 'success'); })
        .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
  });

  $(document).on('click','.btnVoid', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Void write-off?', input:'text', inputLabel:'Reason', showCancelButton:true, confirmButtonText:'Void'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.post(`{{ url('admin/finance/fixed-assets/writeoffs') }}/${id}/void`, {reason:r.value})
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Voided', res.message, 'success'); })
        .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
  });

});
</script>
@endpush