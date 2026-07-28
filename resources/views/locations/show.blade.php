@extends('layouts.master')

@section('title', 'Location Details')

@section('content')
<div class="container-fluid">

    @php
        $locationTypeName = optional($location->locationType)->name ?? optional($location->type)->name ?? 'Location';
        $city = optional($location->city);
        $state = optional($city->state);
        $country = optional($state->country);
        $company = optional($location->company);
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h1 class="h3 text-primary mb-1 d-flex align-items-center flex-wrap gap-2">
                <span class="me-2">{{ $location->name }}</span>
                <span class="badge bg-light text-muted border px-3 py-2">{{ $locationTypeName }}</span>
            </h1>
            <p class="mb-0 text-muted">
                <i class="fas fa-map-marker-alt me-1"></i>
                {{ $location->address ?: 'No address provided' }}
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#editLocationModal">
                <i class="fas fa-edit me-1"></i> Edit Location
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">Company</div>
                    <div class="h6 mb-0 text-dark">{{ $company->name ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs fw-bold text-info text-uppercase mb-1">Country</div>
                    <div class="h6 mb-0 text-dark">{{ $country->name ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs fw-bold text-info text-uppercase mb-1">State</div>
                    <div class="h6 mb-0 text-dark">{{ $state->name ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs fw-bold text-info text-uppercase mb-1">City</div>
                    <div class="h6 mb-0 text-dark">{{ $city->name ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Latitude</div>
                    <div class="h6 mb-0 text-dark">{{ $location->latitude ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Longitude</div>
                    <div class="h6 mb-0 text-dark">{{ $location->longitude ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary">Location Overview</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-bold d-block mb-1">Description</label>
                        <div class="border rounded px-3 py-3 bg-light" style="min-height: 72px;">
                            {{ $location->description ?: 'No description provided' }}
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="text-muted small fw-bold d-block mb-1">Address</label>
                        <div class="border rounded px-3 py-3 bg-light">
                            {{ $location->address ?: 'No address provided' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary">Satellite Location Preview</h6>
                </div>
                <div class="card-body">
                    <div id="locationSatelliteMap"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pb-0">
            <ul class="nav nav-tabs card-header-tabs" id="locationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="blocks-tab" data-bs-toggle="tab" data-bs-target="#blocksPane" type="button" role="tab">
                        Blocks
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="floors-tab" data-bs-toggle="tab" data-bs-target="#floorsPane" type="button" role="tab">
                        Floors
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#roomsPane" type="button" role="tab">
                        Rooms
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="stores-tab" data-bs-toggle="tab" data-bs-target="#storesPane" type="button" role="tab">
                        Stores
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="shelves-tab" data-bs-toggle="tab" data-bs-target="#shelvesPane" type="button" role="tab">
                        Shelves
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content pt-2">
                <div class="tab-pane fade show active" id="blocksPane" role="tabpanel">
                    <table class="table table-hover table-striped table-bordered w-100" id="blocksTable">
                        <thead>
                            <tr>
                                <th>Block Name</th>
                                <th>Created At</th>
                                <th width="110">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="tab-pane fade" id="floorsPane" role="tabpanel">
                    <table class="table table-hover table-striped table-bordered w-100" id="floorsTable">
                        <thead>
                            <tr>
                                <th>Floor Name</th>
                                <th>Block</th>
                                <th width="110">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="tab-pane fade" id="roomsPane" role="tabpanel">
                    <table class="table table-hover table-striped table-bordered w-100" id="roomsTable">
                        <thead>
                            <tr>
                                <th>Room Name</th>
                                <th>Floor</th>
                                <th width="110">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="tab-pane fade" id="storesPane" role="tabpanel">
                    <table class="table table-hover table-striped table-bordered w-100" id="storesTable">
                        <thead>
                            <tr>
                                <th>Store Name</th>
                                <th>Room</th>
                                <th width="110">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="tab-pane fade" id="shelvesPane" role="tabpanel">
                    <table class="table table-hover table-striped table-bordered w-100" id="shelvesTable">
                        <thead>
                            <tr>
                                <th>Shelf Code</th>
                                <th>Store</th>
                                <th width="110">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Location Edit Modal --}}
    <div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form id="editLocationForm" class="modal-content">
                @csrf
                @method('PUT')

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editLocationModalLabel">Edit Location</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id" value="{{ $location->id }}">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" value="{{ $location->name }}" required>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_location_type_id" class="form-label">Location Type</label>
                            <select name="location_type_id" id="edit_location_type_id" class="form-control" required>
                                <option value="">Select Type</option>
                                @foreach($locationTypes as $type)
                                    <option value="{{ $type->id }}"
                                        {{ (int)($location->location_type_id ?? optional($location->locationType)->id ?? 0) === (int)$type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_company_id" class="form-label">Company</label>
                            <select name="company_id" id="edit_company_id" class="form-control" required></select>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_country_id" class="form-label">Country</label>
                            <select id="edit_country_id" class="form-control"></select>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_state_id" class="form-label">State</label>
                            <select id="edit_state_id" class="form-control"></select>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_city_id" class="form-label">City <span class="text-danger">*</span></label>
                            <select name="city_id" id="edit_city_id" class="form-control" required></select>
                        </div>

                        <div class="col-12">
                            <label for="edit_address" class="form-label">Address</label>
                            <textarea name="address" id="edit_address" class="form-control" rows="3">{{ $location->address }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_latitude" class="form-label">Latitude</label>
                            <input type="text" name="latitude" id="edit_latitude" class="form-control" value="{{ $location->latitude }}">
                        </div>

                        <div class="col-md-6">
                            <label for="edit_longitude" class="form-label">Longitude</label>
                            <input type="text" name="longitude" id="edit_longitude" class="form-control" value="{{ $location->longitude }}">
                        </div>

                        <div class="col-12">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="4">{{ $location->description }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Map Preview</label>
                            <div id="editLocationMap"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Generic Child Drill-down Modal --}}
    <div class="modal fade" id="editChildModal" tabindex="-1" aria-labelledby="editChildModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="editChildForm" class="modal-content">
                @csrf
                @method('PUT')

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editChildModalLabel">Edit Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="child_entity_type">
                    <input type="hidden" id="child_entity_id">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="child_primary_value" class="form-label" id="child_primary_label">Name</label>
                            <input type="text" id="child_primary_value" class="form-control">
                        </div>

                        <div class="col-md-6 d-none" id="child_block_group">
                            <label for="child_block_id" class="form-label">Block</label>
                            <select id="child_block_id" class="form-control"></select>
                        </div>

                        <div class="col-md-6 d-none" id="child_floor_group">
                            <label for="child_floor_id" class="form-label">Floor</label>
                            <select id="child_floor_id" class="form-control"></select>
                        </div>

                        <div class="col-md-6 d-none" id="child_store_scope_group">
                            <label for="child_store_scope" class="form-label">Store Assignment</label>
                            <select id="child_store_scope" class="form-select">
                                <option value="direct">Direct to Location</option>
                                <option value="room">Assign to Room</option>
                            </select>
                        </div>

                        <div class="col-md-6 d-none" id="child_room_group">
                            <label for="child_room_id" class="form-label">Room</label>
                            <select id="child_room_id" class="form-control"></select>
                        </div>

                        <div class="col-md-6 d-none" id="child_store_group">
                            <label for="child_store_id" class="form-label">Store</label>
                            <select id="child_store_id" class="form-control"></select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>

<style>
#locationSatelliteMap,
#editLocationMap {
    min-height: 320px;
    border-radius: .5rem;
    overflow: hidden;
}
.select2-container {
    width: 100% !important;
}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const locationId = {{ (int) $location->id }};
const loadedTables = {};
let satelliteMap = null;
let editMap = null;
let editMarker = null;
let hydratingGeo = false;

function bsModal(id) {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
}

function reloadEntityTable(entity) {
    if (loadedTables[entity]) {
        loadedTables[entity].ajax.reload(null, false);
    }
}

function clearSelect2Value(selector) {
    const $el = $(selector);

    if ($el.hasClass('select2-hidden-accessible')) {
        $el.val(null).trigger('change');
    } else {
        $el.val('');
    }
}

function setSelect2Value(selector, id, text) {
    const $el = $(selector);

    if (!id || !text) {
        clearSelect2Value(selector);
        return;
    }

    const optionExists = $el.find("option[value='" + id + "']").length > 0;

    if (!optionExists) {
        const newOption = new Option(text, id, true, true);
        $el.append(newOption);
    } else {
        $el.val(id);
    }

    $el.trigger('change');
    $el.trigger({
        type: 'select2:select',
        params: {
            data: { id: id, text: text }
        }
    });
}

function normalizeSelect2Results(data, params) {
    let items = [];

    if (Array.isArray(data)) {
        items = data;
    } else if (Array.isArray(data.results)) {
        items = data.results;
    } else if (Array.isArray(data.data)) {
        items = data.data;
    }

    const term = (params.term || '').toLowerCase().trim();

    if (term) {
        items = items.filter(function(item) {
            const text = (
                item.text ||
                item.name ||
                item.code ||
                item.block_name ||
                item.floor_name ||
                item.room_name ||
                item.store_name ||
                ''
            ).toLowerCase();

            return text.includes(term);
        });
    }

    return {
        results: items.map(function(item) {
            return {
                id: item.id,
                text: item.text || item.name || item.code || ''
            };
        })
    };
}

function initScopedSelect(selector, url, placeholder, dropdownParentSelector = '#editChildModal', selected = null) {
    const $el = $(selector);

    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }

    $el.empty();

    if (selected && selected.id && selected.text) {
        $el.append(new Option(selected.text, selected.id, true, true));
    }

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        dropdownParent: $(dropdownParentSelector),
        ajax: {
            url: url,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { term: params.term || '' };
            },
            processResults: function(data, params) {
                return normalizeSelect2Results(data, params);
            },
            cache: true
        }
    });

    if (selected && selected.id && selected.text) {
        setSelect2Value(selector, selected.id, selected.text);
    }
}

function initChildScopedSelects(preset = {}) {
    initScopedSelect(
        '#child_block_id',
        `/admin/locations/${locationId}/blocks`,
        'Select block',
        '#editChildModal',
        preset.block || null
    );

    initScopedSelect(
        '#child_floor_id',
        `/admin/locations/${locationId}/floors`,
        'Select floor',
        '#editChildModal',
        preset.floor || null
    );

    initScopedSelect(
        '#child_room_id',
        `/admin/locations/${locationId}/rooms`,
        'Select room',
        '#editChildModal',
        preset.room || null
    );

    initScopedSelect(
        '#child_store_id',
        `/admin/locations/${locationId}/stores`,
        'Select store',
        '#editChildModal',
        preset.store || null
    );
}

function initCompanySelect(selected = null) {
    initScopedSelect(
        '#edit_company_id',
        '/admin/companies/search',
        'Search company',
        '#editLocationModal',
        selected
    );
}

function resetChildModal() {
    $('#child_entity_type').val('');
    $('#child_entity_id').val('');
    $('#child_primary_value').val('');
    $('#child_primary_label').text('Name');

    $('#child_block_group, #child_floor_group, #child_store_scope_group, #child_room_group, #child_store_group')
        .addClass('d-none');

    $('#child_primary_value').prop('required', false);
    $('#child_block_id').prop('required', false);
    $('#child_floor_id').prop('required', false);
    $('#child_room_id').prop('required', false);
    $('#child_store_id').prop('required', false);

    clearSelect2Value('#child_block_id');
    clearSelect2Value('#child_floor_id');
    clearSelect2Value('#child_room_id');
    clearSelect2Value('#child_store_id');

    $('#child_store_scope').val('direct');
}

function applyChildEntityMode(entity, data) {
    resetChildModal();

    const preset = {
        block: data.blockId && data.blockName ? { id: data.blockId, text: data.blockName } : null,
        floor: data.floorId && data.floorName ? { id: data.floorId, text: data.floorName } : null,
        room: data.roomId && data.roomName ? { id: data.roomId, text: data.roomName } : null,
        store: data.storeId && data.storeName ? { id: data.storeId, text: data.storeName } : null
    };

    initChildScopedSelects(preset);

    $('#child_entity_type').val(entity);
    $('#child_entity_id').val(data.id || '');
    $('#editChildModalLabel').text(`Edit ${entity.slice(0, -1)}`);

    if (entity === 'blocks') {
        $('#child_primary_label').text('Block Name');
        $('#child_primary_value').val(data.value || '').prop('required', true);
        return;
    }

    if (entity === 'floors') {
        $('#child_primary_label').text('Floor Name');
        $('#child_primary_value').val(data.value || '').prop('required', true);

        $('#child_block_group').removeClass('d-none');
        $('#child_block_id').prop('required', true);
        return;
    }

    if (entity === 'rooms') {
        $('#child_primary_label').text('Room Name');
        $('#child_primary_value').val(data.value || '').prop('required', true);

        $('#child_floor_group').removeClass('d-none');
        $('#child_floor_id').prop('required', true);
        return;
    }

    if (entity === 'stores') {
        $('#child_primary_label').text('Store Name');
        $('#child_primary_value').val(data.value || '').prop('required', true);

        $('#child_store_scope_group').removeClass('d-none');

        if (data.roomId) {
            $('#child_store_scope').val('room');
            $('#child_room_group').removeClass('d-none');
            $('#child_room_id').prop('required', true);
        } else {
            $('#child_store_scope').val('direct');
            $('#child_room_group').addClass('d-none');
            $('#child_room_id').prop('required', false);
            clearSelect2Value('#child_room_id');
        }
        return;
    }

    if (entity === 'shelves') {
        $('#child_primary_label').text('Shelf Code');
        $('#child_primary_value').val(data.value || '').prop('required', true);

        $('#child_store_group').removeClass('d-none');
        $('#child_store_id').prop('required', true);
    }
}

function initSatelliteMap() {
    const lat = parseFloat(@json($location->latitude));
    const lng = parseFloat(@json($location->longitude));
    const locationName = @json($location->name ?? 'Location');

    if (isNaN(lat) || isNaN(lng)) return;

    if (satelliteMap) {
        satelliteMap.remove();
    }

    satelliteMap = L.map('locationSatelliteMap').setView([lat, lng], 16);

    // Base satellite imagery
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; Esri'
    }).addTo(satelliteMap);

    // Labels / boundaries overlay
    L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; Esri'
    }).addTo(satelliteMap);

    // Marker with tooltip
    L.marker([lat, lng])
        .addTo(satelliteMap)
        .bindTooltip(locationName, {
            permanent: true,
            direction: 'top',
            offset: [0, -10],
            className: 'location-map-tooltip'
        });

    setTimeout(() => satelliteMap.invalidateSize(), 200);
}

function initEditLocationMap() {
    const lat = parseFloat($('#edit_latitude').val()) || 9.0820;
    const lng = parseFloat($('#edit_longitude').val()) || 8.6753;

    if (editMap) {
        editMap.remove();
    }

    editMap = L.map('editLocationMap').setView([lat, lng], 15);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
    }).addTo(editMap);

    editMarker = L.marker([lat, lng], { draggable: true }).addTo(editMap);

    editMarker.on('dragend', function () {
        const pos = editMarker.getLatLng();
        $('#edit_latitude').val(pos.lat);
        $('#edit_longitude').val(pos.lng);
    });

    editMap.on('click', function (e) {
        editMarker.setLatLng(e.latlng);
        $('#edit_latitude').val(e.latlng.lat);
        $('#edit_longitude').val(e.latlng.lng);
    });

    setTimeout(() => editMap.invalidateSize(), 250);
}

function geocodeAndSetEditMap(query) {
    if (!query) return;

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                $('#edit_latitude').val(lat);
                $('#edit_longitude').val(lon);
                initEditLocationMap();
            }
        })
        .catch(() => {});
}

