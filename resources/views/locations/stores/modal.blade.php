<div class="modal fade" id="storeModal" tabindex="-1" role="dialog" aria-labelledby="storeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="storeForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="storeModalLabel">Add Store</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="store_id">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="location_id">Location <span class="text-danger">*</span></label>
                    <select name="location_id" id="location_id" class="form-control" required>
                        <option value="">Select Location</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="location_block_floor_room_id">Room (optional)</label>
                    <select name="location_block_floor_room_id" id="location_block_floor_room_id" class="form-control">
                        <option value="">Select Room</option>
                        <!-- Options will be dynamically loaded -->
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
