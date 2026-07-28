@extends('layouts.master')

@section('title', 'Manage Stores')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Stores</h1>
    <button class="btn btn-primary mb-3" id="addStore">Add Store</button>
    <button class="btn btn-danger mb-3" id="bulkDeleteStores">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="storesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Block</th>
                            <th>Floor</th>
                            <th>Room</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('locations.stores.modal')
</div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });


    const table = $('#storesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.location_stores.list') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name', title: 'Name' },
            { data: 'location', title: 'Location' },
            { data: 'block', title: 'Block' },
            { data: 'floor', title: 'Floor' },
            { data: 'room', title: 'Room' },
            { data: 'actions', orderable: false, searchable: false, title: 'Actions' }
        ]
    });

    $('#addStore').click(() => {
        $('#storeForm')[0].reset();
        $('#location_id').val('');
        $('#storeModalLabel').text('Add Store');
        $('#storeModal').modal('show');
    });

    $('#location_id').on('change', function () {
        const locationId = $(this).val();
        const roomSelect = $('#location_block_floor_room_id');
        roomSelect.empty().append('<option value="">Loading...</option>');

        if (locationId) {
            $.get(`/admin/locations/${locationId}/rooms`, function (response) {
                const rooms = response.data; // ✅ This line extracts the correct array
                roomSelect.empty().append('<option value="">Select Room</option>');
                rooms.forEach(room => {
                    roomSelect.append(`<option value="${room.id}">${room.name}</option>`);
                });
            });
        } else {
            roomSelect.empty().append('<option value="">Select Rooms</option>');
        }
    });

    $('#storeForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#store_id').val();
        const url = id ? `/admin/location_stores/${id}` : '{{ route('admin.location_stores.store') }}';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: $(this).serialize(),
            success: res => {
                $('#storeModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: err => Swal.fire('Error', err.responseJSON.message || 'An error occurred', 'error')
        });
    });

    // delegate EDIT click on the DataTable
    // delegate EDIT click on the DataTable
$('#storesTable').on('click', '.edit-store', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (!id) return Swal.fire('Error', 'No store ID found', 'error');

    // reset & set modal title
    $('#storeForm')[0].reset();
    $('#store_id').val(id);
    $('#storeModalLabel').text('Edit Store');

    // fetch the store record
    $.get(`/admin/location_stores/${id}/edit`)
        .done(store => {
            console.log('EDIT response:', store);

            $('#name').val(store.name);
            $('#location_id').val(store.location_id);

            const roomSelect = $('#location_block_floor_room_id').empty();

            // ---- NULL‑SAFE ROOM HANDLING ----
            if (store.room && store.room.id) {
                roomSelect.append(
                    `<option value="${store.room.id}">${store.room.name}</option>`
                );
                roomSelect.val(store.room.id);
            } else {
                // Provide a fallback option so Select2 doesn't break
                roomSelect.append(`<option value="">No room assigned</option>`);
                roomSelect.val('');
            }

            // show the modal
            $('#storeModal').modal('show');
        })
        .fail(() => {
            Swal.fire('Error', 'Could not load store data', 'error');
        });
});


    $('body').on('click', '.delete-store', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Delete this store?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })
        .then(res => {
            if (res.isConfirmed) {
                $.ajax({
                    url: `/admin/location_stores/${id}`,
                    method: 'DELETE',
                    success: r => { table.ajax.reload(); Swal.fire('Deleted!', r.message, 'success'); }
                });
            }
        });
    });

    $('#selectAll').on('click', function () {
        $('input[name="store_checkbox[]"]').prop('checked', this.checked);
    });

    $('#bulkDeleteStores').on('click', function () {
        const ids = $('input[name="store_checkbox[]"]:checked').map(function () { return this.value }).get();
        if (!ids.length) return Swal.fire('Select at least one!', '', 'info');

        Swal.fire({
            title: 'Delete selected store(s)?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('admin.location_stores.bulk-delete') }}',
                    method: 'POST',
                    data: { ids, _token: '{{ csrf_token() }}' },
                    success: res => { table.ajax.reload(); Swal.fire('Deleted', res.message, 'success'); }
                });
            }
        });
    });
    
</script>
@endpush