@extends('layouts.master')

@section('title', 'Manage Units')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">Units <small class="text-muted">Inventory</small></h1>
        <div>
            <button class="btn btn-danger me-2" id="bulkDeleteBtn" style="display: none;">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addUnitBtn">
                <i class="fas fa-plus me-1"></i> Add Unit
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div>
                        <h6>Total Units</h6>
                        <h4 class="mb-0" id="totalUnits">{{ number_format($units_count ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered w-100" id="unitTable">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="selectAllUnits"></th>
                            <th>Name</th>
                            <th>Symbol</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="unitModal" tabindex="-1" aria-labelledby="unitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="unitForm" class="modal-content">
            @csrf
            <input type="hidden" id="unitId">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="unitModalLabel">Add Unit</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="unitName" class="form-label">Unit Name</label>
                    <input type="text" class="form-control" id="unitName" required>
                </div>
                <div class="mb-3">
                    <label for="unitSymbol" class="form-label">Symbol</label>
                    <input type="text" class="form-control" id="unitSymbol">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Unit</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#unitTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.inventory.products.units.datatable') }}",
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'symbol' },
            { data: 'action', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            $.get("{{ route('admin.inventory.products.units.metrics') }}", function (res) {
                $('#totalUnits').text(res.total);
            });
        }
    });

    // Select All
    $('#selectAllUnits').on('change', function () {
        $('.unit-checkbox').prop('checked', this.checked);
        toggleBulkDeleteBtn();
    });

    $(document).on('change', '.unit-checkbox', function () {
        toggleBulkDeleteBtn();
    });

    function toggleBulkDeleteBtn() {
        $('#bulkDeleteBtn').toggle($('.unit-checkbox:checked').length > 0);
    }

    // Add Unit
    $('#addUnitBtn').click(function () {
        $('#unitForm')[0].reset();
        $('#unitId').val('');
        $('#unitModalLabel').text('Add Unit');
        $('#unitModal').modal('show');
    });

    // Edit Unit
    $('#unitTable').on('click', '.edit', function () {
        const row = $(this).closest('tr');
        const data = table.row(row).data();
        $('#unitId').val(data.id);
        $('#unitName').val(data.name);
        $('#unitSymbol').val(data.symbol);
        $('#unitModalLabel').text('Edit Unit');
        $('#unitModal').modal('show');
    });

    // Save Unit
    $('#unitForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#unitId').val();
        const formData = {
            name: $('#unitName').val(),
            symbol: $('#unitSymbol').val(),
            _token: '{{ csrf_token() }}'
        };
        const url = id 
            ? `{{ url('admin/inventory/products/units') }}/${id}` 
            : `{{ route('admin.inventory.products.units.store') }}`;

        $.ajax({
            url: url,
            type: id ? 'PUT' : 'POST',
            data: formData,
            success: function (res) {
                $('#unitModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', res.message, 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save unit.', 'error');
            }
        });
    });

    // Delete Unit
    $('#unitTable').on('click', '.delete', function () {
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
                    url: `{{ url('admin/inventory/products/units') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', res.message, 'success');
                    }
                });
            }
        });
    });

    // Bulk Delete
    $('#bulkDeleteBtn').on('click', function () {
        const ids = $('.unit-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) return;

        Swal.fire({
            title: `Delete ${ids.length} selected unit(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                $.post("{{ route('admin.inventory.products.units.bulk-delete') }}", {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                }, function (res) {
                    table.ajax.reload();
                    $('#selectAllUnits').prop('checked', false);
                    $('#bulkDeleteBtn').hide();
                    Swal.fire('Deleted!', res.message, 'success');
                }).fail(() => {
                    Swal.fire('Error', 'Failed to delete units.', 'error');
                });
            }
        });
    });
});
</script>
@endpush
