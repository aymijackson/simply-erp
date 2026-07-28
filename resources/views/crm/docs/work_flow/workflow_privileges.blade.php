@extends('layouts.master')

@section('title', 'CRM SOP: Workflows, Privileges & Audit')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 text-primary mb-1">CRM SOP: Workflows, Privileges & Audit</h1>
            <div class="text-muted small">
                A practical Standard Operating Procedure explaining how CRM modules work together, what each flow means,
                and the required permissions + audit expectations.
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>

            {{-- If you created the download routes --}}
            @if(\Illuminate\Support\Facades\Route::has('admin.crm.docs.workflow_privileges.download.pdf'))
                <a class="btn btn-outline-primary" href="{{ route('admin.crm.docs.workflow_privileges.download.pdf') }}">
                    <i class="fas fa-file-pdf me-1"></i> Download SOP (PDF)
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.crm.docs.workflow_privileges.download.html'))
                <a class="btn btn-outline-primary" href="{{ route('admin.crm.docs.workflow_privileges.download.html') }}">
                    <i class="fas fa-code me-1"></i> Download SOP (HTML)
                </a>
            @endif
        </div>
    </div>

    {{-- Quick Nav --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-primary" href="#purpose">Purpose</a>
                <a class="btn btn-sm btn-outline-primary" href="#modules">Modules</a>
                <a class="btn btn-sm btn-outline-primary" href="#workflows">Workflows</a>
                <a class="btn btn-sm btn-outline-primary" href="#implications">Implications</a>
                <a class="btn btn-sm btn-outline-primary" href="#permissions">Privileges</a>
                <a class="btn btn-sm btn-outline-primary" href="#audit">Audit Trail</a>
                <a class="btn btn-sm btn-outline-primary" href="#analytics">Analytics & Segmentation</a>
                <a class="btn btn-sm btn-outline-primary" href="#dq">Data Quality</a>
                <a class="btn btn-sm btn-outline-primary" href="#ops">Operational Rules</a>
            </div>
        </div>
    </div>

    <style>
        /* Simple “visual flow” helpers */
        .flow-wrap { display:flex; flex-wrap:wrap; gap:12px; align-items:stretch; }
        .flow-card {
            min-width: 220px;
            flex: 1 1 220px;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }
        .flow-title { font-weight: 700; margin-bottom: 6px; }
        .flow-sub { color:#6c757d; font-size: .875rem; }
        .flow-arrow {
            display:flex; align-items:center; justify-content:center;
            min-width: 40px;
            color:#0d6efd;
            font-size: 20px;
        }
        .pill { display:inline-block; padding:.15rem .5rem; border-radius:999px; font-size:.75rem; border:1px solid rgba(0,0,0,.12); }
        .pill.ok { background: rgba(25,135,84,.08); border-color: rgba(25,135,84,.25); color:#198754; }
        .pill.warn { background: rgba(255,193,7,.12); border-color: rgba(255,193,7,.35); color:#9a6a00; }
        .pill.danger { background: rgba(220,53,69,.10); border-color: rgba(220,53,69,.30); color:#b02a37; }
        .mini-kpi { border:1px solid rgba(0,0,0,.08); border-radius:12px; padding:12px; background:#fff; }
        .mini-kpi .label { color:#6c757d; font-size:.85rem; }
        .mini-kpi .value { font-weight:800; font-size:1.1rem; }
        .sop-section { scroll-margin-top: 90px; }
        .muted-code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size:.85rem; }
    </style>

    {{-- MAIN CONTENT --}}
    <div class="card shadow-sm">
        <div class="card-body">

            {{-- 1. PURPOSE --}}
            <div id="purpose" class="sop-section">
                <h5 class="mb-2">1) Purpose</h5>
                <p class="text-muted mb-2">
                    This SOP defines how your CRM should be used inside THEKAN-ERP to ensure consistent service delivery,
                    measurable sales execution, and clear accountability. It explains:
                </p>
                <ul class="mb-0">
                    <li><strong>What each module represents</strong> (Customers, Leads, Opportunities, Interactions, Activities, Notes, Support Tickets).</li>
                    <li><strong>How modules connect</strong> (the “workflow”) and what actions should happen next.</li>
                    <li><strong>How analytics is calculated</strong> and how to interpret segmentation output.</li>
                    <li><strong>What permissions exist</strong>, what each permission enables, and recommended role mapping.</li>
                    <li><strong>How audit trails work</strong> and what must be logged for compliance and internal control.</li>
                </ul>
            </div>

            <hr>

            {{-- 2. MODULES --}}
            <div id="modules" class="sop-section">
                <h5 class="mb-2">2) CRM Modules and What They Stand For</h5>

                <div class="row g-3 mt-1">
                    <div class="col-lg-4">
                        <div class="mini-kpi">
                            <div class="value">Customer</div>
                            <div class="label">The “source of truth” record: who we sell to / serve.</div>
                            <div class="small text-muted mt-2">
                                Stores identity + contact details + any linkage to company/organisation.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mini-kpi">
                            <div class="value">Lead</div>
                            <div class="label">A potential customer not yet fully qualified.</div>
                            <div class="small text-muted mt-2">
                                Leads become Customers after qualification/verification.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mini-kpi">
                            <div class="value">Opportunity</div>
                            <div class="label">A sales deal in the pipeline with a value and stage.</div>
                            <div class="small text-muted mt-2">
                                Represents revenue potential and forecasting.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mini-kpi">
                            <div class="value">Interaction</div>
                            <div class="label">A touchpoint: call, email, meeting, WhatsApp, etc.</div>
                            <div class="small text-muted mt-2">
                                Used to measure engagement and calculate “dormant / warm / active” behaviour.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mini-kpi">
                            <div class="value">Activity</div>
                            <div class="label">A planned task: follow-up, demo, call-back, onsite visit.</div>
                            <div class="small text-muted mt-2">
                                Activities represent <em>future intent</em> and SLA discipline.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mini-kpi">
                            <div class="value">Note</div>
                            <div class="label">Internal context: decisions, agreements, next steps.</div>
                            <div class="small text-muted mt-2">
                                Notes reduce knowledge loss and support handovers.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mini-kpi">
                            <div class="value">Support Ticket</div>
                            <div class="label">A customer issue/request tracked from open → resolved → closed.</div>
                            <div class="small text-muted mt-2">
                                Tickets are the backbone of service quality measurement (response time, backlog, reopen rate).
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mini-kpi">
                            <div class="value">Analytics</div>
                            <div class="label">KPIs and drilldowns across pipeline, engagement, and service health.</div>
                            <div class="small text-muted mt-2">
                                Used by managers/executives for decisions and by teams for performance improvement.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <strong>Key idea:</strong> CRM isn’t “data storage”. It is a <em>workflow engine</em>.
                    Each record implies a next action (call, follow-up, stage update, resolution, etc.).
                </div>
            </div>

            <hr>

            {{-- 3. WORKFLOWS (VISUAL) --}}
            <div id="workflows" class="sop-section">
                <h5 class="mb-2">3) Workflows (Visual)</h5>
                <p class="text-muted mb-3">
                    These flows show how modules connect and what each flow means operationally.
                    Use them as the “expected behaviour” inside the CRM.
                </p>

                {{-- 3.1 Customer Lifecycle --}}
                <h6 class="mt-2 mb-2">3.1 Customer Lifecycle (Sales + Service Combined)</h6>
                <div class="flow-wrap">
                    <div class="flow-card">
                        <div class="flow-title">Lead Created</div>
                        <div class="flow-sub">New prospect captured (inbound/outbound/referral).</div>
                        <div class="mt-2">
                            <span class="pill warn">Needs Qualification</span>
                        </div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Qualified → Customer</div>
                        <div class="flow-sub">Verified details, decision-maker, need, timeline.</div>
                        <div class="mt-2">
                            <span class="pill ok">Becomes CRM “Source of Truth”</span>
                        </div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Interactions Logged</div>
                        <div class="flow-sub">Every call/email/meeting recorded.</div>
                        <div class="mt-2">
                            <span class="pill ok">Drives Engagement KPIs</span>
                        </div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Opportunity (If Sales)</div>
                        <div class="flow-sub">Add pipeline value, stage, owner.</div>
                        <div class="mt-2">
                            <span class="pill warn">Requires Stage Updates</span>
                        </div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Support Ticket (If Service)</div>
                        <div class="flow-sub">Issue tracked until resolved/closed.</div>
                        <div class="mt-2">
                            <span class="pill danger">Impacts “At Risk”</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 small text-muted">
                    <strong>Interpretation:</strong> A Customer without recent Interactions becomes “Dormant”.
                    A Customer with high pipeline value becomes “Hot/High Value”.
                    A Customer with many open tickets becomes “At Risk”.
                </div>

                {{-- 3.2 Opportunity Flow --}}
                <h6 class="mt-4 mb-2">3.2 Opportunity Workflow (Pipeline Discipline)</h6>
                <div class="flow-wrap">
                    <div class="flow-card">
                        <div class="flow-title">Create Opportunity</div>
                        <div class="flow-sub">Link to Customer, set owner + value + expected close date.</div>
                        <div class="mt-2"><span class="pill warn">No Owner = Orphan Deal</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Stage Progression</div>
                        <div class="flow-sub">Update stage (e.g. new → qualified → proposal → negotiation).</div>
                        <div class="mt-2"><span class="pill ok">Used for Forecasting</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Evidence</div>
                        <div class="flow-sub">Log Interactions + Notes supporting movement.</div>
                        <div class="mt-2"><span class="pill ok">Reduces “Fake Pipeline”</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Close Outcome</div>
                        <div class="flow-sub">Won/Lost + reason captured.</div>
                        <div class="mt-2"><span class="pill warn">Required for Learning</span></div>
                    </div>
                </div>

                {{-- 3.3 Interactions Flow --}}
                <h6 class="mt-4 mb-2">3.3 Interactions Workflow (Engagement Proof)</h6>
                <div class="flow-wrap">
                    <div class="flow-card">
                        <div class="flow-title">Interaction Logged</div>
                        <div class="flow-sub">Call / email / meeting / WhatsApp with date + channel.</div>
                        <div class="mt-2"><span class="pill ok">Counts for Engagement</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Outcome Captured</div>
                        <div class="flow-sub">What happened? What is next?</div>
                        <div class="mt-2"><span class="pill warn">No outcome = low value</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Create Activity (If Needed)</div>
                        <div class="flow-sub">Follow-up task with due date + owner.</div>
                        <div class="mt-2"><span class="pill ok">Ensures Continuity</span></div>
                    </div>
                </div>

                {{-- 3.4 Activities Flow --}}
                <h6 class="mt-4 mb-2">3.4 Activities Workflow (Execution & SLA)</h6>
                <div class="flow-wrap">
                    <div class="flow-card">
                        <div class="flow-title">Activity Created</div>
                        <div class="flow-sub">Task assigned (e.g., “Call back on Friday”).</div>
                        <div class="mt-2"><span class="pill warn">Must Have Due Date</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Reminder / Due</div>
                        <div class="flow-sub">Work queue for daily execution.</div>
                        <div class="mt-2"><span class="pill ok">Drives Team Productivity</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Complete Activity</div>
                        <div class="flow-sub">Mark done and attach Interaction/Note as evidence.</div>
                        <div class="mt-2"><span class="pill ok">Creates Audit Trail</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Escalate (If overdue)</div>
                        <div class="flow-sub">Overdue tasks indicate execution risk.</div>
                        <div class="mt-2"><span class="pill danger">Operational Risk</span></div>
                    </div>
                </div>

                {{-- 3.5 Support Tickets Flow --}}
                <h6 class="mt-4 mb-2">3.5 Support Tickets Workflow (Service Assurance)</h6>
                <div class="flow-wrap">
                    <div class="flow-card">
                        <div class="flow-title">Ticket Created</div>
                        <div class="flow-sub">Customer issue/request captured with category + priority + channel.</div>
                        <div class="mt-2"><span class="pill danger">Creates Service Obligation</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Assign & Respond</div>
                        <div class="flow-sub">Assign to agent; log first response (comment) + timestamp.</div>
                        <div class="mt-2"><span class="pill warn">First Response SLA</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Work & Update</div>
                        <div class="flow-sub">Add comments, upload evidence, change status.</div>
                        <div class="mt-2"><span class="pill ok">Customer Confidence</span></div>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-card">
                        <div class="flow-title">Resolve → Close</div>
                        <div class="flow-sub">Record resolution details and close.</div>
                        <div class="mt-2"><span class="pill ok">Counts for Quality KPIs</span></div>
                    </div>
                </div>
            </div>

            <hr>

            {{-- 4. IMPLICATIONS --}}
            <div id="implications" class="sop-section">
                <h5 class="mb-2">4) What Each Flow Implies (Why It Matters)</h5>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-2">Leads → Customers</h6>
                                <ul class="mb-0">
                                    <li>Shows <strong>pipeline creation capability</strong> (marketing/outreach effectiveness).</li>
                                    <li>Bad lead quality creates noise and reduces conversion rate.</li>
                                    <li>Conversion requires: valid contact, decision-maker, problem, budget/timeline.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-2">Customers → Interactions</h6>
                                <ul class="mb-0">
                                    <li>Interactions are <strong>proof of engagement</strong> and relationship health.</li>
                                    <li>Analytics relies on interactions to classify customers as warm/dormant.</li>
                                    <li>No interactions = you cannot defend performance or activity levels.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-2">Customers → Opportunities</h6>
                                <ul class="mb-0">
                                    <li>Represents <strong>future revenue</strong> and forecasting.</li>
                                    <li>Stale opportunities = “fake pipeline” and poor planning.</li>
                                    <li>Each stage change should be supported by interaction/note evidence.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-2">Customers → Support Tickets</h6>
                                <ul class="mb-0">
                                    <li>Represents <strong>service risk</strong> and operational load.</li>
                                    <li>Open/pending backlog drives “At Risk” segmentation.</li>
                                    <li>Poor ticket discipline damages retention and conversion.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="alert alert-warning mb-0">
                            <strong>Important:</strong> Workflows create measurable “signals”.
                            If users skip workflow steps (no interaction logs, no stage updates, no ticket updates),
                            analytics becomes blank or misleading.
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            {{-- 5. PERMISSIONS --}}
            <div id="permissions" class="sop-section">
                <h5 class="mb-2">5) Privileges & Permissions (Comprehensive)</h5>
                <p class="text-muted">
                    Permissions are enforced using Spatie Roles/Permissions. Recommended approach: grant the minimum needed
                    (“least privilege”) and separate <strong>view</strong>, <strong>create</strong>, <strong>update</strong>, <strong>delete</strong>, and <strong>analytics</strong>.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:240px;">Module</th>
                                <th>Permissions (typical)</th>
                                <th style="width:260px;">What it enables</th>
                                <th style="width:220px;">Suggested roles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>CRM SOP Docs</td>
                                <td class="muted-code">crm.docs.view</td>
                                <td>View this SOP page</td>
                                <td>All CRM users</td>
                            </tr>

                            <tr>
                                <td>Customers</td>
                                <td class="muted-code">
                                    crm.customers.view, crm.customers.create, crm.customers.update, crm.customers.delete
                                </td>
                                <td>Manage customer master data</td>
                                <td>Sales, Support Lead</td>
                            </tr>

                            <tr>
                                <td>Leads</td>
                                <td class="muted-code">
                                    crm.leads.view, crm.leads.create, crm.leads.update, crm.leads.delete
                                </td>
                                <td>Capture and qualify prospects</td>
                                <td>Sales/Marketing</td>
                            </tr>

                            <tr>
                                <td>Opportunities</td>
                                <td class="muted-code">
                                    crm.opportunities.view, crm.opportunities.create, crm.opportunities.update, crm.opportunities.delete
                                </td>
                                <td>Manage pipeline and forecasting</td>
                                <td>Sales Team</td>
                            </tr>

                            <tr>
                                <td>Interactions</td>
                                <td class="muted-code">
                                    crm.interactions.view, crm.interactions.create, crm.interactions.update, crm.interactions.delete
                                </td>
                                <td>Log engagement touchpoints</td>
                                <td>Sales, Customer Success</td>
                            </tr>

                            <tr>
                                <td>Activities</td>
                                <td class="muted-code">
                                    crm.activities.view, crm.activities.create, crm.activities.update, crm.activities.delete
                                </td>
                                <td>Plan and track tasks/follow-ups</td>
                                <td>Sales, Support</td>
                            </tr>

                            <tr>
                                <td>Notes</td>
                                <td class="muted-code">
                                    crm.notes.view, crm.notes.create, crm.notes.update, crm.notes.delete
                                </td>
                                <td>Internal context and documentation</td>
                                <td>All CRM contributors</td>
                            </tr>

                            <tr>
                                <td>Support Tickets</td>
                                <td class="muted-code">
                                    crm.support_tickets.view, crm.support_tickets.create, crm.support_tickets.update, crm.support_tickets.delete
                                </td>
                                <td>Service desk operations</td>
                                <td>Support Team</td>
                            </tr>

                            <tr>
                                <td>Ticket Comments</td>
                                <td class="muted-code">
                                    crm.support_tickets.comment.create, crm.support_tickets.comment.delete
                                </td>
                                <td>Add/remove ticket updates</td>
                                <td>Support agents, leads</td>
                            </tr>

                            <tr>
                                <td>Ticket Attachments</td>
                                <td class="muted-code">
                                    crm.support_tickets.attachment.create, crm.support_tickets.attachment.delete
                                </td>
                                <td>Upload evidence and logs</td>
                                <td>Support agents</td>
                            </tr>

                            <tr>
                                <td>CRM Analytics (General)</td>
                                <td class="muted-code">crm.analytics.view</td>
                                <td>View analytics pages</td>
                                <td>Managers, Executives</td>
                            </tr>

                            <tr>
                                <td>Customer Segmentation</td>
                                <td class="muted-code">crm.analytics.customer_segmentation.view</td>
                                <td>View segmentation + drilldowns</td>
                                <td>Sales Manager, Head of Support</td>
                            </tr>

                            <tr>
                                <td>Audit Logs</td>
                                <td class="muted-code">audit.logs.view</td>
                                <td>View audit trail records</td>
                                <td>Admin, Compliance</td>
                            </tr>

                            <tr>
                                <td>Audit Analytics</td>
                                <td class="muted-code">audit.analytics.view</td>
                                <td>Audit KPI dashboards (if present)</td>
                                <td>Admin, Compliance</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-secondary mb-0">
                    <strong>Recommendation:</strong> If you use “own records only” rules later,
                    introduce permissions like <span class="muted-code">crm.opportunities.update_own</span>
                    and enforce via policies.
                </div>
            </div>

            <hr>

            {{-- 6. AUDIT --}}
            <div id="audit" class="sop-section">
                <h5 class="mb-2">6) Audit Trail Workflow (Accountability)</h5>

                <p class="text-muted mb-2">
                    Audit logs provide traceability: who did what, when, from where, and what changed.
                    This supports compliance, investigations, and internal control.
                </p>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-2">What must be audited</h6>
                                <ul class="mb-0">
                                    <li>Create / Update / Delete of Customers, Leads, Opportunities, Interactions, Activities, Notes, Tickets.</li>
                                    <li>Ticket status changes (open → pending → resolved → closed).</li>
                                    <li>Assignment changes (who owns the opportunity / ticket).</li>
                                    <li>Analytics “view/filter” events (optional, but helpful for monitoring usage).</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-2">What audit fields mean</h6>
                                <ul class="mb-0">
                                    <li><strong>module</strong>: area e.g. crm</li>
                                    <li><strong>action</strong>: event key e.g. crm.support_tickets.created</li>
                                    <li><strong>description</strong>: human summary</li>
                                    <li><strong>route</strong>: route name (short)</li>
                                    <li><strong>url</strong>: full URL (can be long)</li>
                                    <li><strong>meta</strong>: JSON for before/after + filters</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-3 mb-0">
                    <strong>Known risk:</strong> DataTables URLs can exceed varchar limits.
                    Ensure <span class="muted-code">audit_logs.url</span> is <span class="muted-code">TEXT</span> (or store only path + query in meta),
                    and keep <span class="muted-code">route</span> as the route name (short).
                </div>
            </div>

            <hr>

            {{-- 7. ANALYTICS --}}
            <div id="analytics" class="sop-section">
                <h5 class="mb-2">7) Analytics & Customer Segmentation (How to Read It)</h5>

                <p class="text-muted mb-3">
                    Analytics is only as good as the workflow discipline. Segmentation uses operational signals:
                    pipeline value, open tickets, and engagement (interactions).
                </p>

                <div class="row g-3">
                    <div class="col-lg-3">
                        <div class="mini-kpi">
                            <div class="label">Hot / High Value</div>
                            <div class="value">High Pipeline</div>
                            <div class="small text-muted mt-2">
                                Customers with pipeline value above a threshold (configurable).
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="mini-kpi">
                            <div class="label">At Risk</div>
                            <div class="value">Open Tickets</div>
                            <div class="small text-muted mt-2">
                                Customers with open/pending ticket count above threshold.
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="mini-kpi">
                            <div class="label">Warm</div>
                            <div class="value">Recent Engagement</div>
                            <div class="small text-muted mt-2">
                                Customers with enough interactions in last 30 days.
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="mini-kpi">
                            <div class="label">Dormant</div>
                            <div class="value">No Recent Contact</div>
                            <div class="small text-muted mt-2">
                                Customers with no interaction for N days (configurable).
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h6 class="mb-2">Segmentation inputs typically used</h6>
                        <div class="row g-2 small">
                            <div class="col-md-4"><span class="muted-code">pipeline_value</span>: Sum of opportunity values per customer.</div>
                            <div class="col-md-4"><span class="muted-code">open_tickets</span>: Count of tickets in open/pending statuses.</div>
                            <div class="col-md-4"><span class="muted-code">interactions_30d</span>: Count of interactions in last 30 days.</div>
                            <div class="col-md-4"><span class="muted-code">last_interaction_at</span>: Most recent engagement timestamp.</div>
                            <div class="col-md-4"><span class="muted-code">days_since_interaction</span>: Today - last_interaction_at.</div>
                            <div class="col-md-4"><span class="muted-code">status</span>: Customer status if your schema includes it.</div>
                        </div>

                        <div class="alert alert-info mt-3 mb-0">
                            <strong>Tip:</strong> If your customers table does not contain a stored <span class="muted-code">segment</span> column,
                            compute segment as a <em>derived field</em> in SQL/Query (CASE WHEN ...) and return it as <span class="muted-code">segment</span>
                            for DataTables/Charts.
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            {{-- 8. DATA QUALITY --}}
            <div id="dq" class="sop-section">
                <h5 class="mb-2">8) Data Quality Rules (Non-Negotiable)</h5>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6>Customers</h6>
                                <ul class="mb-0">
                                    <li>Name required. Email/phone strongly recommended.</li>
                                    <li>Prevent duplicates (same email/phone).</li>
                                    <li>Assign owner where possible for accountability.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6>Opportunities</h6>
                                <ul class="mb-0">
                                    <li>Always set value + stage + expected close date.</li>
                                    <li>Update stage after every meaningful interaction.</li>
                                    <li>Close with a reason for learning loops.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6>Interactions</h6>
                                <ul class="mb-0">
                                    <li>Must include date + channel + summary/outcome.</li>
                                    <li>Interactions should be linked to the correct customer/opportunity.</li>
                                    <li>Use consistent interaction types to keep analytics stable.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6>Support Tickets</h6>
                                <ul class="mb-0">
                                    <li>Always set priority + status; category/channel recommended.</li>
                                    <li>First response should be logged via comment.</li>
                                    <li>Use attachments for evidence (screenshots, logs, invoices).</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            {{-- 9. OPS RULES --}}
            <div id="ops" class="sop-section">
                <h5 class="mb-2">9) Operational Rules (SLA + Controls)</h5>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-2">Ticket SLA (Example)</h6>
                                <ul class="mb-0">
                                    <li><strong>High/Urgent:</strong> first response ≤ 1 hour, update every 4 hours.</li>
                                    <li><strong>Medium:</strong> first response ≤ 4 hours, daily updates.</li>
                                    <li><strong>Low:</strong> first response ≤ 1 business day.</li>
                                </ul>
                                <div class="small text-muted mt-2">
                                    Adjust to your organisation’s policy, then enforce via dashboards + audit.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-2">Sales Execution (Example)</h6>
                                <ul class="mb-0">
                                    <li>Every opportunity must have an owner.</li>
                                    <li>No stage movement without a supporting Interaction/Note.</li>
                                    <li>Opportunities not updated in 14 days are flagged for review.</li>
                                </ul>
                                <div class="small text-muted mt-2">
                                    This improves forecast accuracy and prevents pipeline inflation.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-secondary mt-3 mb-0">
                    <strong>Control principle:</strong> The CRM should make it “easy to do the right thing”
                    and “hard to do the wrong thing” (permissions, validation, and audit).
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
