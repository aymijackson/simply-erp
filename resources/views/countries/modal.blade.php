<!-- Country Modal -->
<div class="modal fade" id="countryModal" tabindex="-1" aria-labelledby="countryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="countryForm" autocomplete="off">
            <input type="hidden" name="id" id="country_id">

            <div class="modal-content shadow-sm">

                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title" id="countryModalLabel">Add Country</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- Country Name -->
                    <div class="form-floating mb-3">
                        <input 
                            type="text" 
                            class="form-control" 
                            id="country_name" 
                            name="name" 
                            placeholder="Country Name"
                            required
                        >
                        <label for="country_name">Country Name</label>
                    </div>

                    <!-- Region -->
                    <div class="form-floating mb-3">
                        <select 
                            class="form-select" 
                            id="region_id" 
                            name="region_id" 
                            required
                        >
                            <option value="">Select Region</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                        <label for="region_id">Region</label>
                    </div>

                    <!-- Subregion -->
                    <div class="form-floating mb-3">
                        <select 
                            class="form-select" 
                            id="subregion_id" 
                            name="subregion_id" 
                            required
                        >
                            <option value="">Select Subregion</option>
                        </select>
                        <label for="subregion_id">Subregion</label>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>

            </div>
        </form>
    </div>
</div>