function initCountrySelect(selected = null) {
    initScopedSelect(
        '#edit_country_id',
        '{{ route("admin.countries.search") }}',
        'Search country',
        '#editLocationModal',
        selected
    );
}

function initStateSelect(selected = null) {
    const $el = $('#edit_state_id');

    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }

    $el.empty();

    if (selected && selected.id && selected.text) {
        $el.append(new Option(selected.text, selected.id, true, true));
    }

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search state',
        allowClear: true,
        dropdownParent: $('#editLocationModal'),
        ajax: {
            url: '{{ route("admin.states.search") }}',
            dataType: 'json',
            delay: 250,
            data: params => ({
                term: params.term || '',
                country_id: $('#edit_country_id').val() || ''
            }),
            processResults: data => ({ results: data.results || [] }),
            cache: true
        }
    });

    if (selected && selected.id && selected.text) {
        setSelect2Value('#edit_state_id', selected.id, selected.text);
    }
}

function initCitySelect(selected = null) {
    const $el = $('#edit_city_id');

    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }

    $el.empty();

    if (selected && selected.id && selected.text) {
        $el.append(new Option(selected.text, selected.id, true, true));
    }

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search city',
        allowClear: true,
        dropdownParent: $('#editLocationModal'),
        minimumInputLength: 1,
        ajax: {
            url: '{{ route("admin.cities.search") }}',
            dataType: 'json',
            delay: 250,
            data: params => ({
                term: params.term || '',
                country_id: $('#edit_country_id').val() || '',
                state_id: $('#edit_state_id').val() || ''
            }),
            processResults: data => ({ results: data.results || [] }),
            cache: true
        }
    });

    if (selected && selected.id && selected.text) {
        setSelect2Value('#edit_city_id', selected.id, selected.text);
    }
}

