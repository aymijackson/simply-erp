@extends('layouts.master')

@section('title', 'Stock Dashboard')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h1 class="h3 text-primary mb-1">Stock Inventory Dashboard</h1>
      <div class="text-muted">Inventory / Stock</div>
    </div>

    <div class="d-flex gap-2">
      @can('inventory.stock.dashboard.export')
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
        <div class="col-md-4">
          <label class="form-label">Store</label>
          <select id="store_id" class="form-select">
            <option value="">All stores</option>
            @foreach($stores as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <button id="applyFilters" class="btn btn-primary w-100">
            <i class="fas fa-sync-alt me-1"></i> Refresh
          </button>
        </div>
        <div class="col-md-2 text-muted small">
          Tip: exports follow the filters.
        </div>
      </div>
    </div>
  </div>

  {{-- KPI cards --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-muted">Total Variants</div>
          <div class="h2 mb-0" id="kpiVariants">—</div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-muted">Total Qty on Hand</div>
          <div class="h2 mb-0" id="kpiQty">—</div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-muted">Total Stock Value</div>
          @can('inventory.stock.dashboard.view_cost')
            <div class="h2 mb-0" id="kpiValue">—</div>
          @else
            <div class="h5 mb-0 text-muted">Hidden (no permission)</div>
          @endcan
        </div>
      </div>
    </div>
  </div>

  {{-- Charts --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-bold">
          Net Movement Trend <span class="text-muted fw-normal" id="trendRange"></span>
        </div>
        <div class="card-body">
          <canvas id="trendChart" height="120"></canvas>
        </div>
      </div>
    </div>

    {{-- ===== TOGGLED BY STORE CHART ===== --}}
    <div class="col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <strong>On Hand by Store</strong>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary active" id="btnQty">Qty</button>
            <button class="btn btn-outline-primary" id="btnValue">Value</button>
          </div>
        </div>
        <div class="card-body">
          <canvas id="storeChart" height="160"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-bold">
          Stock Aging (Variants)
          @can('inventory.stock.dashboard.view_aging')
            <span class="text-muted fw-normal">by last movement</span>
          @endcan
        </div>
        <div class="card-body">
          @can('inventory.stock.dashboard.view_aging')
            <canvas id="agingChart" height="160"></canvas>
          @else
            <div class="text-muted">Hidden (no permission)</div>
          @endcan
        </div>
      </div>
    </div>
  </div>

  {{-- Tables --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">Top Movers (7 days)</div>
        <div class="card-body table-responsive">
          @can('inventory.stock.dashboard.view_movers')
          <table class="table table-sm mb-0" id="tblMovers">
            <thead class="table-light">
              <tr>
                <th>SKU</th>
                <th>Product</th>
                <th class="text-end">Total Qty movements</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          @else
            <div class="text-muted">Hidden (no permission)</div>
          @endcan
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">Low Stock Alerts</div>
        <div class="card-body table-responsive">
          @can('inventory.stock.dashboard.view_low_stock')
          <table class="table table-sm mb-0" id="tblLowStock">
            <thead class="table-light">
              <tr>
                <th>SKU</th>
                <th class="text-end">ROP</th>
                <th class="text-end">On hand</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          @else
            <div class="text-muted">Hidden (no permission)</div>
          @endcan
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
  const dataUrl = @json(route('admin.inventory.stock.dashboard.data'));
  const exportUrl = @json(route('admin.inventory.stock.dashboard.export'));

  let trendChart, storeChart, agingChart;
  let storeMetric = 'qty'; // qty | value
  let storeData = { qty: [], value: [] };

  function qs() {
    const p = new URLSearchParams();
    ['from','to','store_id'].forEach(id => {
      const v = document.getElementById(id).value;
      if (v) p.append(id, v);
    });
    return p;
  }

  function money(n){
    return '₦' + Number(n || 0).toLocaleString();
  }

  async function load(){
    const res = await fetch(dataUrl + '?' + qs(), { headers:{'X-Requested-With':'XMLHttpRequest'}});
    const json = await res.json();

    document.getElementById('kpiVariants').textContent = json.kpis.total_variants ?? 0;
    document.getElementById('kpiQty').textContent = json.kpis.total_qty_on_hand ?? 0;
    const kpiValue = document.getElementById('kpiValue');
    if (kpiValue) kpiValue.textContent = money(json.kpis.total_stock_value ?? 0);

    document.getElementById('trendRange').textContent = `(${json.meta.from} → ${json.meta.to})`;

    renderTrend(json.charts.trend || []);
    storeData.qty   = json.charts.by_store_qty   || [];
    storeData.value = json.charts.by_store_value || [];
    renderByStore();

    if (document.getElementById('agingChart'))
      renderAging(json.charts.aging_buckets || []);

    renderMovers(json.tables.top_movers || []);
    renderLowStock(json.tables.low_stock || []);

    document.getElementById('btnExcel').href = exportUrl + '?' + qs() + '&type=excel';
    document.getElementById('btnPdf').href   = exportUrl + '?' + qs() + '&type=pdf';
  }

  function renderTrend(rows){
    if (trendChart) trendChart.destroy();
    trendChart = new Chart(document.getElementById('trendChart'), {
      type: 'line',
      data: {
        labels: rows.map(r => r.d),
        datasets: [{ data: rows.map(r => r.net), label:'Net', tension:.3 }]
      },
      options:{ plugins:{legend:{display:false}} }
    });
  }

  function renderByStore(){
    const rows = storeMetric === 'qty' ? storeData.qty : storeData.value;
    if (storeChart) storeChart.destroy();

    storeChart = new Chart(document.getElementById('storeChart'), {
      type:'bar',
      data:{
        labels: rows.map(r => r.label),
        datasets:[{
          label: storeMetric === 'qty' ? 'Qty on Hand' : 'Stock Value',
          data: rows.map(r => r.value)
        }]
      },
      options:{
        plugins:{legend:{display:false}},
        scales:{
          y:{
            ticks:{
              callback:v => storeMetric === 'qty' ? v : money(v)
            }
          }
        }
      }
    });
  }

  function renderAging(items){
    if (agingChart) agingChart.destroy();
    agingChart = new Chart(document.getElementById('agingChart'), {
      type:'doughnut',
      data:{ labels: items.map(i=>i.label), datasets:[{ data: items.map(i=>i.value) }] },
      options:{ plugins:{legend:{position:'bottom'}} }
    });
  }

  function renderMovers(rows){
    document.querySelector('#tblMovers tbody').innerHTML =
      rows.map(r=>`<tr><td>${r.sku}</td><td>${r.name}</td><td class="text-end">${r.moved}</td></tr>`).join('')
      || '<tr><td colspan="3" class="text-muted text-center">No data</td></tr>';
  }

  function renderLowStock(rows){
    document.querySelector('#tblLowStock tbody').innerHTML =
      rows.map(r=>`<tr><td>${r.sku}</td><td class="text-end">${r.reorder_point}</td><td class="text-end">${r.on_hand}</td></tr>`).join('')
      || '<tr><td colspan="3" class="text-muted text-center">No alerts</td></tr>';
  }

  document.getElementById('btnQty').onclick = () => {
    storeMetric = 'qty';
    document.getElementById('btnQty').classList.add('active');
    document.getElementById('btnValue').classList.remove('active');
    renderByStore();
  };

  document.getElementById('btnValue').onclick = () => {
    storeMetric = 'value';
    document.getElementById('btnValue').classList.add('active');
    document.getElementById('btnQty').classList.remove('active');
    renderByStore();
  };

  document.getElementById('applyFilters').onclick = load;

  load();
})();
</script>
@endpush
