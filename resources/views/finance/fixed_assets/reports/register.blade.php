@extends('layouts.master')
@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Fixed Asset Register</h4>
      <div class="text-muted small">NBV = Cost − Accumulated Depreciation (posted).</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.finance.fixed_assets.reports.index') }}"><i class="fas fa-arrow-left me-1"></i> Back</a>
      <a class="btn btn-primary" href="{{ route('admin.finance.fixed_assets.reports.register.pdf') }}"><i class="fas fa-file-pdf me-1"></i> Download PDF</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Category</th>
            <th>Status</th>
            <th class="text-end">Cost</th>
            <th class="text-end">Accum Dep</th>
            <th class="text-end">NBV</th>
            <th>In Service</th>
          </tr>
        </thead>
        <tbody>
          @php($totCost=0) @php($totAcc=0) @php($totNbv=0)
          @foreach($assets as $a)
            @php($acc = (float)($accumMap[$a->id] ?? 0))
            @php($nbv = max(0, (float)$a->purchase_cost - $acc))
            @php($totCost += (float)$a->purchase_cost)
            @php($totAcc += $acc)
            @php($totNbv += $nbv)
            <tr>
              <td>{{ $a->asset_code }}</td>
              <td>{{ $a->name }}</td>
              <td>{{ $a->category?->name }}</td>
              <td>{{ $a->status }}</td>
              <td class="text-end">{{ number_format((float)$a->purchase_cost,2) }}</td>
              <td class="text-end">{{ number_format($acc,2) }}</td>
              <td class="text-end">{{ number_format($nbv,2) }}</td>
              <td>{{ $a->in_service_date }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr class="fw-semibold">
            <td colspan="4" class="text-end">Totals</td>
            <td class="text-end">{{ number_format($totCost,2) }}</td>
            <td class="text-end">{{ number_format($totAcc,2) }}</td>
            <td class="text-end">{{ number_format($totNbv,2) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection
