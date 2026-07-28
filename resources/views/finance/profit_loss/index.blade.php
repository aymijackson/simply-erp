@extends('layouts.master')

@section('title', 'Profit & Loss')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-primary">Profit &amp; Loss</h1>
            <small class="text-muted">Finance / Reports</small>
        </div>

        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-danger" id="pdfBtn">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </button>
            <button type="button" class="btn btn-outline-success" id="excelBtn">
                <i class="fas fa-file-excel me-1"></i> Excel
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-xl-2 col-md-6">
                    <label class="form-label">From</label>
                    <input type="date" id="date_from" class="form-control" value="{{ $dateFrom ?? date('Y-m-01') }}">
                </div>

                <div class="col-xl-2 col-md-6">
                    <label class="form-label">To</label>
                    <input type="date" id="date_to" class="form-control" value="{{ $dateTo ?? date('Y-m-t') }}">
                </div>

                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Posted Only</label>
                    <select id="posted_only" class="form-control">
                        <option value="1" selected>Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="form-label">Comparison Mode</label>
                    <select id="comparison_mode" class="form-control">
                        <option value="equivalent" selected>Previous equivalent period</option>
                        <option value="previous_month">Previous month</option>
                        <option value="same_period_last_year">Same period last year</option>
                    </select>
                </div>

                <div class="col-xl-3 col-md-12 d-flex gap-2">
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

    {{-- KPI CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 pnl-kpi-card">
                <div class="card-body">
                    <div class="text-muted small">Revenue</div>
                    <div class="fs-4 fw-bold text-success" id="kpiIncome">0.00</div>
                    <div class="small mt-1" id="kpiIncomePrev">Comparison revenue: 0.00</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 pnl-kpi-card">
                <div class="card-body">
                    <div class="text-muted small">Cost of Sales</div>
                    <div class="fs-4 fw-bold text-warning" id="kpiCogs">0.00</div>
                    <div class="small mt-1" id="kpiGross">Gross Profit: 0.00</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 pnl-kpi-card">
                <div class="card-body">
                    <div class="text-muted small">Operating Expenses</div>
                    <div class="fs-4 fw-bold text-danger" id="kpiExpenses">0.00</div>
                    <div class="small mt-1" id="kpiExpenseRatio">Expense Ratio: 0.00%</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 pnl-kpi-card">
                <div class="card-body">
                    <div class="text-muted small">Net Profit</div>
                    <div class="fs-4 fw-bold" id="kpiNetProfit">0.00</div>
                    <div class="small mt-1" id="kpiNetMargin">Net Margin: 0.00%</div>
                </div>
            </div>
        </div>
    </div>

    {{-- COMPARISON STRIP --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 pnl-mini-card">
                <div class="card-body text-center">
                    <div class="text-muted small">Current Period</div>
                    <div class="fw-bold" id="lblCurrentPeriod">—</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 pnl-mini-card">
                <div class="card-body text-center">
                    <div class="text-muted small">Comparison Period</div>
                    <div class="fw-bold" id="lblPreviousPeriod">—</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 pnl-mini-card">
                <div class="card-body text-center">
                    <div class="text-muted small">Net Profit Change</div>
                    <div class="fw-bold d-flex align-items-center justify-content-center gap-2" id="lblNetChangeWrap">
                        <span id="lblNetChangeIcon" class="pnl-change-icon text-muted">
                            <i class="fas fa-minus"></i>
                        </span>
                        <span id="lblNetChange">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 pnl-mini-card">
                <div class="card-body text-center">
                    <div class="text-muted small">Net Profit Change %</div>
                    <div class="fw-bold" id="lblNetChangePct">N/A</div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN TABLES --}}
    <div class="row g-4">
        <div class="col-xxl-4 col-lg-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                    <span>Income</span>
                    <span class="badge bg-success-subtle text-success-emphasis" id="incomeCountBadge">0 lines</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive pnl-table-wrap">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light pnl-sticky-head">
                                <tr>
                                    <th style="width:58%;">Account</th>
                                    <th class="text-end" style="width:24%;">Amount</th>
                                    <th class="text-end" style="width:18%;">% Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="incomeBody">
                                <tr><td colspan="3" class="text-center text-muted">No income accounts found for the selected period.</td></tr>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total Income</td>
                                    <td class="text-end" id="incomeTotal">0.00</td>
                                    <td class="text-end">100.00%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-lg-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                    <span>Cost of Sales (COGS)</span>
                    <span class="badge bg-warning-subtle text-dark" id="cogsCountBadge">0 lines</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive pnl-table-wrap">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light pnl-sticky-head">
                                <tr>
                                    <th style="width:58%;">Account</th>
                                    <th class="text-end" style="width:24%;">Amount</th>
                                    <th class="text-end" style="width:18%;">% Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="cogsBody">
                                <tr><td colspan="3" class="text-center text-muted">No cost-of-sales postings found for this period.</td></tr>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total COGS</td>
                                    <td class="text-end" id="cogsTotal">0.00</td>
                                    <td class="text-end" id="cogsRevenuePct">0.00%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="text-end px-3 py-3 fs-5 border-top bg-light-subtle">
                        Gross Profit: <strong id="grossProfitLbl">0.00</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-lg-12">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                    <span>Expenses</span>
                    <span class="badge bg-danger-subtle text-danger-emphasis" id="expenseCountBadge">0 lines</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive pnl-table-wrap">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light pnl-sticky-head">
                                <tr>
                                    <th style="width:58%;">Account</th>
                                    <th class="text-end" style="width:24%;">Amount</th>
                                    <th class="text-end" style="width:18%;">% Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="expenseBody">
                                <tr><td colspan="3" class="text-center text-muted">No expense accounts found for the selected period.</td></tr>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total Expenses</td>
                                    <td class="text-end" id="expenseTotal">0.00</td>
                                    <td class="text-end" id="expenseRevenuePct">0.00%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="text-end px-3 py-3 fs-5 border-top bg-light-subtle">
                        Net Profit: <strong id="netProfitLblBottom">0.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="drillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Account Drilldown</h5>
                    <small class="text-muted" id="drillPeriodLbl"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 pt-3">
                    <h6 id="drillTitle" class="mb-3 text-primary"></h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light pnl-sticky-head">
                            <tr>
                                <th style="width:120px;">Date</th>
                                <th style="width:160px;">Entry No</th>
                                <th style="width:160px;">Reference</th>
                                <th>Memo</th>
                                <th class="text-end" style="width:140px;">Debit</th>
                                <th class="text-end" style="width:140px;">Credit</th>
                            </tr>
                        </thead>
                        <tbody id="drillBody">
                            <tr><td colspan="6" class="text-center text-muted">No data</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-end px-3 py-3 fw-bold border-top bg-light-subtle">
                    Total Debit: <span id="drillDebitTotal">0.00</span>
                    &nbsp; | &nbsp;
                    Total Credit: <span id="drillCreditTotal">0.00</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .pnl-kpi-card,
    .pnl-mini-card {
        transition: transform .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    .pnl-kpi-card:hover,
    .pnl-mini-card:hover {
        transform: translateY(-1px);
    }

    .pnl-table-wrap {
        max-height: 520px;
        overflow: auto;
    }

    .pnl-sticky-head th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8f9fa !important;
    }

    .pl-group-row td {
        background: #f8f9fc;
        font-weight: 700;
        cursor: pointer;
        user-select: none;
        transition: background-color .15s ease-in-out;
    }

    .pl-group-row:hover td {
        background: #e9eefc;
    }

    .pl-child-row {
        display: none;
    }

    .pl-child-name {
        padding-left: 2.5rem !important;
    }

    .pl-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        margin-right: 8px;
        text-align: center;
        font-size: 15px;
        font-weight: 700;
        color: #2563eb;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.08);
    }

    .pl-toggle.empty {
        visibility: hidden;
    }

    .pl-account-link {
        color: inherit;
        text-decoration: none;
        word-break: break-word;
    }

    .pl-account-link:hover {
        text-decoration: underline;
    }

    .pnl-change-icon {
        min-width: 22px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
    }

    .table td,
    .table th {
        white-space: normal;
        vertical-align: middle;
    }

    .table tfoot td {
        background: #fafafa;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataUrl = "{{ route('admin.finance.reports.profit_loss.data') }}";
    const pdfUrl = "{{ route('admin.finance.reports.profit_loss.pdf') }}";
    const excelUrl = "{{ route('admin.finance.reports.profit_loss.excel') }}";
    const drillUrlBase = "{{ url('admin/finance/reports/account-drilldown') }}";

    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const postedOnly = document.getElementById('posted_only');
    const comparisonMode = document.getElementById('comparison_mode');
    const runBtn = document.getElementById('runBtn');
    const resetBtn = document.getElementById('resetBtn');
    const pdfBtn = document.getElementById('pdfBtn');
    const excelBtn = document.getElementById('excelBtn');

    const incomeBody = document.getElementById('incomeBody');
    const cogsBody = document.getElementById('cogsBody');
    const expenseBody = document.getElementById('expenseBody');

    const incomeTotal = document.getElementById('incomeTotal');
    const cogsTotal = document.getElementById('cogsTotal');
    const expenseTotal = document.getElementById('expenseTotal');
    const cogsRevenuePct = document.getElementById('cogsRevenuePct');
    const expenseRevenuePct = document.getElementById('expenseRevenuePct');

    const grossProfitLbl = document.getElementById('grossProfitLbl');
    const netProfitLblBottom = document.getElementById('netProfitLblBottom');

    const kpiIncome = document.getElementById('kpiIncome');
    const kpiIncomePrev = document.getElementById('kpiIncomePrev');
    const kpiCogs = document.getElementById('kpiCogs');
    const kpiGross = document.getElementById('kpiGross');
    const kpiExpenses = document.getElementById('kpiExpenses');
    const kpiExpenseRatio = document.getElementById('kpiExpenseRatio');
    const kpiNetProfit = document.getElementById('kpiNetProfit');
    const kpiNetMargin = document.getElementById('kpiNetMargin');

    const lblCurrentPeriod = document.getElementById('lblCurrentPeriod');
    const lblPreviousPeriod = document.getElementById('lblPreviousPeriod');
    const lblNetChange = document.getElementById('lblNetChange');
    const lblNetChangePct = document.getElementById('lblNetChangePct');
    const lblNetChangeIcon = document.getElementById('lblNetChangeIcon');

    const incomeCountBadge = document.getElementById('incomeCountBadge');
    const cogsCountBadge = document.getElementById('cogsCountBadge');
    const expenseCountBadge = document.getElementById('expenseCountBadge');

    const drillTitle = document.getElementById('drillTitle');
    const drillPeriodLbl = document.getElementById('drillPeriodLbl');
    const drillBody = document.getElementById('drillBody');
    const drillDebitTotal = document.getElementById('drillDebitTotal');
    const drillCreditTotal = document.getElementById('drillCreditTotal');
    const drillModalEl = document.getElementById('drillModal');

    function fmt(n) {
        return Number(n || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function pct(value, base) {
        const v = Number(value || 0);
        const b = Number(base || 0);
        if (!b) return '0.00%';
        return ((v / b) * 100).toFixed(2) + '%';
    }

    function lineLabel(n) {
        const count = Number(n || 0);
        return `${count} ${count === 1 ? 'line' : 'lines'}`;
    }

    function emptyRow(colspan = 3, message = 'No data') {
        return `<tr><td colspan="${colspan}" class="text-center text-muted">${message}</td></tr>`;
    }

    function showLoading(message = 'Preparing Profit & Loss report') {
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

    function queryString() {
        return new URLSearchParams({
            date_from: dateFrom.value || '',
            date_to: dateTo.value || '',
            posted_only: postedOnly.value || '1',
            comparison_mode: comparisonMode.value || 'equivalent'
        }).toString();
    }

    function openDrillModal() {
        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(drillModalEl);
            modal.show();
            return;
        }

        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery('#drillModal').modal('show');
        }
    }

    function renderSection(groups, sectionKey, revenueBase, emptyMessage) {
        if (!groups || !groups.length) return emptyRow(3, emptyMessage);

        let html = '';

        groups.forEach((group, i) => {
            const groupCode = group.group_code || '';
            const groupName = group.group_name || '';
            const rowKey = `${sectionKey}_${i}`;

            const visibleChildren = (group.children || []).filter(child => {
                return !(
                    String(child.code || '') === String(groupCode) &&
                    String(child.name || '') === String(groupName)
                );
            });

            const hasChildren = visibleChildren.length > 0;

            html += `
                <tr class="pl-group-row" data-row-key="${rowKey}" data-open="0">
                    <td>
                        <span class="pl-toggle ${hasChildren ? '' : 'empty'}">${hasChildren ? '&#9656;' : '&#9679;'}</span>
                        <span>${groupCode} - ${groupName}</span>
                    </td>
                    <td class="text-end">${fmt(group.total)}</td>
                    <td class="text-end">${pct(group.total, revenueBase)}</td>
                </tr>
            `;

            visibleChildren.forEach(child => {
                html += `
                    <tr class="pl-child-row" data-parent-key="${rowKey}">
                        <td class="pl-child-name">
                            <a href="#" class="pl-account-link accountDrill"
                               data-id="${child.id ?? ''}"
                               data-name="${(child.code || '')} - ${(child.name || '')}">
                                ${child.code || ''} - ${child.name || ''}
                            </a>
                        </td>
                        <td class="text-end">${fmt(child.amount)}</td>
                        <td class="text-end">${pct(child.amount, revenueBase)}</td>
                    </tr>
                `;
            });
        });

        return html;
    }

    function setChangeIndicator(netChange) {
        const value = Number(netChange || 0);

        lblNetChangeIcon.classList.remove('text-success', 'text-danger', 'text-muted');

        if (value > 0) {
            lblNetChangeIcon.classList.add('text-success');
            lblNetChangeIcon.innerHTML = '<i class="fas fa-arrow-up"></i>';
        } else if (value < 0) {
            lblNetChangeIcon.classList.add('text-danger');
            lblNetChangeIcon.innerHTML = '<i class="fas fa-arrow-down"></i>';
        } else {
            lblNetChangeIcon.classList.add('text-muted');
            lblNetChangeIcon.innerHTML = '<i class="fas fa-minus"></i>';
        }
    }

    function setTotals(meta) {
        const revenue = Number(meta.income || 0);
        const prevRevenue = Number(meta.previous_income || 0);
        const cogs = Number(meta.cogs || 0);
        const expenses = Number(meta.expenses || 0);
        const grossProfit = Number(meta.grossProfit || 0);
        const netProfit = Number(meta.netProfit || 0);
        const netChange = Number(meta.netProfitChange || 0);

        incomeTotal.textContent = fmt(revenue);
        cogsTotal.textContent = fmt(cogs);
        expenseTotal.textContent = fmt(expenses);

        cogsRevenuePct.textContent = pct(cogs, revenue);
        expenseRevenuePct.textContent = pct(expenses, revenue);

        grossProfitLbl.textContent = fmt(grossProfit);
        netProfitLblBottom.textContent = fmt(netProfit);

        kpiIncome.textContent = fmt(revenue);
        kpiIncomePrev.textContent = `Comparison revenue: ${fmt(prevRevenue)}`;
        kpiCogs.textContent = fmt(cogs);
        kpiGross.textContent = `Gross Profit: ${fmt(grossProfit)}`;
        kpiExpenses.textContent = fmt(expenses);
        kpiExpenseRatio.textContent = `Expense Ratio: ${pct(expenses, revenue)}`;
        kpiNetProfit.textContent = fmt(netProfit);
        kpiNetMargin.textContent = `Net Margin: ${pct(netProfit, revenue)}`;

        kpiNetProfit.classList.remove('text-success', 'text-danger');
        kpiNetProfit.classList.add(netProfit >= 0 ? 'text-success' : 'text-danger');

        lblCurrentPeriod.textContent = meta.current_period_label || '—';
        lblPreviousPeriod.textContent = meta.previous_period_label || '—';
        lblNetChange.textContent = fmt(netChange);
        lblNetChangePct.textContent = meta.netProfitChangePctLabel || 'N/A';

        lblNetChange.classList.remove('text-success', 'text-danger');
        lblNetChangePct.classList.remove('text-success', 'text-danger');

        if (netChange > 0) {
            lblNetChange.classList.add('text-success');
            lblNetChangePct.classList.add('text-success');
        } else if (netChange < 0) {
            lblNetChange.classList.add('text-danger');
            lblNetChangePct.classList.add('text-danger');
        }

        setChangeIndicator(netChange);

        incomeCountBadge.textContent = lineLabel(meta.income_lines || 0);
        cogsCountBadge.textContent = lineLabel(meta.cogs_lines || 0);
        expenseCountBadge.textContent = lineLabel(meta.expense_lines || 0);
    }

    function resetDisplay() {
        incomeBody.innerHTML = emptyRow(3, 'No income accounts found for the selected period.');
        cogsBody.innerHTML = emptyRow(3, 'No cost-of-sales postings found for this period.');
        expenseBody.innerHTML = emptyRow(3, 'No expense accounts found for the selected period.');

        setTotals({
            income: 0,
            previous_income: 0,
            cogs: 0,
            expenses: 0,
            grossProfit: 0,
            netProfit: 0,
            netProfitChange: 0,
            netProfitChangePctLabel: 'N/A',
            current_period_label: '—',
            previous_period_label: '—',
            income_lines: 0,
            cogs_lines: 0,
            expense_lines: 0
        });
    }

    function toggleGroupRow(row) {
        const rowKey = row.getAttribute('data-row-key');
        if (!rowKey) return;

        const children = document.querySelectorAll(`tr.pl-child-row[data-parent-key="${rowKey}"]`);
        if (!children.length) return;

        const icon = row.querySelector('.pl-toggle');
        const isOpen = row.getAttribute('data-open') === '1';
        const newOpen = !isOpen;

        children.forEach(child => {
            child.style.display = newOpen ? 'table-row' : 'none';
        });

        row.setAttribute('data-open', newOpen ? '1' : '0');

        if (icon && !icon.classList.contains('empty')) {
            icon.innerHTML = newOpen ? '&#9662;' : '&#9656;';
        }
    }

    async function fetchReport(withLoader = true) {
        try {
            if (withLoader) showLoading('Preparing Profit & Loss report');

            const response = await fetch(`${dataUrl}?${queryString()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load report.');
            }

            const data = await response.json();
            const meta = data.meta || {};
            const sections = data.sections || {};
            const revenueBase = Number(meta.income || 0);

            incomeBody.innerHTML = renderSection(
                sections.income || [],
                'income',
                revenueBase,
                'No income accounts found for the selected period.'
            );

            cogsBody.innerHTML = renderSection(
                sections.cogs || [],
                'cogs',
                revenueBase,
                'No cost-of-sales postings found for this period.'
            );

            expenseBody.innerHTML = renderSection(
                sections.expenses || [],
                'expenses',
                revenueBase,
                'No expense accounts found for the selected period.'
            );

            setTotals(meta);
            closeLoading();
        } catch (error) {
            closeLoading();
            resetDisplay();
            showError(error.message || 'Failed to load report.');
        }
    }

    async function fetchDrilldown(accountId, accountName) {
        if (!accountId) {
            showError('This account has no drilldown ID.');
            return;
        }

        try {
            showLoading('Loading account drilldown');

            const response = await fetch(`${drillUrlBase}/${accountId}?${queryString()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load drilldown.');
            }

            const rows = await response.json();
            drillTitle.textContent = accountName || 'Account Drilldown';
            drillPeriodLbl.textContent = `${dateFrom.value || ''} to ${dateTo.value || ''}`;

            let totalDebit = 0;
            let totalCredit = 0;

            if (!rows || !rows.length) {
                drillBody.innerHTML = emptyRow(6, 'No ledger entries found for this account and period.');
            } else {
                let html = '';
                rows.forEach(r => {
                    totalDebit += Number(r.debit || 0);
                    totalCredit += Number(r.credit || 0);

                    html += `
                        <tr>
                            <td>${r.entry_date || ''}</td>
                            <td>${r.entry_no || r.entry_number || ''}</td>
                            <td>${r.reference || ''}</td>
                            <td>${r.memo || ''}</td>
                            <td class="text-end">${fmt(r.debit)}</td>
                            <td class="text-end">${fmt(r.credit)}</td>
                        </tr>
                    `;
                });
                drillBody.innerHTML = html;
            }

            drillDebitTotal.textContent = fmt(totalDebit);
            drillCreditTotal.textContent = fmt(totalCredit);

            closeLoading();
            openDrillModal();
        } catch (error) {
            closeLoading();
            showError(error.message || 'Failed to load drilldown.');
        }
    }

    document.addEventListener('click', function (e) {
        const drillLink = e.target.closest('.accountDrill');
        if (drillLink) {
            e.preventDefault();
            e.stopPropagation();
            fetchDrilldown(drillLink.dataset.id, drillLink.dataset.name);
            return;
        }

        const row = e.target.closest('.pl-group-row');
        if (!row) return;
        toggleGroupRow(row);
    });

    runBtn.addEventListener('click', function () {
        fetchReport(true);
    });

    resetBtn.addEventListener('click', function () {
        const now = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        dateFrom.value = first.toISOString().slice(0, 10);
        dateTo.value = last.toISOString().slice(0, 10);
        postedOnly.value = '1';
        comparisonMode.value = 'equivalent';

        fetchReport(true);
    });

    pdfBtn.addEventListener('click', function () {
        window.open(`${pdfUrl}?${queryString()}`, '_blank');
    });

    excelBtn.addEventListener('click', function () {
        window.location.href = `${excelUrl}?${queryString()}`;
    });

    fetchReport(false);
});
</script>
@endpush