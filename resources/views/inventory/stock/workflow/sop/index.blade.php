{{-- resources/views/inventory/stock/workflow/sop/index.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inventory Workflow SOP</title>

  <style>
    :root{
      --ink:#0f172a;
      --muted:#64748b;
      --border:#e2e8f0;
      --bg:#f8fafc;
      --card:#ffffff;
      --accent:#2563eb;
      --accent2:#0ea5e9;
      --ok:#16a34a;
      --warn:#f59e0b;
      --bad:#ef4444;
      --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
    }

    *{ box-sizing:border-box; }
    body{
      font-family: Arial, Helvetica, sans-serif;
      font-size: 14px;
      color: var(--ink);
      background: var(--bg);
      margin:0;
    }
    .container{
        max-width: 1040px;            
        margin: 24px auto;
        padding: 0 16px 48px;
    }

    /* Header */
    .header{
      background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
      color:#fff;
      border-radius: 14px;
      padding: 18px 16px;
      box-shadow: 0 10px 20px rgba(2,6,23,.10);
      position: relative;
      overflow:hidden;
    }
    .header:before{
      content:"";
      position:absolute;
      inset:-50px -50px auto auto;
      width: 260px;
      height: 260px;
      background: rgba(255,255,255,.14);
      border-radius: 999px;
      transform: rotate(15deg);
    }
    h1{
      font-size: 26px;              /* was 20px */
      margin: 0 0 8px;
      letter-spacing: .2px;
      position: relative;
      z-index: 1;
    }
    .subline{
      position: relative;
      z-index: 1;
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      align-items:center;
      color: rgba(255,255,255,.92);
      font-size: 12px;
    }

    .badge{
      display:inline-flex;
      align-items:center;
      gap:6px;
      font-size: 12px;              /* was 11px */
      padding: 4px 12px;
      border: 1px solid rgba(255,255,255,.38);
      border-radius: 999px;
      background: rgba(255,255,255,.10);
      white-space: nowrap;
    }
    .badge b{ font-weight:700; }

    .pill{
      display:inline-flex;
      align-items:center;
      gap:6px;
      border-radius: 999px;
      font-size: 12px;              /* was 11px */
      padding: 3px 10px;
      border: 1px solid var(--border);
      background: #fff;
      color: var(--ink);
      white-space: nowrap;
    }
    .pill.ok{ border-color: rgba(22,163,74,.3); color: var(--ok); }
    .pill.warn{ border-color: rgba(245,158,11,.35); color: #b45309; }
    .pill.bad{ border-color: rgba(239,68,68,.35); color: #b91c1c; }

    .muted{ color: var(--muted); }
    .small{ font-size: 12.5px; }

    /* Layout blocks */
    .grid2{
      display:grid;
      grid-template-columns: 1.12fr .88fr;
      gap: 12px;
      margin-top: 12px;
    }
    .grid3{
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin-top: 10px;
    }

    .card{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px;
      box-shadow: 0 6px 14px rgba(2,6,23,.05);
    }
    .card h2{
      font-size: 16px;              /* was 13px */
      margin: 0 0 10px;
    }

    .callout{
      font-size: 14px;              /* added */
      line-height: 1.65;
      border-left: 4px solid var(--accent);
      background: #eff6ff;
      padding: 10px 10px;
      border-radius: 10px;
    }
    .callout strong{ color:#0b2a6f; }

    .list{
      margin: 0;
      padding-left: 20px;
      line-height: 1.7;
    }

    /* Key rules */
    .rule{
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px;
      background: #fff;
    }
    .rule .title{
      font-weight:700;
      margin-bottom: 4px;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .dot{
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: var(--accent);
      display:inline-block;
      flex: 0 0 auto;
    }
    .dot.ok{ background: var(--ok); }
    .dot.warn{ background: var(--warn); }
    .dot.bad{ background: var(--bad); }

    /* Diagram */
    .diagram{
      margin-top: 12px;
      border: 1px dashed rgba(37,99,235,.25);
      background: rgba(248,250,252,.75);
      border-radius: 14px;
      padding: 12px;
    }
    .dia-row{
      display:grid;
      grid-template-columns: 1fr .14fr 1fr .14fr 1fr;
      gap: 10px;
      align-items: stretch;
    }
    .dia-node{
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 10px;
      background:#fff;
      box-shadow: 0 4px 10px rgba(2,6,23,.04);
      min-height: 92px;
    }
    .dia-title{
      font-weight: 800;
      font-size: 14.5px;            /* added */
      color: var(--accent);
      display:flex;
      justify-content:space-between;
      gap: 8px;
      align-items:flex-start;
    }
    .dia-sub{
      margin-top: 6px;
      font-size: 13px;              /* was 11px */
      line-height: 1.55;
      color: var(--muted);
    }
    .dia-meta{
      margin-top: 8px;
      display:flex;
      flex-wrap:wrap;
      gap:6px;
    }
    .arrow{
      display:flex;
      align-items:center;
      justify-content:center;
      font-size: 18px;
      color: rgba(37,99,235,.9);
    }
    .hr{
      height:1px;
      background: var(--border);
      margin: 10px 0;
    }
    .dia-note{
      margin-top: 8px;
      color: var(--muted);
      font-size: 11px;
      line-height: 1.45;
    }

    /* Table */
    table.grid{
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
      border-radius: 14px;
      border: 1px solid var(--border);
      background:#fff;
    }
    .grid th, .grid td{
      padding: 12px;                /* was 10px */
      vertical-align: top;
      font-size: 13.5px;            /* added */
      line-height: 1.6;             /* added */
    }
    .grid th{
      background: #f1f5f9;
      font-size: 14px;
      text-align:left;
    }
    .grid tr:last-child td{ border-bottom: none; }

    .stepTag{
      font-weight:800;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 8px;
    }
    .stepHint{
      margin-top: 4px;
      color: var(--muted);
      font-size: 11px;
    }

    .kbd{
      font-family: var(--mono);
      font-size: 12px;              /* was 11px */
      padding: 2px 7px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: #f8fafc;
      display:inline-block;
    }

    /* Footer / Print */
    .footer{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      align-items:center;
      justify-content:space-between;
      margin-top: 14px;
    }
    .btn{
      display:inline-block;
      padding: 8px 12px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: #fff;
      color: var(--ink);
      text-decoration:none;
      cursor:pointer;
      font-size: 12px;
    }
    .btn.primary{
      background: var(--accent);
      border-color: rgba(37,99,235,.3);
      color:#fff;
    }
    .btn.ghost{
      background: #fff;
      border-color: rgba(37,99,235,.25);
      color: var(--accent);
    }

    .no-print{ margin-top: 14px; }
    @media print{
      body{ background:#fff; }
      .no-print{ display:none; }
      .card{ box-shadow:none; }
      .header{ box-shadow:none; }
      .diagram{ box-shadow:none; }
    }

    @media (max-width: 920px){
      .grid2{ grid-template-columns: 1fr; }
      .grid3{ grid-template-columns: 1fr; }
      .dia-row{ grid-template-columns: 1fr; }
      .arrow{ display:none; }
    }
  </style>
</head>

<body>
<div class="container">

  <!-- HEADER -->
  <div class="header">
    <h1>Inventory Workflow — Standard Operating Procedure (SOP)</h1>
    <div class="subline">
      <span>Purpose: staff onboarding, consistent stock handling, audit-ready movements.</span>
      <span class="badge"><b>Version</b> 1.0</span>
      <span class="badge"><b>Owner</b> Inventory / Operations</span>
      <span class="badge"><b>Scope</b> Entries, Transfers, Issues, Reports</span>
    </div>
  </div>

  <!-- QUICK SUMMARY -->
  <div class="grid2">
    <div class="card">
      <h2>Golden Rule</h2>
      <div class="callout">
        <strong>Any change in stock quantity must create a Stock Transaction.</strong>
        This keeps Stock Levels, valuations, and dashboards accurate and reconciliation-friendly.
      </div>

      <div class="grid3">
        <div class="rule">
          <div class="title"><span class="dot ok"></span> Accuracy</div>
          <div class="muted">Always pick the correct Store/Shelf. Wrong location = “missing stock”.</div>
        </div>
        <div class="rule">
          <div class="title"><span class="dot warn"></span> Control</div>
          <div class="muted">Drafts can be edited. Once posted, transfers/issues are locked.</div>
        </div>
        <div class="rule">
          <div class="title"><span class="dot bad"></span> Audit</div>
          <div class="muted">Never “manually adjust” outside approved workflows/screens.</div>
        </div>
      </div>

      <div class="muted small" style="margin-top:10px;">
        Reporting depends on clean data. If something looks wrong, start with <span class="kbd">Transactions</span>.
      </div>
    </div>

    <div class="card">
      <h2>Workflow Overview</h2>
      <ol class="list">
        <li><strong>Stock Entries</strong> <span class="pill ok">IN</span></li>
        <li><strong>Stock Transactions</strong> <span class="pill">Audit Trail</span></li>
        <li><strong>Stock Levels</strong> <span class="pill">Current Availability</span></li>
        <li><strong>Low Stock Levels</strong> <span class="pill warn">Reorder</span></li>
        <li><strong>Stock Aging</strong> <span class="pill">Slow-moving</span></li>
        <li><strong>Stock Transfer</strong> <span class="pill">Move</span></li>
        <li><strong>Stock Issues</strong> <span class="pill bad">OUT</span></li>
      </ol>

      <div class="muted small" style="margin-top:10px;">
        Tip: Use <span class="kbd">Filters</span> before exports to match reporting periods and stores.
      </div>
    </div>
  </div>

  <!-- DIAGRAM (ADDED) -->
  <div class="card">
    <h2>Workflow Diagram (How the system connects)</h2>
    <div class="muted small">
      Simple visual showing how actions flow into the audit trail and then into availability & monitoring.
    </div>

    <div class="diagram">
      <div class="dia-row">
        <div class="dia-node">
          <div class="dia-title">
            <span>Stock Entries</span>
            <span class="pill ok">IN</span>
          </div>
          <div class="dia-sub">
            Receiving (PO/GRN), opening balance, production output, returns.
            Creates stock and writes a transaction row.
          </div>
          <div class="dia-meta">
            <span class="pill">Creates Tx</span>
            <span class="pill">Captures cost</span>
          </div>
        </div>

        <div class="arrow">➜</div>

        <div class="dia-node">
          <div class="dia-title">
            <span>Stock Transactions</span>
            <span class="pill">Audit</span>
          </div>
          <div class="dia-sub">
            Source of truth. Every movement appears here (IN/OUT/TRANSFER/ADJUST).
            Used for reconciliation and investigations.
          </div>
          <div class="dia-meta">
            <span class="pill">Filters</span>
            <span class="pill">References</span>
          </div>
        </div>

        <div class="arrow">➜</div>

        <div class="dia-node">
          <div class="dia-title">
            <span>Stock Levels</span>
            <span class="pill">Now</span>
          </div>
          <div class="dia-sub">
            Current availability by Variant + Store/Shelf.
            Computed from the transaction ledger.
          </div>
          <div class="dia-meta">
            <span class="pill">Per store</span>
            <span class="pill">Per variant</span>
          </div>
        </div>
      </div>

      <div class="hr"></div>

      <div class="dia-row">
        <div class="dia-node">
          <div class="dia-title">
            <span>Low Stock</span>
            <span class="pill warn">Reorder</span>
          </div>
          <div class="dia-sub">
            Flags items below minimum levels to prevent stockouts and trigger purchasing.
          </div>
          <div class="dia-meta">
            <span class="pill">Thresholds</span>
            <span class="pill">Procurement</span>
          </div>
        </div>

        <div class="arrow">➜</div>

        <div class="dia-node">
          <div class="dia-title">
            <span>Stock Aging</span>
            <span class="pill">Slow-moving</span>
          </div>
          <div class="dia-sub">
            Identifies stock with long “time in store” / last movement.
            Supports clearance and cashflow control.
          </div>
          <div class="dia-meta">
            <span class="pill">Buckets</span>
            <span class="pill">Dead stock</span>
          </div>
        </div>

        <div class="arrow">➜</div>

        <div class="dia-node">
          <div class="dia-title">
            <span>Transfers & Issues</span>
            <span class="pill bad">OUT</span>
          </div>
          <div class="dia-sub">
            Transfers move stock between stores (OUT + IN). Issues reduce stock for dispatch/usage/write-off.
            Both write ledger transactions.
          </div>
          <div class="dia-meta">
            <span class="pill">Posting locks</span>
            <span class="pill">Traceable</span>
          </div>
        </div>
      </div>

      <div class="dia-note">
        <strong>Posting rule:</strong> Draft documents are editable. Once you click <span class="kbd">Post</span>, the record is locked and the ledger entries become the audit trail.
      </div>
    </div>
  </div>

  <!-- ROLES / RESPONSIBILITY -->
  <div class="card" style="margin-top: 12px;">
    <h2>Roles & Responsibilities</h2>
    <table class="grid">
      <thead>
        <tr>
          <th style="width:24%">Role</th>
          <th style="width:40%">Responsibilities</th>
          <th style="width:36%">Key Controls</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Storekeeper / Inventory Clerk</strong></td>
          <td>Record entries, raise transfer/issue drafts, verify quantities, keep shelves accurate.</td>
          <td>Attach references (PO/GRN/WO) where applicable; always choose correct Store/Shelf.</td>
        </tr>
        <tr>
          <td><strong>Supervisor / Approver</strong></td>
          <td>Review and <strong>post</strong> transfers/issues, approve adjustments, audit exceptions.</td>
          <td>Posting locks the record; ensure availability checks pass before posting.</td>
        </tr>
        <tr>
          <td><strong>Admin</strong></td>
          <td>Manage permissions, master data, system configuration, audit reports.</td>
          <td>Controls who can view cost/value analytics and who can perform configuration changes.</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- DETAILED STEPS -->
  <div class="card" style="margin-top: 12px;">
    <h2>Detailed Steps (Operational SOP)</h2>

    <table class="grid">
      <thead>
        <tr>
          <th style="width:18%">Step</th>
          <th style="width:42%">What to do</th>
          <th style="width:40%">Controls / Notes</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <div class="stepTag">
              <span>1) Stock Entries</span>
              <span class="pill ok">IN</span>
            </div>
            <div class="stepHint">Receiving / Opening / Production / Returns</div>
          </td>
          <td>
            Record stock received.<br>
            Select <strong>Product/Variant</strong> → Choose <strong>Store/Shelf</strong> → Enter <strong>Qty</strong> (and <strong>Cost</strong> if used) → Save.
          </td>
          <td>
            <ul class="list">
              <li>Saving must create a transaction (e.g., <span class="kbd">ENTRY_IN</span>).</li>
              <li>If cost is required, ensure valuation rules are consistent.</li>
            </ul>
          </td>
        </tr>

        <tr>
          <td>
            <div class="stepTag">
              <span>2) Stock Transactions</span>
              <span class="pill">Audit</span>
            </div>
            <div class="stepHint">Traceability</div>
          </td>
          <td>
            Review movements and trace references (PO/GRN/WO/Transfer/Issue). Use filters by date, product, store, and type.
          </td>
          <td>
            <ul class="list">
              <li>Source of truth for investigations and reconciliation.</li>
              <li>Do not edit posted history—use correction workflow if needed.</li>
            </ul>
          </td>
        </tr>

        <tr>
          <td>
            <div class="stepTag">
              <span>3) Stock Levels</span>
              <span class="pill">Now</span>
            </div>
            <div class="stepHint">Availability</div>
          </td>
          <td>
            Check available stock by product/variant and store/shelf before issuing or selling.
          </td>
          <td>
            <ul class="list">
              <li>Calculated from transactions.</li>
              <li>If incorrect, check wrong store, missing transfer leg, or unposted drafts.</li>
            </ul>
          </td>
        </tr>

        <tr>
          <td>
            <div class="stepTag">
              <span>4) Low Stock</span>
              <span class="pill warn">Reorder</span>
            </div>
            <div class="stepHint">Thresholds</div>
          </td>
          <td>
            Monitor items below reorder points. Raise purchase requests or procurement actions.
          </td>
          <td>
            <ul class="list">
              <li>Keep reorder thresholds updated for critical SKUs.</li>
              <li>Review weekly (fast movers) / monthly (slow movers).</li>
            </ul>
          </td>
        </tr>

        <tr>
          <td>
            <div class="stepTag">
              <span>5) Stock Aging</span>
              <span class="pill">Slow-moving</span>
            </div>
            <div class="stepHint">Last Movement</div>
          </td>
          <td>
            Identify stock sitting too long. Actions: promotions, clearance, review purchasing plan.
          </td>
          <td>
            <ul class="list">
              <li>Expiry risk + dead stock reduction.</li>
              <li>Helps warehouse space planning and cashflow optimisation.</li>
            </ul>
          </td>
        </tr>

        <tr>
          <td>
            <div class="stepTag">
              <span>6) Stock Transfer</span>
              <span class="pill">Move</span>
            </div>
            <div class="stepHint">Between stores</div>
          </td>
          <td>
            Create transfer draft: select <strong>From</strong> → <strong>To</strong> → add lines (variant + qty) → save.<br>
            When ready, <strong>Post</strong> to execute movement.
          </td>
          <td>
            <ul class="list">
              <li>Must create <strong>two</strong> transactions: <span class="kbd">TRANSFER_OUT</span> + <span class="kbd">TRANSFER_IN</span>.</li>
              <li>Posting locks the transfer. Double-check before posting.</li>
            </ul>
          </td>
        </tr>

        <tr>
          <td>
            <div class="stepTag">
              <span>7) Stock Issues</span>
              <span class="pill bad">OUT</span>
            </div>
            <div class="stepHint">Dispatch / Use / Write-off</div>
          </td>
          <td>
            Issue stock for sales dispatch, internal use, damage/write-off, production consumption.
          </td>
          <td>
            <ul class="list">
              <li>Capture reason and reference.</li>
              <li>Creates OUT transaction and reduces stock in selected location.</li>
            </ul>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- TROUBLESHOOTING -->
  <div class="grid2" style="margin-top: 12px;">
    <div class="card">
      <h2>Quick Troubleshooting</h2>
      <ul class="list">
        <li><strong>Can’t see a menu item?</strong> Your role may not have permission. Ask Admin.</li>
        <li><strong>Stock Levels incorrect?</strong> Check missing transaction legs, wrong store, or unposted drafts.</li>
        <li><strong>Transfer fails on posting?</strong> Usually insufficient stock in FROM store or valuation missing.</li>
        <li><strong>Value/Cost hidden?</strong> Your role may not have cost permissions.</li>
      </ul>
    </div>

    <div class="card">
      <h2>Controls Checklist (Before Posting)</h2>
      <ul class="list">
        <li>Correct <strong>From Store</strong> and <strong>To Store</strong> selected.</li>
        <li>Each line has valid <strong>Variant</strong> and <strong>Qty</strong> (&gt; 0).</li>
        <li>Availability confirms sufficient stock in FROM store.</li>
        <li>Reference/Reason captured where required.</li>
        <li>Supervisor approval (if your process requires it).</li>
      </ul>
      <div class="muted small" style="margin-top:8px;">
        Recommended: show a “Post confirmation warning” since posting locks edits.
      </div>
    </div>
  </div>

  <!-- FOOTER ACTIONS -->
  <div class="no-print card" style="margin-top: 12px;">
    <div class="footer">
      <div class="muted small">
        Printed documents may be outdated. Always verify the latest SOP version before critical operations.
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn primary" onclick="window.print()">Print SOP</button>

        {{-- Optional PDF download button (if you enabled the pdf route) --}}
        <a class="btn ghost" href="{{ route('admin.inventory.workflow.sop.pdf') }}">Download PDF</a>

        <a class="btn" href="{{ route('admin.inventory.workflow.index') }}">Back to Workflow Help</a>
      </div>
    </div>
  </div>

</div>
</body>
</html>
