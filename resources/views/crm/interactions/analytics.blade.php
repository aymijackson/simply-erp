@extends('layouts.master')
@section('title','Interactions Analytics')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Interactions Analytics</h1>
      <small class="text-muted">CRM</small>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">

        <div class="col-md-3">
          <label class="form-label mb-1">Interaction Type</label>
          <select id="f_type" class="form-control">
            <option value="">All</option>
            <option value="call">Call</option>
            <option value="email">Email</option>
            <option value="meeting">Meeting</option>
            <option value="visit">Visit</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Employee ID (optional)</label>
          <input type="number" id="f_employee_id" class="form-control" placeholder="e.g. 12">
          <small class="text-muted">Tip: we can upgrade to Select2 employee later.</small>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">From</label>
          <input type="date" id="f_from" class="form-control">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">To</label>
          <input type="date" id="f_to" class="form-control">
        </div>

        <div class="col-md-12 d-flex gap-2 mt-2">
          <button class="btn btn-outline-primary" id="applyBtn">
            <i class="fas fa-filter me-1"></i> Apply
          </button>
          <button class="btn btn-outline-secondary" id="resetBtn">
            <i class="fas fa-undo me-1"></i> Reset
          </button>
        </div>

      </div>
    </div>
  </div>

  {{-- KPI Cards --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Total Interactions</div><div class="h4" id="k_total">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Avg / Day</div><div class="h4" id="k_avg_day">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Top Related Type</div><div class="h4" id="k_top_type">—</div><div class="text-muted small" id="k_top_type_count"></div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Calls / Emails</div><div class="h4"><span id="k_calls">—</span> / <span id="k_emails">—</span></div></div></div></div>
  </div>

  {{-- Charts --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>By Interaction Type</strong></div>
        <div class="card-body"><canvas id="chartType" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>By Related Record Type</strong></div>
        <div class="card-body"><canvas id="chartRelatedType" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Daily Trend</strong></div>
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
    kpis:   @json(route('admin.crm.interactions.analytics.kpis')),
    charts: @json(route('admin.crm.interactions.analytics.charts')),
  };

  function filters(){
    return {
      interaction_type: $('#f_type').val() || '',
      employee_id: $('#f_employee_id').val() || '',
      date_from: $('#f_from').val() || '',
      date_to: $('#f_to').val() || '',
    };
  }

  let chartType, chartRelatedType, chartTrend;

  async function loadAll(){
    try{
      const f = filters();

      const k = await $.get(routes.kpis, f);
      $('#k_total').text(k.total ?? '—');
      $('#k_avg_day').text(k.avg_per_day ?? '—');
      $('#k_top_type').text(k.top_interactable_type ?? '—');
      $('#k_top_type_count').text(k.top_interactable_count ? (k.top_interactable_count + ' interactions') : '');
      $('#k_calls').text(k.calls ?? '—');
      $('#k_emails').text(k.emails ?? '—');

      const c = await $.get(routes.charts, f);

      // By type
      const tLabels = (c.by_type || []).map(x => (x.t || '').toString().toUpperCase());
      const tData   = (c.by_type || []).map(x => Number(x.c || 0));
      if (chartType) chartType.destroy();
      chartType = new Chart(document.getElementById('chartType'), {
        type:'bar',
        data:{ labels:tLabels, datasets:[{ label:'Count', data:tData }] }
      });

      // By related type
      const rLabels = (c.by_related_type || []).map(x => x.t);
      const rData   = (c.by_related_type || []).map(x => Number(x.c || 0));
      if (chartRelatedType) chartRelatedType.destroy();
      chartRelatedType = new Chart(document.getElementById('chartRelatedType'), {
        type:'bar',
        data:{ labels:rLabels, datasets:[{ label:'Count', data:rData }] }
      });

      // Trend
      const dLabels = (c.trend || []).map(x => x.d);
      const dData   = (c.trend || []).map(x => Number(x.c || 0));
      if (chartTrend) chartTrend.destroy();
      chartTrend = new Chart(document.getElementById('chartTrend'), {
        type:'line',
        data:{ labels:dLabels, datasets:[{ label:'Count', data:dData }] }
      });

    }catch(e){
      Swal.fire({icon:'error', title:'Error', text:'Failed to load interactions analytics.'});
    }
  }

  $('#applyBtn').on('click', loadAll);
  $('#resetBtn').on('click', function(){
    $('#f_type').val('');
    $('#f_employee_id').val('');
    $('#f_from').val('');
    $('#f_to').val('');
    loadAll();
  });

  loadAll();
})();
</script>
@endpush
