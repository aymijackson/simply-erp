<div class="modal fade" id="crModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="crModalTitle">New Supplier Credit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="crForm">
          <input type="hidden" id="credit_id" name="id">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div id="cr_status_badge"></div>
            <div class="text-muted small">
              Subtotal: <b id="subLbl">0.00</b> |
              Tax: <b id="taxLbl">0.00</b> |
              Total: <b id="totLbl">0.00</b> |
              Unapplied: <b id="unappliedLbl">0.00</b>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-3">
              <label class="text-muted small">Credit No</label>
              <input type="text" class="form-control" id="credit_no" name="credit_no" placeholder="Auto">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Credit Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="credit_date" name="credit_date" required>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Supplier <span class="text-danger">*</span></label>
              <select class="form-control" id="supplier_id" name="supplier_id" style="width:100%"></select>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">AP Control Account (optional)</label>
              <select class="form-control" id="ap_control_account_id" name="ap_control_account_id" style="width:100%"></select>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Currency</label>
              <select class="form-control" id="currency_code" name="currency_code" style="width:100%"></select>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">FX Rate</label>
              <input type="number" step="0.000001" class="form-control" id="fx_rate" name="fx_rate" placeholder="1.000000">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Reference</label>
              <input type="text" class="form-control" id="reference" name="reference" placeholder="Credit note no...">
            </div>

            <div class="col-md-12">
              <label class="text-muted small">Memo</label>
              <textarea class="form-control" id="memo" name="memo" rows="2"></textarea>
            </div>

            <div class="col-md-12">
              <div class="d-flex justify-content-between align-items-center mt-2">
                <h6 class="mb-0">Distribution (What is being credited)</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addDistBtn">
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
                  <tbody id="distTbody"></tbody>
                </table>
              </div>
            </div>

            <div class="col-md-12">
              <div class="d-flex justify-content-between align-items-center mt-3">
                <h6 class="mb-0">Applications (Optional: apply credit to bills)</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addAppBtn">
                  <i class="fas fa-plus"></i> Add Bill
                </button>
              </div>

              <div class="table-responsive mt-2">
                <table class="table table-bordered align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th>Bill</th>
                      <th style="width:25%" class="text-end">Amount Applied</th>
                      <th style="width:6%"></th>
                    </tr>
                  </thead>
                  <tbody id="appTbody"></tbody>
                </table>
              </div>

              <small class="text-muted d-block mt-2">
                Only posted bills with balance due will show. Applied total cannot exceed credit total.
              </small>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveCrBtn"><i class="fas fa-save"></i> Save</button>
      </div>
    </div>
  </div>
</div>