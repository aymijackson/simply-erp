<div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form id="locationForm" class="modal-content">
            @csrf

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="locationModalLabel">Add Location</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id" id="location_id">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="company_id">Company</label>
                            <select name="company_id" id="company_id" class="form-control" required>
                                <option value="">Select Company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="location_type_id">Location Type</label>
                            <select name="location_type_id" id="location_type_id" class="form-control" required>
                                <option value="">Select Type</option>
                                @foreach($locationTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="country_id">Country</label>
                            <select id="country_id" class="form-control"></select>
                        </div>

                        <div class="form-group">
                            <label for="state_id">State</label>
                            <select id="state_id" class="form-control"></select>
                        </div>

                        <div class="form-group">
                            <label for="city_id">City</label>
                            <select name="city_id" id="city_id" class="form-control" required></select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="text" name="longitude" id="longitude" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="text" name="latitude" id="latitude" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Pick Location on Map</h5>
                <div id="locationMap"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
