@extends('layouts.master')

@section('title', 'Manage Users')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
@endpush

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Users Management</h5>
        <div>
            <button id="bulkDeleteBtn" class="btn btn-danger btn-sm me-2">Delete Selected</button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                Add New User
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Permissions</th>
                        <th>Modules</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include('users.modals.add')
@include('users.modals.edit')
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(function() {
    const table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.users.list") }}',
        dom: 'Bfrtip',
        buttons: ['excel', 'pdf', 'print'],
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'email' },
            { data: 'roles', orderable: false },
            { data: 'permissions', orderable: false },
            { data: 'modules', orderable: false },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#select-all').on('click', function() {
        $('input[name="ids[]"]').prop('checked', this.checked);
    });

    $('#bulkDeleteBtn').on('click', function() {
        const ids = $('input[name="ids[]"]:checked').map(function() { return this.value; }).get();
        if (!ids.length) {
            return Swal.fire('Warning', 'No users selected.', 'warning');
        }
        Swal.fire({
            title: 'Delete selected users?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete!'
        }).then(({ isConfirmed }) => {
            if (!isConfirmed) return;
            $.ajax({
                url: '{{ route("admin.users.bulkDelete") }}',
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}', ids },
                success: () => {
                    Swal.fire('Deleted', 'Users deleted!', 'success');
                    table.ajax.reload(null, false);
                },
                error: () => Swal.fire('Error', 'Bulk delete failed.', 'error')
            });
        });
    });

    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        $.post('{{ route("admin.users.store") }}', $(this).serialize())
            .done(res => {
                $('#addUserModal').modal('hide');
                Swal.fire('Created', res.message, 'success');
                table.ajax.reload(null, false);
                this.reset();
            })
            .fail(xhr => {
                const errs = xhr.responseJSON?.errors;
                Swal.fire('Error', errs ? Object.values(errs).join('<br/>') : 'Server error', 'error');
            });
    });

    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        $.get(`/admin/users/${id}/edit`, function(data) {
            $('#editUserId').val(data.user.id);
            $('#editUserName').val(data.user.name);
            $('#editUserEmail').val(data.user.email);

            $('input[name="roles[]"]').prop('checked', false);
            data.userRoleIds.forEach(rid => $(`#editRole_${rid}`).prop('checked', true));

            $('input[name="permissions[]"]').prop('checked', false);
            data.userPermIds.forEach(pid => $(`#editPerm_${pid}`).prop('checked', true));

            $('input[name="modules[]"]').prop('checked', false);
            data.userModuleIds.forEach(mid => $(`#editModule_${mid}`).prop('checked', true));

            $('#editUserModal').modal('show');
        });
    });

    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#editUserId').val();
        $.ajax({
            url: `/admin/users/${id}`,
            type: 'PUT',
            data: $(this).serialize(),
            success: res => {
                $('#editUserModal').modal('hide');
                Swal.fire('Updated', res.message, 'success');
                table.ajax.reload(null, false);
            },
            error: xhr => {
                const errs = xhr.responseJSON?.errors;
                Swal.fire('Error', errs ? Object.values(errs).join('<br/>') : 'Server error', 'error');
            }
        });
    });

    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete this user?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete!'
        }).then(({ isConfirmed }) => {
            if (!isConfirmed) return;
            $.ajax({
                url: `/admin/users/${id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' }
            }).done(res => {
                Swal.fire('Deleted', res.message, 'success');
                table.ajax.reload(null, false);
            }).fail(() => Swal.fire('Error', 'Delete failed', 'error'));
        });
    });
});
</script>
@endpush