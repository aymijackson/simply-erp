<div class="modal fade" id="createDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="documentUploadForm" enctype="multipart/form-data">
                @csrf

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="create_category_id" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type_id" id="create_type_id" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="active" selected>Active</option>
                                <option value="archived">Archived</option>
                                <option value="obsolete">Obsolete</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confidentiality *</label>
                            <select name="confidentiality_level" class="form-select" required>
                                <option value="public">Public</option>
                                <option value="internal" selected>Internal</option>
                                <option value="restricted">Restricted</option>
                                <option value="confidential">Confidential</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective Date</label>
                            <input type="date" name="effective_date" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File *</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>

            </form>

        </div>
    </div>
</div>