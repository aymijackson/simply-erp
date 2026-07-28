@extends('layouts.master')

@section('title', 'Manage Locations')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Locations</h1>

    <div class="mb-3">
        <button class="btn btn-primary" id="addLocation">Add Location</button>
        <button class="btn btn-danger" id="bulkDeleteLocations">Delete Selected</button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="locationsTable" width="100%">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>City</th>
                            <th>Longitude</th>
                            <th>Latitude</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('locations.modal')
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<style>
/* modal scrolling */
#locationModal .modal-dialog {
    max-width: 1400px;
}

#locationModal .modal-body {
    max-height: 75vh;
    overflow-y: auto;
    overflow-x: hidden;
}

/* Select2 sizing + border fix */
.select2-container {
    width: 100% !important;
}

.select2-container--bootstrap4 .select2-selection {
    border: 1px solid #ced4da !important;
    border-radius: .25rem !important;
    min-height: calc(2.25rem + 2px) !important;
    background-color: #fff !important;
    box-shadow: none !important;
}

.select2-container--bootstrap4.select2-container--focus .select2-selection,
.select2-container--bootstrap4.select2-container--open .select2-selection {
    border-color: #80bdff !important;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.15) !important;
}

.select2-container--bootstrap4 .select2-selection--single {
    display: flex !important;
    align-items: center !important;
    padding: 0 !important;
}

.select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
    display: flex !important;
    align-items: center !important;
    line-height: normal !important;
    padding-left: .75rem !important;
    padding-right: 2rem !important;
    color: #495057 !important;
    width: 100%;
    min-height: calc(2.25rem + 2px);
}

.select2-container--bootstrap4 .select2-selection__placeholder {
    color: #6c757d !important;
}

.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
    height: 100% !important;
    right: 6px !important;
    top: 0 !important;
}

.select2-container--bootstrap4 .select2-selection__clear {
    float: none !important;
    margin-right: .5rem !important;
    margin-left: 0 !important;
    color: #6c757d !important;
    font-size: 1rem !important;
    line-height: 1 !important;
    position: relative;
    top: 0 !important;
}

.select2-dropdown {
    border: 1px solid #ced4da !important;
    z-index: 3005 !important;
}

.select2-search--dropdown {
    padding: .5rem !important;
    background: #fff !important;
}

.select2-search__field {
    width: 100% !important;
    box-sizing: border-box !important;
    border: 1px solid #ced4da !important;
    border-radius: .25rem !important;
    padding: .375rem .75rem !important;
}

/* prevent weird inherited styles */
.select2-results__option {
    white-space: normal !important;
}

#locationMap {
    width: 100%;
    height: 320px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.modal { z-index: 2000 !important; }
.modal-backdrop { z-index: 1990 !important; }
.select2-container--open { z-index: 3005 !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

let table = null;
let map = null;
let marker = null;
let hydrating = false;

$(function () {
    if ($.fn.modal && $.fn.modal.Constructor) {
        $.fn.modal.Constructor.prototype._enforceFocus = function () {};
    }

    initTable();
    initSelects();
    bindEvents();
});

function initTable() {
    table = $('#locationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.locations.list") }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'company' },
            { data: 'location_type' },
            { data: 'city' },
            { data: 'longitude' },
            { data: 'latitude' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
}

function initSelects() {
    initStaticSelect('#company_id', 'Select Company');
    initStaticSelect('#location_type_id', 'Select Type');
    initCountrySelect();
    initStateSelect();
    initCitySelect();
}

function initStaticSelect(selector, placeholder) {
    if ($(selector).hasClass('select2-hidden-accessible')) {
        $(selector).select2('destroy');
    }

    $(selector).select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        dropdownParent: $('#locationModal')
    });
}

function initCountrySelect(selected = null) {
    if ($('#country_id').hasClass('select2-hidden-accessible')) {
        $('#country_id').select2('destroy');
    }

    $('#country_id').empty();

    if (selected && selected.id && selected.text) {
        $('#country_id').append(new Option(selected.text, selected.id, true, true));
    }

    $('#country_id').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Search country',
        allowClear: true,
        dropdownParent: $('#locationModal'),
        minimumInputLength: 0,
        ajax: {
            url: '{{ route("admin.countries.search") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term || '' };
            },
            processResults: function (data) {
                return { results: data.results || [] };
            }
        }
    });
}

function initStateSelect(selected = null) {
    if ($('#state_id').hasClass('select2-hidden-accessible')) {
        $('#state_id').select2('destroy');
    }

    $('#state_id').empty();

    if (selected && selected.id && selected.text) {
        $('#state_id').append(new Option(selected.text, selected.id, true, true));
    }

    $('#state_id').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Search state',
        allowClear: true,
        dropdownParent: $('#locationModal'),
        minimumInputLength: 0,
        ajax: {
            url: '{{ route("admin.states.search") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    term: params.term || '',
                    country_id: $('#country_id').val() || ''
                };
            },
            processResults: function (data) {
                return { results: data.results || [] };
            }
        }
    });
}

function initCitySelect(selected = null) {
    if ($('#city_id').hasClass('select2-hidden-accessible')) {
        $('#city_id').select2('destroy');
    }

    $('#city_id').empty();

    if (selected && selected.id && selected.text) {
        $('#city_id').append(new Option(selected.text, selected.id, true, true));
    }

    $('#city_id').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Search city',
        allowClear: true,
        dropdownParent: $('#locationModal'),
        minimumInputLength: 0,
        ajax: {
            url: '{{ route("admin.cities.search.by_state") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    term: params.term || '',
                    country_id: $('#country_id').val() || '',
                    state_id: $('#state_id').val() || ''
                };
            },
            processResults: function (data) {
                return { results: data.results || [] };
            }
        }
    });
}

