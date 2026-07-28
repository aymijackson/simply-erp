<div class="modal fade" id="milestoneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="milestoneModalTitle">New Milestone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="milestoneForm">
                    <input type="hidden" id="milestone_id" name="id">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div id="milestone_status_badge"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="text-muted small">Milestone Code</label>
                            <input type="text" class="form-control" id="milestone_code" name="milestone_code" placeholder="Auto if blank">
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">Milestone Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="milestone_name" name="milestone_name" required>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Project <span class="text-danger">*</span></label>
                            <select class="form-control" id="project_id" name="project_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">Owner</label>
                            <select class="form-control" id="owner_id" name="owner_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Priority</label>
                            <select class="form-control" id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Target Date</label>
                            <input type="date" class="form-control" id="target_date" name="target_date">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Progress %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="progress_percent" name="progress_percent" value="0">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Weight %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="weight_percent" name="weight_percent" value="0">
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Sort Order</label>
                            <input type="number" min="0" class="form-control" id="sort_order" name="sort_order" value="0">
                        </div>

                        <div class="col-md-12">
                            <label class="text-muted small">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
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
                <button type="button" class="btn btn-primary" id="saveMilestoneBtn">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>