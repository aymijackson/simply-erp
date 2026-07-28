<div class="modal fade" id="txModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="txModalTitle">New Bank Transaction</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="txForm">
          <input type="hidden" id="txn_id" value="">

          <div class="row g-3">
            <div class="col-md-3">
              <label class="text-muted small">Txn No (optional)</label>
              <input type="text" class="form-control" id="txn_no" placeholder="Auto if blank">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Date</label>
              <input type="date" class="form-control" id="txn_date" required>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Type</label>
              <select class="form-control" id="type" required>
                <option value="deposit">deposit</option>
                <option value="withdrawal">withdrawal</option>
                <option value="transfer">transfer</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Currency</label>
              <select class="form-control" id="currency_code"></select>
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Bank/Cash Account</label>
              <select class="form-control" id="bank_account_id" required></select>
              <small class="text-muted">This is the account being affected.</small>
            </div>

            <div class="col-md-6 d-none" id="toBankWrap">
              <label class="text-muted small">To Bank/Cash Account</label>
              <select class="form-control" id="to_bank_account_id"></select>
              <small class="text-muted">For transfers only.</small>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Exchange Rate</label>
              <input type="number" step="0.000001" class="form-control" id="exchange_rate" value="1.000000">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Reference</label>
              <input type="text" class="form-control" id="reference" placeholder="e.g. POS-123, TRF-REF">
            </div>

            <div class="col-md-6">
              <label class="text-muted small">Description</label>
              <input type="text" class="form-control" id="description" placeholder="Short memo...">
            </div>

            {{-- Optional bank selection (for info/display) --}}
            <div class="col-md-3">
              <label class="text-muted small">Bank (optional)</label>
              <input type="hidden" id="bank_country" value="NG">
              <select class="form-control" id="bank_id"></select>
            </div>
            <div class="col-md-3">
              <label class="text-muted small">Bank Name (optional)</label>
              <input type="text" class="form-control" id="bank_name" placeholder="Auto if you pick a bank">
            </div>

            {{-- Transfer Total --}}
            <div class="col-md-3 d-none" id="totalTransferWrap">
              <label class="text-muted small">Transfer Amount</label>
              <input type="number" step="0.01" class="form-control" id="transfer_amount" value="0.00">
            </div>

            {{-- Split Lines --}}
            <div class="col-12" id="splitWrap">
              <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="fw-bold">Split Lines</div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
                  <i class="fas fa-plus"></i> Add Line
                </button>
              </div>

              <div class="table-responsive mt-2">
                <table class="table table-bordered align-middle">
                  <thead class="bg-light">
                    <tr>
                      <th>Split Account (GL)</th>
                      <th>Memo</th>
                      <th style="width:140px;">Amount</th>
                      <th style="width:60px;"></th>
                    </tr>
                  </thead>
                  <tbody id="linesTbody"></tbody>
                </table>
              </div>
            </div>

            <div class="col-12">
              <div class="alert alert-secondary mb-0">
                <div class="d-flex justify-content-between">
                  <div><i class="fas fa-calculator me-1"></i> Preview Total</div>
                  <div class="fw-bold"><span id="previewTotal">0.00</span></div>
                </div>
              </div>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveTxBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>