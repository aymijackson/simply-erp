@extends('layouts.master')
@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Depreciation Forecast</h4>
      <div class="text-muted small">Simple monthly forecast based on (Cost − Salvage) / Useful Life.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.finance.fixed_assets.reports.index') }}"><i class="fas fa-arrow-left me-1"></i> Back</a>
      <a class="btn btn-primary" href="{{ route('admin.finance.fixed_assets.reports.forecast.pdf') }}"><i class="fas fa-file-pdf me-1"></i> Download PDF</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Asset Code</th>
            <th>Name</th>
            <th class="text-end">Per Month</th>
            <th class="text-end">Life (months)</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $r)
            <tr>
              <td>{{ $r['asset_code'] }}</td>
              <td>{{ $r['name'] }}</td>
              <td class="text-end">{{ number_format((float)$r['per_month'],2) }}</td>
              <td class="text-end">{{ (int)$r['life_months'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection