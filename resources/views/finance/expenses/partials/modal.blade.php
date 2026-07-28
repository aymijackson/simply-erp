{{-- Modal --}}
<div class="modal fade" id="expModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="expModalTitle">New Expense</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="expForm">
          <input type="hidden" id="expense_id" name="id">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div id="exp_status_badge"></div>
            <div class="text-muted small">
              Subtotal: <b id="subTotalLbl">0.00</b> |
              Tax: <b id="taxTotalLbl">0.00</b> |
              Total: <b id="grandTotalLbl">0.00</b>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-3">
              <label class="text-muted small">Expense No</label>
              <input type="text" class="form-control" id="expense_no" placeholder="Auto">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Expense Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="expense_date" required>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Category <span class="text-danger">*</span></label>
              <select class="form-control" id="category_id" style="width:100%"></select>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Supplier (optional)</label>
              <select class="form-control" id="supplier_id" style="width:100%"></select>
              <small class="text-muted">Selecting a supplier can auto-fill Vendor Name.</small>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Vendor Name (fallback)</label>
              <input type="text" class="form-control" id="vendor_name" placeholder="Use when no supplier exists">
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Payment Mode <span class="text-danger">*</span></label>
              <select class="form-control" id="payment_mode">
                <option value="bank">bank</option>
                <option value="cash">cash</option>
                <option value="credit">credit</option>
              </select>
            </div>

            <div class="col-md-4" id="bankWrap">
              <label class="text-muted small">Bank Account (required for bank)</label>
              <select class="form-control" id="bank_account_id" style="width:100%"></select>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Payable / Control Account</label>
              <select class="form-control" id="payable_account_id" style="width:100%"></select>
              <small class="text-muted">Used for posting when payment_mode=credit.</small>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Currency</label>
              <select class="form-control" id="currency_code" style="width:100%"></select>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">FX Rate</label>
              <input type="number" step="0.000001" class="form-control" id="fx_rate" placeholder="1.000000">
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Reference</label>
              <input type="text" class="form-control" id="reference" placeholder="Receipt no, invoice no...">
            </div>

            <div class="col-md-12">
              <label class="text-muted small">Memo</label>
              <textarea class="form-control" id="memo" rows="2" placeholder="Optional..."></textarea>
            </div>

            <div class="col-md-12">
              <div class="d-flex justify-content-between align-items-center mt-2">
                <h6 class="mb-0">Expense Lines</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
                  <i class="fas fa-plus"></i> Add Line
                </button>
              </div>

              <div class="table-responsive mt-2">
                <table class="table table-bordered align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th style="width:20%">GL Account</th>
                      <th style="width:16%">Description</th>
                      <th style="width:8%" class="text-end">Qty</th>
                      <th style="width:10%" class="text-end">Unit Cost</th>
                      <th style="width:18%">Tax Code</th>
                      <th style="width:8%" class="text-end">Tax %</th>
                      <th style="width:8%" class="text-end">Tax Amt</th>
                      <th style="width:10%" class="text-end">Line Total</th>
                      <th style="width:4%"></th>
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
        <button type="button" class="btn btn-primary" id="saveExpBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>