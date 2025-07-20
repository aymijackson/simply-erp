@extends('layouts.master')

@section('title', 'Manage Regions')

@section('content')
<div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Regions</h1>
        <button class="btn btn-primary mb-3" id="createRegion">Add Region</button>
        <button id="bulkDeleteRegions" class="btn btn-danger mb-3">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="regionsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('regions.modal')
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
        const table = $('#regionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.regions.list') }}',
            columns: [
                { data: 'id', render: id => `<input type="checkbox" name="region_checkbox[]" value="${id}">`, orderable: false, searchable: false },
                { data: 'name' },
                { data: 'actions', orderable: false, searchable: false },
            ]
        });

        $('#createRegion').on('click', function () {
            $('#regionForm')[0].reset();
            $('#region_id').val('');
            $('#regionModalLabel').text('Add Region');
            $('#regionModal').modal('show');
        });

        $('#regionForm').on('submit', function (e) {
            e.preventDefault();
            const id = $('#region_id').val();
            const url = id ? `/admin/regions/${id}` : '{{ route('admin.regions.store') }}';
            const type = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: type,
                data: $(this).serialize(),
                success: function (response) {
                    $('#regionModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#regionForm')[0].reset();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                }
            });
        });

        $('body').on('click', '.edit-region', function () {
            const id = $(this).data('id');
            $.get(`/admin/regions/${id}/edit`, function (res) {
                const r = res.region;
                $('#region_id').val(r.id);
                $('#region_name').val(r.name);
                $('#regionModalLabel').text('Edit Region');
                $('#regionModal').modal('show');
            });
        });

        $('body').on('click', '.delete-region', function () {
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
                        url: `/admin/regions/${id}`,
                        method: 'DELETE',
                        success: function (res) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', res.message, 'success');
                        },
                        error: function () {
                            Swal.fire('Error', 'Failed to delete region.', 'error');
                        }
                    });
                }
            });
        });
    });

    $('#selectAll').on('click', function () {
        $('input[name="region_checkbox[]"]').prop('checked', this.checked);
    });

    $('#bulkDeleteRegions').on('click', function () {
        const ids = $('input[name="region_checkbox[]"]:checked').map(function() { return this.value }).get();
        if (ids.length === 0) {
            return Swal.fire('Select at least one!', '', 'info');
        }
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will delete selected regions.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('admin.regions.bulk-delete') }}',
                    method: 'DELETE',
                    data: { ids, _token: '{{ csrf_token() }}' },
                    success: res => { table.ajax.reload(); Swal.fire('Deleted', res.message, 'success'); },
                    error: () => Swal.fire('Error', 'Failed to delete.', 'error')
                });
            }
        });
    });
</script>
@endpush
