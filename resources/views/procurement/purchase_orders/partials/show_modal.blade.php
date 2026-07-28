<div class="modal fade" id="poShowModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Purchase Order Details</h5>
          <small class="text-muted">View purchase order breakdown</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <small class="text-muted d-block">PO No</small>
            <div class="fw-bold" id="vw_po_no">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Supplier PO Ref</small>
            <div class="fw-bold" id="vw_supplier_po_ref">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">PO Date</small>
            <div class="fw-bold" id="vw_po_date">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Expected Delivery</small>
            <div class="fw-bold" id="vw_expected_delivery_date">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Status</small>
            <div class="fw-bold" id="vw_status">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Supplier</small>
            <div class="fw-bold" id="vw_supplier">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Contact Name</small>
            <div class="fw-bold" id="vw_contact_name">—</div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block">Contact Email / Phone</small>
            <div class="fw-bold">
              <span id="vw_contact_email">—</span> /
              <span id="vw_contact_phone">—</span>
            </div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Purchase Requisition</small>
            <div class="fw-bold" id="vw_purchase_requisition_no">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">RFQ</small>
            <div class="fw-bold" id="vw_rfq_no">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Supplier Quotation</small>
            <div class="fw-bold" id="vw_quotation_no">—</div>
          </div>

          <div class="col-md-2">
            <small class="text-muted d-block">Currency</small>
            <div class="fw-bold" id="vw_currency_code">—</div>
          </div>

          <div class="col-md-2">
            <small class="text-muted d-block">FX Rate</small>
            <div class="fw-bold" id="vw_fx_rate">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Payment Terms</small>
            <div class="fw-bold" id="vw_payment_terms">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Incoterms</small>
            <div class="fw-bold" id="vw_incoterms">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Reference</small>
            <div class="fw-bold" id="vw_reference">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Delivery Location</small>
            <div class="fw-bold" id="vw_delivery_location">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Delivery Store</small>
            <div class="fw-bold" id="vw_delivery_store">—</div>
          </div>

          <div class="col-md-4">
            <small class="text-muted d-block">Bill To Location</small>
            <div class="fw-bold" id="vw_bill_to_location">—</div>
          </div>

          <div class="col-md-6">
            <small class="text-muted d-block">Notes</small>
            <div class="fw-bold" id="vw_notes">—</div>
          </div>

          <div class="col-md-6">
            <small class="text-muted d-block">Internal Notes</small>
            <div class="fw-bold" id="vw_internal_notes">—</div>
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
                <th class="text-end">Unit Price</th>
                <th class="text-end">Disc %</th>
                <th class="text-end">Disc Amt</th>
                <th>Tax Code</th>
                <th class="text-end">Tax %</th>
                <th class="text-end">Tax Amt</th>
                <th class="text-end">Shipping</th>
                <th class="text-end">Other</th>
                <th class="text-end">Line Total</th>
                <th class="text-end">Received Qty</th>
                <th class="text-end">Billed Qty</th>
              </tr>
            </thead>
            <tbody id="vw_lines"></tbody>
            <tfoot>
              <tr>
                <th colspan="13" class="text-end">Subtotal</th>
                <th colspan="2" class="text-end" id="vw_subtotal">0.00</th>
              </tr>
              <tr>
                <th colspan="13" class="text-end">Discount Total</th>
                <th colspan="2" class="text-end" id="vw_discount_total">0.00</th>
              </tr>
              <tr>
                <th colspan="13" class="text-end">Tax Total</th>
                <th colspan="2" class="text-end" id="vw_tax_total">0.00</th>
              </tr>
              <tr>
                <th colspan="13" class="text-end">Shipping Total</th>
                <th colspan="2" class="text-end" id="vw_shipping_total">0.00</th>
              </tr>
              <tr>
                <th colspan="13" class="text-end">Other Charges Total</th>
                <th colspan="2" class="text-end" id="vw_other_charges_total">0.00</th>
              </tr>
              <tr>
                <th colspan="13" class="text-end">Grand Total</th>
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