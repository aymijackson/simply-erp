@extends('layouts.master')

@section('title', 'Audit Analytics')

@push('styles')
<style>
  .metric{
    border:1px solid #e9ecef;
    border-radius:12px;
    padding:14px;
    background:#fff;
  }
  .metric .v{ font-size:22px; font-weight:700; }
  .metric .k{ color:#6c757d; font-size:12px; }

  /* ✅ Prevent infinite resize/scroll loop (Chart.js responsive container feedback) */
  .chart-box{
    position: relative;
    width: 100%;
    height: 320px;       /* fixed height prevents resize recursion */
  }
  .chart-box.h-260{ height: 260px; }
  .chart-box.h-220{ height: 220px; }
  .chart-box.h-180{ height: 180px; }

  .chart-box canvas{
    width: 100% !important;
    height: 100% !important;
    display: block;
  }
</style>
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="mb-0">Audit Analytics</h5>
      <small class="text-muted">Trend and top activity within selected date range.</small>
    </div>

    <div class="d-flex gap-2 align-items-end">
      <div>
        <label class="form-label small mb-1">From</label>
        <input type="date" id="a_from" class="form-control form-control-sm">
      </div>
      <div>
        <label class="form-label small mb-1">To</label>
        <input type="date" id="a_to" class="form-control form-control-sm">
      </div>
      <button class="btn btn-sm btn-primary" id="a_apply" type="button">
        <i class="fas fa-sync me-1"></i> Refresh
      </button>

      <a href="{{ route('admin.audit.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-list me-1"></i> Logs
      </a>
    </div>
  </div>

  <div class="card-body">
    <div class="row g-2 mb-3">
      <div class="col-md-3"><div class="metric"><div class="k">Total (range)</div><div class="v" id="m_total">—</div></div></div>
      <div class="col-md-3"><div class="metric"><div class="k">Today</div><div class="v" id="m_today">—</div></div></div>
      <div class="col-md-3"><div class="metric"><div class="k">Last 24h</div><div class="v" id="m_24h">—</div></div></div>
      <div class="col-md-3"><div class="metric"><div class="k">Last 7 days</div><div class="v" id="m_7d">—</div></div></div>
    </div>

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="border rounded p-3">
          <div class="fw-semibold mb-2">Activity Trend</div>
          <div class="chart-box">
            <canvas id="c_trend"></canvas>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="border rounded p-3">
          <div class="fw-semibold mb-2">Top Modules</div>
          <div class="chart-box h-260">
            <canvas id="c_modules"></canvas>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="border rounded p-3">
          <div class="fw-semibold mb-2">Top Actions</div>
          <div class="chart-box h-220">
            <canvas id="c_actions"></canvas>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="border rounded p-3">
          <div class="fw-semibold mb-2">Most Active Users</div>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Email</th>
                  <th class="text-end">Count</th>
                </tr>
              </thead>
              <tbody id="topUsersBody">
                <tr><td colspan="3" class="text-muted">—</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(function(){

  function getDefaultRange(){
    const today = new Date();
    const to = today.toISOString().slice(0,10);
    const fromDate = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    const from = fromDate.toISOString().slice(0,10);
    return {from, to};
  }

  // init dates
  const r = getDefaultRange();
  $('#a_from').val(r.from);
  $('#a_to').val(r.to);

  // chart refs
  let trendChart = null, modulesChart = null, actionsChart = null;

  // avoid multiple concurrent loads (can cause flicker)
  let loading = false;

  function makeOrUpdate(chartRef, canvasId, type, labels, data){
    const ctx = document.getElementById(canvasId);

    const dataset = {
      label: 'Count',
      data: data
    };

    // ✅ chart options to reduce “blink” + prevent resize loops
    const options = {
      responsive: true,
      maintainAspectRatio: false,
      animation: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    };

    if (!chartRef) {
      return new Chart(ctx, {
        type: type,
        data: { labels: labels, datasets: [dataset] },
        options: options
      });
    }

    chartRef.data.labels = labels;
    chartRef.data.datasets[0].data = data;
    chartRef.update('none'); // no animation
    return chartRef;
  }

  function load(){
    if (loading) return;
    loading = true;

    const from = $('#a_from').val();
    const to   = $('#a_to').val();

    $('#a_apply').prop('disabled', true);

    $.get('{{ route("admin.audit.analytics.data") }}', {from, to})
      .done(function(res){

        $('#m_total').text(res.totals?.total ?? 0);
        $('#m_today').text(res.totals?.today ?? 0);
        $('#m_24h').text(res.totals?.last24h ?? 0);
        $('#m_7d').text(res.totals?.last7 ?? 0);

        // trend
        const tLabels = (res.trend || []).map(x => x.d);
        const tData   = (res.trend || []).map(x => x.c);
        trendChart = makeOrUpdate(trendChart, 'c_trend', 'line', tLabels, tData);

        // modules
        const mLabels = (res.topModules || []).map(x => x.k);
        const mData   = (res.topModules || []).map(x => x.c);
        modulesChart = makeOrUpdate(modulesChart, 'c_modules', 'bar', mLabels, mData);

        // actions
        const aLabels = (res.topActions || []).map(x => x.k);
        const aData   = (res.topActions || []).map(x => x.c);
        actionsChart = makeOrUpdate(actionsChart, 'c_actions', 'bar', aLabels, aData);

        // users table
        const users = res.topUsers || [];
        if (!users.length) {
          $('#topUsersBody').html(`<tr><td colspan="3" class="text-muted">No user activity in range.</td></tr>`);
        } else {
          $('#topUsersBody').html(users.map(u => `
            <tr>
              <td>${u.name || '—'}</td>
              <td class="text-muted small">${u.email || '—'}</td>
              <td class="text-end fw-semibold">${u.count ?? 0}</td>
            </tr>
          `).join(''));
        }

      })
      .fail(function(){
        Swal.fire('Error','Failed to load analytics.','error');
      })
      .always(function(){
        loading = false;
        $('#a_apply').prop('disabled', false);
      });
  }

  $('#a_apply').on('click', load);

  // initial load
  load();
});
</script>
@endpush
