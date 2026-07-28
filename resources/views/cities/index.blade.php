@extends('layouts.master')

@section('title', 'Manage Cities')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Cities</h1>

        <div>
            <button id="addCity" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add City
            </button>

            <button id="bulkDeleteCities" class="btn btn-danger">
                <i class="fas fa-trash"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="citiesTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Name</th>
                            <th>State</th>
                            <th>Country</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

@include('cities.modal')
@endsection

@push('scripts')
<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        timer: 2000,
        showConfirmButton: false,
        timerProgressBar: true
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });

    // ------------------------------------
    // Load States by Country
    // ------------------------------------
    function loadStates(countryId, selectedStateId = null) {
        $('#state_id').html('<option value="">Loading...</option>');
    
        $.get(`/states/by-country/${countryId}`, function (data) {
            let options = '<option value="">Select State</option>';
    
            data.forEach(state => {
                options += `<option value="${state.id}">${state.name}</option>`;
            });
    
            $('#state_id').html(options);
    
            if (selectedStateId) {
                $('#state_id').val(selectedStateId);
            }
        });
    }

    $(function () {

        // ------------------------------------
        // DataTable
        // ------------------------------------
        const table = $('#citiesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("admin.cities.list") }}',
            pageLength: 10,
            order: [[1, 'asc']],
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'name' },
                { data: 'state' },
                { data: 'country' },
                { data: 'actions', orderable: false, searchable: false }
            ]
        });

        // ------------------------------------
        // Add City
        // ------------------------------------
        $('#addCity').on('click', () => {
            $('#cityForm')[0].reset();
            $('#city_id').val('');
            $('#state_id').html('<option value="">Select State</option>');
            $('#cityModalLabel').text('Add City');
            $('#cityModal').modal('show');
        });

        // ------------------------------------
        // Country Change → Load States
        // ------------------------------------
        $('#country_id').on('change', function () {
            const countryId = $(this).val();

            if (!countryId) {
                $('#state_id').html('<option value="">Select State</option>');
                return;
            }

            loadStates(countryId);
        });

        // ------------------------------------
        // Save City
        // ------------------------------------
        $('#cityForm').on('submit', function (e) {
            e.preventDefault();

            const id = $('#city_id').val();
            const url = id ? `/admin/cities/${id}` : '{{ route("admin.cities.store") }}';
            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url,
                method,
                data: $(this).serialize(),
                success: res => {
                    $('#cityModal').modal('hide');
                    table.ajax.reload(null, false);

                    Toast.fire({
                        icon: 'success',
                        title: res.message
                    });
                },
                error: xhr => {
                    Toast.fire({
                        icon: 'error',
                        title: xhr.responseJSON?.message || 'An error occurred'
                    });
                }
            });
        });

        // ------------------------------------
        // Edit City
        // ------------------------------------
        $('body').on('click', '.edit-city', function () {
            const id = $(this).data('id');

            $.get(`/admin/cities/${id}/edit`, function (res) {

                const c = res.country; // your payload

                $('#city_id').val(c.id);
                $('#city_name').val(c.name);

                // Set country first
                $('#country_id').val(c.country_id);

                // Load states for that country, then select the correct one
                loadStates(c.country_id, c.state_id);

                $('#cityModalLabel').text('Edit City');
                $('#cityModal').modal('show');
            });
        });

        // ------------------------------------
        // Delete Single City
        // ------------------------------------
        $('body').on('click', '.delete-city', function () {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Delete City?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: `/admin/cities/${id}`,
                    method: 'DELETE',
                    success: res => {
                        table.ajax.reload(null, false);
                        Toast.fire({ icon: 'success', title: res.message });
                    },
                    error: () => {
                        Toast.fire({ icon: 'error', title: 'Failed to delete city' });
                    }
                });
            });
        });

        // ------------------------------------
        // Select All Checkbox
        // ------------------------------------
        $('#selectAll').on('change', function () {
            $('input[name="city_checkbox[]"]').prop('checked', this.checked);
        });

        // ------------------------------------
        // Bulk Delete
        // ------------------------------------
        $('#bulkDeleteCities').on('click', function () {
            const ids = $('input[name="city_checkbox[]"]:checked').map(function () {
                return this.value;
            }).get();

            if (ids.length === 0) {
                return Toast.fire({ icon: 'info', title: 'Select at least one city' });
            }

            Swal.fire({
                title: 'Delete Selected Cities?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ route("admin.cities.bulk-delete") }}',
                    method: 'DELETE',
                    data: { ids },
                    success: res => {
                        table.ajax.reload(null, false);
                        Toast.fire({ icon: 'success', title: res.message });
                    },
                    error: () => {
                        Toast.fire({ icon: 'error', title: 'Bulk delete failed' });
                    }
                });
            });
        });

    });
</script>
@endpush