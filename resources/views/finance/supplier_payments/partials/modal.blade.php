<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="payModalTitle">New Supplier Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="payForm">
          <input type="hidden" id="payment_id" name="id">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div id="pay_status_badge"></div>
            <div class="text-muted small">
              Amount: <b id="amountLbl">0.00</b>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-3">
              <label class="text-muted small">Payment No</label>
              <input type="text" class="form-control" id="payment_no" name="payment_no" placeholder="Auto">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Payment Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="payment_date" name="payment_date" required>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Supplier <span class="text-danger">*</span></label>
              <select class="form-control" id="supplier_id" name="supplier_id" style="width:100%"></select>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Currency</label>
              <select class="form-control" id="currency_code" name="currency_code" style="width:100%"></select>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">Bank Account <span class="text-danger">*</span></label>
              <select class="form-control" id="bank_account_id" name="bank_account_id" style="width:100%"></select>
            </div>

            <div class="col-md-4">
              <label class="text-muted small">AP Control Account (optional)</label>
              <select class="form-control" id="ap_control_account_id" name="ap_control_account_id" style="width:100%"></select>
              <small class="text-muted">If blank, posting can fallback to Company Settings mapping.</small>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Reference</label>
              <input type="text" class="form-control" id="reference" name="reference" placeholder="Receipt, transfer ref...">
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Memo</label>
              <input type="text" class="form-control" id="memo" name="memo" placeholder="Optional...">
            </div>

            <div class="col-md-12">
              <div class="d-flex justify-content-between align-items-center mt-2">
                <h6 class="mb-0">Allocate to Bills</h6>
                <div style="min-width:380px;">
                  <select class="form-control" id="bill_picker" style="width:100%"></select>
                  <small class="text-muted">Search posted bills (open balance) for the selected supplier.</small>
                </div>
              </div>

              <div class="table-responsive mt-2">
                <table class="table table-bordered align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th style="width:45%">Bill</th>
                      <th style="width:25%" class="text-end">Balance Due</th>
                      <th style="width:25%" class="text-end">Pay Amount</th>
                      <th style="width:5%"></th>
                    </tr>
                  </thead>
                  <tbody id="allocTbody"></tbody>
                </table>
              </div>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="savePayBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>