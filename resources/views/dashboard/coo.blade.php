@extends('layouts.master')

@section('title', 'COO Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 text-primary mb-0">COO / Operations Dashboard</h1>
            <small class="text-muted">Procurement, projects, inventory, production and service operations overview</small>
        </div>
    </div>

    {{-- KPI ROW 1 --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Open Requisitions</div>
                    <div class="fs-4 fw-bold">{{ $kpis['open_requisitions'] }}</div>
                    <div class="small text-muted">Demand intake</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Pending Quotes</div>
                    <div class="fs-4 fw-bold">{{ $kpis['pending_supplier_quotes'] }}</div>
                    <div class="small text-muted">Sourcing stage</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Open POs</div>
                    <div class="fs-4 fw-bold">{{ $kpis['open_purchase_orders'] }}</div>
                    <div class="small text-muted">Supplier commitments</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">GRNs Pending Billing</div>
                    <div class="fs-4 fw-bold text-warning">{{ $kpis['grn_pending_billing'] }}</div>
                    <div class="small text-muted">Finance follow-up</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Active Projects</div>
                    <div class="fs-4 fw-bold">{{ $kpis['active_projects'] }}</div>
                    <div class="small text-muted">Execution load</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Open Tickets</div>
                    <div class="fs-4 fw-bold">{{ $kpis['open_tickets'] }}</div>
                    <div class="small text-muted">Service workload</div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI ROW 2 --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Overdue Tickets</div>
                    <div class="fs-4 fw-bold text-danger">{{ $kpis['overdue_tickets'] }}</div>
                    <div class="small text-muted">Service delay risk</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Low Stock Items</div>
                    <div class="fs-4 fw-bold text-warning">{{ $kpis['low_stock_items'] }}</div>
                    <div class="small text-muted">Replenishment risk</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Open Work Orders</div>
                    <div class="fs-4 fw-bold">{{ $kpis['open_work_orders'] }}</div>
                    <div class="small text-muted">Production queue</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Late Milestones</div>
                    <div class="fs-4 fw-bold text-danger">{{ $kpis['late_milestones'] }}</div>
                    <div class="small text-muted">Project slippage</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Projects Over Budget</div>
                    <div class="fs-4 fw-bold text-danger">{{ $kpis['projects_over_budget'] }}</div>
                    <div class="small text-muted">Execution overspend</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted">Billable Hours This Month</div>
                    <div class="fs-4 fw-bold">{{ number_format($kpis['billable_hours_month'], 2) }}</div>
                    <div class="small text-muted">Delivery effort</div>
                </div>
            </div>
        </div>
    </div>

    {{-- CHARTS / SUMMARY --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Procurement Pipeline</div>
                <div class="card-body">
                    <canvas id="procurementPipelineChart" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Operational Snapshot</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Tasks</span>
                        <strong>{{ $projectExecution['open_tasks'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Completed Tasks</span>
                        <strong>{{ $projectExecution['completed_tasks'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Resolved Tickets</span>
                        <strong>{{ $supportSummary['resolved_tickets'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Total Products</span>
                        <strong>{{ $inventorySummary['total_products'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Total Stock Qty</span>
                        <strong>{{ number_format($inventorySummary['total_stock_qty'], 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FUNCTIONAL PANELS --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Projects Execution</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Active Projects</span>
                        <strong>{{ $projectExecution['active_projects'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Tasks</span>
                        <strong>{{ $projectExecution['open_tasks'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Completed Tasks</span>
                        <strong>{{ $projectExecution['completed_tasks'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Late Milestones</span>
                        <strong class="text-danger">{{ $projectExecution['late_milestones'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Inventory and Production</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Low Stock Items</span>
                        <strong class="text-warning">{{ $inventorySummary['low_stock_items'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Work Orders</span>
                        <strong>{{ $productionSummary['open_work_orders'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Completed Work Orders</span>
                        <strong>{{ $productionSummary['completed_work_orders'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Draft Work Orders</span>
                        <strong>{{ $productionSummary['draft_work_orders'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Support and Service</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Tickets</span>
                        <strong>{{ $supportSummary['open_tickets'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Overdue Tickets</span>
                        <strong class="text-danger">{{ $supportSummary['overdue_tickets'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Resolved / Closed Tickets</span>
                        <strong>{{ $supportSummary['resolved_tickets'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RISK SECTION --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold text-danger">Operational Risks</div>
                <div class="card-body">
                    <h6 class="mb-2">Late Projects</h6>
                    <ul class="list-group mb-3">
                        @forelse($riskItems['late_projects'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->project_name ?? $row->name ?? ('Project-'.$row->id) }}</span>
                                <span>{{ $row->end_date ?? '—' }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No late projects</li>
                        @endforelse
                    </ul>

                    <h6 class="mb-2">Late Milestones</h6>
                    <ul class="list-group">
                        @forelse($riskItems['late_milestones'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->milestone_name ?? ('Milestone-'.$row->id) }}</span>
                                <span>{{ $row->due_date ?? '—' }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No late milestones</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold text-warning">Bottlenecks and Delays</div>
                <div class="card-body">
                    <h6 class="mb-2">Low Stock Items</h6>
                    <ul class="list-group mb-3">
                        @forelse($riskItems['low_stock_items'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->product_name ?? $row->name ?? ('Item-'.$row->id) }}</span>
                                <span>{{ number_format((float)($row->product_stock_quantity ?? 0), 2) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No low stock items</li>
                        @endforelse
                    </ul>

                    <h6 class="mb-2">Overdue Tickets</h6>
                    <ul class="list-group mb-3">
                        @forelse($riskItems['overdue_tickets'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->subject ?? ('Ticket-'.$row->id) }}</span>
                                <span>{{ $row->due_date ?? '—' }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No overdue tickets</li>
                        @endforelse
                    </ul>

                    <h6 class="mb-2">Pending Goods Receipts</h6>
                    <ul class="list-group">
                        @forelse($riskItems['pending_goods_receipt'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $row->grn_no ?? ('GRN-'.$row->id) }}</span>
                                <span>{{ $row->status ?? '—' }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No pending goods receipts</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- RECENT ACTIVITY --}}
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Recent Operations Activity</div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <h6>Recent Purchase Orders</h6>
                            <ul class="list-group">
                                @forelse($recentActivities['purchase_orders'] as $row)
                                    <li class="list-group-item">{{ $row->po_no ?? $row->purchase_order_no ?? ('PO-'.$row->id) }}</li>
                                @empty
                                    <li class="list-group-item text-muted">No recent purchase orders</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-lg-4">
                            <h6>Recent Goods Receipts</h6>
                            <ul class="list-group">
                                @forelse($recentActivities['goods_receipts'] as $row)
                                    <li class="list-group-item">{{ $row->grn_no ?? ('GRN-'.$row->id) }}</li>
                                @empty
                                    <li class="list-group-item text-muted">No recent goods receipts</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-lg-4">
                            <h6>Recent Project Tasks</h6>
                            <ul class="list-group">
                                @forelse($recentActivities['project_tasks'] as $row)
                                    <li class="list-group-item">{{ $row->task_name ?? ('Task-'.$row->id) }}</li>
                                @empty
                                    <li class="list-group-item text-muted">No recent project tasks</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-lg-6">
                            <h6>Recent Timesheets</h6>
                            <ul class="list-group">
                                @forelse($recentActivities['timesheets'] as $row)
                                    <li class="list-group-item">TS-{{ $row->id }} @if(isset($row->entry_date)) | {{ $row->entry_date }} @endif</li>
                                @empty
                                    <li class="list-group-item text-muted">No recent timesheets</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-lg-6">
                            <h6>Recent Support Tickets</h6>
                            <ul class="list-group">
                                @forelse($recentActivities['tickets'] as $row)
                                    <li class="list-group-item">{{ $row->subject ?? ('Ticket-'.$row->id) }}</li>
                                @empty
                                    <li class="list-group-item text-muted">No recent support tickets</li>
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
const procurementPipeline = @json($procurementPipeline);

new Chart(document.getElementById('procurementPipelineChart'), {
    type: 'bar',
    data: {
        labels: procurementPipeline.labels,
        datasets: [{
            label: 'Count',
            data: procurementPipeline.values,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>
@endpush