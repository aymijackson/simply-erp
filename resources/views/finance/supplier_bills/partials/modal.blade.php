<div class="modal fade" id="billModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="billModalTitle">New Supplier Bill</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="billForm">
          <input type="hidden" id="bill_id" name="id">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div id="bill_status_badge"></div>
            <div class="text-muted small">
              Subtotal: <b id="subTotalLbl">0.00</b> |
              Tax: <b id="taxTotalLbl">0.00</b> |
              Total: <b id="grandTotalLbl">0.00</b>
            </div>
          </div>

          <div class="card border shadow-sm mb-3">
            <div class="card-body bill-source-box">
              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <label class="text-muted small">Source Type</label>
                  <select class="form-control" id="source_type" name="source_type">
                    <option value="">Manual Entry</option>
                    <option value="purchase_requisition">Purchase Requisition</option>
                    <option value="rfq">RFQ</option>
                    <option value="supplier_quotation">Supplier Quotation</option>
                    <option value="purchase_order">Purchase Order</option>
                    <option value="goods_receipt">Goods Receipt</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="text-muted small">Source Record</label>
                  <select class="form-control" id="source_id" name="source_id" style="width:100%"></select>
                </div>

                <div class="col-md-2">
                  <button type="button" class="btn btn-outline-primary w-100" id="loadSourceBtn">
                    <i class="fas fa-download"></i> Load
                  </button>
                </div>
              </div>

              <small class="text-muted">
                Load procurement records (PO, GRN, RFQ etc.) to automatically populate the bill.
              </small>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-3">
              <label class="text-muted small">Bill No</label>
              <input type="text" class="form-control" id="bill_no" name="bill_no" placeholder="Auto">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Bill Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="bill_date" name="bill_date" required>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Due Date</label>
              <input type="date" class="form-control" id="due_date" name="due_date">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Currency</label>
              <select class="form-control" id="currency_code" name="currency_code" style="width:100%"></select>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Supplier</label>
              <select class="form-control" id="supplier_id" name="supplier_id" style="width:100%"></select>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Vendor Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="Enter vendor name...">
              <small class="text-muted">This can be typed manually or auto-filled from the selected source or supplier.</small>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">AP Control Account (optional)</label>
              <select class="form-control" id="ap_control_account_id" name="ap_control_account_id" style="width:100%"></select>
              <small class="text-muted">If blank, posting will fallback to Company Settings mapping.</small>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">FX Rate</label>
              <input type="number" step="0.000001" class="form-control" id="fx_rate" name="fx_rate" placeholder="1.000000">
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Reference</label>
              <input type="text" class="form-control" id="reference" name="reference" placeholder="Supplier invoice no...">
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Memo</label>
              <input type="text" class="form-control" id="memo" name="memo" placeholder="Optional...">
            </div>

            <div class="col-md-12">
              <div class="d-flex justify-content-between align-items-center mt-2">
                <h6 class="mb-0">Bill Lines</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
                  <i class="fas fa-plus"></i> Add Line
                </button>
              </div>

              <div class="table-responsive mt-2">
                <table class="table table-bordered align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th style="width:30%">GL Account</th>
                      <th>Description</th>
                      <th style="width:10%" class="text-end">Qty</th>
                      <th style="width:12%" class="text-end">Unit Cost</th>
                      <th style="width:10%" class="text-end">Tax %</th>
                      <th style="width:12%" class="text-end">Line Total</th>
                      <th style="width:6%"></th>
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
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="saveBillBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>