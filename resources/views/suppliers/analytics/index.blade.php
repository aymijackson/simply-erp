@extends('layouts.master')

@section('title', 'Supplier Analytics')

@push('styles')
<style>
    .kpi-card{ border-radius:12px; }
    .dt-toolbar{ display:flex; gap:.5rem; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .dt-toolbar .left{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .dt-toolbar .right{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Supplier Analytics</h1>
            <p class="text-muted mb-0">Supplies vs Supplier Returns • Quality Score • Ranking</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <select id="filter_supplier" class="form-control">
                        <option value="">All suppliers</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Date from</label>
                    <input type="date" id="filter_from" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Date to</label>
                    <input type="date" id="filter_to" class="form-control">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="applyFilters">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm kpi-card">
                <div class="card-body">
                    <div class="text-muted small">Supply Value</div>
                    <div class="h4 mb-0" id="kpi_supply_value">—</div>
                    <div class="small text-muted">Docs: <span id="kpi_supply_docs">—</span></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm kpi-card">
                <div class="card-body">
                    <div class="text-muted small">Return Value</div>
                    <div class="h4 mb-0" id="kpi_return_value">—</div>
                    <div class="small text-muted">Docs: <span id="kpi_return_docs">—</span></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm kpi-card">
                <div class="card-body">
                    <div class="text-muted small">Net Value</div>
                    <div class="h4 mb-0" id="kpi_net_value">—</div>
                    <div class="small text-muted">Return rate: <span id="kpi_return_rate">—</span>%</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm kpi-card">
                <div class="card-body">
                    <div class="text-muted small">Quality Score</div>
                    <div class="h4 mb-0" id="kpi_quality">—</div>
                    <div class="small text-muted">Higher is better</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="analyticsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabOverview" type="button" role="tab">
                        Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabProducts" type="button" role="tab">
                        Product-level
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabReasons" type="button" role="tab">
                        Return Reasons
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">

                {{-- OVERVIEW --}}
                <div class="tab-pane fade show active" id="tabOverview" role="tabpanel">

                    {{-- Trend --}}
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white">
                            <strong>Monthly Trend</strong>
                            <span class="text-muted">Supplies vs Supplier Returns</span>
                        </div>
                        <div class="card-body">
                            <canvas id="trendChart" height="110"></canvas>
                        </div>
                    </div>

                    {{-- Ranking --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <strong>Supplier Ranking</strong>
                            <span class="text-muted">(net value, return rate, score)</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="rankTable" class="table table-bordered w-100">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Supplier</th>
                                            <th>Supply Qty</th>
                                            <th>Supply Value</th>
                                            <th>Return Qty</th>
                                            <th>Return Value</th>
                                            <th>Return Rate %</th>
                                            <th>Quality Score</th>
                                            <th>Net Value</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- PRODUCT LEVEL --}}
                <div class="tab-pane fade" id="tabProducts" role="tabpanel">
                    <div class="dt-toolbar mb-2">
                        <div class="left">
                            <input type="text" class="form-control" id="productSearch" style="min-width:260px" placeholder="Search product name/code...">
                            <input type="number" step="0.01" class="form-control" id="minReturnRate" style="max-width:220px" placeholder="Min return rate %">
                        </div>
                        <div class="right">
                            <button class="btn btn-outline-primary" id="applyProductFilters">Apply</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="productTable" class="table table-bordered w-100">
                            <thead class="bg-light">
                                <tr>
                                    <th>Supplier</th>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Supply Qty</th>
                                    <th>Supply Value</th>
                                    <th>Return Qty</th>
                                    <th>Return Value</th>
                                    <th>Return Rate %</th>
                                    <th>Quality Score</th>
                                    <th>Net Value</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- RETURN REASONS --}}
                <div class="tab-pane fade" id="tabReasons" role="tabpanel">
                    <div class="dt-toolbar mb-2">
                        <div class="left">
                            <input type="text" class="form-control" id="reasonSearch" style="min-width:320px" placeholder="Search reason text...">
                        </div>
                        <div class="right">
                            <button class="btn btn-outline-primary" id="applyReasonFilters">Apply</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="reasonTable" class="table table-bordered w-100">
                            <thead class="bg-light">
                                <tr>
                                    <th>Reason</th>
                                    <th>Return Docs</th>
                                    <th>Return Qty</th>
                                    <th>Return Value</th>
                                    <th>First Return</th>
                                    <th>Last Return</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf } });

    function money(n){
        n = Number(n || 0);
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function filters(){
        return {
            supplier_id: $('#filter_supplier').val(),
            date_from: $('#filter_from').val(),
            date_to: $('#filter_to').val(),
        };
    }

    // Supplier select2
    $('#filter_supplier').select2({
        placeholder: 'All suppliers',
        allowClear: true,
        width: '100%',
        ajax: {
            url: "{{ route('admin.suppliers.select2') }}",
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '' }),
            processResults: data => ({ results: data }),
        }
    });

    // Ranking table
    const dt = $('#rankTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.supplier_analytics.datatable') }}",
            data: function(d){
                d.supplier_id = $('#filter_supplier').val();
            }
        },
        columns: [
            { data: 'supplier_name', name: 'supplier_name' },
            { data: 'supply_qty', name: 'supply_qty' },
            { data: 'supply_value', name: 'supply_value', render: d => money(d) },
            { data: 'return_qty', name: 'return_qty' },
            { data: 'return_value', name: 'return_value', render: d => money(d) },
            { data: 'return_rate_pct', name: 'return_rate_pct' },
            { data: 'quality_score', name: 'quality_score' },
            { data: 'net_value', name: 'net_value', render: d => money(d) },
            { data: 'actions', orderable:false, searchable:false },
        ],
        order: [[7,'desc']]
    });

    // Product-level table
    const productDt = $('#productTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.supplier_analytics.products.datatable') }}",
            data: function(d){
                d.supplier_id = $('#filter_supplier').val();
                d.q = $('#productSearch').val();
                d.min_return_rate = $('#minReturnRate').val();
            }
        },
        columns: [
            { data: 'supplier_name', name:'supplier_name' },
            { data: 'product_code', name:'product_code' },
            { data: 'product_name', name:'product_name' },
            { data: 'supply_qty', name:'supply_qty' },
            { data: 'supply_value', name:'supply_value', render: d => money(d) },
            { data: 'return_qty', name:'return_qty' },
            { data: 'return_value', name:'return_value', render: d => money(d) },
            { data: 'return_rate_pct', name:'return_rate_pct' },
            { data: 'quality_score', name:'quality_score' },
            { data: 'net_value', name:'net_value', render: d => money(d) },
        ],
        order: [[9,'desc']]
    });

    // Reasons table
    const reasonDt = $('#reasonTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.supplier_analytics.reasons.datatable') }}",
            data: function(d){
                d.supplier_id = $('#filter_supplier').val();
                d.q = $('#reasonSearch').val();
            }
        },
        columns: [
            { data: 'reason', name:'reason' },
            { data: 'return_docs', name:'return_docs' },
            { data: 'return_qty', name:'return_qty' },
            { data: 'return_value', name:'return_value', render: d => money(d) },
            { data: 'first_return_at', name:'first_return_at' },
            { data: 'last_return_at', name:'last_return_at' },
        ],
        order: [[3,'desc']]
    });

    // Chart
    const ctx = document.getElementById('trendChart');
    const trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'Supply Qty', data: [] },
                { label: 'Return Qty', data: [] },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true } }
        }
    });

    function loadKpis(){
        $.get("{{ route('admin.supplier_analytics.kpis') }}", filters())
            .done(res => {
                $('#kpi_supply_value').text(money(res.supply_value));
                $('#kpi_supply_docs').text(res.supply_docs);

                $('#kpi_return_value').text(money(res.return_value));
                $('#kpi_return_docs').text(res.return_docs);

                $('#kpi_net_value').text(money(res.net_value));
                $('#kpi_return_rate').text(res.return_rate);

                $('#kpi_quality').text(res.quality_score);
            })
            .fail(() => Swal.fire('Error','Failed to load KPIs','error'));
    }

    function loadTrend(){
        $.get("{{ route('admin.supplier_analytics.trend') }}", filters())
            .done(res => {
                trendChart.data.labels = res.labels || [];
                trendChart.data.datasets[0].data = res.supply_qty || [];
                trendChart.data.datasets[1].data = res.return_qty || [];
                trendChart.update();
            })
            .fail(() => Swal.fire('Error','Failed to load trend','error'));
    }

    // Buttons
    $('#applyProductFilters').on('click', function(){
        productDt.ajax.reload();
    });

    $('#applyReasonFilters').on('click', function(){
        reasonDt.ajax.reload();
    });

    $('#applyFilters').on('click', function(){
        loadKpis();
        loadTrend();
        dt.ajax.reload();
        productDt.ajax.reload();
        reasonDt.ajax.reload();
    });

    // Chart render fix when tab becomes visible
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('data-bs-target');
        if (target === '#tabOverview') {
            trendChart.resize();
            trendChart.update();
        }
    });

    // Initial load
    loadKpis();
    loadTrend();
});
</script>
@endpush
