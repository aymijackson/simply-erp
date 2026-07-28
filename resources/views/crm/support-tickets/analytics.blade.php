@extends('layouts.master')

@section('title', 'Support Tickets Analytics')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Support Tickets Analytics</h1>
            <small class="text-muted">CRM</small>
        </div>
        @can('crm.support_tickets.analytics.export')
            <div class="d-flex gap-2">
                <a class="btn btn-outline-success" id="exportCsvBtn" href="#">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <a class="btn btn-outline-danger" id="exportPdfBtn" href="#">
                    <i class="fas fa-file-pdf me-1"></i> Export PDF
                </a>
            </div>
          @endcan
        <div class="d-flex gap-2">
            <a href="{{ route('admin.crm.support_tickets.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Tickets
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">

                <div class="col-md-4">
                    <label class="form-label mb-1">Customer</label>
                    <select id="filter_customer_id" class="form-control" style="width:100%"></select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Status</label>
                    <select id="filter_status" class="form-control">
                        <option value="">All</option>
                        <option value="open">Open</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Priority</label>
                    <select id="filter_priority" class="form-control">
                        <option value="">All</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Assigned To</label>
                    <select id="filter_assigned_to" class="form-control">
                        <option value="">All</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">
                                {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('Employee #'.$e->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">From</label>
                    <input type="date" id="filter_date_from" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">To</label>
                    <input type="date" id="filter_date_to" class="form-control">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-outline-primary w-100" id="applyFiltersBtn">
                        <i class="fas fa-filter me-1"></i>
                    </button>
                    <button class="btn btn-outline-secondary w-100" id="resetFiltersBtn">
                        <i class="fas fa-undo me-1"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Tickets</div>
                    <div class="h3 mb-0" id="kpi_total">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Backlog (Open+Pending)</div>
                    <div class="h3 mb-0" id="kpi_backlog">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Avg First Response (mins)</div>
                    <div class="h3 mb-0" id="kpi_first_response">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Avg Resolution (mins)</div>
                    <div class="h3 mb-0" id="kpi_resolution">—</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Created vs Resolved (Daily)</strong></div>
                <div class="card-body">
                    <canvas id="chartTrend" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Status Distribution</strong></div>
                <div class="card-body">
                    <canvas id="chartStatus" height="170"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Backlog by Priority</strong></div>
                <div class="card-body">
                    <canvas id="chartPriority" height="130"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Aging Buckets (Open/Pending)</strong></div>
                <div class="card-body">
                    <canvas id="chartAging" height="130"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Agent Chart --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white"><strong>Agent Workload (Open/Pending)</strong></div>
        <div class="card-body">
            <canvas id="chartAgents" height="90"></canvas>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="ticketsTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:35px;">#</th>
                            <th>Ticket No</th>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assigned</th>
                            <th>Created</th>
                            <th style="width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <small class="text-muted d-block mt-2">
                Tip: Apply filters to refresh KPIs, charts, and ticket list.
            </small>
        </div>
    </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
<style>
    .select2-container { width: 100% !important; }
    .select2-selection--single { height: calc(1.5em + .75rem + 2px) !important; }
    .select2-selection__rendered { line-height: calc(1.5em + .75rem) !important; }
    .select2-selection__arrow { height: calc(1.5em + .75rem + 2px) !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const routes = {
        datatable: @json(route('admin.crm.support_tickets.datatable')),
        kpis:      @json(route('admin.crm.support_tickets.analytics.kpis')),
        trends:    @json(route('admin.crm.support_tickets.analytics.trends')),
        aging:     @json(route('admin.crm.support_tickets.analytics.aging')),
        agents:    @json(route('admin.crm.support_tickets.analytics.agents')),

        // CustomerController select2 (use your actual route name if different)
        customerS2: @json(route('admin.customers.select2')),
    };

    // -----------------------------
    // Select2 Customer
    // -----------------------------
    function initCustomerSelect2() {
        const $el = $('#filter_customer_id');

        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            theme: 'bootstrap4',
            placeholder: 'Search customer...',
            allowClear: true,
            ajax: {
                url: routes.customerS2,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '' }),
                processResults: data => ({ results: data }),
                cache: true
            }
        });
    }
    initCustomerSelect2();

    // -----------------------------
    // Filters payload
    // -----------------------------
    function getFilters() {
        return {
            customer_id: $('#filter_customer_id').val() || '',
            status: $('#filter_status').val() || '',
            priority: $('#filter_priority').val() || '',
            assigned_to: $('#filter_assigned_to').val() || '',
            date_from: $('#filter_date_from').val() || '',
            date_to: $('#filter_date_to').val() || '',
        };
    }

    // -----------------------------
    // DataTable
    // -----------------------------
    const table = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: routes.datatable,
            data: function (d) {
                Object.assign(d, getFilters());
            }
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'ticket_no', name: 'ticket_no' },
            { data: 'customer_name', name: 'customer.name', defaultContent: '—' },
            { data: 'subject', name: 'subject' },
            { data: 'status', name: 'status' },
            { data: 'priority', name: 'priority' },
            { data: 'assignee_name', name: 'assignee.first_name', defaultContent: '—' },
            { data: 'created_at_fmt', name: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        drawCallback: function () {}
    });

    // -----------------------------
    // Charts
    // -----------------------------
    let chartTrend, chartStatus, chartPriority, chartAging, chartAgents;

    function destroyChart(c) { if (c) c.destroy(); return null; }

    function renderTrend(createdRows, resolvedRows) {
        // align dates on a unified axis
        const map = {};
        (createdRows || []).forEach(r => { map[r.d] = map[r.d] || {d:r.d, created:0, resolved:0}; map[r.d].created = r.c; });
        (resolvedRows || []).forEach(r => { map[r.d] = map[r.d] || {d:r.d, created:0, resolved:0}; map[r.d].resolved = r.c; });

        const rows = Object.values(map).sort((a,b) => a.d.localeCompare(b.d));
        const labels = rows.map(r => r.d);
        const created = rows.map(r => r.created);
        const resolved = rows.map(r => r.resolved);

        chartTrend = destroyChart(chartTrend);
        chartTrend = new Chart(document.getElementById('chartTrend'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Created', data: created, tension: 0.2 },
                    { label: 'Resolved', data: resolved, tension: 0.2 }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    function renderStatus(rows) {
        const labels = (rows || []).map(r => r.status);
        const data = (rows || []).map(r => r.c);

        chartStatus = destroyChart(chartStatus);
        chartStatus = new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: { labels, datasets: [{ data }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    function renderPriority(rows) {
        const labels = (rows || []).map(r => r.priority);
        const data = (rows || []).map(r => r.c);

        chartPriority = destroyChart(chartPriority);
        chartPriority = new Chart(document.getElementById('chartPriority'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Backlog', data }] },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

    function renderAging(rows) {
        const labels = (rows || []).map(r => r.bucket);
        const data = (rows || []).map(r => r.c);

        chartAging = destroyChart(chartAging);
        chartAging = new Chart(document.getElementById('chartAging'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Tickets', data }] },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

    function renderAgents(rows) {
        const labels = (rows || []).map(r => r.employee_name || 'Unassigned');
        const data = (rows || []).map(r => r.c);

        chartAgents = destroyChart(chartAgents);
        chartAgents = new Chart(document.getElementById('chartAgents'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Open/Pending', data }] },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { ticks: { autoSkip: false } } }
            }
        });
    }

    // -----------------------------
    // Load Analytics
    // -----------------------------
    function loadKpis() {
        return $.get(routes.kpis, getFilters())
            .done(res => {
                $('#kpi_total').text(res.total ?? '—');
                $('#kpi_backlog').text(res.backlog ?? '—');
                $('#kpi_first_response').text(res.avg_first_response_mins ?? '—');
                $('#kpi_resolution').text(res.avg_resolution_mins ?? '—');
            });
    }

    function loadTrends() {
        return $.get(routes.trends, getFilters())
            .done(res => {
                renderTrend(res.created, res.resolved);
                renderStatus(res.status);
                renderPriority(res.priority_backlog);
            });
    }

    function loadAging() {
        return $.get(routes.aging, getFilters())
            .done(res => renderAging(res.aging));
    }

    function loadAgents() {
        return $.get(routes.agents, getFilters())
            .done(res => renderAgents(res.workload));
    }

    function refreshAll() {
        loadKpis();
        loadTrends();
        loadAging();
        loadAgents();
        table.ajax.reload(null, false);
    }

    // Initial load
    refreshAll();

    // Apply / Reset
    $('#applyFiltersBtn').on('click', function () {
        refreshAll();
    });

    $('#resetFiltersBtn').on('click', function () {
        $('#filter_status').val('');
        $('#filter_priority').val('');
        $('#filter_assigned_to').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        $('#filter_customer_id').val(null).trigger('change');
        refreshAll();
    });

    function buildExportUrl(base){
      const p = new URLSearchParams(getFilters());
      return base + '?' + p.toString();
    }
    
    $('#exportCsvBtn').on('click', function(e){
      e.preventDefault();
      window.location.href = buildExportUrl(@json(route('admin.crm.support_tickets.analytics.export.csv')));
    });
    
    $('#exportPdfBtn').on('click', function(e){
      e.preventDefault();
      window.location.href = buildExportUrl(@json(route('admin.crm.support_tickets.analytics.export.pdf')));
    });

})();
</script>
@endpush
