@extends('layouts.master')

@section('title', 'Location Type Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">{{ $location_type->name }} <small class="text-muted">Type Overview</small></h1>
        <a href="{{ route('admin.location_types.edit', $location_type->id) }}" class="btn btn-outline-primary">
            <i class="fas fa-edit mr-1"></i> Edit Type
        </a>
    </div>

    <ul class="nav nav-tabs shadow-sm" id="locationTypeTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#locations">Locations</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#blocks">Blocks</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#floors">Floors</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#rooms">Rooms</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#stores">Stores</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#shelves">Shelves</a></li>
    </ul>

    <div class="tab-content pt-4">
        @foreach(['locations', 'blocks', 'floors', 'rooms', 'stores', 'shelves'] as $tab)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tab }}">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <table class="table table-hover table-bordered w-100" id="{{ $tab }}Table"></table>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
const locationTypeId = {{ $location_type->id }};

$(function () {
    const tables = {
        locations: [
            { data: 'name', title: 'Name' },
            { data: 'type', title: 'Type', render: data => data },
            { data: 'city', title: 'City' },
            { data: 'company', title: 'Company' },
            { data: 'coordinates', title: 'Coordinates' },
            { data: 'actions', title: 'Actions', orderable: false, searchable: false }
        ],
        blocks: [
            { data: 'location', title: 'Location' },
            { data: 'name', title: 'Block Name' },
            { data: 'created_at', title: 'Created At' }
        ],
        floors: [
            { data: 'location', title: 'Location' },
            { data: 'block', title: 'Block' },
            { data: 'name', title: 'Floor Name' }
        ],
        rooms: [
            { data: 'location', title: 'Location' },
            { data: 'floor', title: 'Floor' },
            { data: 'name', title: 'Room Name' }
        ],
        stores: [
            { data: 'location', title: 'Location' },
            { data: 'name', title: 'Store' },
            { data: 'room', title: 'Room' }
        ],
        shelves: [
            { data: 'location', title: 'Location' },
            { data: 'store', title: 'Store' },
            { data: 'code', title: 'Shelf Code' }
        ]
    };

    for (const [key, columns] of Object.entries(tables)) {
        $(`#${key}Table`).DataTable({
            ajax: `/admin/location_types/${locationTypeId}/${key}`,
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
});
</script>
@endpush
