@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0 text-danger">Finance Data Flush</h4>
      <div class="text-muted small">
        Controlled reset utility for finance transactions, reconciliations, tax, petty cash, payroll-related finance data, and related records.
      </div>
    </div>
  </div>

  <div class="alert alert-danger">
    <b>Warning:</b> This action permanently deletes finance data for the current company. Use only for sandbox reset, implementation cleanup, or controlled re-initialisation.
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">Flush Options</h6>
        </div>
        <div class="card-body">

          <div class="mb-3">
            <div class="fw-semibold mb-2">Core Finance Data</div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_transactions" checked>
              <label class="form-check-label" for="include_transactions">Journal Entries / GL Transactions</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_journals" checked>
              <label class="form-check-label" for="include_journals">Journal Headers and Lines</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_banking" checked>
              <label class="form-check-label" for="include_banking">Bank Transactions</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_bank_reconciliation" checked>
              <label class="form-check-label" for="include_bank_reconciliation">Bank Reconciliation</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_budgets" checked>
              <label class="form-check-label" for="include_budgets">Budgets</label>
            </div>
          </div>

          <hr>

          <div class="mb-3">
            <div class="fw-semibold mb-2">Receivables / Payables</div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_ar_ap" checked>
              <label class="form-check-label" for="include_ar_ap">Invoices / Payments / Supplier Bills</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_receivables">
              <label class="form-check-label" for="include_receivables">Accounts Receivable Only</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_payables">
              <label class="form-check-label" for="include_payables">Accounts Payable Only</label>
            </div>
          </div>

          <hr>

          <div class="mb-3">
            <div class="fw-semibold mb-2">Operational Finance Modules</div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_expenses" checked>
              <label class="form-check-label" for="include_expenses">Expenses</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_fixed_assets" checked>
              <label class="form-check-label" for="include_fixed_assets">Fixed Assets Transactions</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_petty_cash" checked>
              <label class="form-check-label" for="include_petty_cash">Petty Cash Transactions</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_reconciliations" checked>
              <label class="form-check-label" for="include_reconciliations">Petty Cash / Finance Reconciliations</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_tax" checked>
              <label class="form-check-label" for="include_tax">Tax Transactions / Tax Data</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_payroll">
              <label class="form-check-label" for="include_payroll">Payroll-Related Finance Data</label>
            </div>
          </div>

          <hr>

          <div class="mb-3">
            <div class="fw-semibold mb-2 text-danger">Setup / Reset Options</div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="include_setup">
              <label class="form-check-label text-danger" for="include_setup">Include Finance Setup / Masters</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="reset_opening_balances">
              <label class="form-check-label" for="reset_opening_balances">Reset Bank Opening Balances</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="reset_period_statuses">
              <label class="form-check-label" for="reset_period_statuses">Reset Fiscal Period Statuses</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input flush-opt" type="checkbox" id="reset_document_numbers">
              <label class="form-check-label" for="reset_document_numbers">Reset Finance Document Numbering</label>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Confirmation Phrase</label>
            <input type="text" class="form-control" id="confirm_phrase" placeholder="Type FLUSH FINANCE to run">
          </div>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="btnPreview">
              <i class="fas fa-search me-1"></i> Preview
            </button>

            @can('finance.data_flush.run')
            <button type="button" class="btn btn-danger" id="btnRun">
              <i class="fas fa-trash-alt me-1"></i> Run Flush
            </button>
            @endcan
          </div>

        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">Preview Summary</h6>
        </div>
        <div class="card-body">
          <div id="previewEmpty" class="text-muted">
            Click <b>Preview</b> to see the exact tables and row counts that will be deleted.
          </div>

          <div id="previewBox" class="d-none">
            <div class="mb-2">
              <b>Total Rows:</b> <span id="totalRows">0</span>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm">
                <thead>
                  <tr>
                    <th>Table</th>
                    <th class="text-end">Rows</th>
                  </tr>
                </thead>
                <tbody id="previewTableBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="card mt-3 border-danger">
        <div class="card-header bg-light">
          <h6 class="mb-0 text-danger">Safety Notes</h6>
        </div>
        <div class="card-body small text-muted">
          <div class="mb-2">Use Preview first to confirm scope before running a flush.</div>
          <div class="mb-2">Avoid enabling <b>Include Finance Setup / Masters</b> unless you intentionally want to wipe configuration data.</div>
          <div class="mb-2">In production, full setup flush remains blocked by the controller.</div>
          <div>Document number and fiscal period resets should only be used during controlled reimplementation or sandbox cleanup.</div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

