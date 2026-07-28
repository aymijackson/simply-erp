{{-- File: Modules/Finance/Resources/views/finance/budgets/create.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <h4 class="mb-3">New Budget</h4>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('admin.finance.budgets.store') }}">
        @csrf

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">Period Type</label>
            <select name="period_type" class="form-select" required>
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="annual">Annual</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Currency (optional)</label>
            <input name="currency_code" class="form-control" maxlength="3" placeholder="e.g. GBP">
          </div>

          <div class="col-md-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
          </div>

          <div class="col-md-12">
            <label class="form-label">Add Accounts now (optional)</label>
            <select class="form-select" name="account_ids[]" multiple>
              @foreach($accounts as $a)
                <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">You can also add/remove accounts later in the budget editor.</small>
          </div>
        </div>

        <div class="mt-3">
          <button class="btn btn-primary">Create</button>
          <a href="{{ route('admin.finance.budgets.index') }}" class="btn btn-light">Cancel</a>
        </div>

      </form>
    </div>
  </div>
</div>
@endsection