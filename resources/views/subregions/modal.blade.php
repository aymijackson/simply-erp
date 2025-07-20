<!-- resources/views/erp/subregions/modal.blade.php -->

<div class="modal fade" id="subregionModal" tabindex="-1" role="dialog" aria-labelledby="subregionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="subregionForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subregionModalLabel">Add Subregion</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="subregion_id">
                <div class="form-group">
                    <label for="subregion_name">Name</label>
                    <input type="text" class="form-control" id="subregion_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="region_id">Region</label>
                    <select name="region_id" id="region_id" class="form-control" required>
                        <option value="">Select Region</option>
                        @foreach(\App\Models\Region::all() as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
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
