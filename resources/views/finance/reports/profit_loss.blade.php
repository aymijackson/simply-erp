@extends('layouts.master')

@section('title', 'Profit & Loss')

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-2 text-primary">Profit &amp; Loss</h1>
    <p class="text-muted mb-4">Period report</p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" id="date_from" class="form-control" value="{{ $dateFrom ?? date('Y-m-01') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" id="date_to" class="form-control" value="{{ $dateTo ?? date('Y-m-t') }}">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="button" class="btn btn-primary" id="runBtn">
                        <i class="fas fa-play me-1"></i> Run
                    </button>

                    <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>

                <div class="col-md-3 text-md-end">
                    <div class="fs-4">
                        Net Profit:
                        <strong id="netProfitLbl">0.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Income</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Account</th>
                                <th class="text-end" style="width:180px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="incomeBody">
                            <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Income</td>
                                <td class="text-end" id="incomeTotal">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Expenses</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Account</th>
                                <th class="text-end" style="width:180px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="expenseBody">
                            <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Expenses</td>
                                <td class="text-end" id="expenseTotal">0.00</td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="text-end mt-3 fs-5">
                        Net Profit: <strong id="netProfitLblBottom">0.00</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Cost of Sales (COGS)</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Account</th>
                                <th class="text-end" style="width:180px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="cogsBody">
                            <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total COGS</td>
                                <td class="text-end" id="cogsTotal">0.00</td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="text-end mt-3 fs-5">
                        Gross Profit: <strong id="grossProfitLbl">0.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .pl-group-row td {
        background: #f8f9fc;
        font-weight: 700;
        cursor: pointer;
    }

    .pl-child-row {
        display: none;
    }

    .pl-child-name {
        padding-left: 36px;
    }

    .pl-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        margin-right: 8px;
        font-size: 12px;
    }

    .pl-toggle.empty {
        visibility: hidden;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataUrl = "{{ url('admin/finance/reports/profit-loss/data') }}";

    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const runBtn = document.getElementById('runBtn');
    const resetBtn = document.getElementById('resetBtn');

    const incomeBody = document.getElementById('incomeBody');
    const cogsBody = document.getElementById('cogsBody');
    const expenseBody = document.getElementById('expenseBody');

    const incomeTotal = document.getElementById('incomeTotal');
    const cogsTotal = document.getElementById('cogsTotal');
    const expenseTotal = document.getElementById('expenseTotal');
    const grossProfitLbl = document.getElementById('grossProfitLbl');
    const netProfitLbl = document.getElementById('netProfitLbl');
    const netProfitLblBottom = document.getElementById('netProfitLblBottom');

    function fmt(n) {
        return Number(n || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function emptyRow() {
        return '<tr><td colspan="2" class="text-center text-muted">No data</td></tr>';
    }

    function showLoading() {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                title: 'Loading...',
                text: 'Preparing Profit & Loss report',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => window.Swal.showLoading()
            });
            return;
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
                text: message || 'Failed to load report.'
            });
            return;
        }
        alert(message || 'Failed to load report.');
    }

    function renderSection(groups, sectionKey) {
        if (!groups || !groups.length) return emptyRow();

        let html = '';

        groups.forEach((group, index) => {
            const groupCode = group.group_code || '';
            const groupName = group.group_name || '';
            const children = group.children || [];
            const childRows = [];
            const toggleId = `${sectionKey}_group_${index}`;
            let hasVisibleChildren = false;

            children.forEach(child => {
                const sameAsParent =
                    String(child.code || '') === String(groupCode) &&
                    String(child.name || '') === String(groupName);

                if (sameAsParent) return;

                hasVisibleChildren = true;
                childRows.push(`
                    <tr class="pl-child-row ${toggleId}">
                        <td class="pl-child-name">${child.code || ''} - ${child.name || ''}</td>
                        <td class="text-end">${fmt(child.amount)}</td>
                    </tr>
                `);
            });

            const toggleIcon = hasVisibleChildren
                ? `<span class="pl-toggle" data-state="closed"><i class="fas fa-chevron-right"></i></span>`
                : `<span class="pl-toggle empty"><i class="fas fa-chevron-right"></i></span>`;

            html += `
                <tr class="pl-group-row" data-toggle-target="${toggleId}" data-has-children="${hasVisibleChildren ? '1' : '0'}">
                    <td>${toggleIcon}${groupCode} - ${groupName}</td>
                    <td class="text-end">${fmt(group.total)}</td>
                </tr>
            `;

            html += childRows.join('');
        });

        return html;
    }

    function bindToggles() {
        document.querySelectorAll('.pl-group-row').forEach(row => {
            row.addEventListener('click', function () {
                if (this.dataset.hasChildren !== '1') return;

                const target = this.dataset.toggleTarget;
                const childRows = document.querySelectorAll('.' + target);
                const iconWrap = this.querySelector('.pl-toggle');
                const icon = iconWrap ? iconWrap.querySelector('i') : null;

                let isOpening = false;

                childRows.forEach(r => {
                    if (r.style.display === 'table-row') {
                        r.style.display = 'none';
                    } else {
                        r.style.display = 'table-row';
                        isOpening = true;
                    }
                });

                if (icon) {
                    if (isOpening) {
                        icon.classList.remove('fa-chevron-right');
                        icon.classList.add('fa-chevron-down');
                    } else {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-right');
                    }
                }
            });
        });
    }

    function setTotals(meta) {
        incomeTotal.textContent = fmt(meta.income);
        cogsTotal.textContent = fmt(meta.cogs);
        expenseTotal.textContent = fmt(meta.expenses);
        grossProfitLbl.textContent = fmt(meta.grossProfit);
        netProfitLbl.textContent = fmt(meta.netProfit);
        netProfitLblBottom.textContent = fmt(meta.netProfit);
    }

    function resetDisplay() {
        incomeBody.innerHTML = emptyRow();
        cogsBody.innerHTML = emptyRow();
        expenseBody.innerHTML = emptyRow();
        setTotals({
            income: 0,
            cogs: 0,
            expenses: 0,
            grossProfit: 0,
            netProfit: 0
        });
    }

    async function fetchReport(withLoader = true) {
        try {
            const params = new URLSearchParams({
                date_from: dateFrom.value || '',
                date_to: dateTo.value || ''
            });

            if (withLoader) showLoading();

            const response = await fetch(`${dataUrl}?${params.toString()}`, {
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

            incomeBody.innerHTML = renderSection(sections.income || [], 'income');
            cogsBody.innerHTML = renderSection(sections.cogs || [], 'cogs');
            expenseBody.innerHTML = renderSection(sections.expenses || [], 'expenses');

            bindToggles();
            setTotals(meta);
            closeLoading();
        } catch (error) {
            closeLoading();
            resetDisplay();
            showError(error.message || 'Failed to load report.');
        }
    }

    runBtn.addEventListener('click', function () {
        fetchReport(true);
    });

    resetBtn.addEventListener('click', function () {
        const now = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        dateFrom.value = first.toISOString().slice(0, 10);
        dateTo.value = last.toISOString().slice(0, 10);

        fetchReport(true);
    });

    fetchReport(false);
});
</script>
@endpush