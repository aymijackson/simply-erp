@extends('layouts.master')

@section('title', 'Projects Module Guide')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 text-primary mb-0">Projects Module Guide</h1>
            <small class="text-muted">Comprehensive workflow, SOP, controls, integrations and architecture</small>
        </div>

        <a href="{{ route('admin.projects.docs.pdf') }}" class="btn btn-danger">
            <i class="fas fa-file-pdf me-1"></i> Download PDF
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h4 class="mb-2">Module Purpose</h4>
                    <p class="mb-2">
                        The Projects module in <strong>{{ $appName }}</strong> is designed to manage the full operational,
                        financial and commercial lifecycle of projects. It connects project planning, task execution,
                        milestones, labour tracking, cost accumulation, budgeting, profitability analysis and billing
                        into one controlled workflow.
                    </p>
                    <p class="mb-0">
                        It serves operational teams, finance teams, project managers, leadership and auditors by providing
                        a single source of truth for project performance from initiation through execution to revenue recovery.
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="fw-bold text-primary mb-2">Module Coverage</div>
                        <div class="small mb-1">• Project setup and structure</div>
                        <div class="small mb-1">• Task and milestone control</div>
                        <div class="small mb-1">• Labour and cost capture</div>
                        <div class="small mb-1">• Budget vs actual control</div>
                        <div class="small mb-1">• Profitability measurement</div>
                        <div class="small">• Project billing and invoicing</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODULE COMPONENTS --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">1. Core Components of the Projects Module</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:22%">Component</th>
                        <th style="width:28%">Purpose</th>
                        <th>Operational Role in the Workflow</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Projects</strong></td>
                        <td>Master record of each project</td>
                        <td>Stores project identity, owner, timeline, status, budget rollups, actual cost rollups and overall project metadata.</td>
                    </tr>
                    <tr>
                        <td><strong>Project Tasks</strong></td>
                        <td>Detailed work breakdown</td>
                        <td>Represents operational activities required to execute the project and supports progress, cost, labour and billing analysis at activity level.</td>
                    </tr>
                    <tr>
                        <td><strong>Project Milestones</strong></td>
                        <td>Major deliverables and control points</td>
                        <td>Used for progress control, completion checkpoints and optional billing triggers where milestone billing is enabled.</td>
                    </tr>
                    <tr>
                        <td><strong>Project Costs</strong></td>
                        <td>Accumulation of direct and indirect project costs</td>
                        <td>Captures materials, labour, logistics, subcontract, overhead and other costs against project, task or milestone.</td>
                    </tr>
                    <tr>
                        <td><strong>Project Timesheets</strong></td>
                        <td>Labour and effort tracking</td>
                        <td>Captures employee effort, billable and non-billable hours, labour cost and billing value for time-based projects.</td>
                    </tr>
                    <tr>
                        <td><strong>Project Budgets</strong></td>
                        <td>Planned financial baseline</td>
                        <td>Defines budget by category, task and milestone and enables budget vs actual monitoring and variance analysis.</td>
                    </tr>
                    <tr>
                        <td><strong>Project Profitability Dashboard</strong></td>
                        <td>Executive monitoring layer</td>
                        <td>Shows budget, actual cost, labour mix, revenue basis, gross profit, gross margin, burn rate and delivery health.</td>
                    </tr>
                    <tr>
                        <td><strong>Project Invoices</strong></td>
                        <td>Commercial billing layer</td>
                        <td>Generates invoices from milestones, timesheets, fixed fee or manual billing and supports billed revenue tracking.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- VISUAL ARCHITECTURE --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">2. Visual Architecture</h5>
        </div>
        <div class="card-body">
            <div class="project-flow-grid">
                <div class="flow-box flow-primary">
                    <div class="flow-title">Project Setup</div>
                    <div class="flow-text">Projects, ownership, dates, status, baseline details</div>
                </div>
                <div class="flow-arrow">↓</div>

                <div class="flow-box flow-info">
                    <div class="flow-title">Work Breakdown</div>
                    <div class="flow-text">Tasks and milestones define how the project will be delivered</div>
                </div>
                <div class="flow-arrow">↓</div>

                <div class="flow-box flow-warning">
                    <div class="flow-title">Execution Tracking</div>
                    <div class="flow-text">Timesheets, milestone progress and operational completion</div>
                </div>
                <div class="flow-arrow">↓</div>

                <div class="flow-box flow-danger">
                    <div class="flow-title">Cost Accumulation</div>
                    <div class="flow-text">Project costs from labour and non-labour sources</div>
                </div>
                <div class="flow-arrow">↓</div>

                <div class="flow-box flow-success">
                    <div class="flow-title">Budget Control</div>
                    <div class="flow-text">Budget baseline compared with actual project cost</div>
                </div>
                <div class="flow-arrow">↓</div>

                <div class="flow-box flow-dark">
                    <div class="flow-title">Billing and Revenue</div>
                    <div class="flow-text">Invoices generated from milestones, timesheets, fixed fee or manual lines</div>
                </div>
                <div class="flow-arrow">↓</div>

                <div class="flow-box flow-secondary">
                    <div class="flow-title">Profitability and Oversight</div>
                    <div class="flow-text">Margin, burn rate, budget remaining, progress and health</div>
                </div>
            </div>
        </div>
    </div>

    {{-- END TO END WORKFLOW --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">3. End-to-End Workflow</h5>
        </div>
        <div class="card-body">
            <div class="workflow-step">
                <div class="workflow-no">1</div>
                <div>
                    <h6 class="mb-1">Create the Project</h6>
                    <p class="mb-0">A project is registered with its identifying details, owner, expected duration, status and strategic objective. This becomes the parent record for all downstream transactions and performance data.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-no">2</div>
                <div>
                    <h6 class="mb-1">Define Tasks and Milestones</h6>
                    <p class="mb-0">Operational work is broken down into tasks, while milestones define major checkpoints, deliverables or commercial triggers. These elements improve visibility and allow reporting below project level.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-no">3</div>
                <div>
                    <h6 class="mb-1">Build the Budget</h6>
                    <p class="mb-0">A project budget is created and approved. Budget lines can be assigned by category, task and milestone, providing a baseline for future variance and control analysis.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-no">4</div>
                <div>
                    <h6 class="mb-1">Execute and Capture Labour</h6>
                    <p class="mb-0">Team members submit timesheets against project work. Approved billable and non-billable time is captured for labour cost tracking, delivery analysis and potential billing.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-no">5</div>
                <div>
                    <h6 class="mb-1">Accumulate Project Costs</h6>
                    <p class="mb-0">Project costs are recorded manually or generated from approved timesheets. Later, procurement and finance transactions can also feed project costs for deeper integration.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-no">6</div>
                <div>
                    <h6 class="mb-1">Monitor Budget vs Actual</h6>
                    <p class="mb-0">Actual costs are compared against budget lines and budget headers. This gives visibility into overspend, underspend, burn rate and remaining budget availability.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-no">7</div>
                <div>
                    <h6 class="mb-1">Generate Project Invoices</h6>
                    <p class="mb-0">Revenue is billed through project invoices. Invoice lines can be generated from milestones, approved billable timesheets, fixed fee lines or manual project commercial agreements.</p>
                </div>
            </div>

            <div class="workflow-step mb-0">
                <div class="workflow-no">8</div>
                <div>
                    <h6 class="mb-1">Review Profitability and Delivery Health</h6>
                    <p class="mb-0">Management reviews budget, actual cost, revenue basis, profit margin, progress, labour mix and project health to support intervention and decision-making.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- SOP --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">4. Standard Operating Procedure (SOP)</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-xl-6">
                    <div class="sop-block">
                        <h6>4.1 Project Registration SOP</h6>
                        <ol class="mb-0">
                            <li>Create the project master record.</li>
                            <li>Define project scope, owner, dates and status.</li>
                            <li>Confirm whether the project is internal, customer-facing or billable.</li>
                            <li>Save project and validate core metadata before operational use.</li>
                        </ol>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="sop-block">
                        <h6>4.2 Task and Milestone Setup SOP</h6>
                        <ol class="mb-0">
                            <li>Create task records that represent the work breakdown structure.</li>
                            <li>Create milestone records for major control points or deliverables.</li>
                            <li>Mark milestones as billable where milestone-based billing is used.</li>
                            <li>Maintain realistic ownership and progress status.</li>
                        </ol>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="sop-block">
                        <h6>4.3 Timesheet SOP</h6>
                        <ol class="mb-0">
                            <li>Employees record time against project and optional task/milestone.</li>
                            <li>Billable and non-billable time must be classified correctly.</li>
                            <li>Submitted time is reviewed and approved or rejected.</li>
                            <li>Approved timesheets create labour cost records automatically.</li>
                        </ol>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="sop-block">
                        <h6>4.4 Cost Recording SOP</h6>
                        <ol class="mb-0">
                            <li>Record direct and indirect project costs promptly.</li>
                            <li>Assign each cost to the right category and, where relevant, task or milestone.</li>
                            <li>Ensure supporting references are captured for traceability.</li>
                            <li>Post only validated costs to preserve budget integrity.</li>
                        </ol>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="sop-block">
                        <h6>4.5 Budget Control SOP</h6>
                        <ol class="mb-0">
                            <li>Create a budget before or at the start of execution.</li>
                            <li>Review budget assumptions by category, task and milestone.</li>
                            <li>Approve the budget to establish a management baseline.</li>
                            <li>Monitor variance regularly and revise only under approved governance.</li>
                        </ol>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="sop-block">
                        <h6>4.6 Billing SOP</h6>
                        <ol class="mb-0">
                            <li>Identify billable source items such as milestones or approved billable time.</li>
                            <li>Create the project invoice and verify quantities, price and tax.</li>
                            <li>Post the invoice only after commercial review.</li>
                            <li>Track unpaid balances and void only under controlled approval.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- INTEGRATION --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">5. Integration with Other ERP Modules</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:18%">Module</th>
                        <th style="width:28%">Integration Point</th>
                        <th>Business Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Finance</strong></td>
                        <td>Project invoices contribute to revenue, receivables and profitability reporting.</td>
                        <td>Ensures commercial activity from projects is reflected in financial performance, management reports and future AR integration.</td>
                    </tr>
                    <tr>
                        <td><strong>Procurement</strong></td>
                        <td>Procurement spend can later be tagged to project, task or milestone and fed into project costs.</td>
                        <td>Connects vendor spend, materials usage and subcontract costs directly to project execution and control.</td>
                    </tr>
                    <tr>
                        <td><strong>HR / Employees</strong></td>
                        <td>Timesheets use employee records as labour resources.</td>
                        <td>Provides labour accountability, effort measurement, utilisation analysis and labour cost attribution.</td>
                    </tr>
                    <tr>
                        <td><strong>CRM / Customers</strong></td>
                        <td>Customer-facing projects can reference customer accounts and invoice commercially.</td>
                        <td>Supports client project management, contract execution and billing recovery.</td>
                    </tr>
                    <tr>
                        <td><strong>Sales</strong></td>
                        <td>Project invoices support revenue generation and can later align with customer sales documents.</td>
                        <td>Allows end-to-end tracking from project delivery into customer billing and collections.</td>
                    </tr>
                    <tr>
                        <td><strong>Reporting / Dashboards</strong></td>
                        <td>Profitability, budget variance, burn rate and health indicators roll into management dashboards.</td>
                        <td>Improves leadership visibility, control and decision quality.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- INTERNAL CONTROL --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">6. Internal Controls and Governance</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="control-box">
                        <h6>Approval Control</h6>
                        <p class="mb-0">Timesheets should pass through review before labour cost is recognised. Budgets should be approved before being treated as baseline. Invoices should be posted only after commercial verification.</p>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="control-box">
                        <h6>Audit Trail</h6>
                        <p class="mb-0">Draft, submitted, approved, posted, rejected and voided states preserve transaction lifecycle and support accountability for changes made within the module.</p>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="control-box">
                        <h6>Source Traceability</h6>
                        <p class="mb-0">Costs and invoice lines should carry source references where possible, such as timesheet ID, milestone ID or future procurement source references.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DATA FLOW --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">7. Data Flow Illustration</h5>
        </div>
        <div class="card-body">
            <div class="dataflow-wrap">
                <div class="dataflow-box">Projects</div>
                <div class="dataflow-line">→</div>
                <div class="dataflow-box">Tasks / Milestones</div>
                <div class="dataflow-line">→</div>
                <div class="dataflow-box">Timesheets / Costs</div>
                <div class="dataflow-line">→</div>
                <div class="dataflow-box">Budgets / Variance</div>
                <div class="dataflow-line">→</div>
                <div class="dataflow-box">Invoices / Revenue</div>
                <div class="dataflow-line">→</div>
                <div class="dataflow-box">Profitability</div>
            </div>

            <div class="alert alert-light border mt-4 mb-0">
                <strong>Interpretation:</strong>
                The project record is the parent object. Tasks and milestones define structure. Timesheets and costs capture execution
                effort and spend. Budgets define expected financial limits. Invoices recover value commercially. Profitability compares
                revenue basis against actual spend and delivery progress.
            </div>
        </div>
    </div>

    {{-- PRACTICAL SCENARIOS --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">8. Practical Operating Scenarios</h5>
        </div>
        <div class="card-body">
            <div class="scenario-box mb-3">
                <h6>Scenario A: Internal Delivery Project</h6>
                <p class="mb-0">The organisation runs an internal transformation project. No external customer is billed, but tasks, milestones, labour and non-labour costs are still tracked. Management uses the module for delivery, accountability and cost control.</p>
            </div>

            <div class="scenario-box mb-3">
                <h6>Scenario B: Client Project with Milestone Billing</h6>
                <p class="mb-0">The project team delivers against defined milestones. Each completed and billable milestone becomes eligible for invoicing, while budget and actual costs are tracked throughout execution.</p>
            </div>

            <div class="scenario-box mb-0">
                <h6>Scenario C: Time-and-Materials Project</h6>
                <p class="mb-0">Employees record billable hours through timesheets. Approved billable time becomes available for project billing, while approved time also creates labour cost records for margin analysis.</p>
            </div>
        </div>
    </div>

    {{-- REPORTING --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">9. Key Reports and Management Use</h5>
        </div>
        <div class="card-body">
            <ul class="mb-0">
                <li><strong>Project Budget vs Actual:</strong> identifies overrun, underrun and remaining budget.</li>
                <li><strong>Project Profitability:</strong> compares revenue basis against actual cost to estimate margin.</li>
                <li><strong>Labour Analysis:</strong> separates billable and non-billable effort for operational efficiency review.</li>
                <li><strong>Project Billing Status:</strong> shows invoiced value and outstanding project billing activity.</li>
                <li><strong>Task and Milestone Progress:</strong> monitors delivery completion against work structure.</li>
                <li><strong>Burn Rate:</strong> shows how quickly budget or spend is being consumed over time.</li>
            </ul>
        </div>
    </div>

    {{-- FUTURE ENHANCEMENTS --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h5 class="mb-0">10. Planned Future Enhancements</h5>
        </div>
        <div class="card-body">
            <p class="mb-2">The current Projects module already supports operational and financial project management. The next recommended enhancements include:</p>
            <ul class="mb-0">
                <li>Project WIP / Unbilled Revenue Dashboard</li>
                <li>Project Payment Tracking</li>
                <li>Project Revenue Recognition</li>
                <li>Project Risks and Issues Register</li>
                <li>Project Resource Allocation and Utilisation</li>
                <li>Portfolio Dashboard across all projects</li>
                <li>Procurement-to-project cost automation</li>
            </ul>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .project-flow-grid{
        display:grid;
        grid-template-columns:1fr;
        gap:.5rem;
        justify-items:center;
    }
    .flow-box{
        width:100%;
        max-width:720px;
        border-radius:1rem;
        padding:1rem 1.25rem;
        color:#fff;
        box-shadow:0 .25rem .75rem rgba(0,0,0,.08);
    }
    .flow-title{
        font-weight:700;
        margin-bottom:.25rem;
    }
    .flow-text{
        font-size:.92rem;
        opacity:.96;
    }
    .flow-arrow{
        font-size:1.4rem;
        font-weight:700;
        color:#64748b;
    }
    .flow-primary{ background:linear-gradient(135deg,#2563eb,#1d4ed8); }
    .flow-info{ background:linear-gradient(135deg,#0891b2,#0e7490); }
    .flow-warning{ background:linear-gradient(135deg,#d97706,#b45309); }
    .flow-danger{ background:linear-gradient(135deg,#dc2626,#b91c1c); }
    .flow-success{ background:linear-gradient(135deg,#16a34a,#15803d); }
    .flow-dark{ background:linear-gradient(135deg,#334155,#1e293b); }
    .flow-secondary{ background:linear-gradient(135deg,#7c3aed,#6d28d9); }

    .workflow-step{
        display:flex;
        gap:1rem;
        padding:1rem;
        border:1px solid #e5e7eb;
        border-radius:.9rem;
        margin-bottom:.85rem;
        background:#fff;
    }
    .workflow-no{
        width:42px;
        height:42px;
        min-width:42px;
        border-radius:50%;
        background:#2563eb;
        color:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        font-weight:700;
    }
    .sop-block{
        border:1px solid #e5e7eb;
        border-radius:1rem;
        padding:1rem;
        background:#fafafa;
        height:100%;
    }
    .control-box{
        border:1px solid #e5e7eb;
        border-radius:1rem;
        padding:1rem;
        background:#f8fafc;
        height:100%;
    }
    .dataflow-wrap{
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        justify-content:center;
        gap:.75rem;
    }
    .dataflow-box{
        padding:.9rem 1rem;
        border-radius:.85rem;
        background:#eff6ff;
        border:1px solid #bfdbfe;
        color:#1d4ed8;
        font-weight:700;
        min-width:150px;
        text-align:center;
    }
    .dataflow-line{
        font-size:1.3rem;
        color:#64748b;
        font-weight:700;
    }
    .scenario-box{
        border-left:4px solid #2563eb;
        background:#f8fafc;
        padding:1rem;
        border-radius:.75rem;
    }
</style>
@endpush