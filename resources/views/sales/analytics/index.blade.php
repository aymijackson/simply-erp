@extends('layouts.master')

@section('title','Sales Analytics')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Analytics</h1>
            <small class="text-muted">Sales / Analytics</small>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">From</label>
                    <input type="date" id="from" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">To</label>
                    <input type="date" id="to" class="form-control" value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">Customer</label>
                    <select id="customer_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select id="status_mode" class="form-control">
                        <option value="posted" selected>Posted only</option>
                        <option value="all">All statuses</option>
                    </select>
                </div>

                <div class="col-md-2 mt-2">
                    <label class="form-label mb-1">Group</label>
                    <select id="group" class="form-control">
                        <option value="day" selected>Daily</option>
                        <option value="month">Monthly</option>
                    </select>
                </div>

                <div class="col-md-2 mt-2">
                    <button class="btn btn-primary w-100" id="applyBtn">
                        <i class="fas fa-search mr-1"></i> Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="row">
        @php
            $cards = [
                ['id'=>'k_invoiced','title'=>'Invoiced (Posted)','icon'=>'fa-file-invoice'],
                ['id'=>'k_payments','title'=>'Payments Received','icon'=>'fa-money-bill-wave'],
                ['id'=>'k_credits','title'=>'Credit Notes','icon'=>'fa-receipt'],
                ['id'=>'k_net','title'=>'Net Sales','icon'=>'fa-chart-line'],
                ['id'=>'k_outstanding','title'=>'Outstanding A/R','icon'=>'fa-exclamation-circle'],
                ['id'=>'k_overdue','title'=>'Overdue A/R','icon'=>'fa-clock'],
                ['id'=>'k_unalloc','title'=>'Unallocated Payments','icon'=>'fa-random'],
            ];
        @endphp

        @foreach($cards as $c)
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-left-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ $c['title'] }}</div>
                        <div class="h5 mb-0 fw-bold" id="{{ $c['id'] }}">0.00</div>
                    </div>
                    <div class="text-primary">
                        <i class="fas {{ $c['icon'] }} fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- CHARTS --}}
    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">
                    Revenue Trend
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="110"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">
                    Payment Allocation Health
                </div>
                <div class="card-body">
                    <canvas id="allocChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- AR Aging + Top Customers --}}
    <div class="row">
        <div class="col-lg-5 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">A/R Aging</div>
                <div class="card-body">
                    <canvas id="agingChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Top Customers</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="topCustomersTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-end">Invoiced</th>
                                    <th class="text-end">Outstanding</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Based on invoices in the selected date range.</small>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const urls = {
    summary: "{{ route('admin.sales.analytics.summary') }}",
    trends: "{{ route('admin.sales.analytics.trends') }}",
    aging: "{{ route('admin.sales.analytics.ar_aging') }}",
    topCustomers: "{{ route('admin.sales.analytics.top_customers') }}",
    alloc: "{{ route('admin.sales.analytics.payment_allocation') }}",
    customersSelect2: "{{ route('admin.customers.select2') ?? '' }}"
};

function fmt2(n){
    n = parseFloat(n || 0);
    return n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}
function params(){
    return {
        from: $('#from').val(),
        to: $('#to').val(),
        customer_id: $('#customer_id').val() || '',
        status_mode: $('#status_mode').val(),
        group: $('#group').val()
    };
}

let trendChart, allocChart, agingChart;

function initSelect2(){
    if(!urls.customersSelect2) return;
    $('#customer_id').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'All customers',
        allowClear: true,
        ajax: {
            url: urls.customersSelect2,
            dataType: 'json',
            delay: 250,
            data: function(p){ return { q: p.term || '' }; },
            processResults: function(data){
                // supports either {results:[...]} or plain list
                if (data.results) return data;
                return {results: (Array.isArray(data) ? data : []).map(x => ({id:x.id, text:x.text || x.name}))};
            }
        }
    });
}

