<div class="modal fade" id="workflowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="workflowModalTitle">New Workflow</h5>
                    <small class="text-muted">Define the workflow trigger and ordered execution steps</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="workflowForm">
                    <input type="hidden" id="workflow_id" name="id">

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="text-muted small">Workflow Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="workflow_name" name="name" required>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Module <span class="text-danger">*</span></label>
                            <select class="form-control" id="workflow_module" name="module" required>
                                <option value="">Select</option>
                                <option value="finance">Finance</option>
                                <option value="procurement">Procurement</option>
                                <option value="inventory">Inventory</option>
                                <option value="projects">Projects</option>
                                <option value="crm">CRM</option>
                                <option value="support">Support</option>
                                <option value="production">Production</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small">Trigger Event <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="workflow_trigger_event" name="trigger_event" placeholder="low_stock, requisition_created..." required>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="workflow_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="workflow_is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Workflow Steps</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addStepBtn">
                            <i class="fas fa-plus me-1"></i> Add Step
                        </button>
                    </div>

                    <div id="stepsContainer"></div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" type="button" id="saveWorkflowBtn">
                    <i class="fas fa-save me-1"></i> Save Workflow
                </button>
            </div>
        </div>
    </div>
</div>