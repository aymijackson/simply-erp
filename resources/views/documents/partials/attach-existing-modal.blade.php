<div class="modal fade" id="attachExistingDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.document-links.store') }}" method="POST" class="modal-content">
            @csrf

            <input type="hidden" name="linkable_type" value="{{ get_class($model) }}">
            <input type="hidden" name="linkable_id" value="{{ $model->id }}">

            <div class="modal-header">
                <h5 class="modal-title">Attach Existing Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Document</label>
                    <select name="document_id" class="form-select" required>
                        <option value="">-- Select Document --</option>
                        @foreach(\Modules\Document\Models\Document::where('is_latest', 1)->orderByDesc('id')->limit(300)->get() as $doc)
                            <option value="{{ $doc->id }}">
                                {{ $doc->document_no }} - {{ $doc->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Relation Type</label>
                    <input type="text" name="relation_type" class="form-control" placeholder="e.g. invoice_copy, signed_copy, id_proof, attachment">
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Optional note">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" data-bs-dismiss="modal" class="btn btn-light">Close</button>
                <button type="submit" class="btn btn-primary">Attach</button>
            </div>
        </form>
    </div>
</div>