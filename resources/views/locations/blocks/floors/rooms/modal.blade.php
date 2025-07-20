<div class="modal fade" id="roomModal" tabindex="-1" role="dialog" aria-labelledby="roomModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="roomForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="roomModalLabel">Add Room</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="room_id">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="location_block_floor_id">Floor</label>
                    <select name="location_block_floor_id" id="location_block_floor_id" class="form-control" required>
                        <option value="">Select Floor</option>
                        @foreach($location_block_floors as $floor)
                            <option value="{{ $floor->id }}">{{ $floor->name }} - (Block) {{ $floor->block->name }} - (location) {{ $floor->block->location->name }}</option>
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
