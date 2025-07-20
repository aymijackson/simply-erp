@extends('layouts.master')

@section('title', 'Raw Materials Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Raw Materials</h1>
        <button class="btn btn-primary" id="addRawMaterialBtn">
            <i class="fas fa-plus me-1"></i> Add Raw Material
        </button>
        
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="rawMaterialsTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Unit</th>
                        <th>Cost</th>
                        <th>Stock</th>
                        <th>Restock Level</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button class="btn btn-danger mt-2" id="bulkDeleteBtn">Delete Selected</button>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="rawMaterialModal" tabindex="-1" aria-labelledby="rawMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="rawMaterialForm">
            @csrf
            <input type="hidden" name="id" id="raw_material_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="rawMaterialModalLabel">Add Raw Material</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" id="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" id="code" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" id="description"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit</label>
                    <select name="unit_id" id="unit_id" class="form-control" required>
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cost</label>
                    <input type="number" name="cost" class="form-control" id="cost" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock Quantity</label>
                    <input type="number" name="stock_quantity" class="form-control" id="stock_quantity" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Restock Level</label>
                    <input type="number" name="restock_level" class="form-control" id="restock_level">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = new bootstrap.Modal(document.getElementById('rawMaterialModal'));
    const table = $('#rawMaterialsTable').DataTable({
        responsive: true,
        processing: true,
        ajax: '{{ route("admin.production.raw-materials.datatable") }}',
        columns: [
            { data: 'checkbox', orderable: false },
            { data: 'name' },
            { data: 'code' },
            { data: 'description' },
            { data: 'unit' },
            { data: 'cost' },
            { data: 'stock_quantity' },
            { data: 'restock_level' },
            { data: 'created_at' },
            { data: 'actions', orderable: false }
        ]
    });

    $('#addRawMaterialBtn').on('click', function () {
        $('#rawMaterialForm')[0].reset();
        $('#raw_material_id').val('');
        $('#rawMaterialModalLabel').text('Add Raw Material');
        modal.show();
    });

    $('#rawMaterialForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#raw_material_id').val();
        const url = id ? `/admin/production/raw-materials/${id}` : '{{ route("admin.production.raw-materials.store") }}';
        const method = id ? 'PUT' : 'POST';
        const data = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.post(url, data, function (res) {
            modal.hide();
            table.ajax.reload(null, false);
            Swal.fire('Success', res.message, 'success');
        }).fail(err => {
            Swal.fire('Error', err.responseJSON?.message || 'Something went wrong.', 'error');
        });
    });

    $('#bulkDeleteBtn').on('click', function () {
        const selected = $('.row-checkbox:checked').map(function(){ return $(this).val(); }).get();
        if (!selected.length) return Swal.fire('Warning', 'No items selected.', 'warning');

        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete selected!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('admin.production.raw-materials.bulk-delete') }}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}', ids: selected },
                    success: res => {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: err => {
                        Swal.fire('Error', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.edit-raw-material', function () {
        const data = $(this).data('record');
        $('#raw_material_id').val(data.id);
        $('#name').val(data.name);
        $('#code').val(data.code);
        $('#description').val(data.description);
        $('#unit_id').val(data.unit_id);
        $('#cost').val(data.cost);
        $('#stock_quantity').val(data.stock_quantity);
        $('#restock_level').val(data.restock_level);
        $('#rawMaterialModalLabel').text('Edit Raw Material');
        modal.show();
    });

    $(document).on('click', '.delete-raw-material', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will delete the raw material permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/production/raw-materials/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    }
                });
            }
        });
    });

    
    $('#selectAll').on('change', function () {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });
});
</script>
@endpush
