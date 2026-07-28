<!-- State Modal -->
<div class="modal fade" id="stateModal" tabindex="-1" aria-labelledby="stateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="stateForm" autocomplete="off" class="modal-content">

            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title" id="stateModalLabel">Add State</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" name="id" id="state_id">

                <!-- State Name -->
                <div class="form-floating mb-3">
                    <input 
                        type="text" 
                        class="form-control" 
                        id="state_name" 
                        name="name" 
                        placeholder="State Name"
                        required
                    >
                    <label for="state_name">State Name</label>
                </div>

                <!-- Country -->
                <div class="form-floating mb-3">
                    <select 
                        name="country_id" 
                        id="country_id" 
                        class="form-select" 
                        required
                    >
                        <option value="">Select Country</option>
                        @foreach(\App\Models\Country::all() as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                    <label for="country_id">Country</label>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Save</button>
            </div>

        </form>
    </div>
</div>