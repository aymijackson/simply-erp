@extends('layouts.master')

@section('title', 'Manage Shelves')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Shelves</h1>
    <button class="btn btn-primary mb-3" id="addShelf">Add Shelf</button>
    <button class="btn btn-danger mb-3" id="bulkDeleteShelves">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="shelvesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Store</th>
                            <th>Code</th>
                            <th>Capacity</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('locations.stores.shelves.modal')
</div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    const table = $('#shelvesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.store_shelves.list') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'store', title: 'Store' },
            { data: 'code', title: 'Code' },
            { data: 'capacity', title: 'Capacity' },
            { data: 'description', title: 'Description' },
            { data: 'actions', orderable: false, searchable: false, title: 'Actions' }
        ]
    });

    $('#addShelf').click(() => {
        $('#shelfForm')[0].reset();
        $('#shelf_id').val('');
        $('#shelfModalLabel').text('Add Shelf');
        $('#shelfModal').modal('show');
    });

    $('#shelfForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#shelf_id').val();
        const url = id ? `/admin/store_shelves/${id}` : '{{ route('admin.store_shelves.store') }}';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url,
            method,
            data: $(this).serialize(),
            success: res => {
                $('#shelfModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: err => Swal.fire('Error', err.responseJSON.message || 'An error occurred', 'error')
        });
    });

    $('body').on('click', '.edit-shelf', function () {
        const id = $(this).data('id');
        $.get(`/admin/store_shelves/${id}/edit`, res => {
            $('#shelf_id').val(res.id);
            $('#code').val(res.code);
            $('#store_id').val(res.store_id);
            $('#description').val(res.description);
            $('#capacity').val(res.capacity);
            $('#shelfModalLabel').text('Edit Shelf');
            $('#shelfModal').modal('show');
        });
    });

    $('body').on('click', '.delete-shelf', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Delete this shelf?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })
        .then(res => {
            if (res.isConfirmed) {
                $.ajax({
                    url: `/admin/store_shelves/${id}`,
                    method: 'DELETE',
                    success: r => { table.ajax.reload(); Swal.fire('Deleted!', r.message, 'success'); }
                });
            }
        });
    });

    $('#selectAll').on('click', function () {
        $('input[name="shelf_checkbox[]"]').prop('checked', this.checked);
    });

    $('#bulkDeleteShelves').on('click', function () {
        const ids = $('input[name="shelf_checkbox[]"]:checked').map(function () { return this.value }).get();
        if (!ids.length) return Swal.fire('Select at least one!', '', 'info');

        Swal.fire({
            title: 'Delete selected shelf(s)?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('admin.store_shelves.bulk-delete') }}',
                    method: 'POST',
                    data: { ids, _token: '{{ csrf_token() }}' },
                    success: res => { table.ajax.reload(); Swal.fire('Deleted', res.message, 'success'); }
                });
            }
        });
    });
</script>
@endpush
