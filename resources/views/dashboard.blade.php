@extends('layouts.master')

@section('title', 'Inventory Dashboard')

@section('content')
<div id="content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
            <a href="#" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>
        </div>

        @include('partials.dashboard.cards-summary') <!-- Extract card blocks into partials -->
        @include('partials.dashboard.charts') <!-- Extract charts into partials -->
        @include('partials.dashboard.projects') <!-- Extract project section into partials -->
    </div>
</div>
@endsection
@push('scripts')
<script>
  const salesLabels = @json($salesLast6MonthsLabels ?? []);
  const salesValues = @json($salesLast6MonthsValues ?? []);

  const area = document.getElementById('myAreaChart');
  if (area) {
    new Chart(area, {
      type: 'line',
      data: { labels: salesLabels, datasets: [{ label: 'Sales', data: salesValues, tension: 0.3, fill: true }] },
      options: { responsive: true, maintainAspectRatio: false }
    });
  }
</script>
@endpush
@push('scripts')
<script>
  const stockAgeMap = @json($stockAgeBuckets ?? []);
  const pieLabels = Object.keys(stockAgeMap);
  const pieValues = Object.values(stockAgeMap);

  const pie = document.getElementById('myPieChart');
  if (pie) {
    new Chart(pie, {
      type: 'doughnut',
      data: { labels: pieLabels, datasets: [{ label: 'Stock Age (Value)', data: pieValues }] },
      options: { responsive: true, maintainAspectRatio: false }
    });
  }
</script>
@endpush

