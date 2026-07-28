<div class="modal fade" id="grnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="grnForm" onsubmit="return false;">
        <input type="hidden" id="grn_id">

        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0" id="grnModalTitle">Goods Receipt</h5>
            <small id="grn_status_badge"></small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body grn-modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label">Purchase Order <span class="text-danger">*</span></label>
              <div class="input-group">
                <select class="form-control" id="purchase_order_id" style="width:100%"></select>
                <button type="button" class="btn btn-outline-primary" id="loadPoBtn">
                  <i class="fas fa-download"></i> Load PO
                </button>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Supplier <span class="text-danger">*</span></label>
              <select class="form-control" id="supplier_id" style="width:100%"></select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Receipt Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="receipt_date">
            </div>

            <div class="col-md-3">
              <label class="form-label">Supplier Delivery Note No</label>
              <input type="text" class="form-control" id="supplier_delivery_note_no">
            </div>

            <div class="col-md-3">
              <label class="form-label">Delivery Location</label>
              <select class="form-control" id="delivery_location_id" style="width:100%"></select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Delivery Store</label>
              <select class="form-control" id="delivery_store_id" style="width:100%"></select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Reference</label>
              <input type="text" class="form-control" id="reference">
            </div>

            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" id="notes" rows="2"></textarea>
            </div>
          </div>

          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong>Receipt Lines</strong>
              <div>
                <span class="badge bg-info">Variant is required for received lines</span>
              </div>
            </div>

            <div class="card-body p-2">
              <div class="grn-lines-wrap">
                <table class="table table-bordered table-sm align-middle grn-lines-table mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="col-product">Product</th>
                      <th class="col-variant">Variant</th>
                      <th class="col-description">Description</th>
                      <th class="col-unit">Unit</th>
                      <th class="col-ordered text-end">Ordered</th>
                      <th class="col-prevrecv text-end">Prev. Received</th>
                      <th class="col-received text-end">Received</th>
                      <th class="col-remaining text-end">Remaining</th>
                      <th class="col-unitcost text-end">Unit Cost</th>
                      <th class="col-total text-end">Line Total</th>
                      <th class="col-accepted text-end">Accepted</th>
                      <th class="col-rejected text-end">Rejected</th>
                      <th class="col-damaged text-end">Damaged</th>
                      <th class="col-batch">Batch No</th>
                      <th class="col-serial">Serial No</th>
                      <th class="col-expiry">Expiry Date</th>
                      <th class="col-remarks">Remarks</th>
                      <th class="col-action text-center">X</th>
                    </tr>
                  </thead>
                  <tbody id="linesTbody"></tbody>
                  <tfoot>
                    <tr>
                      <th colspan="9" class="text-end">Subtotal</th>
                      <th class="text-end">
                        <span id="subTotalLbl">0.00</span>
                      </th>
                      <th colspan="8"></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="saveGrnBtn">
            <i class="fas fa-save"></i> Save Goods Receipt
          </button>
        </div>
      </form>
    </div>
  </div>
</div>