@extends('layouts.master')

@section('title', 'Manage Cities')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Cities</h1>
    <button id="addCity" class="btn btn-primary mb-3">Add City</button>
    <button id="bulkDeleteCities" class="btn btn-danger mb-3">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="citiesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>State</th>
                            <th>Country</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- City Modal -->
    <div class="modal fade" id="cityModal" tabindex="-1" role="dialog" aria-labelledby="cityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="cityForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cityModalLabel">Add City</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="city_id">
                    <div class="form-group">
                        <label for="city_name">Name</label>
                        <input type="text" class="form-control" id="city_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="state_id">State</label>
                        <select name="state_id" id="state_id" class="form-control" required>
                            <option value="">Select State</option>
                            @foreach(\App\Models\State::all() as $state)
                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                            @endforeach
                        </select>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
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
        const table = $('#citiesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("admin.cities.list") }}',
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'name' },
                { data: 'state', name: 'state', orderable: false, searchable: true },
                { data: 'country', name: 'country', orderable: false, searchable: true },
                { data: 'actions', orderable: false, searchable: false }
            ]
        });

        $('#addCity').on('click', function () {
            $('#cityForm')[0].reset();
            $('#city_id').val('');
            $('#cityModalLabel').text('Add City');
            $('#cityModal').modal('show');
        });

        $('#cityForm').on('submit', function (e) {
            e.preventDefault();
            const id = $('#city_id').val();
            const url = id ? `/admin/cities/${id}` : '{{ route("admin.cities.store") }}';
            const type = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: type,
                data: $(this).serialize(),
                success: function (response) {
                    $('#cityModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#cityForm')[0].reset();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                }
            });
        });

        $('body').on('click', '.edit-city', function () {
            const id = $(this).data('id');
            $.get(`/admin/cities/${id}/edit`, function (res) {
                const c = res.city;
                $('#city_id').val(c.id);
                $('#city_name').val(c.name);
                $('#state_id').val(c.state_id);
                $('#country_id').val(c.country_id);
                $('#cityModalLabel').text('Edit City');
                $('#cityModal').modal('show');
            });
        });

        $('body').on('click', '.delete-city', function () {
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
                        url: `/admin/cities/${id}`,
                        method: 'DELETE',
                        success: function (res) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', res.message, 'success');
                        },
                        error: function () {
                            Swal.fire('Error', 'Failed to delete city.', 'error');
                        }
                    });
                }
            });
        });

        $('#selectAll').on('click', function () {
            $('input[name="city_checkbox[]"]').prop('checked', this.checked);
        });

        $('#bulkDeleteCities').on('click', function () {
            const ids = $('input[name="city_checkbox[]"]:checked').map(function() { return this.value }).get();
            if (ids.length === 0) {
                return Swal.fire('Select at least one!', '', 'info');
            }
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will delete selected cities.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("admin.cities.bulk-delete") }}',
                        method: 'DELETE',
                        data: { ids, _token: '{{ csrf_token() }}' },
                        success: res => { table.ajax.reload(); Swal.fire('Deleted', res.message, 'success'); },
                        error: () => Swal.fire('Error', 'Failed to delete.', 'error')
                    });
                }
            });
        });
    });
</script>
@endpush