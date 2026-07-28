@extends('layouts.master')
@section('title','Activities Analytics')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Activities Analytics</h1>
      <small class="text-muted">CRM</small>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">

        <div class="col-md-3">
          <label class="form-label mb-1">Status</label>
          <select id="f_status" class="form-control">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="overdue">Overdue</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Activity Type</label>
          <select id="f_type" class="form-control">
            <option value="">All</option>
            <option value="call">Call</option>
            <option value="meeting">Meeting</option>
            <option value="email">Email</option>
            <option value="task">Task</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Owner ID (optional)</label>
          <input type="number" id="f_owner_id" class="form-control" placeholder="e.g. 12">
          <small class="text-muted">We can upgrade to Select2 later.</small>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Related Type (optional)</label>
          <select id="f_related_type" class="form-control">
            <option value="">All</option>
            <option value="Modules\CRM\Models\Customer">Customer</option>
            <option value="Modules\CRM\Models\Lead">Lead</option>
            <option value="Modules\CRM\Models\Opportunity">Opportunity</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Due From</label>
          <input type="date" id="f_due_from" class="form-control">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Due To</label>
          <input type="date" id="f_due_to" class="form-control">
        </div>

        <div class="col-md-6 d-flex gap-2">
          <button class="btn btn-outline-primary w-50" id="applyBtn">
            <i class="fas fa-filter me-1"></i> Apply
          </button>
          <button class="btn btn-outline-secondary w-50" id="resetBtn">
            <i class="fas fa-undo me-1"></i> Reset
          </button>
        </div>

      </div>
    </div>
  </div>

  {{-- KPIs --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Total</div><div class="h4" id="k_total">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Pending</div><div class="h4" id="k_pending">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Completed</div><div class="h4" id="k_completed">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Overdue</div><div class="h4" id="k_overdue">—</div></div></div></div>

    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Due Today</div><div class="h4" id="k_due_today">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Due Next 7 Days</div><div class="h4" id="k_due_next7">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Completion Rate</div><div class="h4" id="k_rate">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Top Owner</div><div class="h4" id="k_top_owner">—</div><div class="text-muted small" id="k_top_owner_count"></div></div></div></div>
  </div>

  {{-- Charts --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>By Status</strong></div>
        <div class="card-body"><canvas id="chartStatus" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>By Activity Type</strong></div>
        <div class="card-body"><canvas id="chartType" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Due Date Trend</strong></div>
        <div class="card-body"><canvas id="chartTrend" height="120"></canvas></div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  const routes = {
    kpis:   @json(route('admin.crm.activities.analytics.kpis')),
    charts: @json(route('admin.crm.activities.analytics.charts')),
  };

  function filters(){
    return {
      status: $('#f_status').val() || '',
      activity_type: $('#f_type').val() || '',
      owner_id: $('#f_owner_id').val() || '',
      related_type: $('#f_related_type').val() || '',
      due_from: $('#f_due_from').val() || '',
      due_to: $('#f_due_to').val() || '',
    };
  }

  let chartStatus, chartType, chartTrend;

  async function loadAll(){
    try{
      const f = filters();

      const k = await $.get(routes.kpis, f);
      $('#k_total').text(k.total ?? '—');
      $('#k_pending').text(k.pending ?? '—');
      $('#k_completed').text(k.completed ?? '—');
      $('#k_overdue').text(k.overdue ?? '—');
      $('#k_due_today').text(k.due_today ?? '—');
      $('#k_due_next7').text(k.due_next_7 ?? '—');
      $('#k_rate').text((k.completion_rate ?? 0) + '%');

      $('#k_top_owner').text(k.top_owner_id ? ('Employee #' + k.top_owner_id) : '—');
      $('#k_top_owner_count').text(k.top_owner_count ? (k.top_owner_count + ' activities') : '');

      const c = await $.get(routes.charts, f);

      // By status
      const sLabels = (c.by_status || []).map(x => (x.s || '').toString().toUpperCase());
      const sData   = (c.by_status || []).map(x => Number(x.c || 0));
      if (chartStatus) chartStatus.destroy();
      chartStatus = new Chart(document.getElementById('chartStatus'), {
        type:'bar',
        data:{ labels:sLabels, datasets:[{ label:'Count', data:sData }] }
      });

      // By type
      const tLabels = (c.by_type || []).map(x => (x.t || '').toString().toUpperCase());
      const tData   = (c.by_type || []).map(x => Number(x.c || 0));
      if (chartType) chartType.destroy();
      chartType = new Chart(document.getElementById('chartType'), {
        type:'bar',
        data:{ labels:tLabels, datasets:[{ label:'Count', data:tData }] }
      });

      // Trend
      const dLabels = (c.trend || []).map(x => x.d);
      const dData   = (c.trend || []).map(x => Number(x.c || 0));
      if (chartTrend) chartTrend.destroy();
      chartTrend = new Chart(document.getElementById('chartTrend'), {
        type:'line',
        data:{ labels:dLabels, datasets:[{ label:'Due Count', data:dData }] }
      });

    }catch(e){
      Swal.fire({icon:'error', title:'Error', text:'Failed to load activities analytics.'});
    }
  }

  $('#applyBtn').on('click', loadAll);
  $('#resetBtn').on('click', function(){
    $('#f_status').val('');
    $('#f_type').val('');
    $('#f_owner_id').val('');
    $('#f_related_type').val('');
    $('#f_due_from').val('');
    $('#f_due_to').val('');
    loadAll();
  });

  loadAll();
})();
</script>
@endpush
