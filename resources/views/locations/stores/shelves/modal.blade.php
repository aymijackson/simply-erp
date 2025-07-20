<div class="modal fade" id="shelfModal" tabindex="-1" role="dialog" aria-labelledby="shelfModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="shelfForm" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="shelfModalLabel">Add Shelf</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="shelf_id">
        <div class="form-group">
          <label for="store_id">Store</label>
          <select name="store_id" id="store_id" class="form-control" required>
            <option value="">Select Store</option>
            @foreach($stores as $store)
              <option value="{{ $store->id }}">{{ $store->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label for="code">Code</label>
          <input type="text" name="code" id="code" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="capacity">Capacity</label>
          <input type="number" name="capacity" id="capacity" class="form-control">
        </div>
        <div class="form-group">
          <label for="description">Description</label>
          <textarea name="description" id="description" class="form-control"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>