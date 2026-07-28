<div class="modal fade" id="reqShowModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Purchase Requisition Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <small class="text-muted d-block">Requisition No</small>
            <div class="fw-bold" id="vw_requisition_no">—</div>
          </div>
          <div class="col-md-3">
            <small class="text-muted d-block">Date</small>
            <div class="fw-bold" id="vw_requisition_date">—</div>
          </div>
          <div class="col-md-3">
            <small class="text-muted d-block">Needed By</small>
            <div class="fw-bold" id="vw_needed_by_date">—</div>
          </div>
          <div class="col-md-3">
            <small class="text-muted d-block">Priority</small>
            <div class="fw-bold" id="vw_priority">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Status</small>
            <div class="fw-bold" id="vw_status">—</div>
          </div>
          <div class="col-md-3">
            <small class="text-muted d-block">Requested By</small>
            <div class="fw-bold" id="vw_requested_by">—</div>
          </div>
          <div class="col-md-3">
            <small class="text-muted d-block">Approved By</small>
            <div class="fw-bold" id="vw_approved_by">—</div>
          </div>
          <div class="col-md-3">
            <small class="text-muted d-block">Approved At</small>
            <div class="fw-bold" id="vw_approved_at">—</div>
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
                <th class="text-end">Est. Unit Cost</th>
                <th>Tax Code</th>
                <th class="text-end">Tax %</th>
                <th class="text-end">Tax Amt</th>
                <th class="text-end">Line Total</th>
              </tr>
            </thead>
            <tbody id="vw_lines"></tbody>
            <tfoot>
              <tr>
                <th colspan="7" class="text-end">Subtotal</th>
                <th colspan="2" class="text-end" id="vw_subtotal">0.00</th>
              </tr>
              <tr>
                <th colspan="7" class="text-end">Tax Total</th>
                <th colspan="2" class="text-end" id="vw_tax_total">0.00</th>
              </tr>
              <tr>
                <th colspan="7" class="text-end">Grand Total</th>
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