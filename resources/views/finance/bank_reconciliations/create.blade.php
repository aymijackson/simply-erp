{{-- File: Modules/Finance/Resources/views/finance/bank_reconciliations/create.blade.php --}}

@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <h4 class="mb-3">New Bank Reconciliation</h4>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('admin.finance.bank_reconciliations.store') }}">
        @csrf

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Bank Account</label>
            <select name="bank_account_id" class="form-select" required>
              <option value="">-- select --</option>
              @foreach($bankAccounts as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3 mb-3">
            <label class="form-label">Period Start</label>
            <input type="date" name="period_start" class="form-control" required>
          </div>

          <div class="col-md-3 mb-3">
            <label class="form-label">Period End</label>
            <input type="date" name="period_end" class="form-control" required>
          </div>

          <div class="col-md-3 mb-3">
            <label class="form-label">Statement Opening</label>
            <input type="number" step="0.01" name="statement_opening_balance" class="form-control" value="0.00" required>
          </div>

          <div class="col-md-3 mb-3">
            <label class="form-label">Statement Closing</label>
            <input type="number" step="0.01" name="statement_closing_balance" class="form-control" value="0.00" required>
          </div>

          <div class="col-md-12 mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
          </div>
        </div>

        <button class="btn btn-primary">Create</button>
        <a href="{{ route('admin.finance.bank_reconciliations.index') }}" class="btn btn-light">Cancel</a>
      </form>
    </div>
  </div>
</div>
@endsection