function buildTrendChart(series){
    const mapSeries = (arr)=> {
        const m = {};
        (arr||[]).forEach(r => m[r.d] = parseFloat(r.total||0));
        return m;
    };

    const inv = mapSeries(series.invoices);
    const pay = mapSeries(series.payments);
    const cn  = mapSeries(series.credit_notes);

    // union of labels
    const labelsSet = new Set([...Object.keys(inv), ...Object.keys(pay), ...Object.keys(cn)]);
    const labels = Array.from(labelsSet).sort();

    const invData = labels.map(d => inv[d] || 0);
    const payData = labels.map(d => pay[d] || 0);
    const cnData  = labels.map(d => cn[d] || 0);

    if(trendChart) trendChart.destroy();
    trendChart = new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Invoices', data: invData, tension: 0.25 },
                { label: 'Payments', data: payData, tension: 0.25 },
                { label: 'Credit Notes', data: cnData, tension: 0.25 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { ticks: { callback: (v)=> fmt2(v) } }
            }
        }
    });
}

function buildAllocChart(allocated, unallocated){
    if(allocChart) allocChart.destroy();
    allocChart = new Chart(document.getElementById('allocChart'), {
        type: 'doughnut',
        data: {
            labels: ['Allocated', 'Unallocated'],
            datasets: [{ data: [allocated||0, unallocated||0] }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx)=> `${ctx.label}: ${fmt2(ctx.raw)}` } }
            }
        }
    });
}

function buildAgingChart(aging){
    const labels = ['0–30', '31–60', '61–90', '91+'];
    const data = [aging['0_30'], aging['31_60'], aging['61_90'], aging['91_plus']];

    if(agingChart) agingChart.destroy();
    agingChart = new Chart(document.getElementById('agingChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Balance Due', data }] },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { ticks: { callback: (v)=> fmt2(v) } } }
        }
    });
}

function loadSummary(){
    return $.get(urls.summary, params()).done(res => {
        const k = res.kpis || {};
        $('#k_invoiced').text(fmt2(k.invoiced));
        $('#k_payments').text(fmt2(k.payments));
        $('#k_credits').text(fmt2(k.credits));
        $('#k_net').text(fmt2(k.net_sales));
        $('#k_outstanding').text(fmt2(k.outstanding_ar));
        $('#k_overdue').text(fmt2(k.overdue_ar));
        $('#k_unalloc').text(fmt2(k.unallocated_payments));
    });
}

function loadTrends(){
    return $.get(urls.trends, params()).done(res => buildTrendChart(res.series || {}));
}

function loadAging(){
    return $.get(urls.aging, params()).done(res => buildAgingChart(res.aging || {'0_30':0,'31_60':0,'61_90':0,'91_plus':0}));
}

function loadTopCustomers(){
    $('#topCustomersTable tbody').html('<tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>');
    return $.get(urls.topCustomers, params()).done(res => {
        const rows = res.rows || [];
        if(!rows.length){
            $('#topCustomersTable tbody').html('<tr><td colspan="3" class="text-center text-muted">No data.</td></tr>');
            return;
        }
        let html = '';
        rows.forEach(r => {
            html += `
                <tr>
                    <td>${escapeHtml(r.customer_name || 'Customer')}</td>
                    <td class="text-end">${fmt2(r.total_invoiced)}</td>
                    <td class="text-end">${fmt2(r.total_outstanding)}</td>
                </tr>
            `;
        });
        $('#topCustomersTable tbody').html(html);
    });
}

function loadAllocation(){
    return $.get(urls.alloc, params()).done(res => buildAllocChart(res.allocated, res.unallocated));
}

function escapeHtml(str){
    return String(str ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}

function reloadAll(){
    $.when(
        loadSummary(),
        loadTrends(),
        loadAging(),
        loadTopCustomers(),
        loadAllocation()
    );
}

$('#applyBtn').on('click', function(){ reloadAll(); });

$(function(){
    initSelect2();
    reloadAll();
});
</script>
@endpush
