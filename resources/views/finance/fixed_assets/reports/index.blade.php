@extends('layouts.master')
@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Fixed Asset Reports</h4>
      <div class="text-muted small">Download PDF or view reports in-browser.</div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6 col-lg-3">
      <div class="card"><div class="card-body">
        <h6 class="fw-semibold">Asset Register</h6>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.finance.fixed_assets.reports.register.index') }}">View</a>
          <a class="btn btn-sm btn-primary" href="{{ route('admin.finance.fixed_assets.reports.register.pdf') }}">PDF</a>
        </div>
      </div></div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card"><div class="card-body">
        <h6 class="fw-semibold">Depreciation Summary</h6>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.finance.fixed_assets.reports.depreciation.index') }}">View</a>
          <a class="btn btn-sm btn-primary" href="{{ route('admin.finance.fixed_assets.reports.depreciation.pdf') }}">PDF</a>
        </div>
      </div></div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card"><div class="card-body">
        <h6 class="fw-semibold">Movements</h6>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.finance.fixed_assets.reports.movements.index') }}">View</a>
          <a class="btn btn-sm btn-primary" href="{{ route('admin.finance.fixed_assets.reports.movements.pdf') }}">PDF</a>
        </div>
      </div></div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card"><div class="card-body">
        <h6 class="fw-semibold">Depreciation Forecast</h6>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.finance.fixed_assets.reports.forecast.index') }}">View</a>
          <a class="btn btn-sm btn-primary" href="{{ route('admin.finance.fixed_assets.reports.forecast.pdf') }}">PDF</a>
        </div>
      </div></div>
    </div>
  </div>
</div>
@endsection