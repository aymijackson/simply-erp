{{-- resources/views/inventory/stock/workflow/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Inventory Workflow')

@section('content')
@php
  // Designed for SB Admin 2 / Bootstrap 4 style.
@endphp

<style>
  /* -------------------------------
     Page-only styling (scoped)
  -------------------------------- */
  .wf-hero {
    background: linear-gradient(135deg, rgba(78,115,223,.16), rgba(28,200,138,.12));
    border: 1px solid rgba(78,115,223,.15);
    border-radius: .85rem;
    overflow: hidden;
    position: relative;
  }
  .wf-hero:after{
    content:"";
    position:absolute;
    width:320px; height:320px;
    right:-150px; top:-160px;
    border-radius:999px;
    background: radial-gradient(circle at 30% 30%, rgba(78,115,223,.35), rgba(78,115,223,0));
    pointer-events:none;
  }
  .wf-card {
    border: 1px solid rgba(0,0,0,.06);
    border-radius: .85rem;
    background: #fff;
  }
  .wf-kpi {
    border: 1px solid rgba(0,0,0,.06);
    border-radius: .85rem;
    background: #fff;
    height: 100%;
  }
  .wf-kpi .wf-kpi-icon{
    width:44px; height:44px;
    border-radius: 14px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(78,115,223,.12);
    color: #4e73df;
    font-size: 18px;
  }
  .wf-mini { font-size: 12px; color: #858796; }
  .wf-ul li{ margin-bottom: .35rem; }

  /* Pills */
  .wf-badge {
    display:inline-flex; align-items:center; gap:.35rem;
    padding: .2rem .6rem;
    border-radius: 999px;
    border:1px solid rgba(0,0,0,.08);
    background:#fff;
    font-size: 11px;
    color:#5a5c69;
    white-space: nowrap;
  }
  .wf-badge.in { border-color: rgba(28,200,138,.25); color:#1cc88a; }
  .wf-badge.out{ border-color: rgba(231,74,59,.25); color:#e74a3b; }
  .wf-badge.audit{ border-color: rgba(54,185,204,.25); color:#36b9cc; }
  .wf-badge.move{ border-color: rgba(246,194,62,.28); color:#b58a00; }

  /* Step cards */
  .wf-step {
    border: 1px solid rgba(0,0,0,.06);
    border-radius: .85rem;
    overflow:hidden;
    background:#fff;
  }
  .wf-step .wf-step-head{
    display:flex; justify-content:space-between; align-items:flex-start;
    padding: 1rem 1rem;
    background: #f8f9fc;
    border-bottom: 1px solid rgba(0,0,0,.06);
    gap: 1rem;
  }
  .wf-step .wf-step-body{ padding: 1rem; }

  /* Diagram */
  .wf-diagram {
    border: 1px dashed rgba(78,115,223,.25);
    background: rgba(248,249,252,.7);
    border-radius: .85rem;
    padding: 1rem;
  }
  .wf-node {
    border: 1px solid rgba(0,0,0,.07);
    border-radius: .85rem;
    background: #fff;
    padding: .85rem .9rem;
    position: relative;
    height: 100%;
  }
  .wf-node .wf-node-title{
    font-weight: 800;
    color:#4e73df;
    margin-bottom: .2rem;
  }
  .wf-node .wf-node-sub{ font-size: 12px; color:#858796; }
  .wf-node .wf-node-meta{
    margin-top: .55rem;
    font-size: 12px;
    color:#5a5c69;
  }
  .wf-arrow {
    display:flex; align-items:center; justify-content:center;
    color:#4e73df;
    font-size: 18px;
    opacity: .9;
  }
  .wf-legend {
    display:flex; flex-wrap: wrap; gap:.4rem;
    margin-top: .75rem;
  }
  .wf-legend .wf-badge { background: rgba(255,255,255,.85); }
  .wf-diagram-note{
    font-size: 12px;
    color:#858796;
    margin-top: .65rem;
  }

  /* FAQ */
  .wf-faq .wf-q{ font-weight: 800; color:#4e73df; }
  .wf-faq .wf-a{ color:#858796; margin-bottom: .9rem; }

  /* Print */
  @media print {
    .btn, .wf-no-print { display:none !important; }
    .wf-hero:after { display:none !important; }
  }

  @media (max-width: 992px){
    .wf-hero:after{ display:none; }
    .wf-arrow { transform: rotate(90deg); padding: .25rem 0; }
  }
</style>

<div class="container-fluid">

  {{-- Header --}}
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 mb-1 text-gray-800">Inventory Workflow</h1>
      <p class="mb-0 text-muted">
        Understand how Stock Management works end-to-end, and how each menu item fits into the workflow.
      </p>
    </div>

    <div class="d-flex flex-wrap wf-no-print" style="gap:.5rem;">
      @can('inventory.view')
        <a href="{{ route('admin.inventory.stock.dashboard.index') }}" class="btn btn-sm btn-primary">
          <i class="fas fa-chart-bar mr-1"></i> Inventory Dashboard
        </a>
      @endcan

      @can('inventory.reports.view')
        <a href="{{ route('admin.reports.inventory.index') }}" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-file-alt mr-1"></i> Inventory Reports
        </a>
      @endcan

      @can('inventory.stock.workflow.sop.export')
        <a href="{{ route('admin.inventory.workflow.sop.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-print mr-1"></i> Print SOP
        </a>
      @endcan
    </div>
  </div>

  {{-- Hero --}}
  <div class="wf-hero p-3 p-md-4 mb-4">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h5 class="mb-2 text-gray-800">How stock flows in the system</h5>
        <p class="mb-3 text-muted">
          Think of inventory as a chain:
          <strong>Entries</strong> create/increase stock,
          <strong>Transactions</strong> record every movement,
          <strong>Levels</strong> show availability,
          while <strong>Low Stock</strong>, <strong>Aging</strong>, <strong>Transfers</strong>, and <strong>Issues</strong>
          help you monitor, control, and audit.
        </p>

        <div class="alert alert-info mb-0">
          <strong>Golden rule:</strong> If it changes stock quantity, it must create a <strong>Stock Transaction</strong>.
          This keeps reporting and audit trails accurate.
        </div>
      </div>

      <div class="col-lg-4 mt-3 mt-lg-0">
        <div class="row">
          <div class="col-12 mb-2">
            <div class="wf-kpi p-3">
              <div class="d-flex align-items-center">
                <div class="wf-kpi-icon mr-3"><i class="fas fa-check-double"></i></div>
                <div>
                  <div class="text-xs text-uppercase text-muted">Control Principle</div>
                  <div class="font-weight-bold text-gray-800">Every movement is traceable</div>
                  <div class="wf-mini">Entries, transfers, and issues create transactions.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="wf-kpi p-3">
              <div class="d-flex align-items-center">
                <div class="wf-kpi-icon mr-3" style="background:rgba(231,74,59,.12);color:#e74a3b;">
                  <i class="fas fa-user-shield"></i>
                </div>
                <div>
                  <div class="text-xs text-uppercase text-muted">Audit Readiness</div>
                  <div class="font-weight-bold text-gray-800">Transactions = Source of truth</div>
                  <div class="wf-mini">If levels look wrong, investigate transactions first.</div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  {{-- Diagram (NEW) --}}
  <div class="wf-card shadow-sm mb-4">
    <div class="card-header bg-white">
      <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem;">
        <div>
          <h6 class="m-0 font-weight-bold text-primary">
            Workflow diagram (end-to-end)
          </h6>
          <div class="wf-mini">A visual summary of how stock moves and which pages to use.</div>
        </div>

        <div class="wf-no-print d-flex flex-wrap" style="gap:.35rem;">
          <a class="btn btn-sm btn-outline-primary" href="#wf-entries"><i class="fas fa-arrow-down mr-1"></i> Entries</a>
          <a class="btn btn-sm btn-outline-primary" href="#wf-tx"><i class="fas fa-exchange-alt mr-1"></i> Transactions</a>
          <a class="btn btn-sm btn-outline-primary" href="#wf-levels"><i class="fas fa-layer-group mr-1"></i> Levels</a>
          <a class="btn btn-sm btn-outline-warning" href="#wf-low"><i class="fas fa-exclamation-triangle mr-1"></i> Low</a>
          <a class="btn btn-sm btn-outline-primary" href="#wf-aging"><i class="fas fa-hourglass-half mr-1"></i> Aging</a>
          <a class="btn btn-sm btn-outline-primary" href="#wf-transfer"><i class="fas fa-random mr-1"></i> Transfers</a>
          <a class="btn btn-sm btn-outline-danger" href="#wf-issues"><i class="fas fa-minus-circle mr-1"></i> Issues</a>
        </div>
      </div>
    </div>

    <div class="card-body">
      <div class="wf-diagram">
        <div class="row align-items-stretch">

          {{-- Row 1: Entries -> Transactions -> Levels --}}
          <div class="col-lg-4 mb-3 mb-lg-0">
            <div class="wf-node h-100">
              <div class="wf-node-title">
                <i class="fas fa-plus-circle mr-1"></i> Stock Entries
              </div>
              <div class="wf-node-sub">Receive / add stock (IN)</div>
              <div class="wf-node-meta">
                Creates stock + records a transaction.
                <div class="mt-2">
                  <span class="wf-badge in"><i class="fas fa-arrow-down"></i> IN</span>
                  <span class="wf-badge"><i class="fas fa-link"></i> PO/GRN/WO refs</span>
                </div>
              </div>

              <div class="mt-3 wf-no-print">
                @can('inventory.stock.entries.view')
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock_entries.index') }}">
                    Open Entries
                  </a>
                @endcan
              </div>
            </div>
          </div>

          <div class="col-lg-1 mb-3 mb-lg-0 wf-arrow">
            <i class="fas fa-long-arrow-alt-right"></i>
          </div>

          <div class="col-lg-3 mb-3 mb-lg-0">
            <div class="wf-node h-100">
              <div class="wf-node-title" style="color:#36b9cc;">
                <i class="fas fa-clipboard-list mr-1"></i> Transactions
              </div>
              <div class="wf-node-sub">Audit trail (source of truth)</div>
              <div class="wf-node-meta">
                Records every movement: IN, OUT, TRANSFER, ADJUST.
                <div class="mt-2">
                  <span class="wf-badge audit"><i class="fas fa-search"></i> Investigate</span>
                  <span class="wf-badge"><i class="fas fa-filter"></i> Filter</span>
                </div>
              </div>

              <div class="mt-3 wf-no-print">
                @can('inventory.stock.transactions.view')
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock_entries.transactions.index') }}">
                    Open Transactions
                  </a>
                @endcan
              </div>
            </div>
          </div>

          <div class="col-lg-1 mb-3 mb-lg-0 wf-arrow">
            <i class="fas fa-long-arrow-alt-right"></i>
          </div>

          <div class="col-lg-3">
            <div class="wf-node h-100">
              <div class="wf-node-title">
                <i class="fas fa-layer-group mr-1"></i> Stock Levels
              </div>
              <div class="wf-node-sub">Current availability (Now)</div>
              <div class="wf-node-meta">
                Calculated from transactions by variant + store/shelf.
                <div class="mt-2">
                  <span class="wf-badge"><i class="fas fa-map-marker-alt"></i> Per store</span>
                  <span class="wf-badge"><i class="fas fa-boxes"></i> Per variant</span>
                </div>
              </div>

              <div class="mt-3 wf-no-print">
                @can('inventory.stock.levels.view')
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock.levels.index') }}">
                    Open Levels
                  </a>
                @endcan
              </div>
            </div>
          </div>

        </div>

        <hr class="my-3">

        {{-- Row 2: Monitoring + Controls --}}
        <div class="row align-items-stretch">

          <div class="col-lg-3 mb-3 mb-lg-0">
            <div class="wf-node h-100">
              <div class="wf-node-title" style="color:#b58a00;">
                <i class="fas fa-exclamation-triangle mr-1"></i> Low Stock
              </div>
              <div class="wf-node-sub">Reorder alerts</div>
              <div class="wf-node-meta">
                Items below reorder point (min level).
                <div class="mt-2">
                  <span class="wf-badge move"><i class="fas fa-shopping-cart"></i> Replenish</span>
                </div>
              </div>

              <div class="mt-3 wf-no-print">
                @can('inventory.stock.levels.low.view')
                  <a class="btn btn-sm btn-outline-warning" href="{{ route('admin.inventory.stock.levels.low.index') }}">
                    Open Low Stock
                  </a>
                @endcan
              </div>
            </div>
          </div>

          <div class="col-lg-1 mb-3 mb-lg-0 wf-arrow">
            <i class="fas fa-long-arrow-alt-right"></i>
          </div>

          <div class="col-lg-4 mb-3 mb-lg-0">
            <div class="wf-node h-100">
              <div class="wf-node-title" style="color:#858796;">
                <i class="fas fa-hourglass-half mr-1"></i> Stock Aging
              </div>
              <div class="wf-node-sub">Slow-moving / expiry risk</div>
              <div class="wf-node-meta">
                Shows how long stock has stayed without movement.
                <div class="mt-2">
                  <span class="wf-badge"><i class="fas fa-tags"></i> Clearance</span>
                  <span class="wf-badge"><i class="fas fa-clipboard-check"></i> Review purchasing</span>
                </div>
              </div>

              <div class="mt-3 wf-no-print">
                @can('inventory.stock.aging.view')
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock.aging.index') }}">
                    Open Aging
                  </a>
                @endcan
              </div>
            </div>
          </div>

          <div class="col-lg-1 mb-3 mb-lg-0 wf-arrow">
            <i class="fas fa-long-arrow-alt-right"></i>
          </div>

          <div class="col-lg-3">
            <div class="wf-node h-100">
              <div class="wf-node-title" style="color:#e74a3b;">
                <i class="fas fa-minus-circle mr-1"></i> Stock Issues
              </div>
              <div class="wf-node-sub">Consume / dispatch stock (OUT)</div>
              <div class="wf-node-meta">
                Sales dispatch, internal use, damage/write-off, production usage.
                <div class="mt-2">
                  <span class="wf-badge out"><i class="fas fa-arrow-up"></i> OUT</span>
                  <span class="wf-badge"><i class="fas fa-receipt"></i> Reason & reference</span>
                </div>
              </div>

              <div class="mt-3 wf-no-print">
                @can('inventory.stock.issues.view')
                  <a class="btn btn-sm btn-outline-danger" href="{{ route('admin.inventory.stock_issues.index') }}">
                    Open Issues
                  </a>
                @endcan
              </div>
            </div>
          </div>

        </div>

        <hr class="my-3">

        {{-- Transfer as balanced legs --}}
        <div class="row align-items-stretch">
          <div class="col-lg-12">
            <div class="wf-node">
              <div class="d-flex justify-content-between flex-wrap" style="gap:.5rem;">
                <div>
                  <div class="wf-node-title" style="color:#b58a00;">
                    <i class="fas fa-random mr-1"></i> Stock Transfer (balanced movement)
                  </div>
                  <div class="wf-node-sub">
                    Move stock between stores/locations (creates two transactions).
                  </div>
                </div>
                <div class="wf-no-print">
                  @can('inventory.stock.transfers.view')
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock.transfers.index') }}">
                      Open Transfers
                    </a>
                  @endcan
                </div>
              </div>

              <div class="mt-3">
                <div class="row align-items-center">
                  <div class="col-md-5">
                    <div class="p-3" style="border:1px solid rgba(231,74,59,.2); border-radius:.85rem; background:rgba(231,74,59,.05);">
                      <div class="font-weight-bold" style="color:#e74a3b;">
                        <i class="fas fa-arrow-up mr-1"></i> TRANSFER_OUT
                      </div>
                      <div class="wf-mini">Reduces stock in the <strong>source</strong> store.</div>
                    </div>
                  </div>

                  <div class="col-md-2 text-center wf-arrow">
                    <i class="fas fa-exchange-alt"></i>
                  </div>

                  <div class="col-md-5">
                    <div class="p-3" style="border:1px solid rgba(28,200,138,.22); border-radius:.85rem; background:rgba(28,200,138,.06);">
                      <div class="font-weight-bold" style="color:#1cc88a;">
                        <i class="fas fa-arrow-down mr-1"></i> TRANSFER_IN
                      </div>
                      <div class="wf-mini">Increases stock in the <strong>destination</strong> store.</div>
                    </div>
                  </div>
                </div>

                <div class="wf-diagram-note">
                  <strong>Important:</strong> Draft transfers can be edited. Posting locks the transfer and writes both legs (OUT & IN) to the ledger.
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="wf-legend">
          <span class="wf-badge in"><i class="fas fa-arrow-down"></i> IN movement</span>
          <span class="wf-badge out"><i class="fas fa-arrow-up"></i> OUT movement</span>
          <span class="wf-badge audit"><i class="fas fa-clipboard-list"></i> Audit/trace</span>
          <span class="wf-badge move"><i class="fas fa-random"></i> Move between stores</span>
        </div>
      </div>
    </div>
  </div>

  <div class="row">

    {{-- Left column: Steps --}}
    <div class="col-lg-8">

      <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
          <h6 class="m-0 font-weight-bold text-primary">Step-by-step workflow</h6>
        </div>
        <div class="card-body">

          {{-- 1 --}}
          <div class="wf-step mb-4" id="wf-entries">
            <div class="wf-step-head">
              <div>
                <div class="font-weight-bold">
                  1) Stock Entries
                  <span class="wf-badge in ml-2"><i class="fas fa-plus-circle"></i> IN</span>
                  <span class="wf-badge ml-1"><i class="fas fa-truck-loading"></i> Receive/Add</span>
                </div>
                <div class="wf-mini">Purchases, returns, production output, donations, opening balance.</div>
              </div>
              <div class="text-right">
                <div class="wf-mini">Outcome</div>
                <div class="font-weight-bold text-gray-800">Creates + increases stock</div>
              </div>
            </div>
            <div class="wf-step-body">
              <p class="text-muted mb-2">
                Use <strong>Stock Entries</strong> when new stock comes into the business.
                Always confirm destination store/shelf to avoid “missing stock”.
              </p>

              <div class="row">
                <div class="col-md-7">
                  <ul class="text-muted wf-ul mb-2">
                    <li>Select product (and variant if applicable).</li>
                    <li>Choose destination location/store/shelf.</li>
                    <li>Enter quantity, unit cost (if tracked), and reference (PO/GRN/WO).</li>
                    <li>System creates a Stock Transaction automatically.</li>
                  </ul>
                </div>
                <div class="col-md-5">
                  <div class="alert alert-light mb-2">
                    <div class="font-weight-bold text-gray-800 mb-1">Control checks</div>
                    <div class="wf-mini">Verify store/shelf, verify qty & unit cost, attach reference where applicable.</div>
                  </div>
                </div>
              </div>

              <div class="d-flex flex-wrap wf-no-print" style="gap:.5rem;">
                @can('inventory.stock.entries.view')
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock_entries.index') }}">
                    <i class="fas fa-list mr-1"></i> View Stock Entries
                  </a>
                @endcan
              </div>
            </div>
          </div>

          {{-- 2 --}}
          <div class="wf-step mb-4" id="wf-tx">
            <div class="wf-step-head">
              <div>
                <div class="font-weight-bold">
                  2) Stock Transactions
                  <span class="wf-badge audit ml-2"><i class="fas fa-clipboard-list"></i> Audit Trail</span>
                </div>
                <div class="wf-mini">Source of truth for reconciliation and investigations.</div>
              </div>
              <div class="text-right">
                <div class="wf-mini">Outcome</div>
                <div class="font-weight-bold text-gray-800">Trace every movement</div>
              </div>
            </div>
            <div class="wf-step-body">
              <p class="text-muted mb-2">
                Transactions capture every movement (in, out, transfer, adjustments).
                If you doubt what happened, start here.
              </p>

              <ul class="text-muted wf-ul mb-3">
                <li>Trace by product/variant, date range, store, transaction type, or reference.</li>
                <li>Spot missing transfer legs (OUT without IN) or wrong store/shelf.</li>
              </ul>

              <div class="d-flex flex-wrap wf-no-print" style="gap:.5rem;">
                @can('inventory.stock.transactions.view')
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock_entries.transactions.index') }}">
                    <i class="fas fa-exchange-alt mr-1"></i> View Transactions
                  </a>
                @endcan
              </div>
            </div>
          </div>

          {{-- 3 --}}
          <div class="wf-step mb-4" id="wf-levels">
            <div class="wf-step-head">
              <div>
                <div class="font-weight-bold">
                  3) Stock Levels
                  <span class="wf-badge ml-2"><i class="fas fa-layer-group"></i> Availability</span>
                </div>
                <div class="wf-mini">Calculated from transactions, summarized by variant & location.</div>
              </div>
              <div class="text-right">
                <div class="wf-mini">Outcome</div>
                <div class="font-weight-bold text-gray-800">What’s available now</div>
              </div>
            </div>
            <div class="wf-step-body">
              <p class="text-muted mb-2">
                Stock Levels shows what is available right now by product/variant and store/shelf.
              </p>

              <ul class="text-muted wf-ul mb-3">
                <li>Check before issuing, transferring, or committing sales.</li>
                <li>If levels are wrong: check transactions, wrong store, or unposted drafts.</li>
              </ul>

              <div class="d-flex flex-wrap wf-no-print" style="gap:.5rem;">
                @can('inventory.stock.levels.view')
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock.levels.index') }}">
                    <i class="fas fa-layer-group mr-1"></i> View Stock Levels
                  </a>
                @endcan
              </div>
            </div>
          </div>

          {{-- 4 --}}
          <div class="wf-step mb-4" id="wf-low">
            <div class="wf-step-head">
              <div>
                <div class="font-weight-bold">
                  4) Low Stock Levels
                  <span class="wf-badge move ml-2"><i class="fas fa-exclamation-triangle"></i> Reorder Alerts</span>
                </div>
                <div class="wf-mini">Flags items at/under reorder point to prevent stockouts.</div>
              </div>
              <div class="text-right">
                <div class="wf-mini">Outcome</div>
                <div class="font-weight-bold text-gray-800">Procurement action</div>
              </div>
            </div>
            <div class="wf-step-body">
              <p class="text-muted mb-2">
                Low Stock highlights items below their reorder threshold, supporting replenishment planning.
              </p>

              <ul class="text-muted wf-ul mb-3">
                <li>Raise purchase requests or supplier orders.</li>
                <li>Maintain reorder thresholds for critical/fast-moving items.</li>
              </ul>

              <div class="d-flex flex-wrap wf-no-print" style="gap:.5rem;">
                @can('inventory.stock.levels.low.view')
                  <a class="btn btn-sm btn-outline-warning" href="{{ route('admin.inventory.stock.levels.low.index') }}">
                    <i class="fas fa-exclamation-triangle mr-1"></i> View Low Stock
                  </a>
                @endcan
              </div>
            </div>
          </div>

          {{-- 5 --}}
          <div class="wf-step mb-4" id="wf-aging">
            <div class="wf-step-head">
              <div>
                <div class="font-weight-bold">
                  5) Stock Aging
                  <span class="wf-badge ml-2"><i class="fas fa-hourglass-half"></i> Slow-moving</span>
                </div>
                <div class="wf-mini">Identify stock that stays too long without movement.</div>
              </div>
              <div class="text-right">
                <div class="wf-mini">Outcome</div>
                <div class="font-weight-bold text-gray-800">Reduce dead stock</div>
              </div>
            </div>
            <div class="wf-step-body">
              <p class="text-muted mb-2">
                Stock Aging helps identify slow-moving items (expiry risk, dead stock, cash tied up).
              </p>

              <ul class="text-muted wf-ul mb-3">
                <li>Use age buckets (0–30, 31–60, 61–90+ days).</li>
                <li>Supports clearance/markdown and purchasing plan adjustments.</li>
              </ul>

              <div class="d-flex flex-wrap wf-no-print" style="gap:.5rem;">
                @can('inventory.stock.aging.view')
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock.aging.index') }}">
                    <i class="fas fa-hourglass-half mr-1"></i> View Stock Aging
                  </a>
                @endcan
              </div>
            </div>
          </div>

          {{-- 6 --}}
          <div class="wf-step mb-4" id="wf-transfer">
            <div class="wf-step-head">
              <div>
                <div class="font-weight-bold">
                  6) Stock Transfer
                  <span class="wf-badge move ml-2"><i class="fas fa-random"></i> Move</span>
                </div>
                <div class="wf-mini">Warehouse → Shop, Store A → Store B (two-leg transaction).</div>
              </div>
              <div class="text-right">
                <div class="wf-mini">Outcome</div>
                <div class="font-weight-bold text-gray-800">OUT + IN transactions</div>
              </div>
            </div>
            <div class="wf-step-body">
              <p class="text-muted mb-2">
                Transfers move stock between stores/locations and keep stock correct per location.
              </p>

              <ul class="text-muted wf-ul mb-3">
                <li>Creates two transactions: OUT from source + IN to destination.</li>
                <li>Draft can be edited until posted. Posting locks it.</li>
              </ul>

              <div class="d-flex flex-wrap wf-no-print" style="gap:.5rem;">
                @can('inventory.stock.transfers.view')
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.stock.transfers.index') }}">
                    <i class="fas fa-random mr-1"></i> View Transfers
                  </a>
                @endcan
              </div>
            </div>
          </div>

          {{-- 7 --}}
          <div class="wf-step" id="wf-issues">
            <div class="wf-step-head">
              <div>
                <div class="font-weight-bold">
                  7) Stock Issues
                  <span class="wf-badge out ml-2"><i class="fas fa-minus-circle"></i> OUT</span>
                  <span class="wf-badge ml-1"><i class="fas fa-box"></i> Consume/Dispatch</span>
                </div>
                <div class="wf-mini">Sales dispatch, internal use, damage/write-off, production usage.</div>
              </div>
              <div class="text-right">
                <div class="wf-mini">Outcome</div>
                <div class="font-weight-bold text-gray-800">Reduces stock</div>
              </div>
            </div>
            <div class="wf-step-body">
              <p class="text-muted mb-2">
                Stock Issues records stock leaving inventory and reduces levels in the selected location.
              </p>

              <ul class="text-muted wf-ul mb-3">
                <li>Select item + quantity + issue reason (sale/usage/damage/etc.).</li>
                <li>For production, issues can be linked to Work Orders (if enabled).</li>
              </ul>

              <div class="d-flex flex-wrap wf-no-print" style="gap:.5rem;">
                @can('inventory.stock.issues.view')
                  <a class="btn btn-sm btn-outline-danger" href="{{ route('admin.inventory.stock_issues.index') }}">
                    <i class="fas fa-minus-circle mr-1"></i> View Stock Issues
                  </a>
                @endcan
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>

    {{-- Right column --}}
    <div class="col-lg-4">

      <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
          <h6 class="m-0 font-weight-bold text-primary">Best-practice tips</h6>
        </div>
        <div class="card-body">
          <ul class="text-muted mb-0 wf-ul">
            <li><strong>Always use the correct store/shelf</strong> to keep per-location stock accurate.</li>
            <li><strong>Never edit stock quantities directly</strong> without a transaction.</li>
            <li><strong>Reconcile regularly</strong> (physical count vs Stock Levels).</li>
            <li><strong>Use Low Stock</strong> to prevent stockouts and plan procurement.</li>
            <li><strong>Use Aging</strong> to reduce dead stock and improve cashflow.</li>
          </ul>
        </div>
      </div>

      <div class="card shadow-sm mb-4 wf-faq">
        <div class="card-header bg-white">
          <h6 class="m-0 font-weight-bold text-primary">Common questions</h6>
        </div>
        <div class="card-body">
          <div class="wf-q">Why can’t I see some menu items?</div>
          <div class="wf-a">Access is controlled by roles/permissions. Contact an admin if you need access.</div>

          <div class="wf-q">Why are Stock Levels different across stores?</div>
          <div class="wf-a">Stock is tracked per location/store/shelf. Transfers and issues affect each location separately.</div>

          <div class="wf-q">What should I do if stock “disappears”?</div>
          <div class="wf-a mb-0">
            Check (1) wrong store/shelf, (2) missing transfer IN/OUT leg, or (3) unposted drafts.
            Start with <strong>Stock Transactions</strong> filters.
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="font-weight-bold mb-2">Printable SOP for onboarding</h6>
          <p class="text-muted mb-3">
            Export this workflow as a Standard Operating Procedure (SOP) for staff onboarding and audits.
          </p>

          @can('inventory.stock.workflow.sop.export')
            <a href="{{ route('admin.inventory.workflow.sop.index') }}" class="btn btn-sm btn-outline-secondary wf-no-print">
              <i class="fas fa-print mr-1"></i> Print SOP
            </a>

            <a href="{{ route('admin.inventory.workflow.sop.pdf') }}" class="btn btn-sm btn-secondary ml-2 wf-no-print">
              <i class="fas fa-file-pdf mr-1"></i> Download PDF
            </a>
          @endcan
        </div>
      </div>

    </div>
  </div>

</div>
@endsection
