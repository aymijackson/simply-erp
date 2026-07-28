@extends('layouts.master')

@section('title', 'Customer Segmentation Analytics')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 text-primary mb-0">Customer Segmentation</h1>
            <small class="text-muted">Option C: Pipeline + Tickets + Engagement</small>
        </div>
    </div>

    {{-- KPI --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Total Customers</div>
            <div class="h4 mb-0" id="kpiTotal">—</div>
        </div></div></div>

        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Hot / High Value</div>
            <div class="h4 mb-0" id="kpiHot">—</div>
        </div></div></div>

        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">At Risk (Open Tickets)</div>
            <div class="h4 mb-0" id="kpiRisk">—</div>
        </div></div></div>

        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Dormant</div>
            <div class="h4 mb-0" id="kpiDormant">—</div>
        </div></div></div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select id="f_status" class="form-control">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Segment</label>
                    <select id="f_segment" class="form-control">
                        <option value="">All Segments</option>
                        <option>Hot / High Value</option>
                        <option>At Risk (Open Tickets)</option>
                        <option>Dormant</option>
                        <option>Warm</option>
                        <option>New</option>
                        <option>Normal</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Min Pipeline</label>
                    <input id="f_min_pipeline" type="number" class="form-control" placeholder="e.g. 50000">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Min Open Tickets</label>
                    <input id="f_min_open_tickets" type="number" class="form-control" placeholder="e.g. 2">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Min Interactions (30d)</label>
                    <input id="f_min_interactions_30d" type="number" class="form-control" placeholder="e.g. 2">
                </div>

                <div class="col-md-1 d-flex gap-2">
                    <button id="btnApply" class="btn btn-primary w-100">Apply</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Segment Distribution</strong></div>
                <div class="card-body"><canvas id="chartSegments" height="130"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Pipeline Value by Segment</strong></div>
                <div class="card-body"><canvas id="chartPipeline" height="130"></canvas></div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Customers (Drilldown)</strong>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped w-100" id="segTable">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Segment</th>
                        <th>Pipeline</th>
                        <th>Open Tickets</th>
                        <th>Int. 30d</th>
                        <th>Int. 90d</th>
                        <th>Days Since</th>
                        <th>Last Interaction</th>
                        <th>Last Ticket</th>
                        <th>Last Opportunity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function(){
    const routes = {
        summary: @json(route('admin.crm.analytics.customer_segmentation.summary')),
        table:   @json(route('admin.crm.analytics.customer_segmentation.datatable')),
    };

    let chartSeg, chartPipe;

    function filters(){
        return {
            status: $('#f_status').val(),
            segment: $('#f_segment').val(),
            min_pipeline: $('#f_min_pipeline').val(),
            min_open_tickets: $('#f_min_open_tickets').val(),
            min_interactions_30d: $('#f_min_interactions_30d').val(),
            mode: 'C'
        }
    }

    function buildCharts(payload){
        const labels = Object.keys(payload.segments || {});
        const counts = Object.values(payload.segments || {});
        const pipeVals = labels.map(l => (payload.pipeline_by_segment || {})[l] || 0);

        if(chartSeg) chartSeg.destroy();
        if(chartPipe) chartPipe.destroy();

        chartSeg = new Chart(document.getElementById('chartSegments'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Customers', data: counts }] },
            options: { responsive:true }
        });

        chartPipe = new Chart(document.getElementById('chartPipeline'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Pipeline Value', data: pipeVals }] },
            options: { responsive:true }
        });
    }

    async function loadSummary(){
        const qs = new URLSearchParams(filters()).toString();
        const res = await fetch(routes.summary + '?' + qs);
        const payload = await res.json();

        $('#kpiTotal').text(payload.total_customers ?? '—');
        $('#kpiHot').text(payload.hot ?? '—');
        $('#kpiRisk').text(payload.at_risk ?? '—');
        $('#kpiDormant').text(payload.dormant ?? '—');

        buildCharts(payload);
    }

    const table = $('#segTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: routes.table,
            data: function(d){ Object.assign(d, filters()); }
        },
        columns: [
            {data:'customer_name', name:'customer_name'},
            {data:'email', name:'email'},
            {data:'phone', name:'phone'},
            {data:'segment', name:'segment'},
            {data:'pipeline_value', name:'pipeline_value'},
            {data:'open_tickets', name:'open_tickets'},
            {data:'interactions_30d', name:'interactions_30d'},
            {data:'interactions_90d', name:'interactions_90d'},
            {data:'days_since_interaction', name:'days_since_interaction'},
            {data:'last_interaction_at', name:'last_interaction_at'},
            {data:'last_ticket_at', name:'last_ticket_at'},
            {data:'last_opportunity_at', name:'last_opportunity_at'},
            {data:'actions', name:'actions', orderable:false, searchable:false},
        ]
    });

    $('#btnApply').on('click', function(){
        loadSummary();
        table.ajax.reload();
    });

    loadSummary();

})();
</script>
@endpush
