@extends('layouts.master')

@section('title', 'Manage Floors')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Floors</h1>
    <button class="btn btn-primary mb-3" id="addFloor">Add Floor</button>
    <button class="btn btn-danger mb-3" id="bulkDeleteFloors">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="floorsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Block</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('locations.blocks.floors.modal')
</div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });


    const table = $('#floorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.location_floors.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name', title: 'Name' },
            { data: 'location', title: 'Location' },
            { data: 'block', title: 'Block' },
            { data: 'actions', orderable: false, searchable: false, title: 'Actions' }
        ]
    });

    $('#addFloor').click(() => {
        $('#floorForm')[0].reset();
        $('#block_id').val('');
        $('#floorModalLabel').text('Add Floor');
        $('#floorModal').modal('show');
    });

    $('#floorForm').on('submit', function(e) {
        e.preventDefault();
    
        const id = $('#floor_id').val(); // FIXED
        const url = id 
            ? `/admin/location_floors/${id}` 
            : '{{ route('admin.location_floors.store') }}';
    
        const method = id ? 'PUT' : 'POST';
    
        $.ajax({
            url,
            method,
            data: $(this).serialize(),
            success: res => {
                $('#floorModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: err => Swal.fire('Error', err.responseJSON.message || 'An error occurred', 'error')
        });
    });
    
    $('body').on('click', '.edit-floor', function () {
        const id = $(this).data('id');
    
        $.get(`/admin/location_floors/${id}/fetch`, res => {
            const l = res.floor; // FIXED
    
            $('#floor_id').val(l.id);
            $('#name').val(l.name);
    
            $('#floorModalLabel').text('Edit Floor');
            $('#floorModal').modal('show');
        });
    });

    $('body').on('click', '.delete-floor', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Delete this floor?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })
        .then(res => {
            if (res.isConfirmed) {
                $.ajax({
                    url: `/admin/location_floors/${id}`,
                    method: 'DELETE',
                    success: r => { table.ajax.reload(); Swal.fire('Deleted!', r.message, 'success'); }
                });
            }
        });
    });

    $('#selectAll').on('click', function () {
        $('input[name="floor_checkbox[]"]').prop('checked', this.checked);
    });

    $('#bulkDeleteFloors').on('click', function () {
        const ids = $('input[name="floor_checkbox[]"]:checked').map(function () { return this.value }).get();
        if (!ids.length) return Swal.fire('Select at least one!', '', 'info');

        Swal.fire({
            title: 'Delete selected floor(s)?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('admin.location_floors.bulk-delete') }}',
                    method: 'POST',
                    data: { ids, _token: '{{ csrf_token() }}' },
                    success: res => { table.ajax.reload(); Swal.fire('Deleted', res.message, 'success'); }
                });
            }
        });
    });
    
</script>
@endpush