function initLocationEditSelects() {
    $('#edit_location_type_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#editLocationModal'),
        placeholder: 'Select Type'
    });

    initCompanySelect(@json($company->id ? ['id' => $company->id, 'text' => $company->name] : null));
    initCountrySelect(@json($country->id ? ['id' => $country->id, 'text' => $country->name] : null));
    initStateSelect(@json($state->id ? ['id' => $state->id, 'text' => $state->name] : null));
    initCitySelect(@json($city->id ? ['id' => $city->id, 'text' => $city->name] : null));

    $('#edit_country_id').off('change').on('change', function () {
        if (hydratingGeo) return;
        initStateSelect();
        initCitySelect();

        const text = $('#edit_country_id option:selected').text();
        if ($(this).val() && text) geocodeAndSetEditMap(text);
    });

    $('#edit_state_id').off('change').on('change', function () {
        if (hydratingGeo) return;
        initCitySelect();

        const stateText = $('#edit_state_id option:selected').text();
        const countryText = $('#edit_country_id option:selected').text();

        if ($(this).val() && stateText && countryText) {
            geocodeAndSetEditMap(`${stateText}, ${countryText}`);
        }
    });

    $('#edit_city_id').off('change').on('change', function () {
        if (hydratingGeo) return;

        const cityText = $('#edit_city_id option:selected').text();
        const stateText = $('#edit_state_id option:selected').text();
        const countryText = $('#edit_country_id option:selected').text();

        if ($(this).val() && cityText) {
            geocodeAndSetEditMap(`${cityText}, ${stateText}, ${countryText}`);
        }
    });
}

