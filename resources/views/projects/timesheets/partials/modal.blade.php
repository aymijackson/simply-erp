<div class="modal fade" id="timesheetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timesheetModalTitle">New Timesheet Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="timesheetForm">
                    <input type="hidden" id="timesheet_id" name="id">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div id="timesheet_status_badge"></div>
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
                            <label class="text-muted small">Employee <span class="text-danger">*</span></label>
                            <select class="form-control" id="employee_id" name="employee_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Entry Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="entry_date" name="entry_date">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Hours</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="hours" name="hours" value="0">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Hourly Rate</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="hourly_rate" name="hourly_rate" value="0">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Cost Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="cost_amount" name="cost_amount" value="0" readonly>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Billable?</label>
                            <select class="form-control" id="is_billable" name="is_billable">
                                <option value="0" selected>No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Billable Hours</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="billable_hours" name="billable_hours" value="0">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Billing Rate</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="billing_rate" name="billing_rate" value="0">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Billable Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="billable_amount" name="billable_amount" value="0" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="submitted">Submitted</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Source Type</label>
                            <select class="form-control" id="source_type" name="source_type">
                                <option value="manual">Manual</option>
                                <option value="timer">Timer</option>
                                <option value="import">Import</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Source ID</label>
                            <input type="number" min="0" class="form-control" id="source_id" name="source_id">
                        </div>

                        <div class="col-md-12">
                            <label class="text-muted small">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="text-muted small">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="text-muted small">Rejection Reason</label>
                            <textarea class="form-control" id="rejection_reason" rows="2" readonly></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveTimesheetBtn">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>