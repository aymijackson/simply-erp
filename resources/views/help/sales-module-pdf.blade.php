<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Sales Module Guide</title>
<style>
    body{ font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }
    h1{ font-size: 20px; margin: 0 0 6px; }
    h2{ font-size: 15px; margin: 16px 0 6px; }
    h3{ font-size: 13px; margin: 12px 0 6px; }
    p{ margin: 6px 0; }
    .muted{ color:#555; }
    .box{ border:1px solid #333; border-radius: 8px; padding: 10px; margin: 10px 0; }
    .pill{ display:inline-block; padding: 3px 8px; border:1px solid #333; border-radius: 999px; font-size: 11px; }
    table{ width:100%; border-collapse: collapse; margin: 8px 0; }
    th, td{ border:1px solid #333; padding: 6px; vertical-align: top; }
    th{ background:#f0f0f0; }
    ul{ margin: 6px 0 6px 18px; }
</style>
</head>
<body>

<h1>Thekan-ERP — Sales Module Guide</h1>
<p class="muted">
Documentation • Roles/Privileges • Controls • Analytics
</p>

<div class="box">
    <b>Overview</b><br>
    The Sales Module manages the full customer revenue lifecycle:
    Orders → Deliveries → Invoices → Payments → Credit Notes.
    Draft documents are editable; posted documents are locked for audit safety.
</div>

<h2>1. Sales Workflow</h2>
<p><span class="pill">Sales Order</span> → <span class="pill">Delivery</span> → <span class="pill">Invoice</span> → <span class="pill">Payment</span> → <span class="pill">Credit Note</span></p>

<div class="box">
    <b>Key validations</b>
    <ul>
        <li>Invoice qty must not exceed order remaining qty.</li>
        <li>Delivery qty must not exceed store stock (variant-level).</li>
        <li>Payment allocation must not exceed amount received.</li>
        <li>Credit note should be invoice-linked for traceability.</li>
    </ul>
</div>

<h2>2. Sub-Modules</h2>

<h3>Sales Orders</h3>
<ul>
    <li>Create customer order with product variants and quantities.</li>
    <li>Status flow: draft → confirmed → closed.</li>
    <li>Source document for invoice line loading.</li>
</ul>

<h3>Deliveries</h3>
<ul>
    <li>Stock-validated fulfillment by store and variant.</li>
    <li>Supports partial delivery and backorders.</li>
</ul>

<h3>Invoices</h3>
<ul>
    <li>Supports product/custom/percent/discount lines.</li>
    <li>Per-line tax and computed totals.</li>
    <li>PDF printing and verification support.</li>
</ul>

<h3>Payments</h3>
<ul>
    <li>Customer payment captured as amount received.</li>
    <li>Allocations can apply to one or multiple invoices.</li>
    <li>Enforces allocation total ≤ amount received.</li>
</ul>

<h3>Credit Notes</h3>
<ul>
    <li>Best practice: link credit note to invoice.</li>
    <li>Loads invoice lines; user adjusts credited qty/amount.</li>
    <li>Used to reverse posted invoices safely.</li>
</ul>

<h2>3. Status & Posting Rules</h2>
<table>
    <tr><th>Status</th><th>Meaning</th><th>Editable?</th></tr>
    <tr><td>draft</td><td>Work in progress, not final</td><td>Yes</td></tr>
    <tr><td>posted</td><td>Final and included in reporting</td><td>No</td></tr>
    <tr><td>void/cancelled</td><td>Reversed record</td><td>No</td></tr>
</table>

<div class="box">
    <b>Posting guards</b>
    <ul>
        <li>Posting requires proper role permission.</li>
        <li>Invoices/credit notes require at least one line item.</li>
        <li>Payments require at least one allocation before posting.</li>
    </ul>
</div>

<h2>4. Roles & Privileges (Recommended)</h2>
<table>
    <tr>
        <th>Role</th>
        <th>Responsibilities</th>
        <th>Typical Permissions</th>
    </tr>
    <tr>
        <td><b>Sales Clerk</b></td>
        <td>Create/maintain drafts</td>
        <td>sales.orders.create, sales.invoices.create, sales.payments.create, sales.credit_notes.create</td>
    </tr>
    <tr>
        <td><b>Supervisor</b></td>
        <td>Posts and approves documents</td>
        <td>sales.invoices.post, sales.payments.post, sales.credit_notes.post, sales.deliveries.post</td>
    </tr>
    <tr>
        <td><b>Finance Officer</b></td>
        <td>Allocations, receivables control</td>
        <td>sales.payments.allocate, sales.analytics.view</td>
    </tr>
    <tr>
        <td><b>Sales Manager</b></td>
        <td>Performance monitoring</td>
        <td>sales.analytics.view, sales.analytics.export</td>
    </tr>
    <tr>
        <td><b>Admin</b></td>
        <td>Full access + settings</td>
        <td>admin.*, core.settings.*, audit.logs.view</td>
    </tr>
</table>

<h2>5. Analytics Coverage</h2>
<div class="box">
    <b>KPIs</b>
    <ul>
        <li>Total invoiced (Σ posted invoice totals)</li>
        <li>Total paid (Σ payment allocations applied)</li>
        <li>Outstanding (balance due after allocations/credits)</li>
    </ul>

    <b>Common reports</b>
    <ul>
        <li>Top customers</li>
        <li>Sales trend (daily/weekly/monthly)</li>
        <li>Outstanding invoices and aging</li>
        <li>Credit notes impact</li>
    </ul>

    <b>Recommended filters</b>
    <ul>
        <li>Date range, customer, status mode (posted only), currency</li>
    </ul>
</div>

<h2>6. Audit & Compliance</h2>
<ul>
    <li>posted_at, posted_by track approvals</li>
    <li>voided_at, voided_by track reversals</li>
    <li>Use Audit Logs for change visibility</li>
</ul>

</body>
</html>