function payload() {
    return {
        include_transactions: $('#include_transactions').is(':checked') ? 1 : 0,
        include_journals: $('#include_journals').is(':checked') ? 1 : 0,
        include_banking: $('#include_banking').is(':checked') ? 1 : 0,
        include_bank_reconciliation: $('#include_bank_reconciliation').is(':checked') ? 1 : 0,
        include_budgets: $('#include_budgets').is(':checked') ? 1 : 0,

        include_ar_ap: $('#include_ar_ap').is(':checked') ? 1 : 0,
        include_receivables: $('#include_receivables').is(':checked') ? 1 : 0,
        include_payables: $('#include_payables').is(':checked') ? 1 : 0,

        include_expenses: $('#include_expenses').is(':checked') ? 1 : 0,
        include_fixed_assets: $('#include_fixed_assets').is(':checked') ? 1 : 0,
        include_petty_cash: $('#include_petty_cash').is(':checked') ? 1 : 0,
        include_reconciliations: $('#include_reconciliations').is(':checked') ? 1 : 0,
        include_tax: $('#include_tax').is(':checked') ? 1 : 0,
        include_payroll: $('#include_payroll').is(':checked') ? 1 : 0,

        include_setup: $('#include_setup').is(':checked') ? 1 : 0,
        reset_opening_balances: $('#reset_opening_balances').is(':checked') ? 1 : 0,
        reset_period_statuses: $('#reset_period_statuses').is(':checked') ? 1 : 0,
        reset_document_numbers: $('#reset_document_numbers').is(':checked') ? 1 : 0,

        confirm_phrase: $('#confirm_phrase').val()
    };
}

function renderPreview(summary) {
    $('#previewEmpty').addClass('d-none');
    $('#previewBox').removeClass('d-none');

    const rows = summary.tables || [];
    $('#totalRows').text((summary.total_rows || 0).toLocaleString());

    let html = '';
    rows.forEach(function (r) {
        html += `
            <tr>
                <td>${r.table}</td>
                <td class="text-end">${Number(r.rows || 0).toLocaleString()}</td>
            </tr>
        `;
    });

    if (!html) {
        html = `
            <tr>
                <td colspan="2" class="text-center text-muted">No rows matched the selected flush scope.</td>
            </tr>
        `;
    }

    $('#previewTableBody').html(html);
}

$('#btnPreview').on('click', function () {
    $.post("{{ route('admin.finance.data_flush.preview') }}", payload())
        .done(function (res) {
            renderPreview(res.summary || {});
            Swal.fire('Preview ready', 'Review the summary before running the flush.', 'success');
        })
        .fail(function (xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Preview failed', 'error');
        });
});

$('#btnRun').on('click', function () {
    const p = payload();

    Swal.fire({
        title: 'Run finance data flush?',
        html: `
            <div class="text-start">
                <p>This will permanently delete selected finance data for the current company.</p>
                <p><b>Type exactly:</b> FLUSH FINANCE</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Run Flush'
    }).then(function (result) {
        if (!result.isConfirmed) return;

        $.post("{{ route('admin.finance.data_flush.run') }}", p)
            .done(function (res) {
                Swal.fire('Completed', res.message, 'success');
                $('#previewTableBody').html('');
                $('#previewBox').addClass('d-none');
                $('#previewEmpty').removeClass('d-none');
                $('#confirm_phrase').val('');
            })
            .fail(function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Flush failed', 'error');
            });
    });
});
</script>
@endpush