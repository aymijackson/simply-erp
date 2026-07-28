@extends('layouts.master')

@section('title', 'Manage Rooms')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Rooms</h1>
    <button class="btn btn-primary mb-3" id="addRoom">Add Room</button>
    <button class="btn btn-danger mb-3" id="bulkDeleteRooms">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="roomsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Block</th>
                            <th>Floor</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('locations.blocks.floors.rooms.modal')
</div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });


    const table = $('#roomsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.location_rooms.list') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name', title: 'Name' },
            { data: 'location', title: 'Location' },
            { data: 'block', title: 'Block' },
            { data: 'floor', title: 'Floor' },
            { data: 'actions', orderable: false, searchable: false, title: 'Actions' }
        ]
    });

    $('#addRoom').click(() => {
        $('#roomForm')[0].reset();
        $('#room_id').val('');
        $('#roomModalLabel').text('Add Room');
        $('#roomModal').modal('show');
    });
    
    $('#roomForm').on('submit', function(e) {
        e.preventDefault();
    
        const id = $('#room_id').val(); // FIXED
        const url = id 
            ? `/admin/location_rooms/${id}` 
            : '{{ route('admin.location_rooms.store') }}';
    
        const method = id ? 'PUT' : 'POST';
    
        $.ajax({
            url,
            method,
            data: $(this).serialize(),
            success: res => {
                $('#roomModal').modal('hide'); // FIXED
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: err => Swal.fire('Error', err.responseJSON.message || 'An error occurred', 'error')
        });
    });
    
    $('body').on('click', '.edit-room', function () { // FIXED
        const id = $(this).data('id');
    
        $.get(`/admin/location_rooms/${id}/edit`, res => {
            const l = res.location_block_floor_room; // FIXED
    
            $('#room_id').val(l.id);
            $('#name').val(l.name);
    
            $('#roomModalLabel').text('Edit Room'); // FIXED
            $('#roomModal').modal('show');
        });
    });

    $('body').on('click', '.delete-room', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Delete this room?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })
        .then(res => {
            if (res.isConfirmed) {
                $.ajax({
                    url: `/admin/location_rooms/${id}`,
                    method: 'DELETE',
                    success: r => { table.ajax.reload(); Swal.fire('Deleted!', r.message, 'success'); }
                });
            }
        });
    });

    $('#selectAll').on('click', function () {
        $('input[name="room_checkbox[]"]').prop('checked', this.checked);
    });

    $('#bulkDeleteRooms').on('click', function () {
        const ids = $('input[name="room_checkbox[]"]:checked').map(function () { return this.value }).get();
        if (!ids.length) return Swal.fire('Select at least one!', '', 'info');

        Swal.fire({
            title: 'Delete selected room(s)?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('admin.location_rooms.bulk-delete') }}',
                    method: 'POST',
                    data: { ids, _token: '{{ csrf_token() }}' },
                    success: res => { table.ajax.reload(); Swal.fire('Deleted', res.message, 'success'); }
                });
            }
        });
    });
    
</script>
@endpush