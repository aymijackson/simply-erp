@extends('layouts.master')

@section('title', 'Location Types')

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Location Types</h1>

    <button class="btn btn-primary mb-3" id="addType">Add Type</button>
    <button class="btn btn-danger mb-3" id="bulkDeleteTypes">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="locationTypesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('locations.types.modal')

</div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    // ------------------------------------
    // DataTable
    // ------------------------------------
    const table = $('#locationTypesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.location_types.list') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'description' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    // ------------------------------------
    // Add Type
    // ------------------------------------
    $('#addType').click(() => {
        $('#typeForm')[0].reset();
        $('#type_id').val('');
        $('#typeModalLabel').text('Add Location Type');
        $('#typeModal').modal('show');
    });

    // ------------------------------------
    // Save Type (Create/Update)
    // ------------------------------------
    $('#typeForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#type_id').val();
        const url = id ? `/admin/location_types/${id}` : '{{ route('admin.location_types.store') }}';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url,
            method,
            data: $(this).serialize(),
            success: res => {
                $('#typeModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: err => {
                Swal.fire('Error', err.responseJSON.message, 'error');
            }
        });
    });

    // ------------------------------------
    // Edit Type
    // ------------------------------------
    $('body').on('click', '.edit-location_type', function () {
        const id = $(this).data('id');

        $.get(`/admin/location_types/${id}/edit`, res => {
            $('#type_id').val(res.type.id);
            $('#type_name').val(res.type.name);
            $('#type_description').val(res.type.description);

            $('#typeModalLabel').text('Edit Location Type');
            $('#typeModal').modal('show');
        });
    });

    // ------------------------------------
    // Delete Single Type
    // ------------------------------------
    $('body').on('click', '.delete-location_type', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete this type?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/location_types/${id}`,
                    method: 'DELETE',
                    success: res => {
                        table.ajax.reload();
                        Swal.fire('Deleted!', res.message, 'success');
                    }
                });
            }
        });
    });

    // ------------------------------------
    // Select All
    // ------------------------------------
    $('#selectAll').on('click', function () {
        $('input[name="type_checkbox[]"]').prop('checked', this.checked);
    });

    // ------------------------------------
    // Bulk Delete
    // ------------------------------------
    $('#bulkDeleteTypes').on('click', function () {
        const ids = $('input[name="type_checkbox[]"]:checked')
            .map(function () { return this.value })
            .get();

        if (!ids.length) {
            return Swal.fire('Select at least one!', '', 'info');
        }

        Swal.fire({
            title: 'Delete selected?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('admin.location_types.bulk-delete') }}',
                    method: 'POST',
                    data: { ids, _token: '{{ csrf_token() }}' },
                    success: res => {
                        table.ajax.reload();
                        Swal.fire('Deleted', res.message, 'success');
                    }
                });
            }
        });
    });

</script>
@endpush