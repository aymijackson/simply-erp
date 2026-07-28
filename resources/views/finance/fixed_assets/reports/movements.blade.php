@extends('layouts.master')
@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Fixed Asset Movements</h4>
      <div class="text-muted small">Transactions (acquisition/disposal) and transfers.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.finance.fixed_assets.reports.index') }}"><i class="fas fa-arrow-left me-1"></i> Back</a>
      <a class="btn btn-primary" href="{{ route('admin.finance.fixed_assets.reports.movements.pdf') }}"><i class="fas fa-file-pdf me-1"></i> Download PDF</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body table-responsive">
      <h6 class="fw-semibold mb-2">Asset Transactions</h6>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Asset ID</th>
            <th>Type</th>
            <th>Date</th>
            <th class="text-end">Amount</th>
            <th>Status</th>
            <th>Journal</th>
          </tr>
        </thead>
        <tbody>
          @foreach($txns as $t)
            <tr>
              <td>{{ $t->id }}</td>
              <td>{{ $t->asset_id }}</td>
              <td>{{ $t->txn_type }}</td>
              <td>{{ $t->txn_date }}</td>
              <td class="text-end">{{ number_format((float)$t->amount,2) }}</td>
              <td>{{ $t->status }}</td>
              <td>{{ $t->journal_entry_id ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <h6 class="fw-semibold mb-2">Transfers</h6>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Asset ID</th>
            <th>Date</th>
            <th>From</th>
            <th>To</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($transfers as $tr)
            <tr>
              <td>{{ $tr->id }}</td>
              <td>{{ $tr->asset_id }}</td>
              <td>{{ $tr->transfer_date }}</td>
              <td>{{ $tr->from_location ?? '-' }}</td>
              <td>{{ $tr->to_location ?? '-' }}</td>
              <td>{{ $tr->status }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection