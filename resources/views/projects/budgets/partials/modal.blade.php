<div class="modal fade" id="budgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="budgetModalTitle">New Project Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="budgetForm">
                    <input type="hidden" id="budget_id" name="id">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div id="budget_status_badge"></div>
                        <div class="text-muted small">
                            Total Budget: <b id="budget_total_lbl">0.00</b>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted small">Project <span class="text-danger">*</span></label>
                            <select class="form-control" id="project_id" name="project_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Budget Code</label>
                            <input type="text" class="form-control" id="budget_code" name="budget_code">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Budget Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="budget_name" name="budget_name">
                        </div>

                        <div class="col-md-1">
                            <label class="text-muted small">Version</label>
                            <input type="number" min="1" class="form-control" id="version_no" name="version_no" value="1">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Currency</label>
                            <input type="text" class="form-control" id="currency_code" name="currency_code" value="NGN">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Start Date</label>
                            <input type="date" class="form-control" id="budget_start_date" name="budget_start_date">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">End Date</label>
                            <input type="date" class="form-control" id="budget_end_date" name="budget_end_date">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="approved">Approved</option>
                                <option value="revised">Revised</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>

                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <h6 class="mb-0">Budget Lines</h6>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addBudgetLineBtn">
                                    <i class="fas fa-plus"></i> Add Line
                                </button>
                            </div>

                            <div class="table-responsive mt-2">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width:18%">Task</th>
                                            <th style="width:18%">Milestone</th>
                                            <th style="width:14%">Category</th>
                                            <th>Description</th>
                                            <th style="width:9%" class="text-end">Qty</th>
                                            <th style="width:11%" class="text-end">Unit Cost</th>
                                            <th style="width:11%" class="text-end">Budget Amount</th>
                                            <th style="width:7%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="budget_lines_tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveBudgetBtn">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>