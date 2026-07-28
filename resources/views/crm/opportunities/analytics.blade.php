@extends('layouts.master')
@section('title','Opportunities Analytics')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Opportunities Analytics</h1>
      <small class="text-muted">CRM</small>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">

        <div class="col-md-3">
          <label class="form-label mb-1">Stage</label>
          <input type="text" id="f_stage" class="form-control" placeholder="e.g. Prospecting, Negotiation">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Close From</label>
          <input type="date" id="f_close_from" class="form-control">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Close To</label>
          <input type="date" id="f_close_to" class="form-control">
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
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Total Deals</div><div class="h4" id="k_total_count">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Total Value</div><div class="h4" id="k_total_value">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Forecast (Weighted)</div><div class="h4" id="k_weighted_value">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Closing in 30 days</div><div class="h4" id="k_closing_soon">—</div></div></div></div>
  </div>

  {{-- Charts --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Pipeline by Stage (Value)</strong></div>
        <div class="card-body"><canvas id="chartStage" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Probability Buckets</strong></div>
        <div class="card-body"><canvas id="chartProb" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Close Date Trend (Value)</strong></div>
        <div class="card-body"><canvas id="chartCloseTrend" height="120"></canvas></div>
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
    kpis:   @json(route('admin.crm.opportunities.analytics.kpis')),
    charts: @json(route('admin.crm.opportunities.analytics.charts')),
  };

  function filters(){
    return {
      stage: $('#f_stage').val() || '',
      close_from: $('#f_close_from').val() || '',
      close_to: $('#f_close_to').val() || '',
    };
  }

  function money(v){
    v = Number(v || 0);
    return 'NGN ' + v.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
  }

  let chartStage, chartProb, chartCloseTrend;

  async function loadAll(){
    try{
      const f = filters();

      const k = await $.get(routes.kpis, f);
      $('#k_total_count').text(k.total_count ?? '—');
      $('#k_total_value').text(money(k.total_value));
      $('#k_weighted_value').text(money(k.weighted_value));
      $('#k_closing_soon').text(k.closing_soon ?? '—');

      const c = await $.get(routes.charts, f);

      // Stage chart (value)
      const stageLabels = (c.by_stage || []).map(x => x.stage);
      const stageValues = (c.by_stage || []).map(x => Number(x.v || 0));

      if (chartStage) chartStage.destroy();
      chartStage = new Chart(document.getElementById('chartStage'), {
        type: 'bar',
        data: { labels: stageLabels, datasets: [{ label: 'Value', data: stageValues }] }
      });

      // Prob buckets chart (count)
      const pLabels = (c.prob_buckets || []).map(x => x.bucket);
      const pCounts = (c.prob_buckets || []).map(x => Number(x.c || 0));

      if (chartProb) chartProb.destroy();
      chartProb = new Chart(document.getElementById('chartProb'), {
        type: 'bar',
        data: { labels: pLabels, datasets: [{ label: 'Deals', data: pCounts }] }
      });

      // Close trend (value)
      const tLabels = (c.close_trend || []).map(x => x.d);
      const tVals   = (c.close_trend || []).map(x => Number(x.v || 0));

      if (chartCloseTrend) chartCloseTrend.destroy();
      chartCloseTrend = new Chart(document.getElementById('chartCloseTrend'), {
        type: 'line',
        data: { labels: tLabels, datasets: [{ label: 'Value', data: tVals }] }
      });

    }catch(e){
      Swal.fire({icon:'error', title:'Error', text:'Failed to load opportunities analytics.'});
    }
  }

  $('#applyBtn').on('click', loadAll);
  $('#resetBtn').on('click', function(){
    $('#f_stage').val('');
    $('#f_close_from').val('');
    $('#f_close_to').val('');
    loadAll();
  });

  loadAll();
})();
</script>
@endpush
