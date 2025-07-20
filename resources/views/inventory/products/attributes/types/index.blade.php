@extends('layouts.master')

@section('title', 'Product Attribute Types')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Product Attribute Types</h1>
        <button class="btn btn-primary" id="addTypeBtn">
            <i class="fas fa-plus me-1"></i> Add Type
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="typesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="typeModal" tabindex="-1" aria-labelledby="typeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="typeForm">
            @csrf
            <input type="hidden" id="typeId" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="typeModalLabel">Add Attribute Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="typeName" class="form-label">Type Name</label>
                    <input type="text" id="typeName" name="name" class="form-control" required>
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
    const typeModalEl = document.getElementById('typeModal');
    const typeModal = new bootstrap.Modal(typeModalEl);

    const table = $('#typesTable').DataTable({
        ajax: '{{ route('admin.inventory.products.attributes.types.datatable') }}',
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#addTypeBtn').on('click', function () {
        $('#typeForm')[0].reset();
        $('#typeId').val('');
        $('#typeModalLabel').text('Add Attribute Type');
        typeModal.show();
    });

    $('#typesTable').on('click', '.edit-btn', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#typeId').val(id);
        $('#typeName').val(name);
        $('#typeModalLabel').text('Edit Attribute Type');
        typeModal.show();
    });

    $('#typesTable').on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will delete the attribute type permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/inventory/attributes/types/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: function () {
                        Swal.fire('Error', 'Could not delete item.', 'error');
                    }
                });
            }
        });
    });

    $('#typeForm').submit(function (e) {
        e.preventDefault();
        const id = $('#typeId').val();
        const url = id ? `/admin/inventory/products/attributes/types/${id}` : `{{ route('admin.inventory.products.attributes.types.store') }}`;
        const formData = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function (res) {
                table.ajax.reload(null, false);
                typeModal.hide();
                Swal.fire('Success', res.message, 'success');
            },
            error: function (xhr) {
                Swal.fire('Error', 'Failed to save attribute type.', 'error');
            }
        });
    });
});
</script>
@endpush
