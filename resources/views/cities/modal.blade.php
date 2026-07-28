<!-- CITY MODAL (Bootstrap 4 Compatible) -->
    <div class="modal fade" id="cityModal" tabindex="-1" role="dialog" aria-labelledby="cityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <form id="cityForm" class="modal-content" autocomplete="off">

                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title" id="cityModalLabel">Add City</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="id" id="city_id">

                    <div class="form-group">
                        <label for="city_name">City Name</label>
                        <input type="text" class="form-control" id="city_name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="country_id">Country</label>
                        <select name="country_id" id="country_id" class="form-control" required>
                            <option value="">Select Country</option>
                            @foreach(\App\Models\Country::orderBy('name')->get() as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="state_id">State</label>
                        <select name="state_id" id="state_id" class="form-control" required>
                            <option value="">Select State</option>
                        </select>
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

</div>