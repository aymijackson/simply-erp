@extends('layouts.master')

@section('title', 'Manage Countries')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Countries</h1>

        <div>
            <button id="addCountry" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Country
            </button>

            <button id="bulkDeleteCountries" class="btn btn-danger">
                <i class="fas fa-trash"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="countriesTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Name</th>
                            <th>Region</th>
                            <th>Subregion</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('countries.modal')

</div>
@endsection
@push('scripts')
<script>
    // Toast config
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
    // Reusable Subregion Loader
    // ------------------------------------
    function loadSubregions(regionId, selectedId = null) {
        $('#subregion_id').html('<option value="">Loading...</option>');

        $.get(`/admin/regions/${regionId}/subregions`, function (data) {
            let options = '<option value="">Select Subregion</option>';

            data.forEach(sub => {
                options += `<option value="${sub.id}">${sub.name}</option>`;
            });

            $('#subregion_id').html(options);

            if (selectedId) {
                $('#subregion_id').val(selectedId);
            }
        });
    }

    $(function () {

        // ------------------------------------
        // DataTable
        // ------------------------------------
        const table = $('#countriesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("admin.countries.list") }}',
            pageLength: 10,
            order: [[1, 'asc']],
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: id => `<input type="checkbox" class="country-checkbox" value="${id}">`
                },
                { data: 'name' },
                { data: 'region' },
                { data: 'subregion' },
                { data: 'actions', orderable: false, searchable: false }
            ]
        });

        // ------------------------------------
        // Add Country
        // ------------------------------------
        $('#addCountry').on('click', () => {
            $('#countryForm')[0].reset();
            $('#country_id').val('');
            $('#subregion_id').html('<option value="">Select Subregion</option>');
            $('#countryModalLabel').text('Add Country');
            $('#countryModal').modal('show');
        });

        // ------------------------------------
        // Save Country (Create/Update)
        // ------------------------------------
        $('#countryForm').on('submit', function (e) {
            e.preventDefault();

            const id = $('#country_id').val();
            const url = id ? `/admin/countries/${id}` : '{{ route("admin.countries.store") }}';
            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url,
                method,
                data: $(this).serialize(),
                success: res => {
                    $('#countryModal').modal('hide');
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
        // Edit Country
        // ------------------------------------
        $('body').on('click', '.edit-country', function () {
            const id = $(this).data('id');

            $.get(`/admin/countries/${id}/edit`, res => {
                const c = res.country;

                $('#country_id').val(c.id);
                $('#country_name').val(c.name);

                // Set region first
                $('#region_id').val(c.region_id);

                // Load subregions and preselect correct one
                loadSubregions(c.region_id, c.subregion_id);

                $('#countryModalLabel').text('Edit Country');
                $('#countryModal').modal('show');
            });
        });

        // ------------------------------------
        // Delete Single Country
        // ------------------------------------
        $('body').on('click', '.delete-country', function () {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Delete Country?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: `/admin/countries/${id}`,
                    method: 'DELETE',
                    success: res => {
                        table.ajax.reload(null, false);
                        Toast.fire({ icon: 'success', title: res.message });
                    },
                    error: () => {
                        Toast.fire({ icon: 'error', title: 'Failed to delete country' });
                    }
                });
            });
        });

        // ------------------------------------
        // Select All Checkbox
        // ------------------------------------
        $('#selectAll').on('change', function () {
            $('.country-checkbox').prop('checked', this.checked);
        });

        // ------------------------------------
        // Bulk Delete
        // ------------------------------------
        $('#bulkDeleteCountries').on('click', function () {
            const ids = $('.country-checkbox:checked').map(function () {
                return this.value;
            }).get();

            if (ids.length === 0) {
                return Toast.fire({ icon: 'info', title: 'Select at least one country' });
            }

            Swal.fire({
                title: 'Delete Selected Countries?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ route("admin.countries.bulk-delete") }}',
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

        // ------------------------------------
        // Region → Subregion Dynamic Loading
        // ------------------------------------
        $('#region_id').on('change', function () {
            const regionId = $(this).val();

            if (!regionId) {
                $('#subregion_id').html('<option value="">Select Subregion</option>');
                return;
            }

            loadSubregions(regionId);
        });

    });
</script>
@endpush