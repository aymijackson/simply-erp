@extends('layouts.master')

@section('title', 'Manage Location Blocks')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Location Blocks</h1>
    <button class="btn btn-primary mb-3" id="addLocation">Add Location Block</button>
    <button class="btn btn-danger mb-3" id="bulkDeleteLocations">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="locationsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('locations.blocks.modal')
</div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    $('#locationModal').on('shown.bs.modal', function () {
        $('#city_id').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#locationModal'),
        placeholder: 'Select a City',
        minimumInputLength: 2,
        allowClear: true,
        ajax: {
            url: '{{ route("admin.cities.search") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    term: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });

});

    const table = $('#locationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.location_blocks.list') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name', title: 'Name' },
            { data: 'location', title: 'Location' },
            { data: 'actions', orderable: false, searchable: false, title: 'Actions' }
        ]
    });

    $('#addLocation').click(() => {
        $('#locationForm')[0].reset();
        $('#location_id').val('');
        $('#locationModalLabel').text('Add Location');
        $('#locationModal').modal('show');
    });

    $('#locationForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#location_id').val();
        const url = id ? `/admin/locations/blocks/${id}` : '{{ route('admin.location_blocks.store') }}';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: $(this).serialize(),
            success: res => {
                $('#locationModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: err => Swal.fire('Error', err.responseJSON.message || 'An error occurred', 'error')
        });
    });

    $('body').on('click', '.edit-block', function () {
        const id = $(this).data('id');
        $.get(`/admin/location_blocks/${id}/edit`, res => {
            const l = res.location;
            $('#location_id').val(l.id);
            $('#name').val(l.name);
            $('#locationModalLabel').text('Edit Location');
            $('#locationModal').modal('show');
        });
    });

    $('body').on('click', '.delete-block', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Delete this block?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })
        .then(res => {
            if (res.isConfirmed) {
                $.ajax({
                    url: `/admin/location_blocks/${id}`,
                    method: 'DELETE',
                    success: r => { table.ajax.reload(); Swal.fire('Deleted!', r.message, 'success'); }
                });
            }
        });
    });

    $('#selectAll').on('click', function () {
        $('input[name="location_checkbox[]"]').prop('checked', this.checked);
    });

    $('#bulkDeleteLocations').on('click', function () {
        const ids = $('input[name="location_checkbox[]"]:checked').map(function () { return this.value }).get();
        if (!ids.length) return Swal.fire('Select at least one!', '', 'info');

        Swal.fire({
            title: 'Delete selected?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('admin.location_blocks.bulk-delete') }}',
                    method: 'POST',
                    data: { ids, _token: '{{ csrf_token() }}' },
                    success: res => { table.ajax.reload(); Swal.fire('Deleted', res.message, 'success'); }
                });
            }
        });
    });

    $('#city_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Select a City',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route('admin.cities.search') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: data.map(city => ({
                        id: city.id,
                        text: city.name
                    }))
                };
            },
            cache: true
        }
    });
    
</script>
@endpush