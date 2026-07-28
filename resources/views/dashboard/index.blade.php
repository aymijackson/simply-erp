@extends('layouts.master')

@section('title', 'Executive Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 text-primary mb-0">Executive Dashboard</h1>
            <small class="text-muted">Enterprise-wide overview across finance, procurement, projects, CRM and operations</small>
        </div>
    </div>

    {{-- KPI ROW --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Cash Balance</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($kpis['cash_balance'], 2) }}</div>
                    <div class="small text-muted mt-1">Available bank liquidity</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Accounts Receivable</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format($kpis['accounts_receivable'], 2) }}</div>
                    <div class="small text-muted mt-1">Open customer balances</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Accounts Payable</div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format($kpis['accounts_payable'], 2) }}</div>
                    <div class="small text-muted mt-1">Outstanding supplier balances</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Net Profit This Month</div>
                    <div class="fs-4 fw-bold {{ $kpis['net_profit_month'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($kpis['net_profit_month'], 2) }}
                    </div>
                    <div class="small text-muted mt-1">Revenue minus expenses</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="small text-muted">Revenue This Month</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($kpis['revenue_month'], 2) }}</div>
                    <div class="small text-muted mt-1">Commercial activity</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="small text-muted">Expenses This Month</div>
                    <div class="fs-4 fw-bold text-warning">{{ number_format($kpis['expenses_month'], 2) }}</div>
                    <div class="small text-muted mt-1">Operational expenditure</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="small text-muted">Active Projects</div>
                    <div class="fs-4 fw-bold">{{ $kpis['active_projects'] }}</div>
                    <div class="small text-muted mt-1">Current delivery load</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="small text-muted">Open RFQs</div>
                    <div class="fs-4 fw-bold">{{ $kpis['open_rfqs'] }}</div>
                    <div class="small text-muted mt-1">Procurement pipeline</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="small text-muted">Over Budget Projects</div>
                    <div class="fs-4 fw-bold text-danger">{{ $kpis['projects_over_budget'] }}</div>
                    <div class="small text-muted mt-1">Needs attention</div>
                </div>
            </div>
        </div>
    </div>

    {{-- CHARTS --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Finance Trend (Last 6 Months)</div>
                <div class="card-body">
                    <canvas id="financeTrendChart" height="110"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Project Status Mix</div>
                <div class="card-body">
                    <canvas id="projectStatusChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- OPERATIONS SUMMARY --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Procurement Snapshot</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open RFQs</span>
                        <strong>{{ $procurementStatus['rfqs'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Pending Supplier Quotations</span>
                        <strong>{{ $procurementStatus['quotes_pending'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Purchase Orders</span>
                        <strong>{{ $procurementStatus['purchase_orders'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>GRN Pending Billing</span>
                        <strong>{{ $procurementStatus['grn_pending_billing'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Projects Snapshot</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Active Projects</span>
                        <strong>{{ $kpis['active_projects'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Projects Over Budget</span>
                        <strong class="text-danger">{{ $kpis['projects_over_budget'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Billable Hours This Month</span>
                        <strong>{{ number_format($kpis['billable_hours_month'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Project Invoice Total This Month</span>
                        <strong>{{ number_format($kpis['project_invoice_total'], 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">CRM / Service Snapshot</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Active Leads</span>
                        <strong>{{ $kpis['active_leads'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Opportunities</span>
                        <strong>{{ $kpis['open_opportunities'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Support Tickets</span>
                        <strong>{{ $kpis['open_tickets'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Low Stock Items</span>
                        <strong class="text-warning">{{ $kpis['low_stock_items'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ATTENTION REQUIRED --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold text-danger">Attention Required</div>
                <div class="card-body">
                    <h6 class="mb-2">Overdue Customer Invoices</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attentionItems['overdue_customer_invoices'] as $row)
                                    <tr>
                                        <td>{{ $row->invoice_no ?? $row->invoice_number ?? ('INV-'.$row->id) }}</td>
                                        <td>{{ $row->due_date ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted">No overdue customer invoices</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mb-2">Overdue Supplier Bills</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bill</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attentionItems['overdue_supplier_bills'] as $row)
                                    <tr>
                                        <td>{{ $row->bill_no ?? ('BILL-'.$row->id) }}</td>
                                        <td>{{ $row->due_date ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted">No overdue supplier bills</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold text-warning">Risk Watchlist</div>
                <div class="card-body">
                    <h6 class="mb-2">Projects Over Budget</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Project</th>
                                    <th class="text-end">Budget</th>
                                    <th class="text-end">Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attentionItems['over_budget_projects'] as $row)
                                    <tr>
                                        <td>{{ $row->project_name ?? $row->name ?? ('Project-'.$row->id) }}</td>
                                        <td class="text-end">{{ number_format((float)($row->budget_amount ?? 0), 2) }}</td>
                                        <td class="text-end">{{ number_format((float)($row->actual_cost ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No over-budget projects</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mb-2">Low Stock Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Reorder</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attentionItems['low_stock_items'] as $row)
                                    <tr>
                                        <td>{{ $row->product_name ?? $row->name ?? ('Item-'.$row->id) }}</td>
                                        <td class="text-end">{{ number_format((float)($row->product_stock_quantity ?? 0), 2) }}</td>
                                        <td class="text-end">{{ number_format((float)($row->reorder_level ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No low stock items</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TOP COST PROJECTS + RECENT ACTIVITY --}}
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Top Project Cost Consumers</div>
                <div class="card-body">
                    <canvas id="topProjectsChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Recent Activity</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6>Recent Sales Invoices</h6>
                            <ul class="list-group list-group-flush">
                                @forelse($recentActivities['sales_invoices'] as $row)
                                    <li class="list-group-item px-0">
                                        {{ $row->invoice_no ?? $row->invoice_number ?? ('INV-'.$row->id) }}
                                    </li>
                                @empty
                                    <li class="list-group-item px-0 text-muted">No recent sales invoices</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h6>Recent Supplier Bills</h6>
                            <ul class="list-group list-group-flush">
                                @forelse($recentActivities['supplier_bills'] as $row)
                                    <li class="list-group-item px-0">
                                        {{ $row->bill_no ?? ('BILL-'.$row->id) }}
                                    </li>
                                @empty
                                    <li class="list-group-item px-0 text-muted">No recent supplier bills</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h6>Recent Project Invoices</h6>
                            <ul class="list-group list-group-flush">
                                @forelse($recentActivities['project_invoices'] as $row)
                                    <li class="list-group-item px-0">
                                        {{ $row->invoice_no ?? ('PINV-'.$row->id) }}
                                    </li>
                                @empty
                                    <li class="list-group-item px-0 text-muted">No recent project invoices</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h6>Recent Timesheets</h6>
                            <ul class="list-group list-group-flush">
                                @forelse($recentActivities['timesheets'] as $row)
                                    <li class="list-group-item px-0">
                                        TS-{{ $row->id }} | {{ $row->entry_date ?? '—' }}
                                    </li>
                                @empty
                                    <li class="list-group-item px-0 text-muted">No recent timesheets</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-12">
                            <h6>Recent Journal Entries</h6>
                            <ul class="list-group list-group-flush">
                                @forelse($recentActivities['journal_entries'] as $row)
                                    <li class="list-group-item px-0">
                                        {{ $row->entry_no ?? ('JE-'.$row->id) }} @if(!empty($row->reference)) | {{ $row->reference }} @endif
                                    </li>
                                @empty
                                    <li class="list-group-item px-0 text-muted">No recent journal entries</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const financeTrend = @json($financeTrend);
    const projectStatus = @json($projectStatus);
    const topCostProjects = @json($topCostProjects);

    new Chart(document.getElementById('financeTrendChart'), {
        type: 'bar',
        data: {
            labels: financeTrend.labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: financeTrend.revenue,
                    borderWidth: 1
                },
                {
                    label: 'Expenses',
                    data: financeTrend.expenses,
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(document.getElementById('projectStatusChart'), {
        type: 'doughnut',
        data: {
            labels: projectStatus.labels,
            datasets: [{
                data: projectStatus.values,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(document.getElementById('topProjectsChart'), {
        type: 'bar',
        data: {
            labels: topCostProjects.map(x => x.name),
            datasets: [{
                label: 'Cost',
                data: topCostProjects.map(x => x.amount),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>
@endpush