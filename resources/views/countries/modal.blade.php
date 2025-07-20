<!-- Country Modal -->
<div class="modal fade" id="countryModal" tabindex="-1" role="dialog" aria-labelledby="countryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="countryForm">
            <input type="hidden" name="id" id="country_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="countryModalLabel">Add Country</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="country_name">Name</label>
                        <input type="text" class="form-control" name="name" id="country_name" required>
                    </div>

                    <div class="form-group">
                        <label for="region_id">Region</label>
                        <select class="form-control" name="region_id" id="region_id" required>
                            <option value="">Select Region</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subregion_id">Subregion</label>
                        <select class="form-control" name="subregion_id" id="subregion_id" required>
                            <option value="">Select Subregion</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
