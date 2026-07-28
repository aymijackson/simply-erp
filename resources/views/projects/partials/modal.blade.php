<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalTitle">New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="projectForm">
                    <input type="hidden" id="project_id" name="id">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div id="project_status_badge"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="text-muted small">Project Code</label>
                            <input type="text" class="form-control" id="project_code" name="project_code" placeholder="Auto if blank">
                        </div>

                        <div class="col-md-5">
                            <label class="text-muted small">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project_name" name="project_name" required>
                        </div>

                        <div class="col-md-2">
                            <label class="text-muted small">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="planned">Planned</option>
                                <option value="active">Active</option>
                                <option value="on_hold">On Hold</option>
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

                        <div class="col-md-6">
                            <label class="text-muted small">Client</label>
                            <select class="form-control" id="client_id" name="client_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">Project Manager</label>
                            <select class="form-control" id="project_manager_id" name="project_manager_id" style="width:100%"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Budget</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="budget" name="budget" value="0">
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Actual Cost</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="actual_cost" name="actual_cost" value="0">
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
                <button type="button" class="btn btn-primary" id="saveProjectBtn">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>