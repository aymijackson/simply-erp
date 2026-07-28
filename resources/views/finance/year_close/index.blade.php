{{-- resources/views/finance/year_close/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Year-End Close')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    // SweetAlert wrapper: supports swal() even if you only included SweetAlert2 (Swal.fire)
    // If you already have SweetAlert v1, swal() exists and this will not override it.
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Year-End Close</h1>
            <small class="text-muted">Finance / Period Locking / Retained Earnings</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.finance.settings.index') ?? '#' }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-cog me-1"></i> Company Finance Settings
            </a>
        </div>
    </div>

    {{-- Settings status --}}
    <div class="row">
        <div class="col-lg-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="fas fa-info-circle me-1"></i> Pre-close checklist
                </div>
                <div class="card-body">
                    @php
                        $retained = $settings->retained_earnings_account_id ?? null;
                        $incomeSummary = $settings->income_summary_account_id ?? null;
                    @endphp

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <div class="p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small">Retained Earnings Account</div>
                                        <div class="fw-bold">
                                            @if($retained)
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i> Set (Account ID: {{ $retained }})
                                                </span>
                                            @else
                                                <span class="text-danger">
                                                    <i class="fas fa-times-circle me-1"></i> Not set
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ route('admin.finance.settings.index') ?? '#' }}">
                                            Configure
                                        </a>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    This is where year profit/loss is accumulated permanently.
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6 mb-2">
                            <div class="p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small">Income Summary Account</div>
                                        <div class="fw-bold">
                                            @if($incomeSummary)
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i> Set (Account ID: {{ $incomeSummary }})
                                                </span>
                                            @else
                                                <span class="text-danger">
                                                    <i class="fas fa-times-circle me-1"></i> Not set
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ route('admin.finance.settings.index') ?? '#' }}">
                                            Configure
                                        </a>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Temporary account used to close income/expense into retained earnings.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="fas fa-lock me-1"></i>
                        <strong>Important:</strong> Closing a fiscal year will <strong>hard-lock</strong> all fiscal periods within the year
                        (sets <code>finance_fiscal_periods.is_closed = 1</code>) and marks the year as closed.
                        Unposted journal entries in that year will block the close.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Run year close --}}
    <div class="row">
        <div class="col-lg-5 mb-3">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="fas fa-flag-checkered me-1"></i> Run Year-End Close
                </div>
                <div class="card-body">
                    <form id="yearCloseForm" method="POST" action="{{ route('admin.finance.year_close.run') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="text-muted small">Fiscal Year</label>
                            <select class="form-control" name="fiscal_year_id" id="fiscal_year_id" required>
                                <option value="" disabled selected>Select fiscal year...</option>
                                @foreach($years as $y)
                                    <option value="{{ $y->id }}"
                                            data-closed="{{ (int)($y->is_closed ?? 0) }}"
                                            data-start="{{ $y->start_date }}"
                                            data-end="{{ $y->end_date }}">
                                        {{ $y->name ?? ('FY #'.$y->id) }} ({{ $y->start_date }} → {{ $y->end_date }})
                                        @if((int)($y->is_closed ?? 0) === 1) — CLOSED @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('fiscal_year_id')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small">Note (optional)</label>
                            <input type="text" class="form-control" name="note" maxlength="255"
                                   placeholder="e.g. Close FY after audit sign-off">
                            @error('note')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="border rounded p-3 bg-light">
                            <div class="text-muted small mb-2">
                                Safety confirmation (type exactly):
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control" id="confirm_phrase"
                                       placeholder="CLOSE YEAR" autocomplete="off">
                                <span class="badge bg-dark">CLOSE YEAR</span>
                            </div>
                            <small class="text-muted d-block mt-2">
                                This prevents accidental year close.
                            </small>
                        </div>

                        <button type="button"
                                class="btn btn-danger mt-3 w-100"
                                id="runYearCloseBtn"
                                @if(!$retained || !$incomeSummary) disabled @endif>
                            <i class="fas fa-lock me-1"></i> Close Selected Fiscal Year
                        </button>

                        @if(!$retained || !$incomeSummary)
                            <small class="text-danger d-block mt-2">
                                You must configure Retained Earnings and Income Summary accounts before closing a year.
                            </small>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- History --}}
        <div class="col-lg-7 mb-3">
            <div class="card shadow-sm">
                <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-history me-1"></i> Year Close History</span>
                    <span class="text-muted small">{{ count($closes ?? []) }} record(s)</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width:70px;">ID</th>
                                    <th>Fiscal Year</th>
                                    <th style="width:140px;">Period</th>
                                    <th class="text-end" style="width:140px;">Net Profit</th>
                                    <th style="width:140px;">Closing JE</th>
                                    <th style="width:170px;">Closed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($closes as $c)
                                    <tr>
                                        <td class="fw-bold">#{{ $c->id }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $c->fiscal_year_name }}</div>
                                            @if(!empty($c->note))
                                                <small class="text-muted">{{ $c->note }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted d-block">{{ $c->start_date }}</small>
                                            <small class="text-muted d-block">{{ $c->end_date }}</small>
                                        </td>
                                        <td class="text-end fw-bold">
                                            {{ number_format((float)$c->net_profit, 2) }}
                                        </td>
                                        <td>
                                            @if($c->closing_journal_entry_id)
                                                <span class="badge bg-success">
                                                    JE #{{ $c->closing_journal_entry_id }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">None</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted d-block">{{ $c->closed_at }}</small>
                                            <small class="text-muted d-block">By: {{ $c->closed_by }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No year close records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <small class="text-muted">
                        If Net Profit is 0.00, the service records the closure but skips posting a closing JE.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Force swal() usage:
 * - If SweetAlert v1 is present => swal exists already.
 * - If SweetAlert2 (Swal.fire) is present but swal isn't => create a swal wrapper.
 */
if (typeof swal === 'undefined' && typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
  window.swal = function(optsOrTitle, text, icon){
    // support swal("title","text","success") and swal({ ... })
    if (typeof optsOrTitle === 'string') {
      return Swal.fire({ title: optsOrTitle, text: text || '', icon: icon || 'info' });
    }
    return Swal.fire(optsOrTitle || {});
  };
}

function swalSuccess(msg){
  swal({ icon:'success', title:'Success', text: msg || 'Done.' });
}
function swalError(msg){
  swal({ icon:'error', title:'Error', text: msg || 'Something went wrong.' });
}
function swalWarnConfirm(title, text, onYes){
  swal({
    icon: 'warning',
    title: title || 'Are you sure?',
    text: text || '',
    buttons: {
      cancel: { text: 'Cancel', visible: true },
      confirm: { text: 'Yes, continue', closeModal: true }
    },
    dangerMode: true
  }).then(function(ok){
    if (ok) onYes && onYes();
  });
}

document.getElementById('runYearCloseBtn')?.addEventListener('click', function(){
  const fySel = document.getElementById('fiscal_year_id');
  const opt = fySel?.options[fySel.selectedIndex];
  const fyId = fySel?.value;

  if (!fyId) {
    return swalError('Please select a fiscal year.');
  }

  const isClosed = opt?.getAttribute('data-closed') === '1';
  if (isClosed) {
    return swalError('That fiscal year is already closed.');
  }

  const phrase = (document.getElementById('confirm_phrase')?.value || '').trim();
  if (phrase !== 'CLOSE YEAR') {
    return swalError('Type exactly: CLOSE YEAR');
  }

  const start = opt?.getAttribute('data-start') || '';
  const end   = opt?.getAttribute('data-end') || '';

  swalWarnConfirm(
    'Close fiscal year?',
    `This will lock all periods in ${start} → ${end} and post a closing journal to retained earnings (if profit/loss != 0).`,
    function(){
      document.getElementById('yearCloseForm').submit();
    }
  );
});

// flash messages -> swal()
@php
  $success = session('success');
  $error = session('error');
@endphp

@if(!empty($success))
  setTimeout(()=> swalSuccess(@json($success)), 200);
@endif

@if(!empty($error))
  setTimeout(()=> swalError(@json($error)), 200);
@endif
</script>
@endpush