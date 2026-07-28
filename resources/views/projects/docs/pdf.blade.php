<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Projects Module Guide</title>
    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color:#222;
            line-height:1.5;
        }
        h1,h2,h3,h4{
            margin:0 0 8px 0;
            color:#1d4ed8;
        }
        .muted{
            color:#666;
        }
        .section{
            margin-bottom:18px;
        }
        .box{
            border:1px solid #d9dee7;
            border-radius:6px;
            padding:10px;
            margin-bottom:10px;
            background:#fafafa;
        }
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:8px;
        }
        th, td{
            border:1px solid #d9dee7;
            padding:7px;
            vertical-align:top;
        }
        th{
            background:#eef2ff;
            text-align:left;
        }
        .flow{
            text-align:center;
            margin:10px 0;
        }
        .flow-step{
            border:1px solid #cbd5e1;
            background:#f8fafc;
            padding:8px;
            margin:4px 0;
            border-radius:4px;
            font-weight:bold;
        }
        .arrow{
            font-size:14px;
            color:#64748b;
        }
        ul, ol{
            margin:6px 0 0 18px;
            padding:0;
        }
    </style>
</head>
<body>

    <div class="section">
        <h1>{{ $appName }} ERP</h1>
        <h2>Projects Module Guide</h2>
        <div class="muted">Comprehensive workflow, architecture, SOP and integrations</div>
    </div>

    <div class="section box">
        <h3>1. Module Purpose</h3>
        <p>
            The Projects module manages the operational, financial and commercial lifecycle of projects.
            It connects planning, execution, labour tracking, cost capture, budgeting, billing and profitability
            into a structured workflow that supports project teams, finance, management and auditors.
        </p>
    </div>

    <div class="section">
        <h3>2. Module Components</h3>
        <table>
            <thead>
                <tr>
                    <th style="width:22%">Component</th>
                    <th style="width:28%">Purpose</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Projects</td>
                    <td>Master project record</td>
                    <td>Parent entity for all project transactions and reporting.</td>
                </tr>
                <tr>
                    <td>Project Tasks</td>
                    <td>Work breakdown structure</td>
                    <td>Supports execution and granular operational monitoring.</td>
                </tr>
                <tr>
                    <td>Project Milestones</td>
                    <td>Major checkpoints and deliverables</td>
                    <td>Supports control, completion measurement and billing triggers.</td>
                </tr>
                <tr>
                    <td>Project Timesheets</td>
                    <td>Labour and effort tracking</td>
                    <td>Captures labour cost and possible billable value.</td>
                </tr>
                <tr>
                    <td>Project Costs</td>
                    <td>Cost accumulation</td>
                    <td>Tracks labour and non-labour spend.</td>
                </tr>
                <tr>
                    <td>Project Budgets</td>
                    <td>Planned financial baseline</td>
                    <td>Supports budget vs actual and control.</td>
                </tr>
                <tr>
                    <td>Project Invoices</td>
                    <td>Commercial billing</td>
                    <td>Converts project delivery into billable revenue.</td>
                </tr>
                <tr>
                    <td>Project Profitability</td>
                    <td>Executive analytics</td>
                    <td>Shows margin, burn rate, budget usage and project health.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>3. Visual Workflow</h3>
        <div class="flow">
            <div class="flow-step">Project Setup</div>
            <div class="arrow">↓</div>
            <div class="flow-step">Tasks and Milestones</div>
            <div class="arrow">↓</div>
            <div class="flow-step">Timesheets and Costs</div>
            <div class="arrow">↓</div>
            <div class="flow-step">Budget vs Actual</div>
            <div class="arrow">↓</div>
            <div class="flow-step">Project Invoicing</div>
            <div class="arrow">↓</div>
            <div class="flow-step">Profitability and Management Oversight</div>
        </div>
    </div>

    <div class="section">
        <h3>4. Standard Operating Procedure</h3>
        <div class="box">
            <h4>4.1 Project Setup</h4>
            <ol>
                <li>Create the project record.</li>
                <li>Define owner, timeline and status.</li>
                <li>Confirm whether project is billable or internal.</li>
            </ol>
        </div>
        <div class="box">
            <h4>4.2 Work Breakdown</h4>
            <ol>
                <li>Create tasks for the work structure.</li>
                <li>Create milestones for major deliverables and commercial checkpoints.</li>
            </ol>
        </div>
        <div class="box">
            <h4>4.3 Labour and Cost Capture</h4>
            <ol>
                <li>Employees submit timesheets.</li>
                <li>Approved timesheets generate labour cost.</li>
                <li>Additional project costs are recorded and classified.</li>
            </ol>
        </div>
        <div class="box">
            <h4>4.4 Budget Control</h4>
            <ol>
                <li>Create and approve the project budget.</li>
                <li>Compare actual project cost against the approved baseline.</li>
            </ol>
        </div>
        <div class="box">
            <h4>4.5 Billing</h4>
            <ol>
                <li>Create invoice from milestones, timesheets, fixed fee or manual lines.</li>
                <li>Post invoice after review.</li>
                <li>Monitor billed value and outstanding balances.</li>
            </ol>
        </div>
    </div>

    <div class="section">
        <h3>5. Integration with Other Modules</h3>
        <table>
            <thead>
                <tr>
                    <th style="width:20%">Module</th>
                    <th style="width:32%">Integration Point</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Finance</td>
                    <td>Project invoices, profitability and future receivable posting</td>
                    <td>Commercial and financial visibility.</td>
                </tr>
                <tr>
                    <td>Procurement</td>
                    <td>Future direct tagging of procurement spend to projects</td>
                    <td>True landed project cost.</td>
                </tr>
                <tr>
                    <td>HR / Employees</td>
                    <td>Timesheet labour source</td>
                    <td>Labour accountability and utilisation.</td>
                </tr>
                <tr>
                    <td>CRM / Sales</td>
                    <td>Customer-facing project billing</td>
                    <td>Revenue recovery and customer project tracking.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>6. Internal Controls</h3>
        <ul>
            <li>Approval of timesheets before labour cost recognition.</li>
            <li>Approval of budgets before use as baseline.</li>
            <li>Posting control for project invoices.</li>
            <li>Draft and void lifecycle for audit trail preservation.</li>
            <li>Source traceability across costs and invoice lines.</li>
        </ul>
    </div>

    <div class="section">
        <h3>7. Management Reporting Use</h3>
        <ul>
            <li>Budget vs actual cost</li>
            <li>Profitability by project</li>
            <li>Burn rate analysis</li>
            <li>Labour mix and billable ratio</li>
            <li>Task and milestone completion</li>
            <li>Project billing visibility</li>
        </ul>
    </div>

    <div class="section">
        <h3>8. Future Enhancements</h3>
        <ul>
            <li>Project WIP / Unbilled Revenue Dashboard</li>
            <li>Project Payment Tracking</li>
            <li>Project Revenue Recognition</li>
            <li>Project Risks and Issues Register</li>
            <li>Portfolio Dashboard</li>
        </ul>
    </div>

</body>
</html>