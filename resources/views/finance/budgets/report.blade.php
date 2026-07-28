{{-- File: Modules/Finance/Resources/views/finance/budgets/report.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Budget vs Actual</h4>
      <small class="text-muted">{{ $budget->name }} | {{ $budget->start_date->format('Y-m-d') }} → {{ $budget->end_date->format('Y-m-d') }}</small>
    </div>
    <a href="{{ route('admin.finance.budgets.edit', $budget->id) }}" class="btn btn-outline-secondary">Back to Budget</a>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th>Account</th>
            <th class="text-end">Budget</th>
            <th class="text-end">Actual</th>
            <th class="text-end">Variance</th>
            <th class="text-end">Variance %</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $r)
            <tr>
              <td>{{ $r['account_code'] }} — {{ $r['account_name'] }}</td>
              <td class="text-end">{{ number_format($r['budget'],2) }}</td>
              <td class="text-end">{{ number_format($r['actual'],2) }}</td>
              <td class="text-end">{{ number_format($r['variance'],2) }}</td>
              <td class="text-end">
                {{ is_null($r['variance_pct']) ? '-' : number_format($r['variance_pct'],2).'%' }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <small class="text-muted">Note: Actuals are calculated from posted journal lines within the budget date range.</small>
    </div>
  </div>
</div>
@endsection