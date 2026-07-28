@extends('layouts.master')

@section('title', 'Stock Entry Analytics')

@push('styles')
<style>
  .page-title { font-weight: 700; }
  .subtle { color: #6c757d; font-size: .9rem; }

  .filters-card .form-control,
  .filters-card .form-select {
    border-radius: .6rem;
  }

  .kpi-card {
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,.06);
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
  }
  .kpi-card .kpi-label { font-size: .85rem; color:#6c757d; }
  .kpi-card .kpi-value { font-size: 1.75rem; font-weight: 800; margin: 0; }

  .chart-card {
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,.06);
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
  }
  .chart-card .card-header {
    background: transparent;
    border-bottom: 1px solid rgba(0,0,0,.06);
    font-weight: 700;
  }

  .btn-pill { border-radius: 999px; }

  .small-note { font-size: .85rem; color:#6c757d; }

  /* Keep filter bar tidy on smaller screens */
  @media (max-width: 992px) {
    .filters-actions { margin-top: .75rem; }
  }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid py-2">

  {{-- Header --}}
  <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h2 class="page-title mb-1 text-primary">Stock Entry Analytics</h2>
      <div class="subtle">Inventory / Stock Entries</div>
    </div>

    <div class="d-flex gap-2">
      @can('inventory.stock.entries.export')
        <a id="exportExcelBtn" class="btn btn-success btn-sm btn-pill" href="#">
          <i class="fas fa-file-excel me-1"></i> Excel
        </a>
        <a id="exportPdfBtn" class="btn btn-danger btn-sm btn-pill" href="#">
          <i class="fas fa-file-pdf me-1"></i> PDF
        </a>
      @endcan
    </div>
  </div>

  {{-- Filters --}}
  <div class="card chart-card filters-card mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-lg-2 col-md-6">
          <label class="form-label">From</label>
          <input type="date" class="form-control" id="f_from" value="{{ request('from') }}">
        </div>

        <div class="col-lg-2 col-md-6">
          <label class="form-label">To</label>
          <input type="date" class="form-control" id="f_to" value="{{ request('to') }}">
        </div>

        <div class="col-lg-3 col-md-6">
          <label class="form-label">Store</label>
          <select class="form-select" id="f_store">
            <option value="">All stores</option>
            @foreach(($stores ?? []) as $s)
              <option value="{{ $s->id }}" @selected(request('store_id') == $s->id)>{{ $s->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-lg-2 col-md-6">
          <label class="form-label">Status</label>
          <select class="form-select" id="f_status">
            <option value="">All</option>
            <option value="draft" @selected(request('status')=='draft')>Draft</option>
            <option value="approved" @selected(request('status')=='approved')>Approved</option>
            <option value="posted" @selected(request('status')=='posted')>Posted</option>
          </select>
        </div>

        <div class="col-lg-2 col-md-6">
          <label class="form-label">Entry Type</label>
          <select class="form-select" id="f_type">
            <option value="">All</option>
            <option value="normal" @selected(request('entry_type')=='normal')>Normal</option>
            <option value="cust_return" @selected(request('entry_type')=='cust_return')>Customer Return</option>
          </select>
        </div>

        <div class="col-lg-1 col-md-6 filters-actions">
          <button class="btn btn-primary w-100" id="applyFiltersBtn" title="Refresh">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>

        <div class="col-12">
          <div class="small-note">
            Tip: Use filters to narrow insights. Exports will follow the current filters.
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- KPI Cards --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6">
      <div class="card kpi-card">
        <div class="card-body">
          <div class="kpi-label">Total Entries</div>
          <p class="kpi-value" id="kpi_total">—</p>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="card kpi-card">
        <div class="card-body">
          <div class="kpi-label">Draft</div>
          <p class="kpi-value" id="kpi_draft">—</p>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="card kpi-card">
        <div class="card-body">
          <div class="kpi-label">Approved</div>
          <p class="kpi-value" id="kpi_approved">—</p>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="card kpi-card">
        <div class="card-body">
          <div class="kpi-label">Posted</div>
          <p class="kpi-value" id="kpi_posted">—</p>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card chart-card">
        <div class="card-header">
          Entries Trend <span class="small-note" id="rangeLabel"></span>
        </div>
        <div class="card-body">
          <canvas id="trendChart" height="130"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="card chart-card">
        <div class="card-header">By Status</div>
        <div class="card-body">
          <canvas id="statusChart" height="170"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="card chart-card">
        <div class="card-header">By Store</div>
        <div class="card-body">
          <canvas id="storeChart" height="170"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Top Variants --}}
  <div class="card chart-card mb-3">
    <div class="card-header">Top Variants (Qty)</div>
    <div class="card-body">
      <canvas id="topVariantsChart" height="110"></canvas>
    </div>
  </div>

</div>
@endsection

@push('scripts')
{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
(function() {
  // ====== CONFIG ======
  // IMPORTANT: point this to your analytics data endpoint (JSON)
  // Example: Route::get('/admin/inventory/stock-entries/analytics/data', ...)
  const DATA_URL = "{{ route('admin.inventory.stock_entries.analytics.data') }}";

  // IMPORTANT: your export endpoint (already exists in your system)
  // Make sure your routes match these:
  // route('admin.inventory.stock_entries.export') should accept ?type=excel|pdf + filters
  const EXPORT_URL = "{{ route('admin.inventory.stock_entries.export') }}";

  // ====== HELPERS ======
  function qs(params) {
    const u = new URLSearchParams();
    Object.keys(params).forEach(k => {
      if (params[k] !== null && params[k] !== undefined && params[k] !== '') u.append(k, params[k]);
    });
    return u.toString();
  }

  function getFilters() {
    return {
      from: document.getElementById('f_from').value,
      to: document.getElementById('f_to').value,
      store_id: document.getElementById('f_store').value,
      status: document.getElementById('f_status').value,
      entry_type: document.getElementById('f_type').value
    };
  }

  function setExportLinks(filters) {
    const excelHref = EXPORT_URL + "?" + qs({ ...filters, type: 'excel' });
    const pdfHref   = EXPORT_URL + "?" + qs({ ...filters, type: 'pdf' });

    const excelBtn = document.getElementById('exportExcelBtn');
    const pdfBtn   = document.getElementById('exportPdfBtn');

    if (excelBtn) excelBtn.href = excelHref;
    if (pdfBtn) pdfBtn.href = pdfHref;
  }

  function setKpis(kpis) {
    document.getElementById('kpi_total').textContent = kpis.total ?? 0;
    document.getElementById('kpi_draft').textContent = kpis.draft ?? 0;
    document.getElementById('kpi_approved').textContent = kpis.approved ?? 0;
    document.getElementById('kpi_posted').textContent = kpis.posted ?? 0;
  }

  function setRangeLabel(meta) {
    const el = document.getElementById('rangeLabel');
    if (!el) return;
    if (meta?.range_label) el.textContent = `(${meta.range_label})`;
    else el.textContent = '';
  }

  // ====== CHART INSTANCES ======
  let trendChart, statusChart, storeChart, topVariantsChart;

  function destroyIf(chart) {
    if (chart) chart.destroy();
    return null;
  }

  function buildCharts(payload) {
    // payload shape expected from backend:
    // {
    //   meta: { range_label: "YYYY-MM-DD → YYYY-MM-DD" },
    //   kpis: { total, draft, approved, posted },
    //   trend: { labels: [...], values: [...] },
    //   by_status: { labels: [...], values: [...] },
    //   by_store: { labels: [...], values: [...] },
    //   top_variants: { labels: [...], values: [...] }
    // }

    setRangeLabel(payload.meta || {});
    setKpis(payload.kpis || {});

    // Trend (line)
    trendChart = destroyIf(trendChart);
    trendChart = new Chart(document.getElementById('trendChart'), {
      type: 'line',
      data: {
        labels: payload.trend?.labels || [],
        datasets: [{
          label: 'Entries',
          data: payload.trend?.values || [],
          tension: 0.25,
          fill: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true }
        }
      }
    });

    // Status (doughnut)
    statusChart = destroyIf(statusChart);
    statusChart = new Chart(document.getElementById('statusChart'), {
      type: 'doughnut',
      data: {
        labels: payload.by_status?.labels || [],
        datasets: [{ data: payload.by_status?.values || [] }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
      }
    });

    // Store (bar)
    storeChart = destroyIf(storeChart);
    storeChart = new Chart(document.getElementById('storeChart'), {
      type: 'bar',
      data: {
        labels: payload.by_store?.labels || [],
        datasets: [{
          label: 'Entries',
          data: payload.by_store?.values || []
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true }
        }
      }
    });

    // Top variants (horizontal bar)
    topVariantsChart = destroyIf(topVariantsChart);
    topVariantsChart = new Chart(document.getElementById('topVariantsChart'), {
      type: 'bar',
      data: {
        labels: payload.top_variants?.labels || [],
        datasets: [{
          label: 'Qty',
          data: payload.top_variants?.values || []
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { beginAtZero: true },
          y: { grid: { display: false } }
        }
      }
    });
  }

  async function loadAnalytics() {
    const filters = getFilters();
    setExportLinks(filters);

    // simple loading placeholders
    document.getElementById('kpi_total').textContent = '…';
    document.getElementById('kpi_draft').textContent = '…';
    document.getElementById('kpi_approved').textContent = '…';
    document.getElementById('kpi_posted').textContent = '…';

    const url = DATA_URL + "?" + qs(filters);
    const res = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    if (!res.ok) {
      // fail gracefully
      document.getElementById('kpi_total').textContent = '0';
      document.getElementById('kpi_draft').textContent = '0';
      document.getElementById('kpi_approved').textContent = '0';
      document.getElementById('kpi_posted').textContent = '0';
      console.error('Analytics load failed:', await res.text());
      return;
    }

    const payload = await res.json();
    buildCharts(payload);
  }

  document.getElementById('applyFiltersBtn').addEventListener('click', function() {
    loadAnalytics();
  });

  // Initial load
  setExportLinks(getFilters());
  loadAnalytics();

})();
</script>
@endpush
