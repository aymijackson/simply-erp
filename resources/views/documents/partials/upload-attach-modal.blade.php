<div class="modal fade" id="uploadAndAttachDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form id="uploadAttachDocumentForm"
              action="{{ route('admin.documents.store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="modal-content">
            @csrf

            <input type="hidden" name="linkable_type" value="{{ get_class($model) }}">
            <input type="hidden" name="linkable_id" value="{{ $model->id }}">
            <input type="hidden" name="auto_attach" value="1">

            <div class="modal-header">
                <h5 class="modal-title">Upload and Attach Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="uploadAttachDocumentErrors" class="alert alert-danger d-none"></div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Type</label>
                        <select name="type_id" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="draft">Draft</option>
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                            <option value="obsolete">Obsolete</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Confidentiality</label>
                        <select name="confidentiality_level" class="form-select" required>
                            <option value="public">Public</option>
                            <option value="internal" selected>Internal</option>
                            <option value="restricted">Restricted</option>
                            <option value="confidential">Confidential</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Relation Type</label>
                        <input type="text" name="relation_type" class="form-control" placeholder="e.g. attachment, signed_copy">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Effective Date</label>
                        <input type="date" name="effective_date" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional remark for link">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" data-bs-dismiss="modal" class="btn btn-light">Close</button>
                <button type="submit" class="btn btn-primary" id="uploadAttachDocumentSubmitBtn">
                    Upload & Attach
                </button>
            </div>
        </form>
    </div>
</div>