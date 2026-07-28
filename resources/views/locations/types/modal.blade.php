<div class="modal fade" id="typeModal" tabindex="-1" role="dialog" aria-labelledby="typeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form id="typeForm" class="modal-content">

            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title" id="typeModalLabel">Add Location Type</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <input type="hidden" name="id" id="type_id">

                <div class="form-group">
                    <label for="type_name">Name</label>
                    <input type="text" class="form-control" name="name" id="type_name" required>
                </div>

                <div class="form-group">
                    <label for="type_description">Description</label>
                    <textarea class="form-control" name="description" id="type_description"></textarea>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Save
                </button>
            </div>

        </form>
    </div>
</div>