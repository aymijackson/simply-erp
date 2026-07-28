{{-- File: Modules/Finance/Resources/views/finance/docs/pdf.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Finance Module SOP, Workflow & Architecture</title>
  <style>
    @page { margin: 22px 22px 26px 22px; }

    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111827; line-height: 1.35; }

    .header { border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 14px; }
    .title { font-size: 18px; font-weight: 800; margin: 0; }
    .meta { margin-top: 4px; color: #374151; font-size: 10px; }
    .meta b { color: #111827; }
    .pill { display:inline-block; padding: 2px 8px; border: 1px solid #e5e7eb; border-radius: 999px; font-size: 10px; margin-right: 6px; }

    .toc { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #f9fafb; }
    .toc h3 { font-size: 12px; margin: 0 0 6px; }
    .toc ul { margin: 0; padding-left: 18px; }
    .toc li { margin: 2px 0; }

    .section { margin: 14px 0; page-break-inside: avoid; }
    .section h2 { font-size: 13px; margin: 0 0 8px; padding: 6px 8px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; }
    .box { border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px; }
    .note { border-left: 4px solid #f59e0b; background: #fffbeb; padding: 10px; border-radius: 6px; }
    .ok { border-left: 4px solid #10b981; background: #ecfdf5; padding: 10px; border-radius: 6px; }

    .grid2 { width: 100%; }
    .col { width: 49.2%; display: inline-block; vertical-align: top; }
    .col + .col { margin-left: 1%; }
    .spacer { height: 8px; }

    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
    th { background: #f8fafc; text-align: left; font-weight: 700; }

    ul, ol { margin: 6px 0 0 18px; }
    code { font-family: DejaVu Sans Mono, monospace; font-size: 10px; background: #f3f4f6; padding: 1px 4px; border-radius: 4px; }

    .footer { position: fixed; bottom: 10px; left: 22px; right: 22px; font-size: 10px; color: #6b7280; }
    .footer .left { float: left; }
    .footer .right { float: right; }

    /* DomPDF-compatible simple page break helper */
    .page-break { page-break-after: always; }
  </style>
</head>
<body>

  {{-- ===================== HEADER ===================== --}}
  <div class="header">
    <p class="title">Finance Module SOP, Workflow &amp; Architecture</p>
    <div class="meta">
      <span class="pill"><b>App:</b> {{ $appName }}</span>
      <span class="pill"><b>Company:</b> {{ $companyName }}</span>
      <span class="pill"><b>Version:</b> {{ $version }}</span>
      <span class="pill"><b>Generated:</b> {{ $generatedAt }}</span>
      <span class="pill"><b>Prepared by:</b> {{ $author }}</span>
    </div>
  </div>

  {{-- ===================== TABLE OF CONTENTS ===================== --}}
  <div class="toc">
    <h3>Contents</h3>
    <ul>
      <li>1. Overview</li>
      <li>2. Scope &amp; Module Map</li>
      <li>3. Principles &amp; Controls</li>
      <li>4. End-to-End Workflow (SOP)</li>
      <li>5. Posting Rules (Double Entry)</li>
      <li>6. Traceability &amp; Audit Trail (Journal → Source)</li>
      <li>7. Banking &amp; Reconciliation SOP</li>
      <li>8. Budgeting &amp; Variance SOP</li>
      <li>9. Architecture (Actual Tables &amp; Field Contracts)</li>
      <li>10. RACI (Roles &amp; Responsibilities)</li>
      <li>11. Risk &amp; Control Matrix (RCM)</li>
      <li>12. Checklists (Month-End + Reconciliation)</li>
    </ul>
  </div>

  {{-- ===================== 1. OVERVIEW ===================== --}}
  <div class="section">
    <h2>1. Overview</h2>
    <div class="box">
      The Finance module is built on a <b>double-entry accounting engine</b>. Operational documents (Invoices, Bills/AP, Expenses, Payments,
      Bank Transactions) are validated and then posted into the General Ledger. Financial reports (General Ledger, Trial Balance, Profit &amp; Loss,
      Balance Sheet, Cash Flow, and Budget vs Actual) compute from journal entries where <b>status = 'posted'</b>.
      <div class="spacer"></div>
      <b>Design goals:</b>
      <ul>
        <li>Single source of truth: posted journals.</li>
        <li>Strong controls: period lock, reversal-only corrections, and reconciliation integrity.</li>
        <li>Audit-ready traceability: reports → journals → originating document.</li>
      </ul>
    </div>
  </div>

  {{-- ===================== 2. SCOPE ===================== --}}
  <div class="section">
    <h2>2. Scope &amp; Module Map</h2>

    <div class="grid2">
      <div class="col">
        <div class="box">
          <b>Included Modules</b>
          <ul>
            <li>Account Mappings</li>
            <li>Chart of Accounts (<code>finance_accounts</code>)</li>
            <li>Bank &amp; Cash Accounts</li>
            <li>Fiscal Periods + Year Close</li>
            <li>Journal Entries (Manual)</li>
            <li>Invoices + Payments</li>
            <li>Expenses + Categories</li>
            <li>Supplier Bills (AP) + Payables controls</li>
            <li>Bank Transactions + Bank Reconciliation</li>
            <li>Budgets + Variance reporting</li>
            <li>Reports (GL, TB, P&amp;L, BS, CF)</li>
          </ul>
        </div>
      </div>

      <div class="col">
        <div class="box">
          <b>Source Documents &amp; Purpose</b>
          <table>
            <thead>
              <tr>
                <th>Document</th>
                <th>Purpose</th>
                <th>Posts To</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Invoice</td>
                <td>Recognise revenue + receivable</td>
                <td>GL (AR + Revenue)</td>
              </tr>
              <tr>
                <td>Payment</td>
                <td>Receive/pay cash</td>
                <td>GL (Bank + AR/AP/Expense)</td>
              </tr>
              <tr>
                <td>Expense</td>
                <td>Record spend</td>
                <td>GL (Expense + Bank/Payable)</td>
              </tr>
              <tr>
                <td>Supplier Bill (AP)</td>
                <td>Record supplier liability</td>
                <td>GL (Expense/Asset + AP)</td>
              </tr>
              <tr>
                <td>Bank Transaction</td>
                <td>Bank-originated items</td>
                <td>GL (Bank + Counter account)</td>
              </tr>
            </tbody>
          </table>
          <div class="spacer"></div>
          Each source document is linked to its journal entry using <b>source_type</b> and <b>source_id</b>.
        </div>
      </div>
    </div>
  </div>

  {{-- ===================== 3. PRINCIPLES ===================== --}}
  <div class="section">
    <h2>3. Principles &amp; Controls</h2>

    <div class="grid2">
      <div class="col">
        <div class="box">
          <b>Control Principles</b>
          <ol>
            <li><b>Segregation of duties</b>: creator ≠ approver ≠ poster (where practical).</li>
            <li><b>Period control</b>: posting blocked in closed periods; year-close locks history.</li>
            <li><b>Immutable posting</b>: do not edit posted entries; correct via reversal journals.</li>
            <li><b>Traceability</b>: every journal references the originating document (source_type/source_id).</li>
            <li><b>Auditability</b>: capture who posted/reversed and when (posted_by/posted_at etc.).</li>
            <li><b>Bank truth</b>: reconciliation closes only when difference equals 0.00.</li>
          </ol>
        </div>
      </div>

      <div class="col">
        <div class="box">
          <b>Journal Status Model (Your Table)</b>
          <table>
            <thead>
              <tr>
                <th>Status</th>
                <th>Meaning</th>
                <th>Allowed Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><b>draft</b></td>
                <td>Prepared but not committed</td>
                <td>Edit, validate, approve-ready checks</td>
              </tr>
              <tr>
                <td><b>posted</b></td>
                <td>Committed to GL</td>
                <td>View only, reverse if incorrect</td>
              </tr>
              <tr>
                <td><b>reversed</b></td>
                <td>Reversal journal exists</td>
                <td>View only, navigate to reversal</td>
              </tr>
              <tr>
                <td><b>voided</b></td>
                <td>Cancelled with history retained</td>
                <td>View only</td>
              </tr>
            </tbody>
          </table>

          <div class="spacer"></div>
          <div class="ok">
            <b>Audit fields present:</b> posted_at, posted_by, reversed_at, reversed_by, reversal_of_id.
          </div>
        </div>
      </div>
    </div>

    <div class="spacer"></div>
    <div class="note">
      <b>Important:</b> A posted journal is evidence. To correct an error, reverse it and post the correct entry.
      This maintains a clean audit trail and prevents hidden manipulation.
    </div>
  </div>

  {{-- ===================== 4. WORKFLOW SOP ===================== --}}
  <div class="section">
    <h2>4. End-to-End Workflow (SOP)</h2>
    <div class="box">
      <ol>
        <li><b>Create</b> a source document with date, reference, party, amount, and accounts.</li>
        <li><b>Validate</b> required fields, company scope, open fiscal period, and accounting totals.</li>
        <li><b>Approve</b> (recommended) for governance, especially for high-value transactions.</li>
        <li><b>Post</b> to GL: system writes <code>finance_journal_entries</code> + <code>finance_journal_entry_lines</code> and sets <code>status='posted'</code>.</li>
        <li><b>Reconcile</b> bank accounts: match statement lines to uncleared bank-side journal lines; close only when difference = 0.00.</li>
        <li><b>Report</b> from posted journals only: GL, TB, P&amp;L, Balance Sheet, Cash Flow, Budget vs Actual.</li>
        <li><b>Close</b> the fiscal period after review; run year close at fiscal year end.</li>
      </ol>

      <div class="spacer"></div>
      <b>Acceptance checks before posting:</b>
      <ul>
        <li>Debits equal credits (balanced journal).</li>
        <li>Transaction date is within an open fiscal period.</li>
        <li>Account IDs exist in <code>finance_accounts</code>.</li>
        <li>company_id matches the user/company scope.</li>
        <li>Bank-impact lines include <code>bank_account_id</code>.</li>
        <li>Evidence attached where required by policy (invoice, receipt, approval).</li>
      </ul>
    </div>
  </div>

  {{-- ===================== 5. POSTING RULES ===================== --}}
  <div class="section">
    <h2>5. Posting Rules (Double Entry)</h2>

    <div class="note">
      <b>Non-negotiable:</b> once a journal is posted, do not edit it. Corrections must be reversal journals linked via <b>reversal_of_id</b>.
    </div>

    <table>
      <thead>
        <tr>
          <th>Process</th>
          <th>Typical Posting</th>
          <th>Evidence / Notes</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><b>Customer Invoice</b></td>
          <td>Dr Accounts Receivable / Cr Revenue (+ Tax)</td>
          <td>Attach invoice copy; payment later clears AR.</td>
        </tr>
        <tr>
          <td><b>Customer Payment</b></td>
          <td>Dr Bank / Cr Accounts Receivable</td>
          <td>Bank-side line should have bank_account_id for reconciliation.</td>
        </tr>
        <tr>
          <td><b>Supplier Bill (AP)</b></td>
          <td>Dr Expense/Asset / Cr Accounts Payable</td>
          <td>Attach supplier invoice; payment later clears AP.</td>
        </tr>
        <tr>
          <td><b>Supplier Payment</b></td>
          <td>Dr Accounts Payable / Cr Bank</td>
          <td>Reference remittance advice; bank account must be selected.</td>
        </tr>
        <tr>
          <td><b>Expense (Paid Now)</b></td>
          <td>Dr Expense / Cr Bank</td>
          <td>Receipt required; use payable to employee for reimbursements.</td>
        </tr>
        <tr>
          <td><b>Transfer Between Banks</b></td>
          <td>Dr Bank B / Cr Bank A</td>
          <td>Both bank lines must be reconcilable.</td>
        </tr>
        <tr>
          <td><b>Bank Fees / Interest</b></td>
          <td>Dr Charges (or Interest) / Cr Bank</td>
          <td>Often posted as adjustments during reconciliation.</td>
        </tr>
      </tbody>
    </table>
  </div>

  {{-- ===================== 6. TRACEABILITY ===================== --}}
  <div class="section">
    <h2>6. Traceability &amp; Audit Trail (Journal → Source)</h2>
    <div class="box">
      Traceability is enforced using <b>finance_journal_entries.source_type</b> and <b>finance_journal_entries.source_id</b>.
      This enables drilldown from any report total to a journal entry, and from that journal entry back to the original source document.
      <div class="spacer"></div>

      <b>Journal header fields (actual):</b>
      <ul>
        <li><b>Identity</b>: id, company_id, period_id, entry_no</li>
        <li><b>Date</b>: entry_date</li>
        <li><b>Context</b>: reference, memo</li>
        <li><b>Status</b>: status (draft, posted, reversed, voided)</li>
        <li><b>Trace</b>: source_type, source_id</li>
        <li><b>Audit</b>: posted_at, posted_by, reversed_at, reversed_by</li>
        <li><b>Correction linkage</b>: reversal_of_id</li>
      </ul>

      <div class="spacer"></div>
      <b>Recommended source_type conventions:</b>
      <table>
        <thead>
          <tr>
            <th>Business Event</th>
            <th>source_type</th>
            <th>source_id points to</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Customer Invoice</td>
            <td><code>finance_invoice</code></td>
            <td>finance_invoices.id</td>
          </tr>
          <tr>
            <td>Payment</td>
            <td><code>finance_payment</code></td>
            <td>finance_payments.id</td>
          </tr>
          <tr>
            <td>Expense</td>
            <td><code>finance_expense</code></td>
            <td>finance_expenses.id</td>
          </tr>
          <tr>
            <td>Supplier Bill</td>
            <td><code>finance_supplier_bill</code></td>
            <td>finance_supplier_bills.id</td>
          </tr>
          <tr>
            <td>Bank Transaction</td>
            <td><code>finance_bank_txn</code></td>
            <td>finance_bank_transactions.id</td>
          </tr>
          <tr>
            <td>Manual Journal</td>
            <td><code>manual_journal</code></td>
            <td>finance_journal_entries.id</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- ===================== 7. BANKING ===================== --}}
  <div class="section">
    <h2>7. Banking &amp; Reconciliation SOP</h2>
    <div class="box">
      <b>Bank Transactions</b>
      <ul>
        <li>Used for deposits, withdrawals, transfers, bank fees, interest, and bank-originated adjustments.</li>
        <li>Each bank-impact journal line must store <code>bank_account_id</code> to be reconcilable.</li>
        <li>Include date, reference, description, amount, and a clear counter account.</li>
      </ul>

      <div class="spacer"></div>
      <b>Reconciliation Workflow</b>
      <ol>
        <li>Open reconciliation for bank account + statement date range.</li>
        <li>Enter statement opening and closing balances.</li>
        <li>Import/enter statement lines (date, description, amount, reference).</li>
        <li>List uncleared posted bank-side journal lines for the bank_account_id.</li>
        <li>Match statement lines to journal lines (1:1 recommended).</li>
        <li>Post missing items as adjustments (fees/interest) where needed.</li>
        <li>Close only when difference equals 0.00; closed reconciliation is read-only.</li>
      </ol>

      <div class="spacer"></div>
      <b>Integrity Rules</b>
      <ul>
        <li>Only posted journal lines are reconcilable.</li>
        <li>One statement line matches one journal line.</li>
        <li>Adjustments require memo + evidence.</li>
        <li>Closing requires difference = 0.00.</li>
      </ul>
    </div>
  </div>

  {{-- ===================== 8. BUDGET ===================== --}}
  <div class="section">
    <h2>8. Budgeting &amp; Variance SOP</h2>
    <div class="box">
      <b>Budget Setup</b>
      <ol>
        <li>Create a budget (name, start date, end date, period type: monthly/quarterly/annual).</li>
        <li>Select accounts (typically P&amp;L accounts).</li>
        <li>Enter budget amounts per period (grid).</li>
        <li>Review, approve, and lock budget to prevent accidental edits.</li>
      </ol>

      <div class="spacer"></div>
      <b>Budget vs Actual</b>
      <ul>
        <li>Actuals are calculated from posted journal lines within the budget date range.</li>
        <li>Expense variance: Budget − Actual (positive is favourable).</li>
        <li>Income variance: Actual − Budget (positive is favourable).</li>
        <li>Drilldown supported by traceability: account → journal → source document.</li>
      </ul>
    </div>
  </div>

  {{-- ===================== 9. ARCHITECTURE ===================== --}}
  <div class="section">
    <h2>9. Architecture (Actual Tables &amp; Field Contracts)</h2>
    <div class="box">
      <b>Ledger Core</b>
      <ul>
        <li><b>COA:</b> <code>finance_accounts</code></li>
        <li><b>GL Header:</b> <code>finance_journal_entries</code></li>
        <li><b>GL Lines:</b> <code>finance_journal_entry_lines</code></li>
      </ul>

      <div class="spacer"></div>
      <b>Banking &amp; Budget</b>
      <ul>
        <li><b>Bank Accounts:</b> <code>finance_bank_accounts</code></li>
        <li><b>Bank Transactions:</b> <code>finance_bank_transactions</code>, <code>finance_bank_transaction_lines</code></li>
        <li><b>Reconciliation:</b> <code>finance_bank_reconciliations</code>, <code>finance_bank_statement_lines</code>, <code>finance_bank_statement_matches</code></li>
        <li><b>Budgets:</b> <code>finance_budgets</code>, <code>finance_budget_lines</code>, <code>finance_budget_amounts</code></li>
      </ul>

      <div class="spacer"></div>
      <b>Field contracts (must-haves)</b>
      <ul>
        <li>Every journal must belong to the company scope: <code>company_id</code>.</li>
        <li>Every posted journal must balance: sum(debit) = sum(credit).</li>
        <li>Traceability must exist: <code>source_type</code> and <code>source_id</code> set for system-generated journals.</li>
        <li>Bank-impact lines must set <code>bank_account_id</code> for reconciliation.</li>
      </ul>
    </div>
  </div>

  {{-- ===================== 10. RACI ===================== --}}
  <div class="section">
    <h2>10. RACI (Roles &amp; Responsibilities)</h2>
    <div class="box">
      <div class="small">RACI: Responsible (R), Accountable (A), Consulted (C), Informed (I).</div>
      <table>
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
            <td>R</td><td>C</td><td>I</td>
          </tr>
          <tr>
            <td>Approve postings (recommended)</td>
            <td>C</td><td>A</td><td>I</td>
          </tr>
          <tr>
            <td>Post to GL</td>
            <td>R</td><td>A</td><td>I</td>
          </tr>
          <tr>
            <td>Bank reconciliation</td>
            <td>R</td><td>A</td><td>I</td>
          </tr>
          <tr>
            <td>Close period / year close</td>
            <td>C</td><td>A</td><td>R</td>
          </tr>
          <tr>
            <td>Permissions + setup (COA, mappings)</td>
            <td>I</td><td>C</td><td>A</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- ===================== 11. RCM ===================== --}}
  <div class="section">
    <h2>11. Risk &amp; Control Matrix (RCM)</h2>
    <div class="box">
      <table>
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
            <td>Posting in closed period</td>
            <td>Misstatement; audit failure</td>
            <td>Period lock + year close rules</td>
            <td>Blocked by period checks</td>
          </tr>
          <tr>
            <td>Editing posted entries</td>
            <td>Broken audit trail</td>
            <td>No edit after posting; reversal only</td>
            <td>status + reversal_of_id linkage</td>
          </tr>
          <tr>
            <td>Unapproved high-value postings</td>
            <td>Fraud / policy breach</td>
            <td>Approval workflow + thresholds (policy)</td>
            <td>Approver logs (optional)</td>
          </tr>
          <tr>
            <td>Bank mismatch</td>
            <td>Cash misstatement</td>
            <td>Reconciliation closes only at diff=0</td>
            <td>Closed reconciliation record</td>
          </tr>
          <tr>
            <td>Wrong account mapping</td>
            <td>Misclassification</td>
            <td>Mappings review + COA governance</td>
            <td>Mappings + change logs</td>
          </tr>
          <tr>
            <td>Cross-company data access</td>
            <td>Data breach</td>
            <td>company_id scoping + permissions</td>
            <td>Policy checks + scoped queries</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- ===================== 12. CHECKLISTS ===================== --}}
  <div class="section">
    <h2>12. Checklists</h2>

    <div class="grid2">
      <div class="col">
        <div class="box">
          <b>Month-End Close</b>
          <ul>
            <li>All invoices posted</li>
            <li>All bills/expenses posted</li>
            <li>All payments posted and matched</li>
            <li>Bank reconciliation closed (difference = 0.00)</li>
            <li>Trial balance reviewed</li>
            <li>P&amp;L reviewed; variances explained</li>
            <li>Balance sheet reviewed; key accounts validated</li>
            <li>Fiscal period locked</li>
          </ul>
        </div>
      </div>

      <div class="col">
        <div class="box">
          <b>Bank Reconciliation</b>
          <ul>
            <li>Statement range correct</li>
            <li>Opening/closing balances entered</li>
            <li>Statement lines imported/entered</li>
            <li>Matches reviewed</li>
            <li>Fees/interest posted (if any)</li>
            <li>Difference = 0.00</li>
            <li>Reconciliation closed and locked</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="spacer"></div>
    <div class="ok">
      <b>Evidence pack (recommended):</b> keep the PDF + export of Trial Balance + reconciliation closure report for each month-end close.
    </div>
  </div>

  {{-- Footer --}}
  <div class="footer">
    <div class="left">{{ $appName }} • Finance Docs</div>
    <div class="right">Generated: {{ $generatedAt }}</div>
  </div>

</body>
</html>