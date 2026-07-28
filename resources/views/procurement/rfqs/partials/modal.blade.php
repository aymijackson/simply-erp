<div class="modal fade" id="rfqModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="rfqModalTitle">New RFQ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body rfq-modal-body">
        <form id="rfqForm">
          <input type="hidden" id="rfq_id">

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div id="rfq_status_badge"></div>
            <div class="text-muted small text-end">
              Subtotal: <b id="subTotalLbl">0.00</b> |
              Tax: <b id="taxTotalLbl">0.00</b> |
              Total: <b id="grandTotalLbl">0.00</b>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="text-muted small">Approved Requisition</label>
              <div class="input-group">
                <select class="form-control" id="requisition_id"></select>
                <button type="button" class="btn btn-outline-primary" id="loadReqBtn">Load</button>
              </div>
            </div>

            <div class="col-md-2">
              <label class="text-muted small">RFQ Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="rfq_date" required>
            </div>

            <div class="col-md-2">
              <label class="text-muted small">Closing Date</label>
              <input type="date" class="form-control" id="closing_date">
            </div>

            <div class="col-md-2">
              <label class="text-muted small">Currency</label>
              <input type="text" class="form-control" id="currency_code" maxlength="3" placeholder="NGN">
            </div>

            <div class="col-md-2">
              <label class="text-muted small">FX Rate</label>
              <input type="number" step="0.000001" class="form-control" id="fx_rate" placeholder="1.000000">
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Reference</label>
              <input type="text" class="form-control" id="reference" placeholder="Internal ref...">
            </div>

            <div class="col-md-8">
              <label class="text-muted small">Notes</label>
              <textarea class="form-control" id="notes" rows="2" placeholder="Optional..."></textarea>
            </div>

            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                <h6 class="mb-0">RFQ Lines</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
                  <i class="fas fa-plus"></i> Add Line
                </button>
              </div>

              <div class="table-responsive rfq-lines-wrap">
                <table class="table table-bordered align-middle mb-0 rfq-lines-table">
                  <thead class="bg-light">
                    <tr>
                      <th class="col-product">Product</th>
                      <th class="col-description">Description</th>
                      <th class="col-unit">Unit</th>
                      <th class="col-qty text-end">Qty</th>
                      <th class="col-unitcost text-end">Est. Unit Cost</th>
                      <th class="col-taxcode">Tax Code</th>
                      <th class="col-taxrate text-end">Tax %</th>
                      <th class="col-taxamt text-end">Tax Amt</th>
                      <th class="col-total text-end">Line Total</th>
                      <th class="col-action"></th>
                    </tr>
                  </thead>
                  <tbody id="linesTbody"></tbody>
                </table>
              </div>
            </div>

            <div class="col-12 mt-3">
              <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                <h6 class="mb-0">Suppliers</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addSupplierBtn">
                  <i class="fas fa-plus"></i> Add Supplier
                </button>
              </div>

              <div class="table-responsive rfq-suppliers-wrap">
                <table class="table table-bordered align-middle mb-0 rfq-suppliers-table">
                  <thead class="bg-light">
                    <tr>
                      <th class="col-supplier">Supplier</th>
                      <th class="col-contact">Contact</th>
                      <th class="col-name">Contact Name</th>
                      <th class="col-email">Contact Email</th>
                      <th class="col-phone">Contact Phone</th>
                      <th class="col-notes">Notes</th>
                      <th class="col-action"></th>
                    </tr>
                  </thead>
                  <tbody id="suppliersTbody"></tbody>
                </table>
              </div>
              <small class="text-muted">
                Select an existing supplier contact where available. If none exists, enter contact details manually and a supplier contact record will be created automatically.
              </small>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveRfqBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>