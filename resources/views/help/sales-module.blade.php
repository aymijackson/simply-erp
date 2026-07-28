@extends('layouts.master')

@section('title','Sales Module Guide')

@push('styles')
<style>
    /* Layout helpers */
    .help-shell{ display:grid; grid-template-columns: 280px 1fr; gap: 18px; }
    @media (max-width: 992px){
        .help-shell{ grid-template-columns: 1fr; }
        .help-toc{ position: relative !important; top:auto !important; }
    }

    .help-toc{
        position: sticky; top: 90px;
        background: #fff; border: 1px solid rgba(0,0,0,.08);
        border-radius: 12px; padding: 14px;
        box-shadow: 0 6px 22px rgba(0,0,0,.05);
        max-height: calc(100vh - 120px);
        overflow:auto;
    }
    .help-toc a{
        display:block; padding: 8px 10px; border-radius: 10px;
        text-decoration:none;
        color:#334155;
    }
    .help-toc a:hover{ background:#f1f5f9; }
    .help-toc .small{ font-size: 12px; color:#64748b; }

    .help-card{
        background:#fff;
        border:1px solid rgba(0,0,0,.08);
        border-radius: 14px;
        box-shadow: 0 8px 28px rgba(0,0,0,.05);
        overflow:hidden;
    }
    .help-card .help-card-header{
        padding: 14px 16px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        border-bottom: 1px solid rgba(0,0,0,.06);
        display:flex; align-items:center; justify-content:space-between;
        gap: 10px;
    }
    .help-card .help-card-body{ padding: 16px; }

    .pill{
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        border:1px solid rgba(0,0,0,.08);
        background:#f8fafc;
        color:#0f172a;
        white-space: nowrap;
    }

    /* Flow diagram */
    .flow{
        display:flex; gap: 10px; flex-wrap: wrap; align-items:center;
    }
    .flow .node{
        display:flex; align-items:center; gap: 10px;
        padding: 10px 12px;
        border-radius: 14px;
        background:#fff;
        border:1px solid rgba(0,0,0,.1);
        box-shadow: 0 6px 18px rgba(0,0,0,.04);
        min-width: 190px;
    }
    .flow .icon{
        width: 34px; height: 34px;
        border-radius: 10px;
        display:flex; align-items:center; justify-content:center;
        background:#eff6ff;
        color:#1d4ed8;
        font-size: 14px;
    }
    .flow .arrow{
        color:#94a3b8;
        font-weight:700;
        padding: 0 6px;
        font-size: 18px;
    }
    .flow .meta{ font-size: 12px; color:#64748b; }

    /* Module grid */
    .module-grid{
        display:grid; grid-template-columns: repeat(12, 1fr); gap: 14px;
    }
    .module{ grid-column: span 6; }
    @media (max-width: 992px){ .module{ grid-column: span 12; } }
    .module .title{
        display:flex; align-items:center; gap:10px;
        font-weight: 700;
    }
    .module .title i{ color:#2563eb; }
    .module ul{ margin: 10px 0 0 18px; }
    .module li{ margin: 6px 0; }

    /* Rules + badges */
    .rule{
        border:1px dashed rgba(0,0,0,.18);
        border-radius: 12px;
        padding: 12px;
        background:#fff;
    }
    .badge-soft{
        display:inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        border: 1px solid rgba(0,0,0,.08);
        background:#f1f5f9;
        color:#0f172a;
    }

    /* Roles table */
    .roles-table th{ background:#f8fafc; }
    .roles-table td, .roles-table th{ vertical-align: top; }
    .perm{
        display:inline-block;
        padding: 4px 8px;
        border-radius: 10px;
        font-size: 12px;
        margin: 3px 4px 0 0;
        background:#eef2ff;
        color:#3730a3;
        border:1px solid rgba(55,48,163,.12);
    }

    /* Analytics cards */
    .kpi-grid{ display:grid; grid-template-columns: repeat(12, 1fr); gap: 12px; }
    .kpi{ grid-column: span 4; border-radius: 14px; padding: 14px; border:1px solid rgba(0,0,0,.08); background:#fff; }
    @media (max-width: 992px){ .kpi{ grid-column: span 12; } }
    .kpi .label{ color:#64748b; font-size: 12px; }
    .kpi .value{ font-size: 20px; font-weight: 800; color:#0f172a; }
    .kpi .hint{ color:#64748b; font-size: 12px; margin-top: 6px; }

    .callout{
        border-left: 4px solid #2563eb;
        background: #eff6ff;
        border-radius: 12px;
        padding: 12px 14px;
        color:#0f172a;
    }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Module — Guide & Analytics</h1>
            <small class="text-muted">Documentation • Roles/Privileges • Controls • Reports</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.help.sales-module.pdf') }}" class="btn btn-danger">
                <i class="fas fa-file-pdf me-1"></i> Download PDF
            </a>
        </div>
    </div>

    <div class="help-shell">

        {{-- TOC --}}
        <aside class="help-toc">
            <div class="fw-bold mb-2">On this page</div>
            <a href="#overview"><i class="fas fa-info-circle me-1"></i> Overview</a>
            <a href="#workflow"><i class="fas fa-project-diagram me-1"></i> Sales Workflow</a>
            <a href="#modules"><i class="fas fa-layer-group me-1"></i> Sub-Modules</a>
            <a href="#statuses"><i class="fas fa-flag me-1"></i> Status & Posting Rules</a>
            <a href="#roles"><i class="fas fa-user-shield me-1"></i> Roles & Privileges</a>
            <a href="#analytics"><i class="fas fa-chart-line me-1"></i> Analytics</a>
            <a href="#audit"><i class="fas fa-clipboard-check me-1"></i> Audit & Compliance</a>
            <div class="small mt-3">
                Tip: Use the PDF for sharing with staff or onboarding.
            </div>
        </aside>

        {{-- CONTENT --}}
        <main>

            {{-- OVERVIEW --}}
            <section id="overview" class="help-card mb-3">
                <div class="help-card-header">
                    <div class="fw-bold">
                        <i class="fas fa-info-circle text-primary me-1"></i> Overview
                    </div>
                    <span class="pill">Sales • Orders • Invoices • Payments • Credit Notes</span>
                </div>
                <div class="help-card-body">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <p class="mb-2">
                                The Sales Module manages the <b>full customer revenue lifecycle</b>:
                                capturing orders, fulfilling deliveries, issuing invoices, receiving payments,
                                applying credit notes, and tracking performance using analytics.
                            </p>
                            <div class="callout">
                                <b>Principle:</b> Draft documents are editable. Posted documents are locked.
                                This protects reporting accuracy and prevents audit issues.
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="rule">
                                <div class="fw-bold mb-2">What “Best Practice” means here</div>
                                <div class="mb-1"><span class="badge-soft">Invoice-linked payments</span> for clear aging & allocation</div>
                                <div class="mb-1"><span class="badge-soft">Credit notes linked to invoices</span> for traceability</div>
                                <div><span class="badge-soft">Audit trail</span> for who posted / voided / approved</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- WORKFLOW --}}
            <section id="workflow" class="help-card mb-3">
                <div class="help-card-header">
                    <div class="fw-bold">
                        <i class="fas fa-project-diagram text-primary me-1"></i> Sales Workflow
                    </div>
                    <span class="pill">Lifecycle View</span>
                </div>
                <div class="help-card-body">

                    <div class="flow mb-3">
                        <div class="node">
                            <div class="icon"><i class="fas fa-file-alt"></i></div>
                            <div>
                                <div class="fw-bold">Sales Order</div>
                                <div class="meta">Customer intent + items</div>
                            </div>
                        </div>

                        <div class="arrow">→</div>

                        <div class="node">
                            <div class="icon"><i class="fas fa-truck"></i></div>
                            <div>
                                <div class="fw-bold">Delivery</div>
                                <div class="meta">Stock-checked fulfillment</div>
                            </div>
                        </div>

                        <div class="arrow">→</div>

                        <div class="node">
                            <div class="icon"><i class="fas fa-receipt"></i></div>
                            <div>
                                <div class="fw-bold">Invoice</div>
                                <div class="meta">Tax + totals + PDF</div>
                            </div>
                        </div>

                        <div class="arrow">→</div>

                        <div class="node">
                            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div>
                                <div class="fw-bold">Payment</div>
                                <div class="meta">Allocate to invoices</div>
                            </div>
                        </div>

                        <div class="arrow">→</div>

                        <div class="node">
                            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div>
                                <div class="fw-bold">Credit Note</div>
                                <div class="meta">Return / adjustment</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="rule">
                                <div class="fw-bold mb-1">Key validations</div>
                                <ul class="mb-0">
                                    <li>Invoice qty must not exceed order remaining qty.</li>
                                    <li>Delivery qty must not exceed store stock (variant-level).</li>
                                    <li>Payment allocation must not exceed payment amount received.</li>
                                    <li>Credit note must not exceed invoice totals (best practice: line-linked).</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="rule">
                                <div class="fw-bold mb-1">Operational best practice</div>
                                <ul class="mb-0">
                                    <li>Use invoice-linked payments for accurate outstanding balance.</li>
                                    <li>Allow partial delivery + partial invoicing for real-world fulfillment.</li>
                                    <li>Use credit notes for reversals instead of editing posted invoices.</li>
                                    <li>Always post/approve using proper role permission.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            {{-- MODULES --}}
            <section id="modules" class="help-card mb-3">
                <div class="help-card-header">
                    <div class="fw-bold">
                        <i class="fas fa-layer-group text-primary me-1"></i> Sales Sub-Modules (Detailed)
                    </div>
                    <span class="pill">What each screen does</span>
                </div>
                <div class="help-card-body">
                    <div class="module-grid">

                        <div class="help-card module">
                            <div class="help-card-header">
                                <div class="title"><i class="fas fa-file-alt"></i> Sales Orders</div>
                                <span class="pill">Create → Confirm</span>
                            </div>
                            <div class="help-card-body">
                                <ul>
                                    <li>Create customer order with product variants and quantities.</li>
                                    <li>Track order status: draft / confirmed / closed.</li>
                                    <li>Supports partial fulfillment: delivered/invoiced quantities update remaining qty.</li>
                                    <li>Acts as the source document for invoice line loading.</li>
                                </ul>
                                <div class="mt-2">
                                    <span class="perm">sales.orders.view</span>
                                    <span class="perm">sales.orders.create</span>
                                    <span class="perm">sales.orders.update</span>
                                    <span class="perm">sales.orders.confirm</span>
                                </div>
                            </div>
                        </div>

                        <div class="help-card module">
                            <div class="help-card-header">
                                <div class="title"><i class="fas fa-truck"></i> Deliveries</div>
                                <span class="pill">Pick → Dispatch</span>
                            </div>
                            <div class="help-card-body">
                                <ul>
                                    <li>Loads order lines, and suggests “qty to deliver” based on stock.</li>
                                    <li>Stock validation per store & product variant.</li>
                                    <li>Captures driver + dispatch details (optional).</li>
                                    <li>Supports partial delivery and backorders.</li>
                                </ul>
                                <div class="mt-2">
                                    <span class="perm">sales.deliveries.view</span>
                                    <span class="perm">sales.deliveries.create</span>
                                    <span class="perm">sales.deliveries.post</span>
                                </div>
                            </div>
                        </div>

                        <div class="help-card module">
                            <div class="help-card-header">
                                <div class="title"><i class="fas fa-receipt"></i> Invoices</div>
                                <span class="pill">Draft → Posted</span>
                            </div>
                            <div class="help-card-body">
                                <ul>
                                    <li>Invoices can be generated from confirmed orders.</li>
                                    <li>Supports 4 line types:
                                        <b>product</b>, <b>custom charge</b>, <b>percent charge</b>, <b>discount</b>.
                                    </li>
                                    <li>Per-line tax selection + computed totals.</li>
                                    <li>PDF printing and receipt verification (QR optional).</li>
                                </ul>
                                <div class="mt-2">
                                    <span class="perm">sales.invoices.view</span>
                                    <span class="perm">sales.invoices.create</span>
                                    <span class="perm">sales.invoices.update</span>
                                    <span class="perm">sales.invoices.print</span>
                                    <span class="perm">sales.invoices.post</span>
                                </div>
                            </div>
                        </div>

                        <div class="help-card module">
                            <div class="help-card-header">
                                <div class="title"><i class="fas fa-money-bill-wave"></i> Payments</div>
                                <span class="pill">Allocate → Post</span>
                            </div>
                            <div class="help-card-body">
                                <ul>
                                    <li>Create payment against a customer (amount received).</li>
                                    <li>Allocate payment across one or multiple invoices.</li>
                                    <li>Enforces: total allocated ≤ amount received.</li>
                                    <li>Payment receipt printing (PDF) and allocation summary.</li>
                                </ul>
                                <div class="mt-2">
                                    <span class="perm">sales.payments.view</span>
                                    <span class="perm">sales.payments.create</span>
                                    <span class="perm">sales.payments.allocate</span>
                                    <span class="perm">sales.payments.post</span>
                                    <span class="perm">sales.payments.print</span>
                                </div>
                            </div>
                        </div>

                        <div class="help-card module">
                            <div class="help-card-header">
                                <div class="title"><i class="fas fa-file-invoice-dollar"></i> Credit Notes</div>
                                <span class="pill">Invoice-linked</span>
                            </div>
                            <div class="help-card-body">
                                <ul>
                                    <li>Best practice: link credit note to invoice.</li>
                                    <li>Loads invoice lines (via loader endpoint), then user adjusts qty/amount.</li>
                                    <li>Used for returns, price adjustments, and correcting posted invoices.</li>
                                    <li>Prevents editing posted invoices directly (audit-safe reversal).</li>
                                </ul>
                                <div class="mt-2">
                                    <span class="perm">sales.credit_notes.view</span>
                                    <span class="perm">sales.credit_notes.create</span>
                                    <span class="perm">sales.credit_notes.update</span>
                                    <span class="perm">sales.credit_notes.post</span>
                                    <span class="perm">sales.credit_notes.print</span>
                                </div>
                            </div>
                        </div>

                        <div class="help-card module">
                            <div class="help-card-header">
                                <div class="title"><i class="fas fa-undo"></i> Stock Returns (Optional tie)</div>
                                <span class="pill">Inventory</span>
                            </div>
                            <div class="help-card-body">
                                <ul>
                                    <li>Records physical stock returned to store.</li>
                                    <li>Best practice: credit note can reference stock return ID (optional).</li>
                                    <li>Inventory moves are handled here; accounting reversal remains credit note.</li>
                                </ul>
                                <div class="mt-2">
                                    <span class="perm">inventory.stock_returns.view</span>
                                    <span class="perm">inventory.stock_returns.post</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {{-- STATUS RULES --}}
            <section id="statuses" class="help-card mb-3">
                <div class="help-card-header">
                    <div class="fw-bold">
                        <i class="fas fa-flag text-primary me-1"></i> Status & Posting Rules
                    </div>
                    <span class="pill">Prevents audit issues</span>
                </div>
                <div class="help-card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="rule">
                                <div class="fw-bold mb-2">Document Status Meaning</div>
                                <ul class="mb-0">
                                    <li><b>draft</b> — editable, not included in official financial reporting</li>
                                    <li><b>posted</b> — locked, included in analytics and balances</li>
                                    <li><b>void/cancelled</b> — reversed, excluded from totals (depending on reporting mode)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="rule">
                                <div class="fw-bold mb-2">Posting Guards (Best practice)</div>
                                <ul class="mb-0">
                                    <li>Only users with <span class="badge-soft">post</span> permission can post.</li>
                                    <li>Posting requires at least one line item.</li>
                                    <li>Payments require at least one allocation before posting.</li>
                                    <li>Credit notes require at least one line before posting.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ROLES --}}
            <section id="roles" class="help-card mb-3">
                <div class="help-card-header">
                    <div class="fw-bold">
                        <i class="fas fa-user-shield text-primary me-1"></i> Roles & Privileges (Recommended)
                    </div>
                    <span class="pill">RBAC</span>
                </div>
                <div class="help-card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered roles-table">
                            <thead>
                                <tr>
                                    <th style="width:220px;">Role</th>
                                    <th>Primary Responsibilities</th>
                                    <th style="width:420px;">Typical Permissions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><b>Sales Clerk</b></td>
                                    <td>
                                        Create and maintain drafts; prepare invoices and payments for approval.
                                    </td>
                                    <td>
                                        <span class="perm">sales.*.view</span>
                                        <span class="perm">sales.orders.create</span>
                                        <span class="perm">sales.orders.update</span>
                                        <span class="perm">sales.invoices.create</span>
                                        <span class="perm">sales.invoices.update</span>
                                        <span class="perm">sales.payments.create</span>
                                        <span class="perm">sales.credit_notes.create</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td><b>Sales Supervisor</b></td>
                                    <td>
                                        Approves/Posts documents; ensures compliance and correctness.
                                    </td>
                                    <td>
                                        <span class="perm">sales.invoices.post</span>
                                        <span class="perm">sales.payments.post</span>
                                        <span class="perm">sales.credit_notes.post</span>
                                        <span class="perm">sales.deliveries.post</span>
                                        <span class="perm">sales.*.print</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td><b>Finance Officer</b></td>
                                    <td>
                                        Manages receivables, aging, allocations, credit notes, reconciliation.
                                    </td>
                                    <td>
                                        <span class="perm">sales.payments.allocate</span>
                                        <span class="perm">sales.payments.post</span>
                                        <span class="perm">sales.credit_notes.post</span>
                                        <span class="perm">sales.analytics.view</span>
                                        <span class="perm">finance.taxcodes.view</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td><b>Sales Manager</b></td>
                                    <td>
                                        Monitors performance, customer trends, and overall sales KPIs.
                                    </td>
                                    <td>
                                        <span class="perm">sales.analytics.view</span>
                                        <span class="perm">sales.analytics.export</span>
                                        <span class="perm">sales.*.print</span>
                                        <span class="perm">sales.orders.confirm</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td><b>System Admin</b></td>
                                    <td>
                                        Full control, setup of settings, roles, modules, and audit analytics.
                                    </td>
                                    <td>
                                        <span class="perm">admin.*</span>
                                        <span class="perm">core.settings.*</span>
                                        <span class="perm">audit.logs.view</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="callout mt-2">
                        <b>Recommended enforcement:</b> Draft documents can be created by clerks, but only supervisors/finance can post.
                        This prevents accidental posting and protects analytics reliability.
                    </div>
                </div>
            </section>

            {{-- ANALYTICS --}}
            <section id="analytics" class="help-card mb-3">
                <div class="help-card-header">
                    <div class="fw-bold">
                        <i class="fas fa-chart-line text-primary me-1"></i> Sales Analytics (What it covers)
                    </div>
                    <span class="pill">KPIs • Reports • Filters</span>
                </div>
                <div class="help-card-body">

                    <div class="kpi-grid mb-3">
                        <div class="kpi">
                            <div class="label">Total Invoiced</div>
                            <div class="value">Σ Invoice Grand Total</div>
                            <div class="hint">Typically calculated from posted invoices within date range.</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Total Paid</div>
                            <div class="value">Σ Allocated Payments</div>
                            <div class="hint">Based on posted payments and allocations applied to invoices.</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Outstanding Balance</div>
                            <div class="value">Invoice Balance Due</div>
                            <div class="hint">Grand total minus applied payments & credits.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="rule">
                                <div class="fw-bold mb-2">Common Reports</div>
                                <ul class="mb-0">
                                    <li><b>Top Customers</b> — ranked by total invoiced / paid</li>
                                    <li><b>Sales Trend</b> — day/week/month totals</li>
                                    <li><b>Outstanding Invoices</b> — aging and balances</li>
                                    <li><b>Payment Efficiency</b> — collection speed / allocation ratio</li>
                                    <li><b>Credit Notes Impact</b> — reversals and reasons</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="rule">
                                <div class="fw-bold mb-2">Filters (Recommended)</div>
                                <ul class="mb-0">
                                    <li>Date range: <b>from</b> → <b>to</b></li>
                                    <li>Customer filter</li>
                                    <li>Status mode: <b>posted only</b> vs include draft</li>
                                    <li>Currency code (if multi-currency)</li>
                                    <li>Salesperson/branch (optional extension)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="callout mt-3">
                        <b>Important:</b> For analytics accuracy, always qualify columns in joins
                        (e.g., use <code>i.status</code> not just <code>status</code>) to avoid “ambiguous column” errors.
                    </div>

                </div>
            </section>

            {{-- AUDIT --}}
            <section id="audit" class="help-card mb-4">
                <div class="help-card-header">
                    <div class="fw-bold">
                        <i class="fas fa-clipboard-check text-primary me-1"></i> Audit & Compliance
                    </div>
                    <span class="pill">Traceability</span>
                </div>
                <div class="help-card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="rule">
                                <div class="fw-bold mb-2">Audit Fields</div>
                                <ul class="mb-0">
                                    <li><b>posted_at</b>, <b>posted_by</b></li>
                                    <li><b>voided_at</b>, <b>voided_by</b></li>
                                    <li>Change logs via Audit Logs / Analytics</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="rule">
                                <div class="fw-bold mb-2">Why this matters</div>
                                <ul class="mb-0">
                                    <li>Prevents fraud and back-dated changes.</li>
                                    <li>Makes reconciliation easy.</li>
                                    <li>Supports accountability for approvals and postings.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
/* Smooth scroll for TOC */
document.querySelectorAll('.help-toc a[href^="#"]').forEach(a=>{
    a.addEventListener('click', (e)=>{
        e.preventDefault();
        const id = a.getAttribute('href');
        const el = document.querySelector(id);
        if(!el) return;
        el.scrollIntoView({behavior:'smooth', block:'start'});
        history.replaceState(null,'',id);
    });
});
</script>
@endpush
