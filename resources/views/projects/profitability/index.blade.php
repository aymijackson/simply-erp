@extends('layouts.master')

@section('title', 'Project Profitability Dashboard')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 text-primary mb-0">Project Profitability Dashboard</h1>
            <small class="text-muted">Projects / Executive Insights</small>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5 col-md-12">
                    <label class="form-label">Project</label>
                    <select id="project_id" class="form-control" style="width:100%"></select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">From</label>
                    <input type="date" id="date_from" class="form-control">
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">To</label>
                    <input type="date" id="date_to" class="form-control">
                </div>

                <div class="col-lg-3 col-md-12 d-flex gap-2">
                    <button type="button" class="btn btn-primary w-100" id="runBtn">
                        <i class="fas fa-play me-1"></i> Run
                    </button>

                    <button type="button" class="btn btn-outline-secondary w-100" id="resetBtn">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex flex-column">
                        <div class="small text-muted">Selected Project</div>
                        <div class="fs-5 fw-bold" id="projectLabel">—</div>
                        <div class="small text-muted mt-1" id="projectMeta">No project selected.</div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="small text-muted">Health Status</div>
                    <div class="mt-1">
                        <span class="badge bg-secondary fs-6" id="healthBadge">Not Rated</span>
                    </div>
                    <div class="small mt-1">Score: <span id="healthScore">0</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="text-muted small">Budget</div>
                    <div class="fs-4 fw-bold text-primary" id="kpiBudget">0.00</div>
                    <div class="small mt-1">Remaining: <span id="kpiRemainingBudget">0.00</span></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="text-muted small">Actual Cost</div>
                    <div class="fs-4 fw-bold text-danger" id="kpiActualCost">0.00</div>
                    <div class="small mt-1">Budget Used: <span id="kpiBudgetUsedPct">0.00%</span></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="text-muted small">Revenue Basis</div>
                    <div class="fs-4 fw-bold text-success" id="kpiRevenue">0.00</div>
                    <div class="small mt-1">Billed: <span id="kpiBilledRevenue">0.00</span></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="text-muted small">Gross Profit</div>
                    <div class="fs-4 fw-bold" id="kpiGrossProfit">0.00</div>
                    <div class="small mt-1">Margin: <span id="kpiGrossMargin">0.00%</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Labour Cost</div>
                    <div class="fs-5 fw-bold" id="kpiLabourCost">0.00</div>
                    <div class="small mt-1">Labour Ratio: <span id="kpiLabourRatio">0.00%</span></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Non-Labour Cost</div>
                    <div class="fs-5 fw-bold" id="kpiNonLabourCost">0.00</div>
                    <div class="small mt-1">Burn Rate / Day: <span id="kpiBurnRate">0.00</span></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Hours</div>
                    <div class="fs-5 fw-bold" id="kpiTotalHours">0.00</div>
                    <div class="small mt-1">Billable Hours: <span id="kpiBillableHours">0.00</span></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Progress</div>
                    <div class="fs-5 fw-bold" id="kpiTaskProgress">0.00%</div>
                    <div class="small mt-1">Milestones: <span id="kpiMilestoneProgress">0.00%</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Budget vs Actual Overview</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <tbody>
                                <tr>
                                    <th style="width:35%">Budget Amount</th>
                                    <td class="text-end" id="tblBudgetAmount">0.00</td>
                                </tr>
                                <tr>
                                    <th>Actual Cost</th>
                                    <td class="text-end" id="tblActualCost">0.00</td>
                                </tr>
                                <tr>
                                    <th>Remaining Budget</th>
                                    <td class="text-end" id="tblRemainingBudget">0.00</td>
                                </tr>
                                <tr>
                                    <th>Budget Variance</th>
                                    <td class="text-end" id="tblBudgetVariance">0.00</td>
                                </tr>
                                <tr>
                                    <th>Recognised Revenue</th>
                                    <td class="text-end" id="tblRecognisedRevenue">0.00</td>
                                </tr>
                                <tr>
                                    <th>Billed Revenue</th>
                                    <td class="text-end" id="tblBilledRevenue">0.00</td>
                                </tr>
                                <tr class="fw-bold">
                                    <th>Gross Profit</th>
                                    <td class="text-end" id="tblGrossProfit">0.00</td>
                                </tr>
                                <tr class="fw-bold">
                                    <th>Gross Margin %</th>
                                    <td class="text-end" id="tblGrossMargin">0.00%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Execution Snapshot</div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Task Completion</span>
                            <strong id="taskCompletionLbl">0.00%</strong>
                        </div>
                        <div class="progress" style="height: 18px;">
                            <div class="progress-bar bg-primary" id="taskCompletionBar" style="width:0%">0%</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Milestone Completion</span>
                            <strong id="milestoneCompletionLbl">0.00%</strong>
                        </div>
                        <div class="progress" style="height: 18px;">
                            <div class="progress-bar bg-success" id="milestoneCompletionBar" style="width:0%">0%</div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Billable Hours Ratio</span>
                            <strong id="billableRatioLbl">0.00%</strong>
                        </div>
                        <div class="progress" style="height: 18px;">
                            <div class="progress-bar bg-info" id="billableRatioBar" style="width:0%">0%</div>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="small text-muted">Tasks</div>
                            <div class="fw-bold"><span id="completedTasksLbl">0</span> / <span id="totalTasksLbl">0</span></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Milestones</div>
                            <div class="fw-bold"><span id="completedMilestonesLbl">0</span> / <span id="totalMilestonesLbl">0</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Top Cost Categories</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th class="text-end" style="width:180px;">Amount</th>
                                <th class="text-end" style="width:180px;">% of Actual Cost</th>
                            </tr>
                        </thead>
                        <tbody id="costCategoryBody">
                            <tr>
                                <td colspan="3" class="text-center text-muted">No cost data found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .select2-container { width:100% !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataUrl = "{{ route('admin.project_profitability.data') }}";
    const projectsUrl = "{{ route('admin.project_profitability.lookups.projects') }}";

    const projectId = $('#project_id');
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const runBtn = document.getElementById('runBtn');
    const resetBtn = document.getElementById('resetBtn');

    function fmt(n) {
        return Number(n || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function pct(n) {
        return Number(n || 0).toFixed(2) + '%';
    }

    function showLoading(message = 'Loading dashboard...') {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                title: 'Loading...',
                text: message,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => window.Swal.showLoading()
            });
        }
    }

    function closeLoading() {
        if (window.Swal && typeof window.Swal.close === 'function') {
            window.Swal.close();
        }
    }

    function showError(message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message || 'Something went wrong.'
            });
        } else {
            alert(message || 'Something went wrong.');
        }
    }

    function initProjectSelect() {
        projectId.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select project...',
            allowClear: true,
            ajax: {
                url: projectsUrl,
                dataType: 'json',
                delay: 200,
                data: params => ({ q: params.term || '' }),
                processResults: data => data,
                cache: true
            }
        });
    }

    function resetCards() {
        $('#projectLabel').text('—');
        $('#projectMeta').text('No project selected.');
        $('#healthBadge').removeClass().addClass('badge bg-secondary fs-6').text('Not Rated');
        $('#healthScore').text('0');

        $('#kpiBudget, #kpiRemainingBudget, #kpiActualCost, #kpiRevenue, #kpiBilledRevenue, #kpiGrossProfit').text('0.00');
        $('#kpiBudgetUsedPct, #kpiGrossMargin, #kpiLabourRatio, #kpiTaskProgress, #kpiMilestoneProgress').text('0.00%');
        $('#kpiLabourCost, #kpiNonLabourCost, #kpiBurnRate, #kpiTotalHours, #kpiBillableHours').text('0.00');

        $('#tblBudgetAmount, #tblActualCost, #tblRemainingBudget, #tblBudgetVariance, #tblRecognisedRevenue, #tblBilledRevenue, #tblGrossProfit').text('0.00');
        $('#tblGrossMargin').text('0.00%');

        $('#taskCompletionLbl, #milestoneCompletionLbl, #billableRatioLbl').text('0.00%');
        $('#taskCompletionBar').css('width', '0%').text('0%');
        $('#milestoneCompletionBar').css('width', '0%').text('0%');
        $('#billableRatioBar').css('width', '0%').text('0%');

        $('#completedTasksLbl, #totalTasksLbl, #completedMilestonesLbl, #totalMilestonesLbl').text('0');

        $('#costCategoryBody').html(`
            <tr>
                <td colspan="3" class="text-center text-muted">No cost data found.</td>
            </tr>
        `);
    }

    function buildQuery() {
        const params = new URLSearchParams();
        params.set('project_id', projectId.val() || '');
        params.set('date_from', dateFrom.value || '');
        params.set('date_to', dateTo.value || '');
        return params.toString();
    }

    async function fetchDashboard() {
        if (!projectId.val()) {
            showError('Please select a project.');
            return;
        }

        try {
            showLoading('Preparing profitability dashboard...');

            const response = await fetch(`${dataUrl}?${buildQuery()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load dashboard.');
            }

            const data = await response.json();
            const project = data.project || {};
            const kpi = data.kpis || {};
            const health = data.health || {};
            const categories = data.cost_by_category || [];

            $('#projectLabel').text(
                ((project.project_code || '') ? (project.project_code + ' - ') : '') + (project.project_name || '—')
            );
            $('#projectMeta').text(
                `Status: ${project.status || '—'} | Start: ${project.start_date || '—'} | End: ${project.end_date || '—'}`
            );

            $('#healthBadge')
                .removeClass()
                .addClass(`badge bg-${health.class || 'secondary'} fs-6`)
                .text(health.label || 'Not Rated');
            $('#healthScore').text(health.score || 0);

            $('#kpiBudget').text(fmt(kpi.budget_amount));
            $('#kpiRemainingBudget').text(fmt(kpi.remaining_budget));
            $('#kpiActualCost').text(fmt(kpi.actual_cost));
            $('#kpiBudgetUsedPct').text(pct(kpi.budget_used_percent));
            $('#kpiRevenue').text(fmt(kpi.profit_revenue_basis));
            $('#kpiBilledRevenue').text(fmt(kpi.billed_revenue));
            $('#kpiGrossProfit').text(fmt(kpi.gross_profit));
            $('#kpiGrossMargin').text(pct(kpi.gross_margin_percent));
            $('#kpiLabourCost').text(fmt(kpi.labour_cost));
            $('#kpiNonLabourCost').text(fmt(kpi.non_labour_cost));
            $('#kpiLabourRatio').text(pct(kpi.labour_ratio_percent));
            $('#kpiBurnRate').text(fmt(kpi.burn_rate_per_day));
            $('#kpiTotalHours').text(fmt(kpi.total_hours));
            $('#kpiBillableHours').text(fmt(kpi.billable_hours));
            $('#kpiTaskProgress').text(pct(kpi.task_completion_percent));
            $('#kpiMilestoneProgress').text(pct(kpi.milestone_completion_percent));

            $('#tblBudgetAmount').text(fmt(kpi.budget_amount));
            $('#tblActualCost').text(fmt(kpi.actual_cost));
            $('#tblRemainingBudget').text(fmt(kpi.remaining_budget));
            $('#tblBudgetVariance').text(fmt(kpi.budget_variance));
            $('#tblRecognisedRevenue').text(fmt(kpi.recognised_revenue));
            $('#tblBilledRevenue').text(fmt(kpi.billed_revenue));
            $('#tblGrossProfit').text(fmt(kpi.gross_profit));
            $('#tblGrossMargin').text(pct(kpi.gross_margin_percent));

            $('#taskCompletionLbl').text(pct(kpi.task_completion_percent));
            $('#milestoneCompletionLbl').text(pct(kpi.milestone_completion_percent));
            $('#billableRatioLbl').text(pct(kpi.billable_ratio_percent));

            $('#taskCompletionBar').css('width', `${kpi.task_completion_percent || 0}%`).text(pct(kpi.task_completion_percent));
            $('#milestoneCompletionBar').css('width', `${kpi.milestone_completion_percent || 0}%`).text(pct(kpi.milestone_completion_percent));
            $('#billableRatioBar').css('width', `${kpi.billable_ratio_percent || 0}%`).text(pct(kpi.billable_ratio_percent));

            $('#completedTasksLbl').text(kpi.completed_tasks || 0);
            $('#totalTasksLbl').text(kpi.total_tasks || 0);
            $('#completedMilestonesLbl').text(kpi.completed_milestones || 0);
            $('#totalMilestonesLbl').text(kpi.total_milestones || 0);

            if (!categories.length) {
                $('#costCategoryBody').html(`
                    <tr>
                        <td colspan="3" class="text-center text-muted">No cost data found.</td>
                    </tr>
                `);
            } else {
                let html = '';
                categories.forEach(row => {
                    html += `
                        <tr>
                            <td>${row.category || ''}</td>
                            <td class="text-end">${fmt(row.amount)}</td>
                            <td class="text-end">${pct(row.percent)}</td>
                        </tr>
                    `;
                });
                $('#costCategoryBody').html(html);
            }

            closeLoading();
        } catch (error) {
            closeLoading();
            resetCards();
            showError(error.message || 'Failed to load dashboard.');
        }
    }

    runBtn.addEventListener('click', fetchDashboard);

    resetBtn.addEventListener('click', function () {
        projectId.val(null).trigger('change');
        dateFrom.value = '';
        dateTo.value = '';
        resetCards();
    });

    initProjectSelect();
    resetCards();
});
</script>
@endpush