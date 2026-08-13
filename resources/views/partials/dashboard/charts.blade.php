<div class="row">
    <!-- Area Chart: User Registrations -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Sales (Last 6 Months)</h6>

                <div class="dropdown">
                    <a class="dropdown-toggle text-decoration-none" href="#" role="button"
                       id="usersChartMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow animated--fade-in" aria-labelledby="usersChartMenu">
                        <li><h6 class="dropdown-header">Quick actions</h6></li>
                        <li><a class="dropdown-item" href="{{ route('admin.dashboard.index') }}">Refresh</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.dashboard.index') }}?range=12m">View 12 months</a></li>
                    </ul>
                </div>
            </div>

            <div class="card-body">
                <div class="chart-area" style="min-height: 320px;">
                    <canvas id="myAreaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie/Donut Chart: Stock Age Distribution -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Stock Age Distribution (Value)</h6>

                <div class="dropdown">
                    <a class="dropdown-toggle text-decoration-none" href="#" role="button"
                       id="stockAgeMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow animated--fade-in" aria-labelledby="stockAgeMenu">
                        <li><h6 class="dropdown-header">Quick actions</h6></li>
                        <li><a class="dropdown-item" href="{{ route('admin.dashboard.index') }}">Refresh</a></li>
                        <li><a class="dropdown-item" href="#">View stock report</a></li>
                    </ul>
                </div>
            </div>

            <div class="card-body">
                <div class="chart-pie pt-4 pb-2" style="min-height: 260px;">
                    <canvas id="myPieChart"></canvas>
                </div>

                @php
                    $ageMap = $stockAgeBuckets ?? [];
                    $ageKeys = is_array($ageMap) ? array_keys($ageMap) : (method_exists($ageMap, 'keys') ? $ageMap->keys()->toArray() : []);
                @endphp

                @if(empty($ageKeys))
                    <div class="text-center small text-muted mt-3">
                        No stock age data available.
                    </div>
                @else
                    <div class="mt-4 text-center small">
                        @foreach($ageKeys as $k)
                            <span class="me-2">
                                <i class="fas fa-circle text-primary"></i> {{ $k }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // -------- Area chart: Registrations --------
    const regLabels = @json($chartLabels ?? []);
    const regValues = @json($chartRegistrations ?? []);

    const areaEl = document.getElementById('myAreaChart');
    if (areaEl && regLabels.length) {
        new Chart(areaEl, {
            type: 'line',
            data: {
                labels: regLabels,
                datasets: [{
                    label: 'Registrations',
                    data: regValues,
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // -------- Pie/Donut chart: Stock age value --------
    const stockAgeMap = @json($stockAgeBuckets ?? []);
    const pieLabels = Object.keys(stockAgeMap);
    const pieValues = Object.values(stockAgeMap);

    const pieEl = document.getElementById('myPieChart');
    if (pieEl && pieLabels.length) {
        new Chart(pieEl, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    label: 'Stock Value',
                    data: pieValues,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
</script>
@endpush
