@extends('layouts.master')
@section('title', 'Procurement Module Guide')

@push('styles')
<style>
    .guide-hero {
        background: linear-gradient(135deg, #1d4ed8 0%, #0891b2 100%);
        color: #fff;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,.08);
    }
    .guide-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .04);
        height: 100%;
    }
    .guide-card .card-body { padding: 1.25rem; }
    .guide-section-title {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: .35rem;
    }
    .guide-muted { color: #64748b; }
    .guide-chip {
        display: inline-block;
        padding: .35rem .7rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        margin: .15rem .15rem 0 0;
    }
    .guide-chip-primary { background: #dbeafe; color: #1d4ed8; }
    .guide-chip-success { background: #dcfce7; color: #15803d; }
    .guide-chip-warning { background: #fef3c7; color: #b45309; }
    .guide-chip-danger  { background: #fee2e2; color: #b91c1c; }
    .guide-chip-secondary { background: #e2e8f0; color: #475569; }
    .guide-timeline {
        position: relative;
        margin-left: .75rem;
        padding-left: 1.5rem;
        border-left: 3px solid #dbeafe;
    }
    .guide-step {
        position: relative;
        margin-bottom: 1.25rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        padding: 1rem 1rem 1rem 1.15rem;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .04);
    }
    .guide-step::before {
        content: '';
        position: absolute;
        left: -1.98rem;
        top: 1.1rem;
        width: 14px;
        height: 14px;
        border-radius: 999px;
        background: #2563eb;
        border: 3px solid #fff;
        box-shadow: 0 0 0 3px #dbeafe;
    }
    .guide-link-grid a {
        text-decoration: none;
    }
    .guide-link-tile {
        border: 1px solid #e5e7eb;
        border-radius: .9rem;
        padding: 1rem;
        background: #fff;
        transition: .2s ease;
        height: 100%;
    }
    .guide-link-tile:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
        border-color: #bfdbfe;
    }
    .guide-link-title {
        font-weight: 700;
        color: #0f172a;
    }
    .guide-link-desc {
        font-size: .92rem;
        color: #64748b;
        margin-top: .35rem;
    }
    .guide-kpi {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .04);
        height: 100%;
    }
    .guide-kpi-label {
        font-size: .85rem;
        color: #64748b;
        margin-bottom: .25rem;
    }
    .guide-kpi-value {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
    }
    .guide-table td, .guide-table th { vertical-align: top; }
</style>
@endpush

@section('content')
@php
    $featureLinks = [
        [
            'title' => 'Suppliers',
            'desc' => 'Manage suppliers used across quotations, purchase orders, and goods receipts.',
            'route' => 'admin.procurement.suppliers.index',
            'permission' => 'procurement.suppliers.view',
            'icon' => 'fas fa-truck-loading',
        ],
        [
            'title' => 'Supplier Contacts',
            'desc' => 'Maintain supplier contact people for RFQs, quotations, and purchase orders.',
            'route' => 'admin.procurement.supplier_contacts.index',
            'permission' => 'procurement.supplier_contacts.view',
            'icon' => 'fas fa-address-book',
        ],
        [
            'title' => 'Purchase Requisitions',
            'desc' => 'Start the procurement process by raising requests for goods or services.',
            'route' => 'admin.procurement.purchase_requisitions.index',
            'permission' => 'procurement.purchase_requisitions.view',
            'icon' => 'fas fa-file-signature',
        ],
        [
            'title' => 'RFQs',
            'desc' => 'Request quotations from suppliers and manage sourcing rounds.',
            'route' => 'admin.procurement.rfqs.index',
            'permission' => 'procurement.rfqs.view',
            'icon' => 'fas fa-envelope-open-text',
        ],
        [
            'title' => 'Supplier Quotations',
            'desc' => 'Compare supplier offers, prices, taxes, and delivery terms.',
            'route' => 'admin.procurement.supplier_quotations.index',
            'permission' => 'procurement.supplier_quotations.view',
            'icon' => 'fas fa-balance-scale',
        ],
        [
            'title' => 'Purchase Orders',
            'desc' => 'Create, approve, issue, and monitor purchase orders.',
            'route' => 'admin.procurement.purchase_orders.index',
            'permission' => 'procurement.purchase_orders.view',
            'icon' => 'fas fa-file-invoice-dollar',
        ],
        [
            'title' => 'Goods Receipts',
            'desc' => 'Receive, approve, and post delivered items into inventory.',
            'route' => 'admin.procurement.goods_receipts.index',
            'permission' => 'procurement.goods_receipts.view',
            'icon' => 'fas fa-boxes',
        ],
        [
            'title' => 'Products',
            'desc' => 'View products and variants used during procurement and receiving.',
            'route' => 'admin.products.index',
            'permission' => 'inventory.products.view',
            'icon' => 'fas fa-box-open',
        ],
        [
            'title' => 'Locations',
            'desc' => 'Review delivery locations used on procurement and receipt documents.',
            'route' => 'admin.locations.index',
            'permission' => 'locations.view',
            'icon' => 'fas fa-map-marker-alt',
        ],
        [
            'title' => 'Stores',
            'desc' => 'Review destination stores that receive stock from procurement.',
            'route' => 'admin.location-stores.index',
            'permission' => 'location_stores.view',
            'icon' => 'fas fa-warehouse',
        ],
        [
            'title' => 'Supplier Bills',
            'desc' => 'Finance interface for matching supplier invoices to procurement records.',
            'route' => 'admin.finance.supplier_bills.index',
            'permission' => 'finance.supplier_bills.view',
            'icon' => 'fas fa-money-check-alt',
        ],
        [
            'title' => 'Audit Logs',
            'desc' => 'Review who created, approved, posted, cancelled, or edited records.',
            'route' => 'admin.audit-logs.index',
            'permission' => 'audit_logs.view',
            'icon' => 'fas fa-history',
        ],
    ];
@endphp

<div class="container-fluid py-3">
    <div class="guide-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.14em; opacity:.9;">Simply-ERP • Procurement Knowledge Hub</div>
                <h1 class="mb-3">Complete Procurement Module Workflow Guide</h1>
                <p class="mb-0 fs-6" style="max-width: 980px; color: rgba(255,255,255,.92);">
                    This page explains how the procurement module works from start to finish for first-time users and experienced staff alike.
                    It covers setup, roles, permissions, document statuses, receiving, stock impact, finance interfaces, and where each feature connects to other modules in Simply-ERP.
                </p>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="guide-card border-0" style="background: rgba(255,255,255,.12); color:#fff; backdrop-filter: blur(6px);">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">What this page helps you do</div>
                        <ul class="mb-0 ps-3" style="line-height:1.8; color: rgba(255,255,255,.92);">
                            <li>Understand the full source-to-receipt cycle</li>
                            <li>Know which role performs which task</li>
                            <li>Learn what each status means</li>
                            <li>Navigate to relevant pages based on access</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="guide-kpi">
                <div class="guide-kpi-label">Primary objective</div>
                <div class="guide-kpi-value">Buy correctly, receive correctly, record correctly</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="guide-kpi">
                <div class="guide-kpi-label">Core operational chain</div>
                <div class="guide-kpi-value">Requisition → RFQ → Quotation → PO → GRN → Bill → Payment</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="guide-kpi">
                <div class="guide-kpi-label">Most important novice rule</div>
                <div class="guide-kpi-value">Never post a GRN until store, quantities, and variants are confirmed</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="guide-card">
                <div class="card-body">
                    <h2 class="guide-section-title">1. Procurement workflow at a glance</h2>
                    <p class="guide-muted mb-4">Each document plays a specific role. A novice should understand what each stage does before transacting.</p>

                    <div class="guide-timeline">
                        <div class="guide-step">
                            <h5 class="mb-2">Step 1: Master data and setup</h5>
                            <p class="mb-2 guide-muted">Before any document can be created, make sure suppliers, supplier contacts, products, variants, units, locations, stores, taxes, and access rights are configured correctly.</p>
                            <div>
                                <span class="guide-chip guide-chip-secondary">Suppliers</span>
                                <span class="guide-chip guide-chip-secondary">Products</span>
                                <span class="guide-chip guide-chip-secondary">Variants</span>
                                <span class="guide-chip guide-chip-secondary">Stores</span>
                                <span class="guide-chip guide-chip-secondary">Permissions</span>
                            </div>
                        </div>

                        <div class="guide-step">
                            <h5 class="mb-2">Step 2: Raise purchase requisition</h5>
                            <p class="mb-2 guide-muted">The requester records what is needed, why it is needed, when it is needed, and where it should be delivered.</p>
                            <ul class="mb-0 ps-3 small">
                                <li>Enter item lines, quantities, required date, and justification.</li>
                                <li>Submit for approval according to policy.</li>
                            </ul>
                        </div>

                        <div class="guide-step">
                            <h5 class="mb-2">Step 3: Source suppliers using RFQ and quotations</h5>
                            <p class="mb-2 guide-muted">Procurement invites suppliers, records quotations, compares offers, and recommends the best option.</p>
                            <ul class="mb-0 ps-3 small">
                                <li>Track price, tax, discount, lead time, and delivery terms.</li>
                                <li>Obtain approval for the selected supplier where required.</li>
                            </ul>
                        </div>

                        <div class="guide-step">
                            <h5 class="mb-2">Step 4: Create and approve purchase order</h5>
                            <p class="mb-2 guide-muted">The PO becomes the formal commitment to the supplier and the main reference for receiving and billing.</p>
                            <div>
                                <span class="guide-chip guide-chip-secondary">draft</span>
                                <span class="guide-chip guide-chip-primary">approved</span>
                                <span class="guide-chip guide-chip-primary">issued</span>
                                <span class="guide-chip guide-chip-warning">partially_rcv</span>
                                <span class="guide-chip guide-chip-success">fully_rcv</span>
                                <span class="guide-chip guide-chip-secondary">closed</span>
                                <span class="guide-chip guide-chip-danger">cancelled</span>
                            </div>
                        </div>

                        <div class="guide-step">
                            <h5 class="mb-2">Step 5: Create goods receipt from purchase order</h5>
                            <p class="mb-2 guide-muted">The store or receiving officer loads PO lines into a GRN, then records what was actually delivered.</p>
                            <ul class="mb-0 ps-3 small">
                                <li>Enter received, accepted, rejected, and damaged quantities.</li>
                                <li>Select the correct product variant for each received line.</li>
                                <li>Enter batch, serial, expiry, and remarks where applicable.</li>
                            </ul>
                        </div>

                        <div class="guide-step">
                            <h5 class="mb-2">Step 6: Approve and post the goods receipt</h5>
                            <p class="mb-2 guide-muted">Approval authorises the GRN. Posting updates inventory, stock entry lines, and PO received quantities.</p>
                            <div class="mb-2">
                                <span class="guide-chip guide-chip-secondary">GRN status: draft</span>
                                <span class="guide-chip guide-chip-primary">approved</span>
                                <span class="guide-chip guide-chip-success">posted</span>
                                <span class="guide-chip guide-chip-danger">cancelled</span>
                            </div>
                            <div>
                                <span class="guide-chip guide-chip-warning">receipt_status: partial</span>
                                <span class="guide-chip guide-chip-success">receipt_status: complete</span>
                            </div>
                        </div>

                        <div class="guide-step mb-0">
                            <h5 class="mb-2">Step 7: Match invoice and process payment</h5>
                            <p class="mb-0 guide-muted">Finance uses the PO and GRN as evidence when creating supplier bills and processing payments.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="guide-card mb-4">
                <div class="card-body">
                    <h2 class="guide-section-title">2. Roles and responsibilities</h2>
                    <div class="table-responsive">
                        <table class="table table-sm guide-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Role</th>
                                    <th>Main responsibility</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Requester</strong></td>
                                    <td>Raises requisitions, provides business justification, and confirms operational need.</td>
                                </tr>
                                <tr>
                                    <td><strong>Approver</strong></td>
                                    <td>Approves requisitions, quotations, exceptions, or POs according to approval authority.</td>
                                </tr>
                                <tr>
                                    <td><strong>Procurement Officer</strong></td>
                                    <td>Runs sourcing, compares quotations, creates POs, and monitors supplier fulfilment.</td>
                                </tr>
                                <tr>
                                    <td><strong>Store / Warehouse Officer</strong></td>
                                    <td>Receives goods, checks condition, confirms variants, and posts GRNs.</td>
                                </tr>
                                <tr>
                                    <td><strong>Finance / AP</strong></td>
                                    <td>Matches supplier invoice to PO and GRN, then processes bills and payments.</td>
                                </tr>
                                <tr>
                                    <td><strong>System Administrator</strong></td>
                                    <td>Maintains permissions, numbering, setup tables, and workflow governance.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>
            <div class="guide-card">
                <div class="card-body">
                    <h2 class="guide-section-title">3. Permissions novice users should understand</h2>
                    <p class="guide-muted">If a button is missing, it may be permission-related rather than a system issue.</p>
                    <div class="small">
                        <p class="mb-2"><strong>Common procurement permissions</strong></p>
                        <ul class="ps-3 mb-0">
                            <li>procurement.purchase_requisitions.view / create / approve</li>
                            <li>procurement.rfqs.view / create / approve</li>
                            <li>procurement.supplier_quotations.view / create / approve</li>
                            <li>procurement.purchase_orders.view / create / edit / approve / issue / cancel / close</li>
                            <li>procurement.goods_receipts.view / create / edit / approve / post / cancel / delete</li>
                            <li>procurement.suppliers.view / create / edit</li>
                            <li>procurement.supplier_contacts.view / create / edit</li>
                            <li>audit_logs.view</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="guide-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <div>
                    <h2 class="guide-section-title mb-1">4. Navigate to procurement features and connected modules</h2>
                    <p class="guide-muted mb-0">These tiles only appear when the route exists and the user has permission.</p>
                </div>
            </div>

            <div class="row g-3 guide-link-grid">
                @foreach($featureLinks as $item)
                    @if(Route::has($item['route']))
                        @can($item['permission'])
                            <div class="col-md-6 col-xl-4">
                                <a href="{{ route($item['route']) }}">
                                    <div class="guide-link-tile">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="text-primary fs-4"><i class="{{ $item['icon'] }}"></i></div>
                                            <div>
                                                <div class="guide-link-title">{{ $item['title'] }}</div>
                                                <div class="guide-link-desc">{{ $item['desc'] }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endcan
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="guide-card">
                <div class="card-body">
                    <h2 class="guide-section-title">5. How procurement links to other modules</h2>
                    <div class="accordion" id="interfacesAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingInventory">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInventory">
                                    Inventory Module Interface
                                </button>
                            </h2>
                            <div id="collapseInventory" class="accordion-collapse collapse show" data-bs-parent="#interfacesAccordion">
                                <div class="accordion-body small">
                                    Procurement depends on products, variants, units, locations, and stores. When a GRN is posted,
                                    accepted quantities create stock entries and stock entry lines. Variant-level receiving is important where stock is tracked per SKU variant.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFinance">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFinance">
                                    Finance Module Interface
                                </button>
                            </h2>
                            <div id="collapseFinance" class="accordion-collapse collapse" data-bs-parent="#interfacesAccordion">
                                <div class="accordion-body small">
                                    Procurement feeds supplier billing and payment. Supplier bills should be matched against PO and GRN.
                                    Taxes, charges, payment terms, and supplier balances all connect procurement to finance.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSecurity">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSecurity">
                                    Users, Roles, and Audit Interface
                                </button>
                            </h2>
                            <div id="collapseSecurity" class="accordion-collapse collapse" data-bs-parent="#interfacesAccordion">
                                <div class="accordion-body small">
                                    Roles and permissions determine what a user can create, approve, issue, post, cancel, or review.
                                    Audit logs should capture who performed each significant action.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="guide-card">
                <div class="card-body">
                    <h2 class="guide-section-title">6. Common mistakes to avoid</h2>
                    <ul class="small ps-3 mb-0" style="line-height: 1.9;">
                        <li>Creating a GRN without confirming that the goods were actually delivered.</li>
                        <li>Posting a GRN without selecting the correct product variant.</li>
                        <li>Using accepted quantity incorrectly and thereby overstating inventory.</li>
                        <li>Ignoring rejected or damaged quantities during receiving.</li>
                        <li>Issuing or receiving against the wrong location or store.</li>
                        <li>Paying a supplier invoice without matching it to PO and GRN.</li>
                        <li>Assuming a missing button is a bug when it is actually permission-restricted.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="guide-card mb-4">
        <div class="card-body">
            <h2 class="guide-section-title">7. Suggested sidebar integration</h2>
            <div class="row g-3">
                <div class="col-lg-12">
<pre class="bg-light p-3 rounded small mb-0"><code>@can('procurement.guide.view')
<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.procurement.guide.index') }}">
        <i class="fas fa-book me-2"></i>
        <span>Procurement Guide</span>
    </a>
</li>
@endcan</code></pre>
                </div>
            </div>
        </div>
    </div>
</div></div>
@endsection