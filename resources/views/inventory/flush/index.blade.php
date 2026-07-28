@extends('layouts.master')

@section('title', 'Flush Inventory')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0">Flush Inventory (Danger)</h5>
      <small class="text-muted">
        Use only for test data reset. This will permanently delete inventory operational records.
      </small>
    </div>
  </div>

  <div class="card-body">

    @if(session('ok'))
      <div class="alert alert-success">{{ session('ok') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-warning">
      <strong>What this clears:</strong>
      <ul class="mb-0">
        @foreach($tables as $t)
          <li><code>{{ $t }}</code></li>
        @endforeach
      </ul>
    </div>

    <div class="d-flex gap-2 mb-3">
      <button class="btn btn-outline-secondary btn-sm" id="btnPreview">
        <i class="fas fa-search me-1"></i> Preview counts
      </button>
    </div>

    <div id="previewBox" class="border rounded p-3 d-none">
      <div class="fw-semibold mb-2">Preview (rows per table)</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead>
            <tr><th>Table</th><th class="text-end">Rows</th></tr>
          </thead>
          <tbody id="previewBody"></tbody>
        </table>
      </div>
    </div>

    <hr>

    @can('inventory.flush.execute')
    <form method="POST" action="{{ route('admin.inventory.flush.run') }}" class="mt-3">
      @csrf

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Type <code>FLUSH</code> to confirm</label>
          <input type="text" name="confirm_word" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Confirm your password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <div class="col-md-4 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="dry_run" value="1" id="dryRun">
            <label class="form-check-label" for="dryRun">Dry-run (do not delete)</label>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <button class="btn btn-danger" onclick="return confirm('This will permanently delete inventory records. Continue?')">
          <i class="fas fa-trash-alt me-1"></i> Flush Inventory
        </button>
      </div>
    </form>
    @else
      <div class="alert alert-info mb-0">
        You don’t have permission to flush inventory. (Needs <code>inventory.flush.execute</code>)
      </div>
    @endcan

  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  $('#btnPreview').on('click', function () {
    $.post('{{ route("admin.inventory.flush.preview") }}', {_token:'{{ csrf_token() }}'}, function (res) {
      const rows = Object.keys(res.counts || {}).map(t => {
        const c = res.counts[t];
        return `<tr><td><code>${t}</code></td><td class="text-end">${c ?? '—'}</td></tr>`;
      }).join('');
      $('#previewBody').html(rows || `<tr><td colspan="2" class="text-muted">No data</td></tr>`);
      $('#previewBox').removeClass('d-none');
    }).fail(function () {
      alert('Preview failed');
    });
  });
});
</script>
@endpush
