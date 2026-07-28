<div class="modal fade" id="jeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="jeModalTitle">New Journal Entry</h5>
          <div class="mt-1" id="je_status_badge"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="jeForm">
          <input type="hidden" id="je_id" name="id">

          <div class="row g-3">
            <div class="col-md-3">
              <label class="text-muted small">Entry No (optional)</label>
              <input class="form-control" id="entry_no" name="entry_no" placeholder="Auto-generate if empty">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Entry Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="entry_date" name="entry_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Reference</label>
              <input class="form-control" id="reference" name="reference" placeholder="Optional reference">
            </div>

            <div class="col-md-3">
              <label class="text-muted small">Memo</label>
              <input class="form-control" id="memo" name="memo" placeholder="Optional memo">
            </div>
          </div>

          <hr class="my-3">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
                <i class="fas fa-plus"></i> Add Line
              </button>
            </div>

            <div class="d-flex gap-2 align-items-center">
              <span class="badge" id="balanceBadge">NOT BALANCED</span>
              <span class="text-muted small">Debit: <b id="totDebit">0.00</b></span>
              <span class="text-muted small">Credit: <b id="totCredit">0.00</b></span>
              <span class="text-muted small">Diff: <b id="totDiff">0.00</b></span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="bg-light">
                <tr>
                  <th>GL Account</th>
                  <th class="text-end">Debit</th>
                  <th class="text-end">Credit</th>
                  <th>Currency</th>
                  <th class="text-end">FX</th>
                  <th>Bank Account</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="linesTbody"></tbody>
            </table>
          </div>

          <small class="text-muted d-block">
            Tip: Use Bank Account column only for bank/cash impacting lines (optional). Entry must still balance.
          </small>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" type="button" id="saveJeBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>