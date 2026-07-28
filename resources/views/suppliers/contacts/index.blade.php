@extends('layouts.master')

@section('title', 'All Supplier Contacts')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container">
    <h3 class="mb-4">All Supplier Contacts</h3>

    <button class="btn btn-primary mb-3" id="addContactBtn">Add New Contact</button>
    <button class="btn btn-danger mb-3" id="bulkDeleteBtn" disabled>Delete Selected</button>

    <table id="supplierContactTable" class="table table-bordered">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>#</th>
                <th>Supplier Name</th>
                <th>Role</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Notes</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" role="dialog" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="contactForm">
            @csrf
            <input type="hidden" name="id" id="contactId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add / Edit Supplier Contact</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-control" required>
                            <option value="">-- Select Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" name="role" id="role" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" name="email" id="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" id="notes" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf }
    });

    const table = $('#supplierContactTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.suppliers.contacts.datatable") }}',
        columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'supplier.name', name: 'supplier.name' },
            { data: 'role', name: 'role' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'notes', name: 'notes' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Bulk select toggle
    $(document).on('change', '#selectAll', function () {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        toggleBulkDelete();
    });

    $(document).on('change', '.row-checkbox', function () {
        toggleBulkDelete();
    });

    function toggleBulkDelete() {
        $('#bulkDeleteBtn').prop('disabled', $('.row-checkbox:checked').length === 0);
    }

    // ✅ BULK DELETE (SweetAlert)
    $('#bulkDeleteBtn').on('click', function () {
        const ids = $('.row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) return;

        Swal.fire({
            title: 'Delete selected contacts?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route("admin.suppliers.contacts.bulk-delete") }}',
                method: 'POST',
                data: { ids },
                success: function (resp) {
                    $('#selectAll').prop('checked', false);
                    $('#bulkDeleteBtn').prop('disabled', true);
                    table.ajax.reload(null, false);

                    Swal.fire('Deleted!', resp.message ?? 'Selected contacts deleted.', 'success');
                },
                error: function () {
                    Swal.fire('Error', 'Bulk delete failed.', 'error');
                }
            });
        });
    });

    // ✅ OPEN ADD MODAL
    $('#addContactBtn').on('click', function () {
        $('#contactForm')[0].reset();
        $('#contactId').val('');
        $('#notes').val('');
        $('#contactModal').modal('show');
    });

    // ✅ SAVE (SweetAlert)
    $('#contactForm').on('submit', function (e) {
        e.preventDefault();

        let id = $('#contactId').val();
        let url = id
            ? `/admin/suppliers/contacts/${id}`
            : '{{ route("admin.suppliers.contacts.store") }}';

        $.ajax({
            url: url,
            type: id ? 'PUT' : 'POST',
            data: $(this).serialize(),
            success: function (resp) {
                $('#contactModal').modal('hide');
                table.ajax.reload(null, false);

                Swal.fire('Success', resp.message ?? 'Contact saved successfully.', 'success');
            },
            error: function (xhr) {
                let msg = 'Failed to save contact.';
                if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // ✅ EDIT
    $(document).on('click', '.edit-btn', function () {
        let data = $(this).data();

        $('#contactId').val(data.id);
        $('#supplier_id').val(data.supplier_id);
        $('#name').val(data.name);
        $('#role').val(data.role);
        $('#email').val(data.email);
        $('#phone').val(data.phone);
        $('#notes').val(data.notes);

        $('#contactModal').modal('show');
    });

    // ✅ DELETE (SweetAlert)
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete this contact?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: `/admin/suppliers/contacts/${id}`,
                type: 'DELETE',
                data: {},
                success: function (resp) {
                    table.ajax.reload(null, false);
                    Swal.fire('Deleted!', resp.message ?? 'Contact deleted.', 'success');
                },
                error: function () {
                    Swal.fire('Error', 'Failed to delete contact.', 'error');
                }
            });
        });
    });

});
</script>
@endpush
