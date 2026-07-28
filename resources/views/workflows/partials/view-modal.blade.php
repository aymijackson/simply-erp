<div class="modal fade" id="workflowViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Workflow Details</h5>
                    <small class="text-muted">Trigger, status and ordered steps</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="text-muted small">Name</label>
                        <div class="fw-bold" id="view_workflow_name">—</div>
                    </div>

                    <div class="col-md-2">
                        <label class="text-muted small">Module</label>
                        <div id="view_workflow_module">—</div>
                    </div>

                    <div class="col-md-3">
                        <label class="text-muted small">Trigger Event</label>
                        <div id="view_workflow_trigger_event">—</div>
                    </div>

                    <div class="col-md-1">
                        <label class="text-muted small">Status</label>
                        <div id="view_workflow_status">—</div>
                    </div>

                    <div class="col-md-2">
                        <label class="text-muted small">Created</label>
                        <div id="view_workflow_created_at">—</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:80px;">Order</th>
                                <th style="width:150px;">Action Type</th>
                                <th style="width:180px;">Action Target</th>
                                <th style="width:120px;">Delay</th>
                                <th>Action Value</th>
                            </tr>
                        </thead>
                        <tbody id="view_workflow_steps"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>