<div class="modal fade" id="reqModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reqModalTitle">New Purchase Requisition</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body procurement-modal-body">
        <form id="reqForm">
          <input type="hidden" id="req_id" name="id">

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div id="req_status_badge"></div>
            <div class="text-muted small text-end">
              Subtotal: <b id="subTotalLbl">0.00</b> |
              Tax: <b id="taxTotalLbl">0.00</b> |
              Total: <b id="grandTotalLbl">0.00</b>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-lg-3 col-md-6">
              <label class="text-muted small">Requisition Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="requisition_date" required>
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="text-muted small">Needed By Date</label>
              <input type="date" class="form-control" id="needed_by_date">
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="text-muted small">Priority <span class="text-danger">*</span></label>
              <select class="form-control" id="priority">
                <option value="low">Low</option>
                <option value="normal" selected>Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>

            <div class="col-lg-3 col-md-6">
              <label class="text-muted small">Reference</label>
              <input type="text" class="form-control" id="reference" placeholder="Internal ref...">
            </div>

            <div class="col-12">
              <label class="text-muted small">Notes</label>
              <textarea class="form-control" id="notes" rows="2" placeholder="Optional..."></textarea>
            </div>

            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                <h6 class="mb-0">Requisition Lines</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
                  <i class="fas fa-plus"></i> Add Line
                </button>
              </div>

              <div class="table-responsive requisition-lines-wrap">
                <table class="table table-bordered align-middle mb-0 requisition-lines-table">
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
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveReqBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>