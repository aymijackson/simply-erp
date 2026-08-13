@extends('layouts.master')

@section('title','Fiscal Periods')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Fiscal Periods</h1>
      <small class="text-muted">Finance / Period Locking</small>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('admin.finance.periods.index') ?? '#' }}" class="btn btn-outline-secondary">
        <i class="fas fa-calendar-alt me-1"></i> Fiscal Years
      </a>
      <a href="{{ route('admin.finance.settings.index') ?? '#' }}" class="btn btn-outline-primary">
        <i class="fas fa-cog me-1"></i> Lock Settings
      </a>
    </div>
  </div>

  {{-- Settings summary --}}
  <div class="row mb-3">
    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">
          <i class="fas fa-shield-alt me-1"></i> Posting Controls
        </div>
        <div class="card-body">
          @php
            $lockDate = $settings->lock_date ?? null;
            $allowClosed = (int)($settings->allow_post_to_closed_period ?? 0);
            $maxBack = $settings->max_backdate_days ?? null;
            $restrictFuture = (int)($settings->restrict_future_posting ?? 0);
          @endphp

          <div class="row">
            <div class="col-md-3 mb-2">
              <div class="border rounded p-3">
                <div class="text-muted small">Lock Date</div>
                <div class="fw-bold">{{ $lockDate ? $lockDate : 'Not set' }}</div>
                <small class="text-muted d-block mt-1">Blocks posting on/before lock date.</small>
              </div>
            </div>

            <div class="col-md-3 mb-2">
              <div class="border rounded p-3">
                <div class="text-muted small">Post to Closed Period</div>
                <div class="fw-bold">
                  @if($allowClosed)
                    <span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Allowed</span>
                  @else
                    <span class="text-success"><i class="fas fa-check-circle me-1"></i> Blocked</span>
                  @endif
                </div>
                <small class="text-muted d-block mt-1">Should normally be Blocked.</small>
              </div>
            </div>

            <div class="col-md-3 mb-2">
              <div class="border rounded p-3">
                <div class="text-muted small">Max Backdate Days</div>
                <div class="fw-bold">{{ is_null($maxBack) ? 'No limit' : (int)$maxBack }}</div>
                <small class="text-muted d-block mt-1">Optional backdate restriction.</small>
              </div>
            </div>

            <div class="col-md-3 mb-2">
              <div class="border rounded p-3">
                <div class="text-muted small">Restrict Future Posting</div>
                <div class="fw-bold">
                  @if($restrictFuture)
                    <span class="text-warning"><i class="fas fa-lock me-1"></i> Enabled</span>
                  @else
                    <span class="text-muted">Disabled</span>
                  @endif
                </div>
                <small class="text-muted d-block mt-1">Optional future date control.</small>
              </div>
            </div>
          </div>

          <div class="alert alert-info mt-3 mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Closing a period prevents posting/editing within its dates (unless admin override is enabled).
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Periods table --}}
  <div class="card shadow-sm">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
      <span><i class="fas fa-calendar-check me-1"></i> Periods</span>
      <span class="text-muted small">Company: {{ $companyId ?? (auth()->user()->company_id ?? 1) }}</span>
    </div>

    <div class="card-body">
      <form class="row g-2 mb-3" method="GET" action="{{ url()->current() }}">
        <div class="col-md-4">
          <label class="text-muted small">Fiscal Year</label>
          <select class="form-control" name="fiscal_year_id" onchange="this.form.submit()">
            <option value="">All years</option>
            @foreach(($years ?? []) as $y)
              <option value="{{ $y->id }}" @selected(request('fiscal_year_id') == $y->id)>
                {{ $y->name ?? ('FY #'.$y->id) }} ({{ $y->start_date }} → {{ $y->end_date }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-4">
          <label class="text-muted small">Show</label>
          <select class="form-control" name="status" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="open" @selected(request('status')==='open')>Open only</option>
            <option value="closed" @selected(request('status')==='closed')>Closed only</option>
          </select>
        </div>

        <div class="col-md-4 d-flex align-items-end justify-content-end gap-2">
          <a class="btn btn-outline-secondary" href="{{ url()->current() }}">
            <i class="fas fa-undo me-1"></i> Reset
          </a>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="bg-light">
            <tr>
              <th style="width:90px;">ID</th>
              <th>Period</th>
              <th style="width:120px;">Start</th>
              <th style="width:120px;">End</th>
              <th style="width:120px;">Status</th>
              <th style="width:170px;">Closed</th>
              <th style="width:140px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse(($periods ?? []) as $p)
              @php $closed = (int)($p->is_closed ?? 0) === 1; @endphp
              <tr>
                <td class="fw-bold">#{{ $p->id }}</td>
                <td>
                  <div class="fw-bold">{{ $p->name ?? ('Period #'.$p->id) }}</div>
                  <small class="text-muted">FY: {{ $p->fiscal_year_name ?? '-' }}</small>
                </td>
                <td>{{ $p->start_date }}</td>
                <td>{{ $p->end_date }}</td>
                <td>
                  @if($closed)
                    <span class="badge bg-secondary">CLOSED</span>
                  @else
                    <span class="badge bg-success">OPEN</span>
                  @endif
                </td>
                <td>
                  @if($closed)
                    <small class="text-muted d-block">{{ $p->closed_at }}</small>
                    <small class="text-muted d-block">By: {{ $p->closed_by }}</small>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    @if(!$closed)
                      <button type="button"
                        class="btn btn-outline-danger btn-close-period"
                        data-action="{{ route('admin.finance.periods.close', ['id' => $p->id]) }}"
                        data-name="{{ e($p->name ?? ('Period #'.$p->id)) }}"
                        data-start="{{ $p->start_date }}"
                        data-end="{{ $p->end_date }}">
                        <i class="fas fa-lock"></i>
                      </button>
                    @else
                      <button type="button"
                        class="btn btn-outline-primary btn-open-period"
                        data-action="{{ route('admin.finance.periods.reopen', ['id' => $p->id]) }}"
                        data-name="{{ e($p->name ?? ('Period #'.$p->id)) }}">
                        <i class="fas fa-unlock"></i>
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted">No fiscal periods found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <small class="text-muted">
        Tip: Use period closing after month-end reconciliation. Use year-end close to hard-lock the year.
      </small>
    </div>
  </div>
</div>

{{-- Hidden POST form (we set action dynamically) --}}
<form id="periodActionForm" method="POST" action="">
  @csrf
</form>
@endsection

@push('scripts')
<script>
/**
 * Force swal() usage.
 * If only SweetAlert2 exists, we map swal() to Swal.fire().
 */
if (typeof swal === 'undefined' && typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
  window.swal = function(arg1, arg2, arg3){
    if (typeof arg1 === 'string') {
      return Swal.fire({ title: arg1, text: arg2 || '', icon: arg3 || 'info' });
    }
    return Swal.fire(arg1 || {});
  };
}

function swalOk(msg){ swal({ icon:'success', title:'Success', text: msg || 'Done.' }); }
function swalErr(msg){ swal({ icon:'error', title:'Error', text: msg || 'Something went wrong.' }); }

function submitPeriodAction(url){
  const form = document.getElementById('periodActionForm');
  form.action = url;
  form.submit();
}

$(document).on('click', '.btn-close-period', function(){
  const url = $(this).data('action');
  const name = $(this).data('name');
  const start = $(this).data('start');
  const end = $(this).data('end');

  swal({
    icon: 'warning',
    title: 'Close period?',
    text: `${name} (${start} → ${end}) will be locked for posting.`,
    buttons: { cancel: true, confirm: { text: 'Yes, close', value: true } },
    dangerMode: true
  }).then((ok) => {
    if(!ok) return;
    submitPeriodAction(url);
  });
});

$(document).on('click', '.btn-open-period', function(){
  const url = $(this).data('action');
  const name = $(this).data('name');

  swal({
    icon: 'warning',
    title: 'Re-open period?',
    text: `${name} will become OPEN again. Use only with proper approval.`,
    buttons: { cancel: true, confirm: { text: 'Yes, open', value: true } },
    dangerMode: true
  }).then((ok) => {
    if(!ok) return;
    submitPeriodAction(url);
  });
});

@if(session('success'))
  setTimeout(()=>swalOk(@json(session('success'))), 200);
@endif
@if(session('error'))
  setTimeout(()=>swalErr(@json(session('error'))), 200);
@endif
</script>
@endpush