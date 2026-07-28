{{-- File: Modules/Finance/Resources/views/finance/docs/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="container-fluid">

  {{-- ===================== HEADER ===================== --}}
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-1">Finance Module SOP, Workflow & Architecture</h4>
      <div class="text-muted small">
        <span class="me-2"><b>{{ $appName }}</b></span>
        <span class="me-2">•</span>
        <span class="me-2">{{ $companyName }}</span>
        <span class="me-2">•</span>
        <span class="me-2">Version: {{ $version }}</span>
        <span class="me-2">•</span>
        <span class="me-2">Generated: {{ $generatedAt }}</span>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('admin.finance.docs.pdf') }}" class="btn btn-primary">
        <i class="fas fa-file-pdf me-1"></i> Download PDF
      </a>
      <button class="btn btn-outline-secondary" onclick="window.print()">
        <i class="fas fa-print me-1"></i> Print
      </button>
    </div>
  </div>

  {{-- ===================== SUMMARY CARDS ===================== --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card border h-100">
        <div class="card-body">
          <div class="text-muted small">Single source of truth</div>
          <div class="fw-semibold">Posted Journals</div>
          <div class="small text-muted mt-1">
            All operational modules post to GL via <b>finance_journal_entries</b> + <b>finance_journal_entry_lines</b>.
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border h-100">
        <div class="card-body">
          <div class="text-muted small">Traceability</div>
          <div class="fw-semibold">source_type + source_id</div>
          <div class="small text-muted mt-1">
            Every journal links back to the originating document for audit and drilldown.
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border h-100">
        <div class="card-body">
          <div class="text-muted small">Controls</div>
          <div class="fw-semibold">Period Lock + Reversal</div>
          <div class="small text-muted mt-1">
            No edit after posting. Corrections are handled via reversal journals (<b>reversal_of_id</b>).
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border h-100">
        <div class="card-body">
          <div class="text-muted small">Bank accuracy</div>
          <div class="fw-semibold">Reconciliation</div>
          <div class="small text-muted mt-1">
            Statement lines must match bank-side journal lines; close only when difference = <b>0.00</b>.
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===================== TOC + QUICK NAV ===================== --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-lg-3">
          <div class="fw-semibold mb-2">Contents</div>
          <div class="list-group small">
            <a class="list-group-item list-group-item-action" href="#overview">1. Overview</a>
            <a class="list-group-item list-group-item-action" href="#scope">2. Scope & Module Map</a>
            <a class="list-group-item list-group-item-action" href="#principles">3. Principles & Controls</a>
            <a class="list-group-item list-group-item-action" href="#workflow">4. End-to-End Workflow</a>
            <a class="list-group-item list-group-item-action" href="#posting">5. Posting Rules (Double Entry)</a>
            <a class="list-group-item list-group-item-action" href="#traceability">6. Traceability & Audit Trail</a>
            <a class="list-group-item list-group-item-action" href="#banking">7. Banking & Reconciliation SOP</a>
            <a class="list-group-item list-group-item-action" href="#budget">8. Budgeting & Variance SOP</a>
            <a class="list-group-item list-group-item-action" href="#architecture">9. Architecture (Actual Tables)</a>
            <a class="list-group-item list-group-item-action" href="#raci">10. RACI (Roles)</a>
            <a class="list-group-item list-group-item-action" href="#rcm">11. Risk & Control Matrix</a>
            <a class="list-group-item list-group-item-action" href="#checklists">12. Checklists</a>
          </div>
        </div>

        <div class="col-lg-9">
          <div class="alert alert-info mb-0">
            <div class="fw-semibold">Purpose</div>
            <div class="small">
              This page documents how Finance works in <b>{{ $appName }}</b> for onboarding, audit evidence,
              and consistent operations. Use the PDF for governance files and external audits.
            </div>
          </div>

          <div class="mt-3 row g-3">
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <div class="fw-semibold mb-1">Operational Docs</div>
                <div class="small text-muted">SOP steps for each key process with controls and acceptance checks.</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <div class="fw-semibold mb-1">Technical Docs</div>
                <div class="small text-muted">Tables, fields, and architecture patterns used across the module.</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <div class="fw-semibold mb-1">Audit Readiness</div>
                <div class="small text-muted">Traceability, RACI, and risk-control matrix for internal controls.</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  {{-- ===================== 1. OVERVIEW ===================== --}}
  <div class="card mb-3" id="overview">
    <div class="card-header fw-semibold">1. Overview</div>
    <div class="card-body">
      <p class="mb-2">
        The Finance module is designed as a <b>double-entry accounting engine</b>. Operational documents
        (invoices, bills, expenses, payments, bank transactions) are validated and then posted into the General Ledger.
        Reports and analysis (Trial Balance, P&amp;L, Balance Sheet, Cash Flow, Budget vs Actual) read from <b>posted</b> journals only.
      </p>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Key Outcomes</div>
            <ul class="mb-0">
              <li>Accurate financial statements generated from a consistent ledger.</li>
              <li>Governance controls: approvals, period lock, and year close processes.</li>
              <li>Audit trail linking each ledger entry to its originating document.</li>
              <li>Reconciliation ensures the system’s bank position matches the bank statement.</li>
              <li>Budgets support planning and variance analysis with management visibility.</li>
            </ul>
          </div>
        </div>

        <div class="col-md-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Core Design Decisions</div>
            <ul class="mb-0">
              <li><b>Posted = immutable</b>: corrections via reversal journals, never edit posted entries.</li>
              <li><b>Traceability</b>: use <code>source_type</code> + <code>source_id</code> on journal headers.</li>
              <li><b>Company scoping</b>: data is filtered by <code>company_id</code> (user/company boundary).</li>
              <li><b>Period control</b>: posting restricted by fiscal periods and year-close locks.</li>
              <li><b>Bank accuracy</b>: reconciliation closes only at difference = 0.</li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>

  {{-- ===================== 2. SCOPE & MODULE MAP ===================== --}}
  <div class="card mb-3" id="scope">
    <div class="card-header fw-semibold">2. Scope & Module Map</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Included Modules</div>
            <div class="row">
              <div class="col-6">
                <ul class="mb-0">
                  <li>Account Mappings</li>
                  <li>Chart of Accounts</li>
                  <li>Bank &amp; Cash Accounts</li>
                  <li>Fiscal Periods + Year Close</li>
                  <li>Journal Entries</li>
                </ul>
              </div>
              <div class="col-6">
                <ul class="mb-0">
                  <li>Invoices + Payments</li>
                  <li>Expenses + Categories</li>
                  <li>Supplier Bills (AP)</li>
                  <li>Bank Transactions</li>
                  <li>Bank Reconciliation</li>
                  <li>Budgets + Reports</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Operational “Source Documents”</div>
            <div class="small text-muted mb-2">
              Source documents are the operational transactions users create. They are posted into the ledger.
            </div>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>Document</th>
                    <th>Main Purpose</th>
                    <th>Posts To</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Invoice</td>
                    <td>Record revenue + receivable</td>
                    <td>GL (AR + Revenue)</td>
                  </tr>
                  <tr>
                    <td>Payment</td>
                    <td>Receive/Pay money</td>
                    <td>GL (Bank + AR/AP/Expense)</td>
                  </tr>
                  <tr>
                    <td>Expense</td>
                    <td>Record spend (paid now / reimbursable)</td>
                    <td>GL (Expense + Bank/Payable)</td>
                  </tr>
                  <tr>
                    <td>Supplier Bill (AP)</td>
                    <td>Record liability to supplier</td>
                    <td>GL (Expense/Asset + AP)</td>
                  </tr>
                  <tr>
                    <td>Bank Transaction</td>
                    <td>Direct bank items (fees, interest, transfers)</td>
                    <td>GL (Bank + Counter account)</td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===================== 3. PRINCIPLES & CONTROLS ===================== --}}
  <div class="card mb-3" id="principles">
    <div class="card-header fw-semibold">3. Principles & Controls</div>
    <div class="card-body">
      <div class="row g-3">

        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Control Principles</div>
            <ol class="mb-0">
              <li><b>Segregation of Duties</b>: creator ≠ approver ≠ poster (where practical).</li>
              <li><b>Period Control</b>: posting blocked in closed periods; year close locks historic periods.</li>
              <li><b>Immutable Posting</b>: posted journals cannot be edited; corrections are reversals.</li>
              <li><b>Traceability</b>: each journal references the originating document via source_type + source_id.</li>
              <li><b>Auditability</b>: timestamps + user IDs captured for posting and reversing.</li>
              <li><b>Bank Truth</b>: reconciliation closes only when statement balance aligns with system.</li>
            </ol>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Journal Status Model (Your Table)</div>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>Status</th>
                    <th>Meaning</th>
                    <th>Allowed Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><span class="badge bg-secondary">draft</span></td>
                    <td>Prepared but not committed</td>
                    <td>Edit, validate, approve</td>
                  </tr>
                  <tr>
                    <td><span class="badge bg-success">posted</span></td>
                    <td>Committed to GL</td>
                    <td>View only, reverse if wrong</td>
                  </tr>
                  <tr>
                    <td><span class="badge bg-warning text-dark">reversed</span></td>
                    <td>Reversal was created</td>
                    <td>View only, link to reversal</td>
                  </tr>
                  <tr>
                    <td><span class="badge bg-danger">voided</span></td>
                    <td>Cancelled</td>
                    <td>View only</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="small text-muted mt-2">
              Audit fields present: <b>posted_at</b>, <b>posted_by</b>, <b>reversed_at</b>, <b>reversed_by</b>,
              and linkage via <b>reversal_of_id</b>.
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- ===================== 4. WORKFLOW ===================== --}}
  <div class="card mb-3" id="workflow">
    <div class="card-header fw-semibold">4. End-to-End Workflow</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-lg-7">
          <div class="border rounded p-3">
            <div class="fw-semibold mb-2">Workflow Map (Source → GL → Reports)</div>

            {{-- Inline SVG: prints nicely --}}
            <svg viewBox="0 0 980 360" width="100%" height="auto" role="img" aria-label="Finance workflow diagram">
              <defs>
                <style>
                  .bx { fill:#fff; stroke:#cbd5e1; stroke-width:2; rx:12; }
                  .ttl { font: 700 14px sans-serif; fill:#0f172a; }
                  .txt { font: 12px sans-serif; fill:#334155; }
                  .arr { stroke:#64748b; stroke-width:2; marker-end:url(#arrow); }
                  .sub { fill:#f8fafc; stroke:#e2e8f0; stroke-width:1; rx:10; }
                </style>
                <marker id="arrow" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                  <path d="M0,0 L10,3 L0,6 Z" fill="#64748b"></path>
                </marker>
              </defs>

              <!-- Source docs -->
              <rect class="bx" x="20" y="35" width="220" height="110"></rect>
              <text class="ttl" x="38" y="62">Source Documents</text>
              <text class="txt" x="38" y="85">Invoices • Payments</text>
              <text class="txt" x="38" y="104">Expenses • Supplier Bills (AP)</text>
              <text class="txt" x="38" y="123">Bank Transactions</text>

              <!-- Validation -->
              <rect class="bx" x="280" y="35" width="220" height="110"></rect>
              <text class="ttl" x="298" y="62">Validation & Controls</text>
              <text class="txt" x="298" y="85">Company scope • Required fields</text>
              <text class="txt" x="298" y="104">Period open • Balanced totals</text>
              <text class="txt" x="298" y="123">Approval (optional)</text>

              <!-- Posting -->
              <rect class="bx" x="540" y="35" width="220" height="110"></rect>
              <text class="ttl" x="558" y="62">Posting Engine</text>
              <text class="txt" x="558" y="85">Creates finance_journal_entries</text>
              <text class="txt" x="558" y="104">Creates finance_journal_entry_lines</text>
              <text class="txt" x="558" y="123">Sets source_type + source_id</text>

              <!-- GL -->
              <rect class="bx" x="800" y="35" width="160" height="110"></rect>
              <text class="ttl" x="818" y="62">General Ledger</text>
              <text class="txt" x="818" y="85">Only status=posted</text>
              <text class="txt" x="818" y="104">feeds reports</text>

              <!-- Controls bottom -->
              <rect class="sub" x="280" y="200" width="300" height="120"></rect>
              <text class="ttl" x="300" y="228">Control Layer</text>
              <text class="txt" x="300" y="250">• Period Lock / Year Close</text>
              <text class="txt" x="300" y="270">• No edit after posting</text>
              <text class="txt" x="300" y="290">• Reversal journals (reversal_of_id)</text>
              <text class="txt" x="300" y="310">• Audit fields (posted_by, reversed_by)</text>

              <!-- Outputs bottom -->
              <rect class="sub" x="620" y="200" width="340" height="120"></rect>
              <text class="ttl" x="640" y="228">Outputs</text>
              <text class="txt" x="640" y="250">Reports: GL • Trial Balance • P&amp;L • Balance Sheet • Cash Flow</text>
              <text class="txt" x="640" y="270">Bank Reconciliation • Budget vs Actual</text>
              <text class="txt" x="640" y="290">Exports: PDF / Excel</text>
              <text class="txt" x="640" y="310">Drilldown: Report → Journal → Source</text>

              <!-- Arrows -->
              <line class="arr" x1="240" y1="90" x2="280" y2="90"></line>
              <line class="arr" x1="500" y1="90" x2="540" y2="90"></line>
              <line class="arr" x1="760" y1="90" x2="800" y2="90"></line>
              <line class="arr" x1="650" y1="145" x2="430" y2="200"></line>
              <line class="arr" x1="880" y1="145" x2="790" y2="200"></line>
            </svg>

            <div class="small text-muted mt-2">
              Reports always compute from <b>posted</b> journals. Any corrections must be posted as reversals.
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Operational Workflow Steps (SOP)</div>
            <ol class="mb-0">
              <li><b>Create</b> source document with correct date, reference, party, and accounts.</li>
              <li><b>Validate</b> required fields, company scope, and period open.</li>
              <li><b>Approve</b> (recommended): manager reviews correctness and evidence.</li>
              <li><b>Post</b>: system writes balanced journal entry + lines and captures audit fields.</li>
              <li><b>Reconcile</b>: clear bank lines vs statement lines and close when difference is zero.</li>
              <li><b>Report</b>: GL/TB/P&amp;L/BS/CF generated from posted journals only.</li>
              <li><b>Close</b>: lock fiscal period after review; run year-close at end of fiscal year.</li>
            </ol>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- ===================== 5. POSTING RULES ===================== --}}
  <div class="card mb-3" id="posting">
    <div class="card-header fw-semibold">5. Posting Rules (Double Entry)</div>
    <div class="card-body">

      <div class="alert alert-warning">
        <div class="fw-semibold">Non-negotiable rule</div>
        <div class="small mb-0">
          Once a journal entry is <b>posted</b>, it must never be edited. Correct errors using a <b>reversal journal</b> (linked via <b>reversal_of_id</b>).
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Process</th>
              <th>Typical Posting</th>
              <th>Common Accounts</th>
              <th>Evidence / Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><b>Customer Invoice</b></td>
              <td>Dr Accounts Receivable / Cr Revenue (+ Tax)</td>
              <td>AR, Sales, VAT Output</td>
              <td>Attach invoice copy, delivery proof (if needed). Payment clears AR.</td>
            </tr>
            <tr>
              <td><b>Customer Payment</b></td>
              <td>Dr Bank / Cr Accounts Receivable</td>
              <td>Bank, AR</td>
              <td>Bank line should carry <b>bank_account_id</b> for reconciliation.</td>
            </tr>
            <tr>
              <td><b>Supplier Bill (AP)</b></td>
              <td>Dr Expense/Asset / Cr Accounts Payable</td>
              <td>Expense/Asset, AP</td>
              <td>Attach supplier invoice. Payment clears AP.</td>
            </tr>
            <tr>
              <td><b>Supplier Payment</b></td>
              <td>Dr Accounts Payable / Cr Bank</td>
              <td>AP, Bank</td>
              <td>Reference remittance advice; ensure bank account selected.</td>
            </tr>
            <tr>
              <td><b>Expense (Paid Now)</b></td>
              <td>Dr Expense / Cr Bank</td>
              <td>Expense, Bank</td>
              <td>Receipt mandatory for audit. If employee reimbursement, use payable to employee.</td>
            </tr>
            <tr>
              <td><b>Transfer Between Banks</b></td>
              <td>Dr Bank B / Cr Bank A</td>
              <td>Bank accounts</td>
              <td>Use transfer type; both sides should be reconcilable.</td>
            </tr>
            <tr>
              <td><b>Bank Fee / Interest</b></td>
              <td>Dr Bank Charges (or Bank Interest) / Cr Bank</td>
              <td>Charges/Interest, Bank</td>
              <td>Usually posted during reconciliation as adjustment.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="border rounded p-3">
        <div class="fw-semibold mb-2">Posting Acceptance Checks</div>
        <div class="row g-2 small">
          <div class="col-md-4"><span class="badge bg-light text-dark">Check</span> Debits = Credits</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Check</span> Period is open</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Check</span> Accounts exist in finance_accounts</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Check</span> company_id matches user</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Check</span> Evidence attached where required</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Check</span> bank_account_id set for bank lines</div>
        </div>
      </div>

    </div>
  </div>

  {{-- ===================== 6. TRACEABILITY & AUDIT TRAIL ===================== --}}
  <div class="card mb-3" id="traceability">
    <div class="card-header fw-semibold">6. Traceability & Audit Trail (Journal → Source)</div>
    <div class="card-body">
      <p class="mb-2">
        Finance is audit-ready when every number in a report can be traced back to a posted journal, and from that journal to the originating document.
        Your GL header table (<b>finance_journal_entries</b>) already supports this with <b>source_type</b> and <b>source_id</b>.
      </p>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">How Drilldown Works</div>
            <ol class="mb-0">
              <li>User views a report (e.g., P&amp;L).</li>
              <li>User drills into an account total (sum of posted journal lines).</li>
              <li>User opens a journal entry header (finance_journal_entries).</li>
              <li>System resolves the source document using:
                <div class="mt-2 small">
                  <span class="badge bg-light text-dark">source_type</span>
                  <span class="badge bg-light text-dark">source_id</span>
                </div>
              </li>
              <li>User opens the originating record (invoice/expense/payment/bill/bank transaction).</li>
            </ol>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Journal Header Fields (Actual)</div>
            <div class="small text-muted">
              <div><b>Identity</b>: id, company_id, period_id, entry_no</div>
              <div><b>Date</b>: entry_date</div>
              <div><b>Context</b>: reference, memo</div>
              <div><b>Status</b>: status (draft/posted/reversed/voided)</div>
              <div><b>Trace</b>: source_type, source_id</div>
              <div><b>Audit</b>: posted_at, posted_by, reversed_at, reversed_by</div>
              <div><b>Correction Link</b>: reversal_of_id</div>
              <div><b>Soft delete</b>: deleted_at</div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-3 border rounded p-3">
        <div class="fw-semibold mb-2">Recommended Source Type Convention</div>
        <div class="small text-muted mb-2">
          Use consistent strings so routing/drilldowns are deterministic and reporting remains clean.
        </div>

        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Business Event</th>
                <th>source_type (example)</th>
                <th>source_id points to</th>
                <th>Typical Posting</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Customer Invoice</td>
                <td><code>finance_invoice</code></td>
                <td>finance_invoices.id</td>
                <td>Dr AR / Cr Revenue</td>
              </tr>
              <tr>
                <td>Customer Payment</td>
                <td><code>finance_payment</code></td>
                <td>finance_payments.id</td>
                <td>Dr Bank / Cr AR</td>
              </tr>
              <tr>
                <td>Expense</td>
                <td><code>finance_expense</code></td>
                <td>finance_expenses.id</td>
                <td>Dr Expense / Cr Bank</td>
              </tr>
              <tr>
                <td>Supplier Bill</td>
                <td><code>finance_supplier_bill</code></td>
                <td>finance_supplier_bills.id</td>
                <td>Dr Expense/Asset / Cr AP</td>
              </tr>
              <tr>
                <td>Bank Transaction</td>
                <td><code>finance_bank_txn</code></td>
                <td>finance_bank_transactions.id</td>
                <td>Dr/Cr Bank + Counter account</td>
              </tr>
              <tr>
                <td>Manual Journal</td>
                <td><code>manual_journal</code></td>
                <td>finance_journal_entries.id</td>
                <td>Balanced entry</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-3 alert alert-success mb-0">
        <div class="fw-semibold">Audit principle</div>
        <div class="small">
          Any adjustment must be explainable: journal entry memo + reference + evidence + linked source document.
        </div>
      </div>

    </div>
  </div>

  {{-- ===================== 7. BANKING & RECONCILIATION SOP ===================== --}}
  <div class="card mb-3" id="banking">
    <div class="card-header fw-semibold">7. Banking & Reconciliation SOP</div>
    <div class="card-body">

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Bank Transactions (Operational)</div>
            <ul class="mb-0">
              <li>Used for deposits, withdrawals, transfers, bank fees, interest, and bank-originated items.</li>
              <li>Each transaction should result in a posted journal entry with bank-side line linked by <b>bank_account_id</b>.</li>
              <li>Transfers should create two bank impacts (outflow + inflow) or an explicit transfer record depending on your design.</li>
              <li>All bank transactions must include: date, reference, description, amount, and counter account.</li>
            </ul>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Bank Reconciliation (Control)</div>
            <ul class="mb-0">
              <li>Creates a reconciliation session for a specific bank account and statement period.</li>
              <li>Imports/records statement lines and matches them to uncleared bank-side journal lines.</li>
              <li>Unmatched statement items are either:
                <b>posted as adjustments</b> (fees/interest), or
                investigated as missing/incorrect postings.</li>
              <li>Reconciliation is closed only when difference equals <b>0.00</b>.</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="mt-3 border rounded p-3">
        <div class="fw-semibold mb-2">Reconciliation Workflow (Detailed)</div>
        <ol class="mb-0">
          <li><b>Open reconciliation</b> for the bank account + statement range.</li>
          <li><b>Enter statement opening balance</b> and statement closing balance.</li>
          <li><b>Import statement lines</b> (or add manually): date, description, amount, reference.</li>
          <li><b>System lists uncleared bank-side journal lines</b> (posted only) for that bank account.</li>
          <li><b>Match</b> statement lines to bank journal lines (1:1 recommended).</li>
          <li><b>Post adjustments</b> for fees/interest if they exist on statement but not in system.</li>
          <li><b>Review difference</b>: must equal 0.00 before closing.</li>
          <li><b>Close reconciliation</b>: lock the session to prevent modifications.</li>
        </ol>
      </div>

      <div class="mt-3 border rounded p-3">
        <div class="fw-semibold mb-2">Reconciliation Integrity Rules</div>
        <div class="row g-2 small">
          <div class="col-md-4"><span class="badge bg-success">Rule</span> Only posted journal lines are reconcilable</div>
          <div class="col-md-4"><span class="badge bg-success">Rule</span> bank_account_id must exist on bank-impact lines</div>
          <div class="col-md-4"><span class="badge bg-success">Rule</span> One statement line matches one journal line</div>
          <div class="col-md-4"><span class="badge bg-success">Rule</span> Closing requires difference = 0.00</div>
          <div class="col-md-4"><span class="badge bg-success">Rule</span> Closed recon is read-only</div>
          <div class="col-md-4"><span class="badge bg-success">Rule</span> Adjustments must have memo + evidence</div>
        </div>
      </div>

    </div>
  </div>

  {{-- ===================== 8. BUDGETING & VARIANCE SOP ===================== --}}
  <div class="card mb-3" id="budget">
    <div class="card-header fw-semibold">8. Budgeting & Variance SOP</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Budget Setup (Operational Steps)</div>
            <ol class="mb-0">
              <li>Create budget: name, start date, end date, period type (monthly/quarterly/annual).</li>
              <li>Select accounts (typically Income + Expense accounts in P&amp;L).</li>
              <li>Enter period amounts using grid editor (spread/copy/even allocation supported).</li>
              <li>Review totals and approve internally.</li>
              <li>Lock budget when finalized to prevent accidental edits.</li>
            </ol>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Budget vs Actual (Management Reporting)</div>
            <ul class="mb-0">
              <li>Actuals are computed from <b>posted</b> journal lines within the budget range.</li>
              <li>Variance calculations:
                <ul class="mt-1">
                  <li><b>Expense variance</b>: Budget − Actual (favourable if positive)</li>
                  <li><b>Income variance</b>: Actual − Budget (favourable if positive)</li>
                </ul>
              </li>
              <li>Drilldown path: variance line → account → journal lines → journal header → source document.</li>
              <li>Use variance insights to adjust operations, forecasting, and spending controls.</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="mt-3 border rounded p-3">
        <div class="fw-semibold mb-2">Budget Governance Rules</div>
        <div class="row g-2 small">
          <div class="col-md-4"><span class="badge bg-light text-dark">Rule</span> Budget owner required</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Rule</span> Lock approved budgets</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Rule</span> Use posted actuals only</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Rule</span> Document assumptions (notes)</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Rule</span> Changes require approval</div>
          <div class="col-md-4"><span class="badge bg-light text-dark">Rule</span> Track revisions (optional)</div>
        </div>
      </div>

    </div>
  </div>

  {{-- ===================== 9. ARCHITECTURE (ACTUAL TABLES) ===================== --}}
  <div class="card mb-3" id="architecture">
    <div class="card-header fw-semibold">9. Architecture (Actual Tables & Field Contracts)</div>
    <div class="card-body">

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Ledger Core (Actual)</div>

            <div class="small">
              <div class="mb-2"><b>COA</b>: <code>finance_accounts</code></div>

              <div class="mb-2"><b>GL Header</b>: <code>finance_journal_entries</code></div>
              <div class="ms-3 text-muted">
                id, company_id, period_id, entry_no, entry_date, reference, memo,
                status, <b>source_type</b>, <b>source_id</b>, posted_at, posted_by,
                reversed_at, reversed_by, <b>reversal_of_id</b>, created_at, updated_at, deleted_at
              </div>

              <div class="mt-2 mb-2"><b>GL Lines</b>: <code>finance_journal_entry_lines</code></div>
              <div class="ms-3 text-muted">
                id, journal_entry_id, account_id, description, debit, credit, memo,
                currency_code, fx_rate, party_type, party_id, bank_account_id, timestamps
              </div>
            </div>

            <div class="small text-muted mt-2">
              Contract: every posted journal must be balanced (sum debit = sum credit) and must belong to the same company_id scope.
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Banking + Budget (Actual)</div>
            <div class="small">
              <div class="mb-2"><b>Bank Accounts</b>: <code>finance_bank_accounts</code></div>
              <div class="ms-3 text-muted">
                company_id, name, type, currency_code, bank_name, account_number, sort_code, iban, swift,
                opening_balance, opening_balance_date, gl_account_id, is_active, notes, timestamps
              </div>

              <div class="mt-2 mb-2"><b>Bank Txns</b>: <code>finance_bank_transactions</code> + <code>finance_bank_transaction_lines</code></div>
              <div class="ms-3 text-muted">
                deposit/withdrawal/transfer with status and journal_entry_id linkage (posting integration).
              </div>

              <div class="mt-2 mb-2"><b>Budget</b>: <code>finance_budgets</code> + <code>finance_budget_lines</code> + <code>finance_budget_amounts</code></div>
              <div class="ms-3 text-muted">
                Budget grid by account_id and period_start → amount; approved/locked governance recommended.
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-3 border rounded p-3">
        <div class="fw-semibold mb-2">Reference Architecture Pattern</div>
        <div class="small">
          <ul class="mb-0">
            <li><b>Controllers</b> validate + enforce permissions + company scoping.</li>
            <li><b>Services</b> perform posting: build journal header + lines, enforce balancing, set status, set source_type/source_id.</li>
            <li><b>Reports</b> query posted journals; never compute from source documents directly.</li>
            <li><b>Reconciliation</b> links statement lines to bank-impact journal lines using bank_account_id.</li>
            <li><b>Budget actuals</b> are aggregates of posted journal lines filtered by date range and account.</li>
          </ul>
        </div>
      </div>

    </div>
  </div>

  {{-- ===================== 10. RACI ===================== --}}
  <div class="card mb-3" id="raci">
    <div class="card-header fw-semibold">10. RACI (Roles & Responsibilities)</div>
    <div class="card-body">
      <div class="small text-muted mb-2">
        RACI: <b>R</b> Responsible, <b>A</b> Accountable, <b>C</b> Consulted, <b>I</b> Informed.
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Activity</th>
              <th>Finance Officer</th>
              <th>Finance Manager</th>
              <th>System Admin</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Create invoices/expenses/bills</td>
              <td><span class="badge bg-primary">R</span></td>
              <td><span class="badge bg-secondary">C</span></td>
              <td><span class="badge bg-light text-dark">I</span></td>
            </tr>
            <tr>
              <td>Approve postings (optional control)</td>
              <td><span class="badge bg-light text-dark">C</span></td>
              <td><span class="badge bg-primary">A</span></td>
              <td><span class="badge bg-light text-dark">I</span></td>
            </tr>
            <tr>
              <td>Post to GL</td>
              <td><span class="badge bg-primary">R</span></td>
              <td><span class="badge bg-primary">A</span></td>
              <td><span class="badge bg-light text-dark">I</span></td>
            </tr>
            <tr>
              <td>Bank reconciliation</td>
              <td><span class="badge bg-primary">R</span></td>
              <td><span class="badge bg-primary">A</span></td>
              <td><span class="badge bg-light text-dark">I</span></td>
            </tr>
            <tr>
              <td>Close fiscal period / year close</td>
              <td><span class="badge bg-secondary">C</span></td>
              <td><span class="badge bg-primary">A</span></td>
              <td><span class="badge bg-primary">R</span></td>
            </tr>
            <tr>
              <td>Permissions + setup (COA, mappings)</td>
              <td><span class="badge bg-light text-dark">I</span></td>
              <td><span class="badge bg-secondary">C</span></td>
              <td><span class="badge bg-primary">A</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ===================== 11. RISK & CONTROL MATRIX ===================== --}}
  <div class="card mb-3" id="rcm">
    <div class="card-header fw-semibold">11. Risk & Control Matrix (RCM)</div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Risk</th>
              <th>Impact</th>
              <th>Control</th>
              <th>System Evidence</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Posting into closed period</td>
              <td>Misstated accounts / audit failure</td>
              <td>Period lock and year close prevents posting</td>
              <td>Blocked by period rules + logs</td>
            </tr>
            <tr>
              <td>Editing posted entries</td>
              <td>Broken audit trail</td>
              <td>Disable edits; only allow reversal</td>
              <td>status=posted + reversal_of_id link</td>
            </tr>
            <tr>
              <td>Unapproved high-value postings</td>
              <td>Fraud / policy breach</td>
              <td>Approval workflow + thresholds (policy)</td>
              <td>Approver audit log (optional)</td>
            </tr>
            <tr>
              <td>Bank balance mismatch</td>
              <td>Cash misstatement</td>
              <td>Reconciliation closes only at difference=0</td>
              <td>Closed reconciliation record</td>
            </tr>
            <tr>
              <td>Wrong account mapping</td>
              <td>Misclassification of expenses/revenue</td>
              <td>Account mappings review + COA governance</td>
              <td>Mappings table + change logs (optional)</td>
            </tr>
            <tr>
              <td>Cross-company data access</td>
              <td>Data breach</td>
              <td>company_id scoping + permissions</td>
              <td>Query scoping + policy checks</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="small text-muted mt-2">
        Tip: add an audit log table for approvals and critical actions if you want stronger governance evidence.
      </div>
    </div>
  </div>

  {{-- ===================== 12. CHECKLISTS ===================== --}}
  <div class="card mb-5" id="checklists">
    <div class="card-header fw-semibold">12. Operational Checklists</div>
    <div class="card-body">
      <div class="row g-3">

        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Month-End Close Checklist</div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">All invoices posted</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">All supplier bills/expenses posted</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">All payments posted and matched</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Bank reconciliation closed (difference = 0.00)</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Trial balance reviewed</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">P&amp;L reviewed (major variances explained)</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Balance Sheet reviewed (key accounts verified)</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Fiscal period locked</label></div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold mb-2">Bank Reconciliation Checklist</div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Statement range set correctly</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Opening/closing balances entered</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Statement lines imported/entered</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">All matches reviewed and correct</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Fees/interest posted as adjustments (if any)</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Difference equals 0.00</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox"> <label class="form-check-label">Reconciliation closed and locked</label></div>
            <div class="small text-muted mt-2">
              If difference ≠ 0.00, do not close: investigate unmatched items or wrong postings.
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@push('styles')
<style>
  /* Small UX improvement for in-page links */
  html { scroll-behavior: smooth; }

  /* Print rules */
  @media print {
    .btn, .sidebar, .navbar, .footer, .list-group, .sidebar-heading { display:none !important; }
    .card { border: 1px solid #ddd !important; break-inside: avoid; }
    .card-header { background: #f3f4f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    a[href]:after { content: "" !important; }
    .container-fluid { padding: 0 !important; }
  }
</style>
@endpush