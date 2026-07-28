@extends('layouts.master')

@section('title', 'CEO Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 text-primary mb-0">CEO Dashboard</h1>
            <small class="text-muted">Strategic enterprise overview across finance, procurement, projects and operations</small>
        </div>
    </div>

    {{-- TOP KPI ROW --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Cash Balance</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($kpis['cash_balance'], 2) }}</div>
                    <div class="small text-muted">Enterprise liquidity position</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Revenue This Month</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format($kpis['revenue_month'], 2) }}</div>
                    <div class="small text-muted">Top-line commercial performance</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Expenses This Month</div>
                    <div class="fs-4 fw-bold text-warning">{{ number_format($kpis['expenses_month'], 2) }}</div>
                    <div class="small text-muted">Operational spend</div>
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
                    <div class="small text-muted">Current operating result</div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECOND KPI ROW --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Accounts Receivable</div>
                    <div class="fs-4 fw-bold text-info">{{ number_format($kpis['accounts_receivable'], 2) }}</div>
                    <div class="small text-muted">Outstanding customer balances</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Accounts Payable</div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format($kpis['accounts_payable'], 2) }}</div>
                    <div class="small text-muted">Outstanding supplier obligations</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Active Projects</div>
                    <div class="fs-4 fw-bold">{{ $kpis['active_projects'] }}</div>
                    <div class="small text-muted">Current delivery load</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Open Procurement</div>
                    <div class="fs-4 fw-bold">{{ $kpis['open_procurement'] }}</div>
                    <div class="small text-muted">Open supply pipeline</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Projects Over Budget</div>
                    <div class="fs-4 fw-bold text-danger">{{ $kpis['projects_over_budget'] }}</div>
                    <div class="small text-muted">Strategic risk items</div>
                </div>
            </div>
        </div>
    </div>

    {{-- CHARTS --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Revenue vs Expenses Trend (6 Months)</div>
                <div class="card-body">
                    <canvas id="financeTrendChart" height="110"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Strategic Exposure Snapshot</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Opportunities</span>
                        <strong>{{ $kpis['open_opportunities'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Tickets</span>
                        <strong>{{ $kpis['open_tickets'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Low Stock Items</span>
                        <strong class="text-warning">{{ $kpis['low_stock_items'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Receivables vs Payables Gap</span>
                        <strong class="{{ ($kpis['accounts_receivable'] - $kpis['accounts_payable']) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($kpis['accounts_receivable'] - $kpis['accounts_payable'], 2) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BUSINESS UNITS --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Projects Overview</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Active Projects</span>
                        <strong>{{ $projectSummary['active_projects'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Over Budget Projects</span>
                        <strong class="text-danger">{{ $projectSummary['over_budget_projects'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Project Revenue</span>
                        <strong>{{ number_format($projectSummary['project_revenue'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Project Cost</span>
                        <strong>{{ number_format($projectSummary['project_cost'], 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Procurement Overview</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open RFQs</span>
                        <strong>{{ $procurementSummary['rfqs'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Pending Supplier Quotations</span>
                        <strong>{{ $procurementSummary['supplier_quotes'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Purchase Orders</span>
                        <strong>{{ $procurementSummary['purchase_orders'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>GRN Pending Billing</span>
                        <strong>{{ $procurementSummary['goods_receipts_pending_billing'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Operational Watch</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Opportunities</span>
                        <strong>{{ $kpis['open_opportunities'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Support Tickets</span>
                        <strong>{{ $kpis['open_tickets'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Low Stock Items</span>
                        <strong class="text-warning">{{ $kpis['low_stock_items'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Cash vs AP Cover</span>
                        <strong class="{{ ($kpis['cash_balance'] - $kpis['accounts_payable']) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($kpis['cash_balance'] - $kpis['accounts_payable'], 2) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RISK AND RECENT --}}
    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold text-danger">Key Risks</div>
                <div class="card-body">
                    <h6 class="mb-2">Overdue Customer Invoices</h6>
                    <ul class="list-group mb-3">
                        @forelse($riskItems['overdue_invoices'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->invoice_no ?? $row->invoice_number ?? ('INV-'.$row->id) }}</span>
                                <span>{{ $row->due_date ?? '—' }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No overdue customer invoices</li>
                        @endforelse
                    </ul>

                    <h6 class="mb-2">Overdue Supplier Bills</h6>
                    <ul class="list-group mb-3">
                        @forelse($riskItems['overdue_bills'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->bill_no ?? ('BILL-'.$row->id) }}</span>
                                <span>{{ $row->due_date ?? '—' }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No overdue supplier bills</li>
                        @endforelse
                    </ul>

                    <h6 class="mb-2">Over Budget Projects</h6>
                    <ul class="list-group">
                        @forelse($riskItems['over_budget_projects'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->project_name ?? $row->name ?? ('Project-'.$row->id) }}</span>
                                <span class="text-danger">{{ number_format((float)($row->actual_cost ?? 0), 2) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No over-budget projects</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Recent Strategic Activity</div>
                <div class="card-body">
                    <h6>Recent Sales Invoices</h6>
                    <ul class="list-group mb-3">
                        @forelse($recentActivities['sales_invoices'] as $row)
                            <li class="list-group-item">{{ $row->invoice_no ?? $row->invoice_number ?? ('INV-'.$row->id) }}</li>
                        @empty
                            <li class="list-group-item text-muted">No recent sales invoices</li>
                        @endforelse
                    </ul>

                    <h6>Recent Supplier Bills</h6>
                    <ul class="list-group mb-3">
                        @forelse($recentActivities['supplier_bills'] as $row)
                            <li class="list-group-item">{{ $row->bill_no ?? ('BILL-'.$row->id) }}</li>
                        @empty
                            <li class="list-group-item text-muted">No recent supplier bills</li>
                        @endforelse
                    </ul>

                    <h6>Recent Project Invoices</h6>
                    <ul class="list-group mb-3">
                        @forelse($recentActivities['project_invoices'] as $row)
                            <li class="list-group-item">{{ $row->invoice_no ?? ('PINV-'.$row->id) }}</li>
                        @empty
                            <li class="list-group-item text-muted">No recent project invoices</li>
                        @endforelse
                    </ul>

                    <h6>Recent Journal Entries</h6>
                    <ul class="list-group">
                        @forelse($recentActivities['journal_entries'] as $row)
                            <li class="list-group-item">{{ $row->entry_no ?? ('JE-'.$row->id) }}</li>
                        @empty
                            <li class="list-group-item text-muted">No recent journal entries</li>
                        @endforelse
                    </ul>
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
</script>
@endpush