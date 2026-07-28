@extends('layouts.master')

@section('title', 'Supplier Analytics — '.$supplier->supplier_name)

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Supplier Analytics</h3>
            <div class="text-muted">{{ $supplier->supplier_name }}</div>
        </div>
        <a href="{{ route('admin.supplier_analytics.index') }}" class="btn btn-outline-secondary">
            ← Back
        </a>
    </div>

    {{-- KPI Cards --}}
    <div class="row mb-4" id="kpiCards">
        @foreach(['Supply Value','Return Value','Net Value','Quality Score'] as $label)
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted">{{ $label }}</div>
                    <div class="h4 fw-bold">—</div>
                    <div class="small text-muted sub"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#trendTab">
                Trend
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#productsTab">
                Product-level
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reasonsTab">
                Return Reasons
            </button>
        </li>
    </ul>

    <div class="tab-content">

        {{-- Trend --}}
        <div class="tab-pane fade show active" id="trendTab">
            <div class="card shadow-sm">
                <div class="card-body">
                    <canvas id="trendChart" height="110"></canvas>
                </div>
            </div>
        </div>

        {{-- Products --}}
        <div class="tab-pane fade" id="productsTab">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-bordered" id="productTable">
                        <thead>
                        <tr>
                            <th>Product</th>
                            <th>Supply Qty</th>
                            <th>Supply Value</th>
                            <th>Return Qty</th>
                            <th>Return Value</th>
                            <th>Return %</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- Reasons --}}
        <div class="tab-pane fade" id="reasonsTab">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-bordered" id="reasonTable">
                        <thead>
                        <tr>
                            <th>Reason</th>
                            <th>Return Docs</th>
                            <th>Return Qty</th>
                            <th>Return Value</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection


@push('scripts')
<script>
const supplierId = {{ $supplier->id }};

loadKpis();
loadTrend();

function loadKpis(){
    $.get("{{ route('admin.supplier_analytics.kpis') }}", {supplier_id: supplierId}, function(r){
        const cards = $('#kpiCards .card-body');
        cards.eq(0).find('.h4').text(r.supply_value.toLocaleString());
        cards.eq(1).find('.h4').text(r.return_value.toLocaleString());
        cards.eq(2).find('.h4').text(r.net_value.toLocaleString());
        cards.eq(3).find('.h4').text(r.quality_score);
    });
}

function loadTrend(){
    $.get("{{ route('admin.supplier_analytics.trend') }}", {supplier_id: supplierId}, function(r){
        new Chart(document.getElementById('trendChart'), {
            type:'bar',
            data:{
                labels:r.labels,
                datasets:[
                    {label:'Supply Qty', data:r.supply_qty},
                    {label:'Return Qty', data:r.return_qty}
                ]
            }
        });
    });
}

$('#productTable').DataTable({
    processing:true,
    serverSide:true,
    ajax:{
        url:"{{ route('admin.supplier_analytics.products.datatable') }}",
        data:{supplier_id: supplierId}
    },
    columns:[
        {data:'product_name'},
        {data:'supply_qty'},
        {data:'supply_value'},
        {data:'return_qty'},
        {data:'return_value'},
        {data:'return_rate_pct'}
    ]
});

$('#reasonTable').DataTable({
    processing:true,
    serverSide:true,
    ajax:{
        url:"{{ route('admin.supplier_analytics.reasons.datatable') }}",
        data:{supplier_id: supplierId}
    },
    columns:[
        {data:'reason'},
        {data:'return_docs'},
        {data:'return_qty'},
        {data:'return_value'}
    ]
});
</script>
@endpush
