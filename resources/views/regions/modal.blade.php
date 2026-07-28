<!-- resources/views/erp/regions/modal.blade.php -->
<div class="modal fade" id="regionModal" tabindex="-1" aria-labelledby="regionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-sm">

            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title" id="regionModalLabel">Add Region</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="regionForm" autocomplete="off">
                <div class="modal-body">

                    <input type="hidden" name="id" id="region_id">

                    <div class="form-floating mb-3">
                        <input 
                            type="text" 
                            class="form-control" 
                            id="region_name" 
                            name="name" 
                            placeholder="Region Name"
                            required
                        >
                        <label for="region_name">Region Name</label>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                        Save
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>