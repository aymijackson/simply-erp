<div class="modal fade" id="poModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0" id="poModalTitle">New Purchase Order</h5>
          <small class="text-muted">Create PO manually or from supplier quotation</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body purchase-order-modal-body">
        <form id="poForm">
          <input type="hidden" id="po_id">
          <input type="hidden" id="purchase_requisition_id">
          <input type="hidden" id="rfq_id">
          <input type="hidden" id="supplier_quotation_id">

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div id="po_status_badge"></div>
            <div class="text-muted small text-end">
              Subtotal: <b id="subTotalLbl">0.00</b> |
              Discount: <b id="discountTotalLbl">0.00</b> |
              Tax: <b id="taxTotalLbl">0.00</b> |
              Shipping: <b id="shippingTotalLbl">0.00</b> |
              Other: <b id="otherChargesTotalLbl">0.00</b> |
              Total: <b id="grandTotalLbl">0.00</b>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Supplier Quotation</label>
              <div class="input-group">
                <select class="form-control" id="quotation_id"></select>
                <button type="button" class="btn btn-outline-primary" id="loadQuotationBtn">
                  <i class="fas fa-download"></i> Load
                </button>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Supplier <span class="text-danger">*</span></label>
              <select class="form-control" id="supplier_id"></select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Supplier Contact</label>
              <select class="form-control" id="supplier_contact_id"></select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Supplier PO Ref</label>
              <input type="text" class="form-control" id="supplier_po_ref" placeholder="Supplier reference">
            </div>

            <div class="col-md-3">
              <label class="form-label">PO Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="po_date">
            </div>

            <div class="col-md-3">
              <label class="form-label">Expected Delivery</label>
              <input type="date" class="form-control" id="expected_delivery_date">
            </div>

            <div class="col-md-3">
              <label class="form-label">Currency</label>
              <input type="text" class="form-control" id="currency_code" maxlength="3" placeholder="NGN">
            </div>

            <div class="col-md-2">
              <label class="form-label">FX Rate</label>
              <input type="number" step="0.000001" class="form-control" id="fx_rate" placeholder="1.000000">
            </div>

            <div class="col-md-3">
              <label class="form-label">Delivery Location</label>
              <select class="form-control" id="delivery_location_id"></select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Delivery Store</label>
              <select class="form-control" id="delivery_store_id"></select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Bill To Location</label>
              <select class="form-control" id="bill_to_location_id"></select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Payment Terms</label>
              <input type="text" class="form-control" id="payment_terms" placeholder="Net 30, etc.">
            </div>

            <div class="col-md-3">
              <label class="form-label">Incoterms</label>
              <input type="text" class="form-control" id="incoterms" placeholder="FOB, CIF, EXW...">
            </div>

            <div class="col-md-6">
              <label class="form-label">Reference</label>
              <input type="text" class="form-control" id="reference" placeholder="Internal reference">
            </div>

            <div class="col-md-6">
              <label class="form-label">Notes</label>
              <textarea class="form-control" id="notes" rows="2" placeholder="Supplier-facing notes"></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">Internal Notes</label>
              <textarea class="form-control" id="internal_notes" rows="2" placeholder="Internal procurement notes"></textarea>
            </div>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">PO Lines</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
              <i class="fas fa-plus"></i> Add Line
            </button>
          </div>

          <div class="po-lines-wrap">
            <table class="table table-bordered align-middle po-lines-table mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="col-product">Product</th>
                  <th class="col-description">Description</th>
                  <th class="col-unit">Unit</th>
                  <th class="col-location">Location</th>
                  <th class="col-store">Store</th>
                  <th class="col-qty text-end">Qty</th>
                  <th class="col-unitprice text-end">Unit Price</th>
                  <th class="col-discpct text-end">Disc %</th>
                  <th class="col-discamt text-end">Disc Amt</th>
                  <th class="col-taxcode">Tax Code</th>
                  <th class="col-taxrate text-end">Tax %</th>
                  <th class="col-taxamt text-end">Tax Amt</th>
                  <th class="col-shipping text-end">Shipping</th>
                  <th class="col-othercharges text-end">Other Charges</th>
                  <th class="col-total text-end">Line Total</th>
                  <th class="col-leadtime text-end">Lead Time</th>
                  <th class="col-expdelivery">Expected Delivery</th>
                  <th class="col-remarks">Remarks</th>
                  <th class="col-action text-center">X</th>
                </tr>
              </thead>
              <tbody id="linesTbody"></tbody>
            </table>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="savePoBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>