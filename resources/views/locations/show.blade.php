@extends('layouts.master')

@section('title', 'Location Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">{{ $location->name }} <small class="text-muted">({{ ucfirst($location->type->name) }})</small></h1>
        <button class="btn btn-outline-primary" data-toggle="modal" data-target="#editLocationModal">
            <i class="fas fa-edit mr-1"></i> Edit Location
        </button>
    </div>

    <ul class="nav nav-tabs shadow-sm" id="locationTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#blocks">Blocks</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#floors">Floors</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#rooms">Rooms</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#stores">Stores</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#shelves">Shelves</a></li>
    </ul>

    <div class="tab-content pt-4">
        @foreach(['blocks', 'floors', 'rooms', 'stores', 'shelves'] as $tab)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tab }}">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <table class="table table-hover table-bordered w-100" id="{{ $tab }}Table"></table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Edit Location Modal -->
    <div class="modal fade" id="editLocationModal" tabindex="-1" role="dialog" aria-labelledby="editLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="editLocationForm" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editLocationModalLabel">Edit Location</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="{{ $location->id }}">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ $location->name }}" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="warehouse" {{ $location->type == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                            <option value="office" {{ $location->type == 'office' ? 'selected' : '' }}>Office</option>
                            <option value="site" {{ $location->type == 'site' ? 'selected' : '' }}>Site</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3">{{ $location->address }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const locationId = {{ $location->id }};

$(function () {
    const tables = {
        blocks: [
            { data: 'name', title: 'Block Name' },
            { data: 'created_at', title: 'Created At' }
        ],
        floors: [
            { data: 'block_name', title: 'Block' },
            { data: 'name', title: 'Floor Name' }
        ],
        rooms: [
            { data: 'floor_name', title: 'Floor' },
            { data: 'name', title: 'Room Name' }
        ],
        stores: [
            { data: 'name', title: 'Store' },
            { data: 'room_name', title: 'Room' }
        ],
        shelves: [
            { data: 'code', title: 'Shelf Code' },
            { data: 'store_name', title: 'Store' }
        ]
    };

    for (const [key, columns] of Object.entries(tables)) {
        $(`#${key}Table`).DataTable({
            ajax: `/admin/locations/${locationId}/${key}`,
            columns: columns,
            responsive: true,
            language: {
                search: "",
                searchPlaceholder: "Search...",
                emptyTable: "No data available"
            },
            pageLength: 10,
            order: []
        });
    }

    $('#editLocationForm').on('submit', function(e) {
        e.preventDefault();
        const url = `/admin/locations/${locationId}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: $(this).serialize(),
            success: res => {
                $('#editLocationModal').modal('hide');
                Swal.fire('Success', res.message, 'success');
            },
            error: err => {
                Swal.fire('Error', err.responseJSON.message || 'Something went wrong', 'error');
            }
        });
    });
});
</script>
@endpush
