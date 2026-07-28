<div class="modal fade" id="quotationShowModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Supplier Quotation Details</h5>
          <small class="text-muted">View quotation breakdown</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <small class="text-muted d-block">Quotation No</small>
            <div class="fw-bold" id="vw_quotation_no">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Quotation Date</small>
            <div class="fw-bold" id="vw_quotation_date">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Valid Until</small>
            <div class="fw-bold" id="vw_valid_until">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Status</small>
            <div class="fw-bold" id="vw_status">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">RFQ No</small>
            <div class="fw-bold" id="vw_rfq_no">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Supplier</small>
            <div class="fw-bold" id="vw_supplier">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Supplier Quote No</small>
            <div class="fw-bold" id="vw_supplier_quote_no">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Reference</small>
            <div class="fw-bold" id="vw_reference">—</div>
          </div>

          <div class="col-md-8">
            <small class="text-muted d-block">Notes</small>
            <div class="fw-bold" id="vw_notes">—</div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="bg-light">
              <tr>
                <th>Product</th>
                <th>Description</th>
                <th>Unit</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Disc %</th>
                <th class="text-end">Disc Amt</th>
                <th>Tax Code</th>
                <th class="text-end">Tax %</th>
                <th class="text-end">Tax Amt</th>
                <th class="text-end">Line Total</th>
              </tr>
            </thead>
            <tbody id="vw_lines"></tbody>
            <tfoot>
              <tr>
                <th colspan="9" class="text-end">Subtotal</th>
                <th colspan="2" class="text-end" id="vw_subtotal">0.00</th>
              </tr>
              <tr>
                <th colspan="9" class="text-end">Discount Total</th>
                <th colspan="2" class="text-end" id="vw_discount_total">0.00</th>
              </tr>
              <tr>
                <th colspan="9" class="text-end">Tax Total</th>
                <th colspan="2" class="text-end" id="vw_tax_total">0.00</th>
              </tr>
              <tr>
                <th colspan="9" class="text-end">Grand Total</th>
                <th colspan="2" class="text-end" id="vw_total_amount">0.00</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <a href="#" target="_blank" id="vw_pdf_link" class="btn btn-outline-dark">
          <i class="fas fa-file-pdf"></i> Download PDF
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>