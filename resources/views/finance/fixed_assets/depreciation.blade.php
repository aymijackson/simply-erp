{{-- =========================================================
   Fixed Asset Depreciation (Run Wizard + Runs List)
   Path: Modules/Finance/Resources/views/finance/fixed_assets/depreciation.blade.php
   Controller: DepreciationController@index (passes $runs)
   ========================================================= --}}

@extends('layouts.master')

@section('content')
<div class="container-fluid">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Fixed Asset Depreciation</h4>
      <div class="text-muted small">
        Workflow: <b>Preview</b> → <b>Create Draft Run</b> → <b>Post</b> (GL) → <b>Void</b> (GL reversal).
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ url('admin/finance/fixed-assets') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Fixed Assets
      </a>
      <a href="{{ route('admin.finance.fixed_assets.reports.index') }}" class="btn btn-outline-primary">
        <i class="fas fa-file-alt me-1"></i> Reports
      </a>
    </div>
  </div>

  {{-- Run Wizard --}}
  @can('finance.fixed_asset_depreciation.run')
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h6 class="mb-0">Depreciation Run Wizard</h6>
        <div class="text-muted small">Preview lines before creating the draft run for the period.</div>
      </div>
    </div>

    <div class="card-body">
      <div class="row g-3 align-items-end">

        <div class="col-md-3">
          <label class="form-label">Period Start</label>
          <input type="date" class="form-control" id="period_start" value="{{ date('Y-m-01') }}">
        </div>

        <div class="col-md-3">
          <label class="form-label">Period End</label>
          <input type="date" class="form-control" id="period_end" value="{{ date('Y-m-t') }}">
        </div>

        <div class="col-md-3">
          <label class="form-label">Run Date</label>
          <input type="date" class="form-control" id="run_date" value="{{ date('Y-m-d') }}">
        </div>

        <div class="col-md-3">
          <label class="form-label">Notes (optional)</label>
          <input type="text" class="form-control" id="notes" maxlength="255" placeholder="Monthly depreciation run">
        </div>

        <div class="col-12">
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary" id="btnPreview">
              <i class="fas fa-search me-1"></i> Preview
            </button>

            <button class="btn btn-primary" id="btnCreateDraft" disabled>
              <i class="fas fa-plus me-1"></i> Create Draft Run
            </button>
          </div>
        </div>

      </div>

      {{-- Preview Summary --}}
      <div class="mt-3 d-none" id="previewBox">
        <div class="alert alert-info mb-2">
          <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
              <b>Preview Summary</b>
              <div class="small">
                Lines: <span id="pv_count">0</span> |
                Total Depreciation: <span id="pv_total">0.00</span>
              </div>
            </div>
            <div class="small text-muted">
              Only assets with depreciation &gt; 0 appear.
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped w-100" id="tblPreview">
            <thead>
              <tr>
                <th>Asset ID</th>
                <th>Asset Code</th>
                <th>Asset Name</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
  @endcan

  {{-- Existing Runs --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h6 class="mb-0">Depreciation Runs</h6>
        <div class="text-muted small">Latest runs (Draft / Posted / Voided).</div>
      </div>
    </div>

    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped w-100" id="tblRuns">
        <thead>
          <tr>
            <th>ID</th>
            <th>Period Start</th>
            <th>Period End</th>
            <th>Run Date</th>
            <th>Status</th>
            <th>Journal</th>
            <th>Notes</th>
            <th style="width:280px;">Actions</th>
          </tr>
        </thead>

        <tbody>
          @foreach($runs as $r)
            <tr>
              <td>{{ $r->id }}</td>
              <td>{{ $r->period_start }}</td>
              <td>{{ $r->period_end }}</td>
              <td>{{ $r->run_date }}</td>
              <td>
                @php($map=['draft'=>'secondary','posted'=>'success','voided'=>'danger'])
                <span class="badge bg-{{ $map[$r->status] ?? 'secondary' }}">{{ $r->status }}</span>
              </td>
              <td>{{ $r->journal_entry_id ?? '-' }}</td>
              <td>{{ $r->notes ?? '-' }}</td>
              <td>
                @can('finance.fixed_asset_depreciation.post')
                  @if($r->status === 'draft')
                    <button class="btn btn-sm btn-success me-1 btnPost" data-id="{{ $r->id }}">
                      <i class="fas fa-check"></i> Post
                    </button>
                  @endif
                @endcan

                @can('finance.fixed_asset_depreciation.void')
                  @if($r->status === 'posted')
                    <button class="btn btn-sm btn-outline-danger me-1 btnVoid" data-id="{{ $r->id }}">
                      <i class="fas fa-ban"></i> Void
                    </button>
                  @endif
                @endcan

                @if($r->status === 'posted' && $r->journal_entry_id)
                  <span class="small text-muted">JE #{{ $r->journal_entry_id }}</span>
                @endif

                @if($r->status === 'voided')
                  <span class="small text-muted">
                    {{ $r->void_reason ? 'Reason: '.$r->void_reason : '' }}
                  </span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>

      </table>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({
    headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}
});

function fmt(n){
  return Number(n || 0).toLocaleString(undefined, {
      minimumFractionDigits:2,
      maximumFractionDigits:2
  });
}

/*
|--------------------------------------------------------------------------
| ROUTE ENDPOINTS
|--------------------------------------------------------------------------
*/
const ROUTES = {
    preview: "{{ route('admin.finance.fixed_assets.depreciation.preview') }}",
    run: "{{ route('admin.finance.fixed_assets.depreciation.run') }}",
    post: "{{ route('admin.finance.fixed_assets.depreciation.post', ['runId' => 'RUN_ID']) }}",
    void: "{{ route('admin.finance.fixed_assets.depreciation.void', ['runId' => 'RUN_ID']) }}"
};

function routeReplace(url, id){
    return url.replace('RUN_ID', id);
}

$(function(){

    // Enhance runs table
    if ($.fn.DataTable) {
        $('#tblRuns').DataTable({
            pageLength: 25,
            order: [[0,'desc']]
        });
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW
    |--------------------------------------------------------------------------
    */
    $('#btnPreview').on('click', function(){

        const payload = {
            period_start: $('#period_start').val(),
            period_end: $('#period_end').val(),
            run_date: $('#run_date').val()
        };

        $('#btnCreateDraft').prop('disabled', true);
        $('#previewBox').addClass('d-none');
        $('#tblPreview tbody').html('');

        $.post(ROUTES.preview, payload)
        .done(function(res){

            const lines = res.lines || [];

            $('#pv_count').text(res.count ?? lines.length);
            $('#pv_total').text(fmt(res.total));

            let html = '';

            lines.forEach(function(l){
                html += `
                <tr>
                    <td>${l.asset_id}</td>
                    <td>${l.asset_code}</td>
                    <td>${l.asset_name}</td>
                    <td class="text-end fw-semibold">${fmt(l.amount)}</td>
                </tr>`;
            });

            $('#tblPreview tbody').html(html);
            $('#previewBox').removeClass('d-none');

            $('#btnCreateDraft').prop('disabled', lines.length === 0);

            Swal.fire('Preview Ready','Depreciation preview generated','success');

        }).fail(function(xhr){
            Swal.fire('Error', xhr.responseJSON?.message || 'Preview failed', 'error');
        });

    });


    /*
    |--------------------------------------------------------------------------
    | CREATE RUN
    |--------------------------------------------------------------------------
    */
    $('#btnCreateDraft').on('click', function(){

        const payload = {
            period_start: $('#period_start').val(),
            period_end: $('#period_end').val(),
            run_date: $('#run_date').val(),
            notes: $('#notes').val()
        };

        Swal.fire({
            title:'Create draft depreciation run?',
            icon:'question',
            showCancelButton:true,
            confirmButtonText:'Create'
        }).then(function(r){

            if(!r.isConfirmed) return;

            $.post(ROUTES.run, payload)
            .done(function(res){

                Swal.fire('Created', res.message || 'Run created', 'success')
                .then(()=> location.reload());

            }).fail(function(xhr){
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error');
            });

        });

    });


    /*
    |--------------------------------------------------------------------------
    | POST RUN
    |--------------------------------------------------------------------------
    */
    $(document).on('click','.btnPost',function(){

        const id = $(this).data('id');

        Swal.fire({
            title:'Post depreciation run?',
            text:'This will create GL journal entries.',
            icon:'warning',
            showCancelButton:true,
            confirmButtonText:'Post'
        }).then(function(r){

            if(!r.isConfirmed) return;

            $.post(routeReplace(ROUTES.post,id))
            .done(function(res){

                Swal.fire('Posted',res.message,'success')
                .then(()=> location.reload());

            }).fail(function(xhr){
                Swal.fire('Error',xhr.responseJSON?.message || 'Post failed','error');
            });

        });

    });


    /*
    |--------------------------------------------------------------------------
    | VOID RUN
    |--------------------------------------------------------------------------
    */
    $(document).on('click','.btnVoid',function(){

        const id = $(this).data('id');

        Swal.fire({
            title:'Void depreciation run?',
            input:'text',
            inputLabel:'Reason',
            showCancelButton:true,
            confirmButtonText:'Void'
        }).then(function(r){

            if(!r.isConfirmed) return;
            if(!r.value) return Swal.fire('Reason required');

            $.post(routeReplace(ROUTES.void,id), {reason:r.value})
            .done(function(res){

                Swal.fire('Voided',res.message,'success')
                .then(()=> location.reload());

            }).fail(function(xhr){
                Swal.fire('Error',xhr.responseJSON?.message || 'Void failed','error');
            });

        });

    });

});
</script>
@endpush