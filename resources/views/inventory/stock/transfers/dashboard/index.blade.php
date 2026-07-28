@extends('layouts.master')

@section('title', 'Stock Transfer Dashboard')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h1 class="h3 text-primary mb-1">Stock Transfer Dashboard</h1>
      <div class="text-muted">Inventory / Stock Transfers</div>
    </div>

    <div class="d-flex gap-2">
      @can('admin.inventory.stock.transfers.dashboard.export')
        <a id="btnExcel" class="btn btn-success btn-sm">
          <i class="fas fa-file-excel me-1"></i> Excel
        </a>
        <a id="btnPdf" class="btn btn-danger btn-sm">
          <i class="fas fa-file-pdf me-1"></i> PDF
        </a>
      @endcan
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label">From</label>
          <input type="date" id="from" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">To</label>
          <input type="date" id="to" class="form-control">
        </div>

        <div class="col-md-3">
          <label class="form-label">From Store</label>
          <select id="from_store_id" class="form-select">
            <option value="">All</option>
            @foreach($stores as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">To Store</label>
          <select id="to_store_id" class="form-select">
            <option value="">All</option>
            @foreach($stores as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-1">
          <label class="form-label">Status</label>
          <select id="status" class="form-select">
            <option value="">All</option>
            <option value="draft">Draft</option>
            <option value="posted">Posted</option>
          </select>
        </div>

        <div class="col-md-1">
          <button id="applyFilters" class="btn btn-primary w-100">
            <i class="fas fa-sync-alt me-1"></i> Go
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- KPIs --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-2">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">Transfers</div>
        <div class="h3 mb-0" id="kpiTransfers">—</div>
      </div></div>
    </div>
    <div class="col-lg-2">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">Posted</div>
        <div class="h3 mb-0" id="kpiPosted">—</div>
      </div></div>
    </div>
    <div class="col-lg-2">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">Draft</div>
        <div class="h3 mb-0" id="kpiDraft">—</div>
      </div></div>
    </div>
    <div class="col-lg-2">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">Lines</div>
        <div class="h3 mb-0" id="kpiLines">—</div>
      </div></div>
    </div>
    <div class="col-lg-2">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">Qty moved</div>
        <div class="h3 mb-0" id="kpiQtyMoved">—</div>
      </div></div>
    </div>
    <div class="col-lg-2">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">Value moved</div>
        @can('admin.inventory.stock.transfers.dashboard.view_cost')
          <div class="h3 mb-0" id="kpiValueMoved">—</div>
        @else
          <div class="text-muted">Hidden</div>
        @endcan
      </div></div>
    </div>
  </div>

  {{-- Charts --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-bold">
          Transfers Trend <span class="text-muted fw-normal" id="trendRange"></span>
        </div>
        <div class="card-body">
          <canvas id="trendChart" height="120"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-bold">Top Routes (Qty)</div>
        <div class="card-body">
          <canvas id="routesChart" height="160"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-bold">Draft Aging</div>
        <div class="card-body">
          <canvas id="draftChart" height="160"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-bold">Outbound by From Store (Qty)</div>
        <div class="card-body">
          <canvas id="fromStoreChart" height="120"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-bold">Inbound by To Store (Qty)</div>
        <div class="card-body">
          <canvas id="toStoreChart" height="120"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Tables --}}
  <div class="row g-3">
    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">Top Moved SKUs</div>
        <div class="card-body table-responsive">
          <table class="table table-sm mb-0" id="tblSkus">
            <thead class="table-light">
              <tr>
                <th>SKU</th>
                <th>Product</th>
                <th class="text-end">Qty moved</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
(function(){
  const dataUrl   = @json(route('admin.inventory.stock.transfers.dashboard.data'));
  const exportUrl = @json(route('admin.inventory.stock.transfers.dashboard.export'));

  let trendChart, routesChart, draftChart, fromStoreChart, toStoreChart;

  function qs() {
    const p = new URLSearchParams();
    ['from','to','from_store_id','to_store_id','status'].forEach(id => {
      const v = document.getElementById(id).value;
      if (v) p.append(id, v);
    });
    return p;
  }

  function money(n){
    try { return new Intl.NumberFormat(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}).format(n); }
    catch(e){ return (Number(n||0)).toFixed(2); }
  }

  async function load(){
    const url = dataUrl + '?' + qs().toString();
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    const json = await res.json();

    // KPIs
    document.getElementById('kpiTransfers').textContent = json.kpis.total_transfers ?? 0;
    document.getElementById('kpiPosted').textContent    = json.kpis.posted_transfers ?? 0;
    document.getElementById('kpiDraft').textContent     = json.kpis.draft_transfers ?? 0;
    document.getElementById('kpiLines').textContent     = json.kpis.total_lines ?? 0;
    document.getElementById('kpiQtyMoved').textContent  = json.kpis.total_qty_moved ?? 0;

    const kpiVal = document.getElementById('kpiValueMoved');
    if (kpiVal) kpiVal.textContent = money(json.kpis.total_value_moved ?? 0);

    document.getElementById('trendRange').textContent = `(${json.meta.from} → ${json.meta.to})`;

    renderTrend(json.charts.trend || []);
    renderBar('routesChart', json.charts.top_routes || [], v => routesChart = v, () => routesChart);
    renderDoughnut('draftChart', json.charts.draft_buckets || [], v => draftChart = v, () => draftChart);
    renderBar('fromStoreChart', json.charts.by_from_store || [], v => fromStoreChart = v, () => fromStoreChart);
    renderBar('toStoreChart', json.charts.by_to_store || [], v => toStoreChart = v, () => toStoreChart);

    renderTopSkus(json.tables.top_skus || []);

    // Export buttons follow filters
    const btnExcel = document.getElementById('btnExcel');
    const btnPdf   = document.getElementById('btnPdf');
    if (btnExcel) btnExcel.href = exportUrl + '?' + qs().toString() + '&type=excel';
    if (btnPdf)   btnPdf.href   = exportUrl + '?' + qs().toString() + '&type=pdf';
  }

  function renderTrend(rows){
    const labels = rows.map(r => r.d);
    const values = rows.map(r => Number(r.transfers || 0));

    const ctx = document.getElementById('trendChart');
    if (trendChart) trendChart.destroy();
    trendChart = new Chart(ctx, {
      type: 'line',
      data: { labels, datasets: [{ label: 'Transfers', data: values, tension: 0.25 }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
  }

  function renderBar(canvasId, items, setRef, getRef){
    const labels = items.map(i => i.label);
    const values = items.map(i => Number(i.value || 0));

    const ctx = document.getElementById(canvasId);
    const existing = getRef();
    if (existing) existing.destroy();

    const ch = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: [{ label: 'Qty', data: values }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
    setRef(ch);
  }

  function renderDoughnut(canvasId, items, setRef, getRef){
    const labels = items.map(i => i.label);
    const values = items.map(i => Number(i.value || 0));

    const ctx = document.getElementById(canvasId);
    const existing = getRef();
    if (existing) existing.destroy();

    const ch = new Chart(ctx, {
      type: 'doughnut',
      data: { labels, datasets: [{ data: values }] },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    setRef(ch);
  }

  function renderTopSkus(rows){
    const tbody = document.querySelector('#tblSkus tbody');
    tbody.innerHTML = (rows.length ? rows.map(r => `
      <tr>
        <td>${r.sku ?? ''}</td>
        <td class="text-muted">${r.name ?? ''}</td>
        <td class="text-end">${r.moved ?? 0}</td>
      </tr>
    `).join('') : `<tr><td colspan="3" class="text-center text-muted">No data</td></tr>`);
  }

  document.getElementById('applyFilters').addEventListener('click', load);
  load();
})();
</script>
@endpush
