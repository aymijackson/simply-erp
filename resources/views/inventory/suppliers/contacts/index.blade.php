@extends('layouts.master')

@section('title', 'All Supplier Contacts')

@section('content')
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
                        <textarea type="text" name="notes" id="notes" class="form-control" required></textarea>
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
<script>
$(function () {
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

    $('#bulkDeleteBtn').click(function () {
        const ids = $('.row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (ids.length && confirm('Are you sure you want to delete selected contacts?')) {
            $.ajax({
                url: '{{ route("admin.suppliers.contacts.bulk-delete") }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', ids },
                success: function () {
                    $('#selectAll').prop('checked', false);
                    $('#bulkDeleteBtn').prop('disabled', true);
                    table.ajax.reload();
                },
                error: function () {
                    alert('Bulk delete failed.');
                }
            });
        }
    });

    $('#addContactBtn').click(function () {
        $('#contactForm')[0].reset();
        $('#contactId').val('');
        $('#name').val('');
        $('#role').val('');
        $('#email').val('');
        $('#phone').val('');
        $('#notes').html('');
        $('#contactModal').modal('show');
    });

    $('#contactForm').submit(function (e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let id = $('#contactId').val();
        let url = id
            ? `/admin/inventory/suppliers/contacts/${id}`
            : '{{ route("admin.suppliers.contacts.store") }}';
        let method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function () {
                $('#contactModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', response.message, 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save contact.', 'error');
            }
        });
    });

    $(document).on('click', '.edit-btn', function () {
        let data = $(this).data();
        $('#contactId').val(data.id);
        $('#supplier_id').val(data.supplier_id);
        $('#name').val(data.name);
        $('#role').val(data.role);
        $('#email').val(data.email);
        $('#phone').val(data.phone);
        $('#notes').html(data.notes);

        $('#contactModal').modal('show');
    });

    $(document).on('click', '.delete-btn', function () {
        if (confirm('Are you sure you want to delete this contact?')) {
            $.ajax({
                url: `/admin/inventory/suppliers/contacts/${$(this).data('id')}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function () {
                    table.ajax.reload();
                }
            });
        }
    });
});
</script>
@endpush
