<div class="modal fade" id="stateModal" tabindex="-1" role="dialog" aria-labelledby="stateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="stateForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stateModalLabel">Add State</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="state_id">
                    <div class="form-group">
                        <label for="state_name">Name</label>
                        <input type="text" class="form-control" id="state_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="country_id">Country</label>
                        <select name="country_id" id="country_id" class="form-control" required>
                            <option value="">Select Country</option>
                            @foreach(\App\Models\Country::all() as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
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
</div>