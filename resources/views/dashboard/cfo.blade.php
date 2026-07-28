@extends('layouts.master')

@section('title', 'CFO Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 text-primary mb-0">CFO Dashboard</h1>
            <small class="text-muted">Finance, liquidity, receivables, payables and control overview</small>
        </div>
    </div>

    {{-- KPI ROW 1 --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Cash Balance</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($kpis['cash_balance'], 2) }}</div>
                    <div class="small text-muted">Available liquidity</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Accounts Receivable</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format($kpis['accounts_receivable'], 2) }}</div>
                    <div class="small text-muted">Open customer balances</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Accounts Payable</div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format($kpis['accounts_payable'], 2) }}</div>
                    <div class="small text-muted">Outstanding supplier liabilities</div>
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
                    <div class="small text-muted">Monthly operating result</div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI ROW 2 --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Overdue Receivables</div>
                    <div class="fs-4 fw-bold text-warning">{{ number_format($kpis['overdue_receivables'], 2) }}</div>
                    <div class="small text-muted">Past due collections</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Overdue Payables</div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format($kpis['overdue_payables'], 2) }}</div>
                    <div class="small text-muted">Past due supplier obligations</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Posted Sales Invoices</div>
                    <div class="fs-4 fw-bold">{{ $kpis['posted_sales_invoices'] }}</div>
                    <div class="small text-muted">Revenue documents</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Posted Supplier Bills</div>
                    <div class="fs-4 fw-bold">{{ $kpis['posted_supplier_bills'] }}</div>
                    <div class="small text-muted">Payables documents</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Supplier Credits</div>
                    <div class="fs-4 fw-bold text-info">{{ number_format($kpis['supplier_credit_total'], 2) }}</div>
                    <div class="small text-muted">Unapplied offsets</div>
                </div>
            </div>
        </div>
    </div>

    {{-- CHARTS --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Revenue, Expenses and Profit Trend</div>
                <div class="card-body">
                    <canvas id="profitTrendChart" height="110"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Bank Balance Breakdown</div>
                <div class="card-body">
                    <canvas id="bankBreakdownChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- AR/AP + SNAPSHOT --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Receivables vs Payables Trend</div>
                <div class="card-body">
                    <canvas id="arApTrendChart" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Finance Snapshot</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Revenue This Month</span>
                        <strong>{{ number_format($kpis['revenue_month'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Expenses This Month</span>
                        <strong>{{ number_format($kpis['expenses_month'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Project Invoice Total This Month</span>
                        <strong>{{ number_format($kpis['project_invoice_total'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Receivables less Payables</span>
                        <strong class="{{ ($kpis['accounts_receivable'] - $kpis['accounts_payable']) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($kpis['accounts_receivable'] - $kpis['accounts_payable'], 2) }}
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Cash less Payables</span>
                        <strong class="{{ ($kpis['cash_balance'] - $kpis['accounts_payable']) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($kpis['cash_balance'] - $kpis['accounts_payable'], 2) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RISK + LARGEST EXPOSURES --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold text-danger">Overdue Financial Risks</div>
                <div class="card-body">
                    <h6 class="mb-2">Overdue Customer Invoices</h6>
                    <ul class="list-group mb-3">
                        @forelse($riskItems['overdue_customer_invoices'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->invoice_no ?? $row->invoice_number ?? ('INV-'.$row->id) }}</span>
                                <span>{{ $row->due_date ?? '—' }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No overdue customer invoices</li>
                        @endforelse
                    </ul>

                    <h6 class="mb-2">Overdue Supplier Bills</h6>
                    <ul class="list-group">
                        @forelse($riskItems['overdue_supplier_bills'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->bill_no ?? ('BILL-'.$row->id) }}</span>
                                <span>{{ $row->due_date ?? '—' }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No overdue supplier bills</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold text-warning">Largest Unpaid Exposures</div>
                <div class="card-body">
                    <h6 class="mb-2">Largest Unpaid Customer Invoices</h6>
                    <ul class="list-group mb-3">
                        @forelse($riskItems['largest_unpaid_invoices'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->invoice_no ?? $row->invoice_number ?? ('INV-'.$row->id) }}</span>
                                <span>{{ number_format((float)($row->balance_due ?? $row->balance ?? 0), 2) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No unpaid invoices</li>
                        @endforelse
                    </ul>

                    <h6 class="mb-2">Largest Unpaid Supplier Bills</h6>
                    <ul class="list-group">
                        @forelse($riskItems['largest_unpaid_bills'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->bill_no ?? ('BILL-'.$row->id) }}</span>
                                <span>{{ number_format((float)($row->balance_due ?? 0), 2) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No unpaid supplier bills</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- RECENT FINANCIAL ACTIVITY --}}
    <div class="row g-4">
        <div class="col-xl-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Recent Financial Activity</div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <h6>Recent Sales Invoices</h6>
                            <ul class="list-group">
                                @forelse($recentActivities['sales_invoices'] as $row)
                                    <li class="list-group-item">{{ $row->invoice_no ?? $row->invoice_number ?? ('INV-'.$row->id) }}</li>
                                @empty
                                    <li class="list-group-item text-muted">No recent sales invoices</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-lg-4">
                            <h6>Recent Supplier Bills</h6>
                            <ul class="list-group">
                                @forelse($recentActivities['supplier_bills'] as $row)
                                    <li class="list-group-item">{{ $row->bill_no ?? ('BILL-'.$row->id) }}</li>
                                @empty
                                    <li class="list-group-item text-muted">No recent supplier bills</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-lg-4">
                            <h6>Recent Supplier Credits</h6>
                            <ul class="list-group">
                                @forelse($recentActivities['supplier_credits'] as $row)
                                    <li class="list-group-item">{{ $row->credit_no ?? ('SCR-'.$row->id) }}</li>
                                @empty
                                    <li class="list-group-item text-muted">No recent supplier credits</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-lg-6">
                            <h6>Recent Project Invoices</h6>
                            <ul class="list-group">
                                @forelse($recentActivities['project_invoices'] as $row)
                                    <li class="list-group-item">{{ $row->invoice_no ?? ('PINV-'.$row->id) }}</li>
                                @empty
                                    <li class="list-group-item text-muted">No recent project invoices</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-lg-6">
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
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const profitTrend = @json($profitTrend);
const bankBreakdown = @json($bankBreakdown);
const arApTrend = @json($arApTrend);

new Chart(document.getElementById('profitTrendChart'), {
    type: 'bar',
    data: {
        labels: profitTrend.labels,
        datasets: [
            {
                label: 'Revenue',
                data: profitTrend.revenue,
                borderWidth: 1
            },
            {
                label: 'Expenses',
                data: profitTrend.expenses,
                borderWidth: 1
            },
            {
                label: 'Profit',
                data: profitTrend.profit,
                type: 'line',
                borderWidth: 2,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('bankBreakdownChart'), {
    type: 'doughnut',
    data: {
        labels: bankBreakdown.map(x => x.name),
        datasets: [{
            data: bankBreakdown.map(x => x.balance),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('arApTrendChart'), {
    type: 'line',
    data: {
        labels: arApTrend.labels,
        datasets: [
            {
                label: 'Accounts Receivable',
                data: arApTrend.ar,
                borderWidth: 2,
                fill: false
            },
            {
                label: 'Accounts Payable',
                data: arApTrend.ap,
                borderWidth: 2,
                fill: false
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