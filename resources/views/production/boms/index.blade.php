@extends('layouts.master')

@section('title', 'Bill of Materials Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Bill of Materials</h1>
        <button class="btn btn-primary" id="addBillOfMaterialBtn">
            <i class="fas fa-plus me-1"></i> Add Bill of Material
        </button>
        
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="billOfMaterialsTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Product</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Notes</th>
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
<div class="modal fade" id="billOfMaterialModal" tabindex="-1" aria-labelledby="billOfMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="billOfMaterialForm">
            @csrf
            <input type="hidden" name="id" id="bill_of_material_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="billOfMaterialModalLabel">Add Bill of Material</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
            <div class="mb-3">
                    <label class="form-label">Product</label>
                    <select name="product_id" id="product_id" class="form-control" required>
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Version</label>
                    <input type="text" name="version" class="form-control" id="version" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <textarea name="status" class="form-control" id="status"></textarea>
                </div>                
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" id="notes" required></textarea>
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
    const modal = new bootstrap.Modal(document.getElementById('billOfMaterialModal'));
    const table = $('#billOfMaterialsTable').DataTable({
        responsive: true,
        processing: true,
        ajax: '{{ route("admin.production.boms.datatable") }}',
        columns: [
            { data: 'checkbox', orderable: false },
            { data: 'product' },
            { data: 'version' },
            { data: 'status' },
            { data: 'notes' },
            { data: 'created_at' },
            { data: 'actions', orderable: false }
        ]
    });

    $('#addBillOfMaterialBtn').on('click', function () {
        $('#billOfMaterialForm')[0].reset();
        $('#bill_of_material_id').val('');
        $('#billOfMaterialModalLabel').text('Add Bill of Material');
        modal.show();
    });

    $('#billOfMaterialForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#bill_of_material_id').val();
        const url = id ? `/admin/production/boms/${id}` : '{{ route("admin.production.boms.store") }}';
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
                    url: `{{ route('admin.production.boms.bulk-delete') }}`,
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

    $(document).on('click', '.edit-bill-of-material', function () {
        const data = $(this).data('record');
        $('#bill_of_material_id').val(data.id);
        $('#product_id').val(data.product_id);
        $('#version').val(data.version);
        $('#status').val(data.status);
        $('#notes').text(data.notes);
        modal.show();
    });

    $(document).on('click', '.delete-bill-of-material', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will delete the bill of material permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/production/boms/${id}`,
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
