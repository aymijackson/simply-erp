@extends('layouts.master')

@section('title', 'Document Categories')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Document Categories</h1>
            <p class="mb-0 text-muted">Maintain category master data.</p>
        </div>

        @can('documents.categories.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
            <i class="fas fa-plus me-1"></i> New Category
        </button>
        @endcan
    </div>

    <div class="card shadow">
        <div class="card-body table-responsive">

            <table id="categoriesTable" class="table table-bordered table-hover w-100">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th width="140">Actions</th>
                    </tr>
                </thead>
            </table>

        </div>
    </div>
</div>

{{-- CREATE MODAL --}}
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="createCategoryForm" method="POST" action="{{ route('admin.document-categories.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input name="code" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" checked>
                    <label class="form-check-label">Active</label>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" type="submit">Create</button>
            </div>
        </form>
    </div>
</div>
{{-- EDIT MODAL --}}
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editCategoryForm" class="modal-content">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_id">

            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input id="edit_name" name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input id="edit_code" name="code" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea id="edit_description" name="description" class="form-control"></textarea>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="form-check-input">
                    <label class="form-check-label">Active</label>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" type="submit">Save changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {

    let table = $('#categoriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.document-categories.index') }}",
        columns: [
            { data: 'name', name: 'name' },
            { data: 'code', name: 'code' },
            { data: 'description', name: 'description' },
            {
                data: 'is_active',
                name: 'is_active',
                render: function (data) {
                    return data
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>';
                }
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ]
    });

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    // CREATE
    $('#createCategoryForm').on('submit', function (e) {
        e.preventDefault();

        $.post("{{ route('admin.document-categories.store') }}", $(this).serialize())
            .done(() => {
                $('#createCategoryModal').modal('hide');
                $('#createCategoryForm')[0].reset();
                table.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: 'Created',
                    text: 'Category created successfully'
                });
            })
            .fail(xhr => {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: Object.values(xhr.responseJSON.errors).join('<br>')
                });
            });
    });

    // OPEN EDIT MODAL
    $(document).on('click', '.editCategoryBtn', function () {
        let btn = $(this);

        $('#edit_id').val(btn.data('id'));
        $('#edit_name').val(btn.data('name'));
        $('#edit_code').val(btn.data('code'));
        $('#edit_description').val(btn.data('description'));

        let active = btn.data('active') == 1;
        $('#edit_is_active').prop('checked', active);

        $('#editCategoryModal').modal('show');
    });

    // UPDATE
    $('#editCategoryForm').on('submit', function (e) {
        e.preventDefault();

        let id = $('#edit_id').val();
        let url = "{{ route('admin.document-categories.update', ':id') }}".replace(':id', id);

        $.post(url, $(this).serialize())
            .done(() => {
                $('#editCategoryModal').modal('hide');
                table.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: 'Category updated successfully'
                });
            })
            .fail(xhr => {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: Object.values(xhr.responseJSON.errors).join('<br>')
                });
            });
    });

    // DELETE
    $(document).on('click', '.deleteCategoryBtn', function () {
        let id = $(this).data('id');
        let url = "{{ route('admin.document-categories.destroy', ':id') }}".replace(':id', id);

        Swal.fire({
            title: 'Delete Category?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Delete'
        }).then(result => {
            if (result.isConfirmed) {
                $.post(url, { _method: 'DELETE' })
                    .done(() => {
                        table.ajax.reload(null, false);

                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: 'Category deleted successfully'
                        });
                    })
                    .fail(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete category'
                        });
                    });
            }
        });
    });

});
</script>
@endpush