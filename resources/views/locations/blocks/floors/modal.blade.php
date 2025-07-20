<div class="modal fade" id="floorModal" tabindex="-1" role="dialog" aria-labelledby="floorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="floorForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="floorModalLabel">Add Location</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="floor_id">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="location_block_id">Block</label>
                    <select name="location_block_id" id="block_id" class="form-control" required>
                        <option value="">Select Block</option>
                        @foreach($location_blocks as $block)
                            <option value="{{ $block->id }}">{{ $block->name }} - (location) {{ $block->location->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
