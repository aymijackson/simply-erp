<div class="modal fade" id="notificationViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Notification Details</h5>
                    <small class="text-muted">Full message and metadata</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="text-muted small">Title</label>
                        <div class="fw-bold" id="view_title">—</div>
                    </div>

                    <div class="col-md-2">
                        <label class="text-muted small">Type</label>
                        <div id="view_type">—</div>
                    </div>

                    <div class="col-md-2">
                        <label class="text-muted small">Status</label>
                        <div id="view_status">—</div>
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted small">Reference</label>
                        <div id="view_reference">—</div>
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted small">Created At</label>
                        <div id="view_created_at">—</div>
                    </div>

                    <div class="col-md-12">
                        <label class="text-muted small">Message</label>
                        <div class="border rounded p-3 bg-light" style="white-space:pre-wrap;" id="view_message">—</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>