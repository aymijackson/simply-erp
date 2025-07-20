@extends('layouts.master')

@section('title', 'Manage Countries')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Countries</h1>
    <button id="addCountry" class="btn btn-primary mb-3">Add Country</button>
    <button id="bulkDeleteCountries" class="btn btn-danger mb-3">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="countriesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Region</th>
                            <th>Subregion</th>
                            <th>Actions</th>
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
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });
    $(function () {
        const table = $('#countriesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("admin.countries.list") }}',
            columns: [
                { data: 'id', render: id => `<input type="checkbox" name="country_checkbox[]" value="${id}">`, orderable: false, searchable: false },
                { data: 'name' },
                { data: 'region' },
                { data: 'subregion' },
                { data: 'actions', orderable: false, searchable: false },
            ]
        });

        $('#addCountry').on('click', function () {
            $('#countryForm')[0].reset();
            $('#country_id').val('');
            $('#countryModalLabel').text('Add Country');
            $('#countryModal').modal('show');
        });

        $('#countryForm').on('submit', function (e) {
            e.preventDefault();
            const id = $('#country_id').val();
            const url = id ? `/admin/countries/${id}` : '{{ route("admin.countries.store") }}';
            const type = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: type,
                data: $(this).serialize(),
                success: function (response) {
                    $('#countryModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#countryForm')[0].reset();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                }
            });
        });

        $('body').on('click', '.edit-country', function () {
            const id = $(this).data('id');
            $.get(`/admin/countries/${id}/edit`, function (res) {
                const c = res.country;
                $('#country_id').val(c.id);
                $('#country_name').val(c.name);
                $('#region_id').val(c.region_id);
                $('#subregion_id').val(c.subregion_id);
                $('#countryModalLabel').text('Edit Country');
                $('#countryModal').modal('show');
            });
        });

        $('body').on('click', '.delete-country', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/countries/${id}`,
                        method: 'DELETE',
                        success: function (res) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', res.message, 'success');
                        },
                        error: function () {
                            Swal.fire('Error', 'Failed to delete country.', 'error');
                        }
                    });
                }
            });
        });

        $('#selectAll').on('click', function () {
            $('input[name="country_checkbox[]"]').prop('checked', this.checked);
        });

        $('#bulkDeleteCountries').on('click', function () {
            const ids = $('input[name="country_checkbox[]"]:checked').map(function() { return this.value }).get();
            if (ids.length === 0) {
                return Swal.fire('Select at least one!', '', 'info');
            }
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will delete selected countries.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("admin.countries.bulk-delete") }}',
                        method: 'DELETE',
                        data: { ids, _token: '{{ csrf_token() }}' },
                        success: res => { table.ajax.reload(); Swal.fire('Deleted', res.message, 'success'); },
                        error: () => Swal.fire('Error', 'Failed to delete.', 'error')
                    });
                }
            });
        });
    });

    $('#region_id').on('change', function () {
        const regionId = $(this).val();
        $('#subregion_id').html('<option value="">Loading...</option>');

        if (regionId) {
            $.get(`/admin/regions/${regionId}/subregions`, function (data) {
                let options = '<option value="">Select Subregion</option>';
                data.forEach(sub => {
                    options += `<option value="${sub.id}">${sub.name}</option>`;
                });
                $('#subregion_id').html(options);
            });
        } else {
            $('#subregion_id').html('<option value="">Select Subregion</option>');
        }
    });

</script>
@endpush
