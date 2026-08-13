@extends('layouts.master')

@section('title', 'Manage Manufacturers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">Manufacturers <small class="text-muted">Inventory</small></h1>
        <div>
            <button class="btn btn-danger me-2 d-none" id="deleteSelectedBtn">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addManufacturerBtn">
                <i class="fas fa-plus me-1"></i> Add Manufacturer
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h6>Total Manufacturers</h6>
                        <h4 class="mb-0" id="totalManufacturers">{{ number_format($manufacturers_count ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered w-100" id="manufacturerTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Manufacturer Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody> 
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Manufacturer Modal -->
<div class="modal fade" id="manufacturerModal" tabindex="-1" aria-labelledby="manufacturerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="manufacturerForm" class="modal-content">
            @csrf
            <input type="hidden" id="manufacturerId">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="manufacturerModalLabel">Add Manufacturer</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="manufacturerName" class="form-label">Manufacturer Name</label>
                    <input type="text" class="form-control" id="manufacturerName" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Manufacturer</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    let table = $('#manufacturerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.inventory.products.manufacturers.datatable') }}",
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'manufacturer_name' },
            { data: 'action', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            $.get("{{ route('admin.inventory.products.manufacturers.metrics') }}", function (response) {
                $('#totalManufacturers').text(response.total);
            });
        }
    });

    $('#addManufacturerBtn').click(function () {
        $('#manufacturerForm')[0].reset();
        $('#manufacturerId').val('');
        $('#manufacturerModalLabel').text('Add Manufacturer');
        $('#manufacturerModal').modal('show');
    });

    $('#manufacturerTable').on('click', '.edit', function () {
        let id = $(this).data('id');
        let name = $(this).data('name');
        $('#manufacturerId').val(id);
        $('#manufacturerName').val(name);
        $('#manufacturerModalLabel').text('Edit Manufacturer');
        $('#manufacturerModal').modal('show');
    });

    $('#manufacturerForm').on('submit', function (e) {
        e.preventDefault();
        const manufacturerId = $('#manufacturerId').val();
        const formData = {
            manufacturer_name: $('#manufacturerName').val(),
            _token: '{{ csrf_token() }}'
        };

        const url = manufacturerId 
            ? `{{ url('admin/inventory/products/manufacturers') }}/${manufacturerId}`
            : `{{ route('admin.inventory.products.manufacturers.store') }}`;

        $.ajax({
            url: url,
            type: manufacturerId ? 'PUT' : 'POST',
            data: formData,
            success: function (response) {
                $('#manufacturerModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', response.message, 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save manufacturer.', 'error');
            }
        });
    });

    $('#manufacturerTable').on('click', '.delete', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/inventory/products/manufacturers') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    }
                });
            }
        });
    });

    // Select All
    $('#selectAll').on('click', function () {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        toggleDeleteSelectedBtn();
    });

    $('#manufacturerTable').on('change', '.row-checkbox', function () {
        toggleDeleteSelectedBtn();
    });

    function toggleDeleteSelectedBtn() {
        let anyChecked = $('.row-checkbox:checked').length > 0;
        $('#deleteSelectedBtn').toggleClass('d-none', !anyChecked);
    }

    // Bulk Delete
    $('#deleteSelectedBtn').click(function () {
        const ids = $('.row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (ids.length === 0) return;

        Swal.fire({
            title: `Delete ${ids.length} selected manufacturer(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                $.post("{{ route('admin.inventory.products.manufacturers.bulk-delete') }}", {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                }, function (response) {
                    table.ajax.reload();
                    $('#selectAll').prop('checked', false);
                    $('#deleteSelectedBtn').addClass('d-none');
                    Swal.fire('Deleted!', response.message, 'success');
                }).fail(function () {
                    Swal.fire('Error', 'Failed to delete selected manufacturers.', 'error');
                });
            }
        });
    });
});
</script>
@endpush
