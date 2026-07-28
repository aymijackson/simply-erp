@extends('layouts.master')

@section('title', 'Manage Shelves')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h1 class="h3 text-primary mb-1">Shelves</h1>
            <p class="text-muted mb-0">Manage shelves assigned to stores.</p>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" id="addShelf">
                <i class="fas fa-plus me-1"></i> Add Shelf
            </button>

            <button type="button" class="btn btn-danger" id="bulkDeleteShelves">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle w-100" id="shelvesTable">
                    <thead class="table-light">
                        <tr>
                            <th width="50" class="text-center">
                                <input type="checkbox" class="form-check-input m-0" id="selectAll">
                            </th>
                            <th>Store</th>
                            <th>Code</th>
                            <th>Capacity</th>
                            <th>Description</th>
                            <th width="120" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('locations.stores.shelves.modal')
</div>
@endsection

@push('styles')
<style>
    #shelvesTable {
        width: 100% !important;
        margin-bottom: 0 !important;
    }

    #shelvesTable th,
    #shelvesTable td {
        vertical-align: middle !important;
        white-space: nowrap;
    }

    #shelvesTable th:first-child,
    #shelvesTable td:first-child {
        width: 50px !important;
        min-width: 50px !important;
        max-width: 50px !important;
        text-align: center !important;
        overflow: visible !important;
        padding-left: .75rem !important;
        padding-right: .75rem !important;
    }

    #shelvesTable th:last-child,
    #shelvesTable td:last-child {
        width: 120px !important;
        min-width: 120px !important;
        text-align: center !important;
    }

    .dataTables_wrapper {
        width: 100%;
    }

    .table-responsive {
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
    }

    .card,
    .card-body,
    .container-fluid {
        overflow: visible !important;
    }

    .btn-group .btn {
        min-width: 40px;
    }

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
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const modalElement = document.getElementById('shelfModal');
    const shelfModal = modalElement ? new bootstrap.Modal(modalElement) : null;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    if ($.fn.select2) {
        $('#store_id').select2({
            dropdownParent: $('#shelfModal'),
            width: '100%',
            placeholder: 'Select Store',
            allowClear: true
        });
    }

    const table = $('#shelvesTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        scrollX: false,
        ajax: '{{ route("admin.store_shelves.list") }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false, width: '50px', className: 'text-center' },
            { data: 'store', title: 'Store' },
            { data: 'code', title: 'Code' },
            { data: 'capacity', title: 'Capacity' },
            { data: 'description', title: 'Description' },
            { data: 'actions', orderable: false, searchable: false, width: '120px', className: 'text-center', title: 'Actions' }
        ]
    });

    function resetForm() {
        $('#shelfForm')[0].reset();
        $('#shelf_id').val('');
        $('#shelfModalLabel').text('Add Shelf');

        if ($('#store_id').hasClass('select2-hidden-accessible')) {
            $('#store_id').val(null).trigger('change');
        } else {
            $('#store_id').val('');
        }
    }

    $('#addShelf').on('click', function () {
        resetForm();
        if (shelfModal) shelfModal.show();
    });

    $('#shelfForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#shelf_id').val();
        const url = id
            ? '{{ url("admin/store_shelves") }}/' + id
            : '{{ route("admin.store_shelves.store") }}';

        const method = id ? 'PUT' : 'POST';

        $('#saveShelfBtn')
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function (res) {
                $('#saveShelfBtn').prop('disabled', false).text('Save Shelf');
                if (shelfModal) shelfModal.hide();
                table.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: res.message || 'Shelf saved successfully.'
                });
            },
            error: function (xhr) {
                $('#saveShelfBtn').prop('disabled', false).text('Save Shelf');

                let msg = 'An error occurred.';
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

    $('body').on('click', '.edit-shelf', function () {
        const id = $(this).data('id');

        $.ajax({
            url: '{{ url("admin/store_shelves") }}/' + id + '/edit',
            type: 'GET',
            success: function (res) {
                const shelf = res.shelf || res.store_shelf || res.data || res;

                if (!shelf || !shelf.id) {
                    Swal.fire('Error', 'Invalid edit response returned from server.', 'error');
                    return;
                }

                resetForm();

                $('#shelf_id').val(shelf.id);
                $('#code').val(shelf.code || '');
                $('#capacity').val(shelf.capacity || '');
                $('#description').val(shelf.description || '');

                const storeId = String(shelf.store_id || '');
                const storeText =
                    shelf.store_name ||
                    shelf.store?.name ||
                    $('#store_id option[value="' + storeId + '"]').text() ||
                    'Selected Store';

                if ($('#store_id').hasClass('select2-hidden-accessible')) {
                    let optionExists = $('#store_id option[value="' + storeId + '"]').length > 0;

                    if (!optionExists) {
                        const newOption = new Option(storeText, storeId, true, true);
                        $('#store_id').append(newOption);
                    } else {
                        $('#store_id').val(storeId);
                    }

                    $('#store_id').trigger('change');
                } else {
                    $('#store_id').val(storeId);
                }

                $('#shelfModalLabel').text('Edit Shelf');
                if (shelfModal) shelfModal.show();
            },
            error: function (xhr) {
                let msg = 'Unable to load shelf details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    $('body').on('click', '.delete-shelf', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete this shelf?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("admin/store_shelves") }}/' + id,
                    type: 'DELETE',
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message || 'Shelf deleted successfully.', 'success');
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Delete failed.', 'error');
                    }
                });
            }
        });
    });

    $('#selectAll').on('change', function () {
        $('.row-checkbox').prop('checked', $(this).is(':checked'));
    });

    $('#shelvesTable').on('change', '.row-checkbox', function () {
        const total = $('.row-checkbox').length;
        const checked = $('.row-checkbox:checked').length;
        $('#selectAll').prop('checked', total > 0 && total === checked);
    });

    $('#shelvesTable').on('draw.dt', function () {
        $('#selectAll').prop('checked', false);
    });

    $('#bulkDeleteShelves').on('click', function () {
        const ids = $('input[name="shelf_checkbox[]"]:checked').map(function () {
            return this.value;
        }).get();

        if (!ids.length) {
            Swal.fire('Select at least one shelf!', '', 'info');
            return;
        }

        Swal.fire({
            title: 'Delete selected shelf(s)?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.store_shelves.bulk-delete") }}',
                    type: 'POST',
                    data: { ids: ids },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        $('#selectAll').prop('checked', false);
                        Swal.fire('Deleted', res.message || 'Selected shelves deleted successfully.', 'success');
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Bulk delete failed.', 'error');
                    }
                });
            }
        });
    });

    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function () {
            resetForm();
        });
    }
});
</script>
@endpush