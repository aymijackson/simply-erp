@extends('layouts.master')
@section('title','Stock Levels Dashboard')

@section('content')
<div class="container-fluid">

  <div class="d-sm-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="h3 mb-1 text-gray-800">Stock Levels Dashboard</h1>
      <p class="mb-0 text-muted">Cumulative inventory (from v_stock_levels) with filters and quick insights.</p>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label mb-1">Store</label>
          <select id="f_store" class="form-select">
            <option value="">All stores</option>
            @foreach($stores as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-5">
          <label class="form-label mb-1">Product Variant</label>
          <select id="f_variant" class="form-select" style="width:100%"></select>
          <small class="text-muted">Search SKU or product name</small>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">View Mode</label>
          <select id="f_mode" class="form-select">
            <option value="per_store">Per Store</option>
            <option value="global">Global (All Stores)</option>
          </select>
        </div>

        <div class="col-md-3 mt-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="f_in_stock" checked>
            <label class="form-check-label" for="f_in_stock">Only In-Stock</label>
          </div>
        </div>

        <div class="col-md-9 mt-2 text-end">
          <button class="btn btn-outline-secondary" id="btnReset">
            <i class="fas fa-undo me-1"></i> Reset
          </button>
          <button class="btn btn-primary" id="btnApply">
            <i class="fas fa-filter me-1"></i> Apply
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- KPIs --}}
  <div class="row">
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Total Variants</div>
          <div class="h4 mb-0" id="kpi_variants">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Total Qty On Hand</div>
          <div class="h4 mb-0" id="kpi_qty">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Total Stock Value</div>
          <div class="h4 mb-0" id="kpi_value">0</div>
          <small class="text-muted">(Shows 0 if value_on_hand not available)</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Chart + Table --}}
  <div class="row">
    <div class="col-lg-4 mb-3">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white">
          <h6 class="m-0 font-weight-bold text-primary">Top Stores by Qty</h6>
        </div>
        <div class="card-body">
          <canvas id="chartByStore" height="240"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-8 mb-3">
      <div class="card shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
          <h6 class="m-0 font-weight-bold text-primary">Cumulative Stock Levels</h6>
          <span class="badge bg-light text-dark" id="tableModeBadge">Per Store</span>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle" id="tblLevels">
              <thead class="table-light">
              <tr id="tblHeadRow">
                <th>SKU</th>
                <th>Product</th>
                <th>Store</th>
                <th class="text-end">Qty On Hand</th>
              </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <small class="text-muted">Showing up to 500 rows (filters reduce results).</small>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {

  const $store   = $('#f_store');
  const $variant = $('#f_variant');
  const $mode    = $('#f_mode');
  const $inStock = $('#f_in_stock');

  // Variant Select2 (reuse your existing endpoint)
  $variant.select2({
    width: '100%',
    placeholder: 'Search SKU or product…',
    allowClear: true,
    ajax: {
      url: "{{ route('admin.inventory.stock.transfers.fetch-variants') }}", // <- ensure this route exists
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term || '' }),
      processResults: data => ({ results: data.map(x => ({ id: x.id, text: `${x.sku} — ${x.product_name}` })) }),
      cache: true
    }
  });

  let chart;

  function fmt(n) {
    const x = parseFloat(n || 0);
    return x.toLocaleString(undefined, { maximumFractionDigits: 2 });
  }

  function buildTable(mode, rows) {
    const $thead = $('#tblHeadRow');
    const $tbody = $('#tblLevels tbody').empty();

    if (mode === 'global') {
      $('#tableModeBadge').text('Global');
      $thead.html(`
        <th>SKU</th>
        <th>Product</th>
        <th class="text-end">Qty On Hand</th>
      `);

      rows.forEach(r => {
        $tbody.append(`
          <tr>
            <td>${r.sku ?? ''}</td>
            <td>${r.product_name ?? ''}</td>
            <td class="text-end">${fmt(r.qty_on_hand)}</td>
          </tr>
        `);
      });

      return;
    }

    $('#tableModeBadge').text('Per Store');
    $thead.html(`
      <th>SKU</th>
      <th>Product</th>
      <th>Store</th>
      <th class="text-end">Qty On Hand</th>
    `);

    rows.forEach(r => {
      $tbody.append(`
        <tr>
          <td>${r.sku ?? ''}</td>
          <td>${r.product_name ?? ''}</td>
          <td>${r.store_name ?? ''}</td>
          <td class="text-end">${fmt(r.qty_on_hand)}</td>
        </tr>
      `);
    });
  }

  function drawByStoreChart(items) {
    const labels = items.map(x => x.label);
    const values = items.map(x => parseFloat(x.value || 0));

    const ctx = document.getElementById('chartByStore');
    if (chart) chart.destroy();

    chart = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: [{ label: 'Qty', data: values }] },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  function loadDashboard() {
    const params = {
      store_id: $store.val() || '',
      product_variant_id: $variant.val() || '',
      mode: $mode.val(),
      in_stock: $inStock.is(':checked') ? 1 : 0
    };

    $.get("{{ route('admin.inventory.stock.levels.dashboard.data') }}", params)
      .done(res => {
        $('#kpi_variants').text(fmt(res.kpis.total_variants));
        $('#kpi_qty').text(fmt(res.kpis.total_qty_on_hand));
        $('#kpi_value').text(fmt(res.kpis.total_stock_value));

        drawByStoreChart(res.charts.by_store || []);
        buildTable(res.table.mode, res.table.rows || []);
      })
      .fail(() => {
        Swal.fire('Error', 'Failed to load dashboard data.', 'error');
      });
  }

  $('#btnApply').on('click', loadDashboard);

  $('#btnReset').on('click', function () {
    $store.val('');
    $variant.val(null).trigger('change');
    $mode.val('per_store');
    $inStock.prop('checked', true);
    loadDashboard();
  });

  // initial
  loadDashboard();
});
</script>
@endpush
