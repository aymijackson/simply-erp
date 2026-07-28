<div class="modal fade" id="projectInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectInvoiceModalTitle">New Project Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="projectInvoiceForm">
                    <input type="hidden" id="invoice_id" name="id">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div id="invoice_status_badge"></div>
                        <div class="text-muted small">
                            Subtotal: <b id="invoice_subtotal_lbl">0.00</b> |
                            Tax: <b id="invoice_tax_lbl">0.00</b> |
                            Total: <b id="invoice_total_lbl">0.00</b>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted small">Project <span class="text-danger">*</span></label>
                            <select class="form-control" id="project_id" name="project_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Invoice No</label>
                            <input type="text" class="form-control" id="invoice_no" name="invoice_no">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="invoice_date" name="invoice_date">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Method</label>
                            <select class="form-control" id="billing_method" name="billing_method">
                                <option value="manual">Manual</option>
                                <option value="fixed_fee">Fixed Fee</option>
                                <option value="milestone">Milestone</option>
                                <option value="timesheet">Timesheet</option>
                                <option value="mixed">Mixed</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Currency</label>
                            <input type="text" class="form-control" id="currency_code" name="currency_code" value="NGN">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">FX Rate</label>
                            <input type="number" step="0.000001" min="0.000001" class="form-control" id="fx_rate" name="fx_rate" value="1.000000">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference">
                        </div>

                        <div class="col-md-5">
                            <label class="text-muted small">Memo</label>
                            <input type="text" class="form-control" id="memo" name="memo">
                        </div>

                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <h6 class="mb-0">Invoice Lines</h6>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addProjectInvoiceLineBtn">
                                    <i class="fas fa-plus"></i> Add Line
                                </button>
                            </div>

                            <div class="table-responsive mt-2">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width:12%">Source Type</th>
                                            <th style="width:18%">Source</th>
                                            <th>Description</th>
                                            <th style="width:9%" class="text-end">Qty</th>
                                            <th style="width:11%" class="text-end">Unit Price</th>
                                            <th style="width:9%" class="text-end">Tax %</th>
                                            <th style="width:11%" class="text-end">Line Total</th>
                                            <th style="width:6%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="project_invoice_lines_tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveProjectInvoiceBtn">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>