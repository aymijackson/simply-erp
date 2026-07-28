@extends('layouts.master')
@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Depreciation Summary</h4>
      <div class="text-muted small">Recent depreciation runs and journal references.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.finance.fixed_assets.reports.index') }}"><i class="fas fa-arrow-left me-1"></i> Back</a>
      <a class="btn btn-primary" href="{{ route('admin.finance.fixed_assets.reports.depreciation.pdf') }}"><i class="fas fa-file-pdf me-1"></i> Download PDF</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Period Start</th>
            <th>Period End</th>
            <th>Run Date</th>
            <th>Status</th>
            <th>Journal</th>
          </tr>
        </thead>
        <tbody>
          @foreach($runs as $r)
            <tr>
              <td>{{ $r->id }}</td>
              <td>{{ $r->period_start }}</td>
              <td>{{ $r->period_end }}</td>
              <td>{{ $r->run_date }}</td>
              <td>{{ $r->status }}</td>
              <td>{{ $r->journal_entry_id ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