function resetForm() {
    $('#locationForm')[0].reset();
    $('#location_id').val('');
    $('#name').val('');
    $('#address').val('');
    $('#longitude').val('');
    $('#latitude').val('');
    $('#description').val('');

    $('#company_id').val(null).trigger('change');
    $('#location_type_id').val(null).trigger('change');

    initCountrySelect();
    initStateSelect();
    initCitySelect();

    $('#locationModalLabel').text('Add Location');
}

function fillForm(data) {
    $('#location_id').val(data.id || '');
    $('#name').val(data.name || '');
    $('#address').val(data.address || '');
    $('#longitude').val(data.longitude || '');
    $('#latitude').val(data.latitude || '');
    $('#description').val(data.description || '');

    $('#company_id').val(data.company_id ? String(data.company_id) : null).trigger('change');
    $('#location_type_id').val(data.location_type_id ? String(data.location_type_id) : null).trigger('change');

    initCountrySelect(
        data.country_id && data.country_name
            ? { id: data.country_id, text: data.country_name }
            : null
    );

    initStateSelect(
        data.state_id && data.state_name
            ? { id: data.state_id, text: data.state_name }
            : null
    );

    initCitySelect(
        data.city_id && data.city_name
            ? { id: data.city_id, text: data.city_name }
            : null
    );
}

function bindEvents() {
    $('#addLocation').on('click', function () {
        hydrating = true;
        resetForm();
        hydrating = false;
        $('#locationModal').modal('show');
    });

    $('body').on('click', '.edit-location', function () {
        const id = $(this).data('id');

        $.get(`/admin/locations/${id}/edit`, function (res) {
            if (!res.location) {
                Swal.fire('Error', 'Could not load location.', 'error');
                return;
            }

            hydrating = true;
            resetForm();
            fillForm(res.location);
            $('#locationModalLabel').text('Edit Location');
            $('#locationModal').modal('show');
            hydrating = false;
        });
    });

    $('#country_id').on('change', function () {
        if (hydrating) return;

        initStateSelect();
        initCitySelect();

        const countryText = $('#country_id option:selected').text();
        if ($(this).val() && countryText) {
            geocodeAndRefocus(countryText);
        }
    });

    $('#state_id').on('change', function () {
        if (hydrating) return;

        // clear and narrow city by selected state
        initCitySelect();

        const stateText = $('#state_id option:selected').text();
        const countryText = $('#country_id option:selected').text();

        if ($(this).val() && stateText && countryText) {
            geocodeAndRefocus(`${stateText}, ${countryText}`);
        }
    });

    $('#city_id').on('change', function () {
        if (hydrating) return;

        const cityText = $('#city_id option:selected').text();
        const stateText = $('#state_id option:selected').text();
        const countryText = $('#country_id option:selected').text();

        if ($(this).val() && cityText) {
            geocodeAndRefocus(`${cityText}, ${stateText}, ${countryText}`);
        }
    });

    $('#locationModal').on('shown.bs.modal', function () {
        initMap($('#latitude').val(), $('#longitude').val());

        setTimeout(function () {
            if (map) map.invalidateSize();
        }, 200);
    });

    $('#locationForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#location_id').val();
        const url = id ? `/admin/locations/${id}` : '{{ route("admin.locations.store") }}';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function (res) {
                $('#locationModal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire('Success', res.message || 'Saved successfully', 'success');
            },
            error: function (err) {
                Swal.fire('Error', err.responseJSON?.message || 'An error occurred', 'error');
            }
        });
    });

    $('body').on('click', '.delete-location', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete this location?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/locations/${id}`,
                    method: 'DELETE',
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted', res.message || 'Deleted successfully', 'success');
                    }
                });
            }
        });
    });

    $('#selectAll').on('click', function () {
        $('input[name="location_checkbox[]"]').prop('checked', this.checked);
    });

    $('#bulkDeleteLocations').on('click', function () {
        const ids = $('input[name="location_checkbox[]"]:checked')
            .map(function () { return this.value; })
            .get();

        if (!ids.length) {
            Swal.fire('Select at least one!', '', 'info');
            return;
        }

        Swal.fire({
            title: 'Delete selected?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.locations.bulk-delete") }}',
                    method: 'POST',
                    data: { ids },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted', res.message || 'Deleted successfully', 'success');
                    }
                });
            }
        });
    });
}

function geocodeAndRefocus(query) {
    if (!query) return;

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);

                $('#latitude').val(lat);
                $('#longitude').val(lon);

                initMap(lat, lon);
            }
        })
        .catch(() => {});
}

function setLatLngAndMap(lat, lon) {
    lat = parseFloat(lat);
    lon = parseFloat(lon);

    if (isNaN(lat) || isNaN(lon)) return;

    $('#latitude').val(lat);
    $('#longitude').val(lon);
    initMap(lat, lon);
}

function initMap(lat, lng) {
    lat = parseFloat(lat);
    lng = parseFloat(lng);

    if (isNaN(lat) || isNaN(lng)) {
        lat = 9.0820;
        lng = 8.6753;
    }

    if (map) {
        map.remove();
    }

    map = L.map('locationMap').setView([lat, lng], 6);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
    }).addTo(map);

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    marker.on('dragend', function () {
        const pos = marker.getLatLng();
        $('#latitude').val(pos.lat);
        $('#longitude').val(pos.lng);
    });

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        $('#latitude').val(e.latlng.lat);
        $('#longitude').val(e.latlng.lng);
    });
}
</script>
@endpush
