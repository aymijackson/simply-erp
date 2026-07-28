<div class="modal fade" id="grnShowModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Goods Receipt Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <label class="text-muted small">GRN No</label>
            <div id="vw_grn_no" class="fw-semibold">—</div>
          </div>
          <div class="col-md-3">
            <label class="text-muted small">Receipt Date</label>
            <div id="vw_receipt_date" class="fw-semibold">—</div>
          </div>
          <div class="col-md-3">
            <label class="text-muted small">Supplier Delivery Note No</label>
            <div id="vw_supplier_delivery_note_no" class="fw-semibold">—</div>
          </div>
          <div class="col-md-3">
            <label class="text-muted small">PO No</label>
            <div id="vw_po_no" class="fw-semibold">—</div>
          </div>

          <div class="col-md-3">
            <label class="text-muted small">Supplier</label>
            <div id="vw_supplier" class="fw-semibold">—</div>
          </div>
          <div class="col-md-3">
            <label class="text-muted small">Delivery Location</label>
            <div id="vw_delivery_location" class="fw-semibold">—</div>
          </div>
          <div class="col-md-3">
            <label class="text-muted small">Delivery Store</label>
            <div id="vw_delivery_store" class="fw-semibold">—</div>
          </div>
          <div class="col-md-3">
            <label class="text-muted small">Status</label>
            <div id="vw_status" class="fw-semibold">—</div>
          </div>

          <div class="col-md-4">
            <label class="text-muted small">Reference</label>
            <div id="vw_reference" class="fw-semibold">—</div>
          </div>
          <div class="col-md-8">
            <label class="text-muted small">Notes</label>
            <div id="vw_notes" class="fw-semibold">—</div>
          </div>

          <div class="col-md-3">
            <label class="text-muted small">Subtotal</label>
            <div id="vw_subtotal" class="fw-semibold">0.00</div>
          </div>
          <div class="col-md-3">
            <label class="text-muted small">Received By</label>
            <div id="vw_received_by" class="fw-semibold">—</div>
          </div>
          <div class="col-md-3">
            <label class="text-muted small">Posted By</label>
            <div id="vw_posted_by" class="fw-semibold">—</div>
          </div>
          <div class="col-md-3">
            <label class="text-muted small">Posted At</label>
            <div id="vw_posted_at" class="fw-semibold">—</div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Product</th>
                <th>Variant</th>
                <th>Description</th>
                <th>Unit</th>
                <th class="text-end">Ordered</th>
                <th class="text-end">Prev. Received</th>
                <th class="text-end">Received</th>
                <th class="text-end">Remaining</th>
                <th class="text-end">Accepted</th>
                <th class="text-end">Rejected</th>
                <th class="text-end">Damaged</th>
                <th>Batch No</th>
                <th>Serial No</th>
              </tr>
            </thead>
            <tbody id="vw_lines"></tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <a href="javascript:void(0)" target="_blank" id="vw_pdf_link" class="btn btn-outline-danger">
          <i class="fas fa-file-pdf"></i> PDF
        </a>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>