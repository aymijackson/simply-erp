<div class="modal fade" id="costModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="costModalTitle">New Project Cost</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="costForm">
                    <input type="hidden" id="cost_id" name="id">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div id="cost_status_badge"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="text-muted small">Project <span class="text-danger">*</span></label>
                            <select class="form-control" id="project_id" name="project_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Task</label>
                            <select class="form-control" id="task_id" name="task_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Milestone</label>
                            <select class="form-control" id="milestone_id" name="milestone_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Cost Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="cost_date" name="cost_date">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Cost Category</label>
                            <select class="form-control" id="cost_category" name="cost_category">
                                <option value="materials">Materials</option>
                                <option value="labour">Labour</option>
                                <option value="logistics">Logistics</option>
                                <option value="subcontract">Subcontract</option>
                                <option value="overhead">Overhead</option>
                                <option value="expense">Expense</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Source Type</label>
                            <select class="form-control" id="source_type" name="source_type">
                                <option value="manual">Manual</option>
                                <option value="purchase_order">Purchase Order</option>
                                <option value="goods_receipt">Goods Receipt</option>
                                <option value="supplier_bill">Supplier Bill</option>
                                <option value="expense">Expense</option>
                                <option value="journal_entry">Journal Entry</option>
                                <option value="timesheet">Timesheet</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Source ID</label>
                            <input type="number" class="form-control" id="source_id" name="source_id" min="0">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Reference No</label>
                            <input type="text" class="form-control" id="reference_no" name="reference_no">
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">Description</label>
                            <input type="text" class="form-control" id="description" name="description">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Quantity</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="quantity" name="quantity" value="1">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Unit Cost</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="unit_cost" name="unit_cost" value="0">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" value="0">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Currency</label>
                            <input type="text" class="form-control" id="currency_code" name="currency_code" value="NGN">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="posted" selected>Posted</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="text-muted small">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveCostBtn">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>