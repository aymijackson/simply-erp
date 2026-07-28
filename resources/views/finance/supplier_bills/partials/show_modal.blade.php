<div class="modal fade" id="billShowModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Supplier Bill Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex justify-content-end mb-3">
          <a href="#" target="_blank" id="vw_pdf_link" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-file-pdf"></i> Open PDF
          </a>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-3"><strong>Bill No:</strong><br><span id="vw_bill_no">—</span></div>
          <div class="col-md-3"><strong>Bill Date:</strong><br><span id="vw_bill_date">—</span></div>
          <div class="col-md-3"><strong>Due Date:</strong><br><span id="vw_due_date">—</span></div>
          <div class="col-md-3"><strong>Status:</strong><br><span id="vw_status">—</span></div>

          <div class="col-md-4"><strong>Supplier:</strong><br><span id="vw_supplier">—</span></div>
          <div class="col-md-4"><strong>Vendor Name:</strong><br><span id="vw_vendor_name">—</span></div>
          <div class="col-md-2"><strong>Currency:</strong><br><span id="vw_currency">—</span></div>
          <div class="col-md-2"><strong>FX Rate:</strong><br><span id="vw_fx_rate">—</span></div>

          <div class="col-md-4"><strong>Reference:</strong><br><span id="vw_reference">—</span></div>
          <div class="col-md-4"><strong>Source:</strong><br><span id="vw_source">—</span></div>
          <div class="col-md-4"><strong>Payable Account:</strong><br><span id="vw_payable_account">—</span></div>

          <div class="col-md-8"><strong>Memo:</strong><br><span id="vw_memo">—</span></div>
          <div class="col-md-4"><strong>Journal Entry:</strong><br><span id="vw_journal_entry">—</span></div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="bg-light">
              <tr>
                <th>GL Account</th>
                <th>Description</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Cost</th>
                <th class="text-end">Tax</th>
                <th class="text-end">Line Total</th>
                <th>Memo</th>
              </tr>
            </thead>
            <tbody id="vw_lines"></tbody>
            <tfoot>
              <tr>
                <th colspan="5" class="text-end">Subtotal</th>
                <th class="text-end" id="vw_subtotal">0.00</th>
                <th></th>
              </tr>
              <tr>
                <th colspan="5" class="text-end">Tax Total</th>
                <th class="text-end" id="vw_tax_total">0.00</th>
                <th></th>
              </tr>
              <tr>
                <th colspan="5" class="text-end">Total Amount</th>
                <th class="text-end" id="vw_total_amount">0.00</th>
                <th></th>
              </tr>
              <tr>
                <th colspan="5" class="text-end">Amount Paid</th>
                <th class="text-end" id="vw_amount_paid">0.00</th>
                <th></th>
              </tr>
              <tr>
                <th colspan="5" class="text-end">Balance Due</th>
                <th class="text-end" id="vw_balance_due">0.00</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>