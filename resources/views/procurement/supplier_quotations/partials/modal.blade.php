<div class="modal fade" id="quotationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0" id="quotationModalTitle">New Supplier Quotation</h5>
          <small class="text-muted">Capture supplier response against RFQ</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body supplier-quotation-modal-body">
        <form id="quotationForm">
          <input type="hidden" id="quotation_id">
          <input type="hidden" id="supplier_id">

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div id="quotation_status_badge"></div>
            <div class="text-muted small text-end">
              Subtotal: <b id="subTotalLbl">0.00</b> |
              Discount: <b id="discountTotalLbl">0.00</b> |
              Tax: <b id="taxTotalLbl">0.00</b> |
              Total: <b id="grandTotalLbl">0.00</b>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">RFQ <span class="text-danger">*</span></label>
              <select class="form-control" id="rfq_id"></select>
            </div>

            <div class="col-md-4">
              <label class="form-label">RFQ Supplier <span class="text-danger">*</span></label>
              <select class="form-control" id="rfq_supplier_id"></select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Supplier</label>
              <input type="text" class="form-control sq-readonly" id="supplier_label" placeholder="Auto-filled from RFQ supplier" readonly>
            </div>

            <div class="col-md-3">
              <label class="form-label">Contact</label>
              <input type="text" class="form-control sq-readonly" id="contact_label" placeholder="Optional contact from RFQ supplier" readonly>
            </div>

            <div class="col-md-3">
              <label class="form-label">Supplier Quote No</label>
              <input type="text" class="form-control" id="supplier_quote_no" placeholder="Supplier's quotation reference">
            </div>

            <div class="col-md-2">
              <label class="form-label">Quotation Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="quotation_date">
            </div>

            <div class="col-md-2">
              <label class="form-label">Valid Until</label>
              <input type="date" class="form-control" id="valid_until">
            </div>

            <div class="col-md-2">
              <label class="form-label d-block">&nbsp;</label>
              <button type="button" class="btn btn-outline-primary w-100" id="loadRfqBtn">
                <i class="fas fa-download"></i> Load RFQ Lines
              </button>
            </div>

            <div class="col-md-2">
              <label class="form-label">Currency</label>
              <input type="text" class="form-control" id="currency_code" maxlength="3" placeholder="NGN">
            </div>

            <div class="col-md-2">
              <label class="form-label">FX Rate</label>
              <input type="number" step="0.000001" class="form-control" id="fx_rate" placeholder="1.000000">
            </div>

            <div class="col-md-4">
              <label class="form-label">Reference</label>
              <input type="text" class="form-control" id="reference" placeholder="Internal / external reference">
            </div>

            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" id="notes" rows="2" placeholder="Optional notes..."></textarea>
            </div>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Quotation Lines</h6>
          </div>

          <div class="quotation-lines-wrap">
            <table class="table table-bordered align-middle quotation-lines-table mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="col-product">Product</th>
                  <th class="col-description">Description</th>
                  <th class="col-unit">Unit</th>
                  <th class="col-qty text-end">Qty</th>
                  <th class="col-unitprice text-end">Unit Price</th>
                  <th class="col-discpct text-end">Disc %</th>
                  <th class="col-discamt text-end">Disc Amt</th>
                  <th class="col-taxcode">Tax Code</th>
                  <th class="col-taxrate text-end">Tax %</th>
                  <th class="col-taxamt text-end">Tax Amt</th>
                  <th class="col-total text-end">Line Total</th>
                  <th class="col-leadtime text-end">Lead Time</th>
                  <th class="col-remarks">Remarks</th>
                  <th class="col-action text-center">X</th>
                </tr>
              </thead>
              <tbody id="linesTbody"></tbody>
            </table>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveQuotationBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>