$(function () {
    const tableConfigs = {
        blocks: {
            selector: '#blocksTable',
            columns: [
                { data: 'name', title: 'Block Name', defaultContent: '' },
                { data: 'created_at', title: 'Created At', defaultContent: '' },
                { data: 'actions', title: 'Actions', orderable: false, searchable: false, defaultContent: '' }
            ]
        },
        floors: {
            selector: '#floorsTable',
            columns: [
                { data: 'name', title: 'Floor Name', defaultContent: '' },
                { data: 'block_name', title: 'Block', defaultContent: '' },
                { data: 'actions', title: 'Actions', orderable: false, searchable: false, defaultContent: '' }
            ]
        },
        rooms: {
            selector: '#roomsTable',
            columns: [
                { data: 'name', title: 'Room Name', defaultContent: '' },
                { data: 'floor_name', title: 'Floor', defaultContent: '' },
                { data: 'actions', title: 'Actions', orderable: false, searchable: false, defaultContent: '' }
            ]
        },
        stores: {
            selector: '#storesTable',
            columns: [
                { data: 'name', title: 'Store Name', defaultContent: '' },
                { data: 'room_name', title: 'Room', defaultContent: '' },
                { data: 'actions', title: 'Actions', orderable: false, searchable: false, defaultContent: '' }
            ]
        },
        shelves: {
            selector: '#shelvesTable',
            columns: [
                { data: 'code', title: 'Shelf Code', defaultContent: '' },
                { data: 'store_name', title: 'Store', defaultContent: '' },
                { data: 'actions', title: 'Actions', orderable: false, searchable: false, defaultContent: '' }
            ]
        }
    };

    function initTable(key) {
        if (loadedTables[key]) return;

        const config = tableConfigs[key];

        loadedTables[key] = $(config.selector).DataTable({
            processing: true,
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: `/admin/locations/${locationId}/${key}`,
                dataSrc: function(json) {
                    if (Array.isArray(json)) return json;
                    if (Array.isArray(json.data)) return json.data;
                    return [];
                }
            },
            columns: config.columns,
            language: {
                search: '',
                searchPlaceholder: 'Search...',
                emptyTable: `No ${key} available`
            }
        });
    }

    initTable('blocks');

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        const target = $(this).attr('data-bs-target');
        const key = target.replace('#', '').replace('Pane', '');
        initTable(key);

        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
    });

    initSatelliteMap();
    initLocationEditSelects();

    document.getElementById('editLocationModal').addEventListener('shown.bs.modal', function () {
        initEditLocationMap();
    });

    $('body').on('click', '.edit-child', function () {
        const entity = $(this).data('entity');

        applyChildEntityMode(entity, {
            id: $(this).data('id'),
            value: $(this).data('value'),
            blockId: $(this).data('block-id'),
            blockName: $(this).data('block-name'),
            floorId: $(this).data('floor-id'),
            floorName: $(this).data('floor-name'),
            roomId: $(this).data('room-id'),
            roomName: $(this).data('room-name'),
            storeId: $(this).data('store-id'),
            storeName: $(this).data('store-name')
        });

        bsModal('editChildModal').show();
    });

    $('#child_store_scope').off('change').on('change', function () {
        if ($(this).val() === 'room') {
            $('#child_room_group').removeClass('d-none');
            $('#child_room_id').prop('required', true);
        } else {
            $('#child_room_group').addClass('d-none');
            $('#child_room_id').prop('required', false);
            clearSelect2Value('#child_room_id');
        }
    });

    $('#editLocationForm').off('submit').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: `/admin/locations/${locationId}`,
            method: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                bsModal('editLocationModal').hide();
                Swal.fire('Success', res.message || 'Location updated successfully.', 'success')
                    .then(() => window.location.reload());
            },
            error: function (err) {
                Swal.fire('Error', err.responseJSON?.message || 'Something went wrong.', 'error');
            }
        });
    });

    $('#editChildForm').off('submit').on('submit', function(e) {
        e.preventDefault();

        const entity = $('#child_entity_type').val();
        const id = $('#child_entity_id').val();

        let payload = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            _method: 'PUT'
        };

        if (entity === 'blocks') {
            payload.name = $('#child_primary_value').val();
        }

        if (entity === 'floors') {
            payload.name = $('#child_primary_value').val();
            payload.location_block_id = $('#child_block_id').val();
        }

        if (entity === 'rooms') {
            payload.name = $('#child_primary_value').val();
            payload.location_block_floor_id = $('#child_floor_id').val();
        }

        if (entity === 'stores') {
            payload.name = $('#child_primary_value').val();

            if ($('#child_store_scope').val() === 'room') {
                payload.location_id = '';
                payload.location_block_floor_room_id = $('#child_room_id').val();
            } else {
                payload.location_id = locationId;
                payload.location_block_floor_room_id = '';
            }
        }

        if (entity === 'shelves') {
            payload.code = $('#child_primary_value').val();
            payload.store_id = $('#child_store_id').val();
        }

        $.ajax({
            url: `/admin/locations/${entity}/${id}`,
            method: 'POST',
            data: payload,
            success: function (res) {
                bsModal('editChildModal').hide();
                reloadEntityTable(entity);

                if (entity === 'blocks') reloadEntityTable('floors');
                if (entity === 'floors') reloadEntityTable('rooms');
                if (entity === 'rooms') reloadEntityTable('stores');
                if (entity === 'stores') reloadEntityTable('shelves');

                Swal.fire('Success', res.message || 'Updated successfully.', 'success');
            },
            error: function (err) {
                Swal.fire('Error', err.responseJSON?.message || 'Failed to update.', 'error');
            }
        });
    });
});
</script>
@endpush