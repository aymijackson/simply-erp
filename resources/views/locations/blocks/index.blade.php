@extends('layouts.master')

@section('title', 'Location Blocks')

@section('content')
<div class="container-fluid">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h1 class="h3 text-primary mb-1">Location Blocks</h1>
            <p class="text-muted mb-0">Manage blocks under each location.</p>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" id="addBlock">
                <i class="fas fa-plus me-1"></i> Add Block
            </button>

            <button type="button" class="btn btn-danger" id="bulkDeleteBlocks">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle w-100" id="locationBlocksTable">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Name</th>
                            <th>Location</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="locationForm" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">Add Location Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id" id="block_id">

                <div class="mb-3">
                    <label for="name" class="form-label">Block Name</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="location_id" class="form-label">Location</label>
                    <select name="location_id" id="location_id" class="form-select" required>
                        <option value="">Select Location</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveBlockBtn">Save Block</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        min-height: 38px !important;
        border: 1px solid #ced4da !important;
        border-radius: .375rem !important;
        padding: .375rem .75rem !important;
        display: flex !important;
        align-items: center !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #212529 !important;
        line-height: 1.5 !important;
        padding-left: 0 !important;
        padding-right: 20px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        right: 8px !important;
    }

    .select2-dropdown {
        z-index: 9999 !important;
    }

    #locationBlocksTable {
        width: 100% !important;
        margin-bottom: 0 !important;
    }

    #locationBlocksTable th,
    #locationBlocksTable td {
        vertical-align: middle !important;
        white-space: nowrap;
    }

    #locationBlocksTable th:first-child,
    #locationBlocksTable td:first-child {
        width: 50px !important;
        min-width: 50px !important;
        max-width: 50px !important;
        text-align: center !important;
        overflow: visible !important;
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }

    #locationBlocksTable th:last-child,
    #locationBlocksTable td:last-child {
        width: 120px !important;
        min-width: 120px !important;
        text-align: center !important;
    }

    .dataTables_wrapper {
        width: 100%;
    }

    .dataTables_wrapper .dataTables_scroll {
        overflow: visible !important;
    }

    .dataTables_wrapper .dataTables_scrollHead,
    .dataTables_wrapper .dataTables_scrollBody {
        overflow: visible !important;
    }

    .table-responsive {
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
    }

    .card-body {
        overflow: visible !important;
    }

    .btn-group .btn {
        min-width: 40px;
    }
</style>
@endpush
@push('scripts')
<script>
$(document).ready(function () {
    const modalElement = document.getElementById('locationModal');
    const locationModal = new bootstrap.Modal(modalElement);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    if ($.fn.select2) {
        $('#location_id').select2({
            dropdownParent: $('#locationModal'),
            width: '100%',
            placeholder: 'Select Location',
            allowClear: true
        });
    }

    const table = $('#locationBlocksTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.location-blocks.datatable") }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'location', name: 'location', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    function resetForm() {
        $('#locationForm')[0].reset();
        $('#block_id').val('');
        $('#locationModalLabel').text('Add Location Block');

        if ($('#location_id').hasClass('select2-hidden-accessible')) {
            $('#location_id').val(null).trigger('change');
        } else {
            $('#location_id').val('');
        }
    }

    $('#addBlock').on('click', function () {
        resetForm();
        locationModal.show();
    });

    $('#locationForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#block_id').val();
        const url = id
            ? '{{ url("admin/location-blocks") }}/' + id
            : '{{ route("admin.location-blocks.store") }}';

        const method = id ? 'PUT' : 'POST';

        $('#saveBlockBtn')
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function (res) {
                $('#saveBlockBtn').prop('disabled', false).text('Save Block');
                locationModal.hide();
                table.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: res.message || 'Saved successfully.'
                });
            },
            error: function (xhr) {
                $('#saveBlockBtn').prop('disabled', false).text('Save Block');

                let msg = 'Something went wrong.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: msg
                });
            }
        });
    });

    $('body').on('click', '.edit-block', function () {
        const id = $(this).data('id');

        $.ajax({
            url: '{{ url("admin/location-blocks") }}/' + id + '/edit',
            type: 'GET',
            success: function (res) {
                const block = res.location_block || res.block || res.data || res;

                if (!block || !block.id) {
                    Swal.fire('Error', 'Invalid edit response returned from server.', 'error');
                    return;
                }

                resetForm();

                $('#block_id').val(block.id);
                $('#name').val(block.name || '');

                const locationId = String(block.location_id || '');
                const locationText =
                    block.location_name ||
                    block.location?.name ||
                    $('#location_id option[value="' + locationId + '"]').text() ||
                    'Selected Location';

                if ($('#location_id').hasClass('select2-hidden-accessible')) {
                    let optionExists = $('#location_id option[value="' + locationId + '"]').length > 0;

                    if (!optionExists) {
                        const newOption = new Option(locationText, locationId, true, true);
                        $('#location_id').append(newOption);
                    } else {
                        $('#location_id').val(locationId);
                    }

                    $('#location_id').trigger('change');
                } else {
                    $('#location_id').val(locationId);
                }

                $('#locationModalLabel').text('Edit Location Block');
                locationModal.show();
            },
            error: function (xhr) {
                let msg = 'Unable to load block details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    $('body').on('click', '.delete-block', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete this block?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("admin/location-blocks") }}/' + id,
                    type: 'DELETE',
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted', res.message, 'success');
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Delete failed.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    });

    $('#selectAll').on('change', function () {
        $('input[name="location_block_checkbox[]"]').prop('checked', this.checked);
    });

    $('#locationBlocksTable').on('draw.dt', function () {
        $('#selectAll').prop('checked', false);
    });

    $('#bulkDeleteBlocks').on('click', function () {
        const ids = $('input[name="location_block_checkbox[]"]:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) {
            Swal.fire('No selection', 'Select at least one block.', 'info');
            return;
        }

        Swal.fire({
            title: 'Delete selected blocks?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete selected'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.location-blocks.bulk-delete") }}',
                    type: 'POST',
                    data: { ids: ids },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        $('#selectAll').prop('checked', false);
                        Swal.fire('Deleted', res.message, 'success');
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Bulk delete failed.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });
});
</script>
@endpush