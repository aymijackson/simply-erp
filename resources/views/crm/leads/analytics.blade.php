@extends('layouts.master')
@section('title','Leads Analytics')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Leads Analytics</h1>
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
            <option value="new">New</option>
            <option value="contacted">Contacted</option>
            <option value="qualified">Qualified</option>
            <option value="converted">Converted</option>
            <option value="closed">Closed</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Date From</label>
          <input type="date" id="f_date_from" class="form-control">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Date To</label>
          <input type="date" id="f_date_to" class="form-control">
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyBtn">
            <i class="fas fa-filter me-1"></i> Apply
          </button>
          <button class="btn btn-outline-secondary w-100" id="resetBtn">
            <i class="fas fa-undo me-1"></i> Reset
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- KPI Cards --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Total Leads</div><div class="h4" id="k_total">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Converted</div><div class="h4" id="k_converted">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Conversion Rate</div><div class="h4" id="k_conv_rate">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Overdue Follow-ups</div><div class="h4" id="k_overdue">—</div></div></div></div>
  </div>

  {{-- Charts --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Status Breakdown</strong></div>
        <div class="card-body"><canvas id="chartStatus" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Top Sources</strong></div>
        <div class="card-body"><canvas id="chartSource" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Lead Creation Trend</strong></div>
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
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const routes = {
    kpis: @json(route('admin.crm.leads.analytics.kpis')),
    charts: @json(route('admin.crm.leads.analytics.charts')),
  };

  function filters(){
    return {
      status: $('#f_status').val() || '',
      date_from: $('#f_date_from').val() || '',
      date_to: $('#f_date_to').val() || '',
    };
  }

  let chartStatus, chartSource, chartTrend;

  async function loadAll(){
    try{
      const f = filters();

      const k = await $.get(routes.kpis, f);
      $('#k_total').text(k.total ?? '—');
      $('#k_converted').text(k.converted ?? '—');
      $('#k_conv_rate').text((k.conversion_rate ?? 0) + '%');
      $('#k_overdue').text(k.overdue_followups ?? '—');

      const c = await $.get(routes.charts, f);

      // Status
      const sLabels = (c.by_status || []).map(x => x.status || 'Unknown');
      const sData   = (c.by_status || []).map(x => x.c);

      if (chartStatus) chartStatus.destroy();
      chartStatus = new Chart(document.getElementById('chartStatus'), {
        type: 'bar',
        data: { labels: sLabels, datasets: [{ label: 'Leads', data: sData }] }
      });

      // Source
      const srcLabels = (c.by_source || []).map(x => x.source || 'Unknown');
      const srcData   = (c.by_source || []).map(x => x.c);

      if (chartSource) chartSource.destroy();
      chartSource = new Chart(document.getElementById('chartSource'), {
        type: 'bar',
        data: { labels: srcLabels, datasets: [{ label: 'Leads', data: srcData }] }
      });

      // Trend
      const tLabels = (c.trend || []).map(x => x.d);
      const tData   = (c.trend || []).map(x => x.c);

      if (chartTrend) chartTrend.destroy();
      chartTrend = new Chart(document.getElementById('chartTrend'), {
        type: 'line',
        data: { labels: tLabels, datasets: [{ label: 'Leads per day', data: tData }] }
      });

    }catch(e){
      Swal.fire({icon:'error', title:'Error', text:'Failed to load analytics.'});
    }
  }

  $('#applyBtn').on('click', loadAll);
  $('#resetBtn').on('click', function(){
    $('#f_status').val('');
    $('#f_date_from').val('');
    $('#f_date_to').val('');
    loadAll();
  });

  loadAll();
})();
</script>
@endpush
