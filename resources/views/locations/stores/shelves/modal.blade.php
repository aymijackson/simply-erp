<div class="modal fade" id="shelfModal" tabindex="-1" aria-labelledby="shelfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="shelfForm" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title" id="shelfModalLabel">Add Shelf</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id" id="shelf_id">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="store_id" class="form-label">Store</label>
                        <select name="store_id" id="store_id" class="form-select" required>
                            <option value="">Select Store</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">Shelf Code</label>
                        <input type="text" name="code" id="code" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="capacity" class="form-label">Capacity</label>
                        <input type="number" step="0.01" min="0" name="capacity" id="capacity" class="form-control">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="3" class="form-control"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveShelfBtn">Save Shelf</button>
            </div>
        </form>
    </div>
</div>