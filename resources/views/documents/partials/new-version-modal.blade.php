<div class="modal fade" id="newVersionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form action="{{ route('admin.documents.versions.store', $document->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">Upload New Version</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">
                    You are creating a new version of:
                    <strong>{{ $document->document_no }}</strong> — {{ $document->title }}
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ $document->title }}" required>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach(\Modules\Document\Models\DocumentCategory::where('is_active', 1)->orderBy('name')->get() as $category)
                                <option value="{{ $category->id }}" @selected($document->category_id == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Type</label>
                        <select name="type_id" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach(\Modules\Document\Models\DocumentType::where('is_active', 1)->orderBy('name')->get() as $type)
                                <option value="{{ $type->id }}" @selected($document->type_id == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="draft" @selected($document->status === 'draft')>Draft</option>
                            <option value="active" @selected($document->status === 'active')>Active</option>
                            <option value="archived" @selected($document->status === 'archived')>Archived</option>
                            <option value="obsolete" @selected($document->status === 'obsolete')>Obsolete</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Confidentiality</label>
                        <select name="confidentiality_level" class="form-select" required>
                            <option value="public" @selected($document->confidentiality_level === 'public')>Public</option>
                            <option value="internal" @selected($document->confidentiality_level === 'internal')>Internal</option>
                            <option value="restricted" @selected($document->confidentiality_level === 'restricted')>Restricted</option>
                            <option value="confidential" @selected($document->confidentiality_level === 'confidential')>Confidential</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Effective Date</label>
                        <input type="date" name="effective_date" class="form-control"
                               value="{{ $document->effective_date ? $document->effective_date->format('Y-m-d') : '' }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control"
                               value="{{ $document->expiry_date ? $document->expiry_date->format('Y-m-d') : '' }}">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $document->description }}</textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2">{{ $document->notes }}</textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">New File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" data-bs-dismiss="modal" class="btn btn-light">Close</button>
                <button type="submit" class="btn btn-primary">Create New Version</button>
            </div>
        </form>
    </div>
</div>