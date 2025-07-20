@extends('layouts.master')

@section('title', 'Support Tickets')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Support Tickets</h1>
        <button class="btn btn-primary" id="addTicketBtn">
            <i class="fas fa-plus me-1"></i> Add Ticket
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <button class="btn btn-danger btn-sm mb-2" id="bulkDeleteBtn" disabled>Delete Selected</button>
            <table class="table table-bordered" id="ticketTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="ticketForm">
            @csrf
            <input type="hidden" name="id" id="ticket_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="ticketModalLabel">Add Ticket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" id="priority" class="form-control" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" id="customer_id" class="form-control">
                        <option value="">-- Select Employee --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assigned To</label>
                    <select name="assigned_to" id="assigned_to" class="form-control">
                        <option value="">-- Select Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Attach Files</label>
                    <input type="file" name="attachments[]" class="form-control" multiple>
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
    const modal = new bootstrap.Modal(document.getElementById('ticketModal'));
    const table = $('#ticketTable').DataTable({
        responsive: true,
        processing: true,
        ajax: '{{ route('admin.crm.support-tickets.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'subject' },
            { data: 'status' },
            { data: 'priority' },
            { data: 'assigned_to' },
            { data: 'created_by' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#addTicketBtn').click(function () {
        $('#ticketForm')[0].reset();
        $('#ticket_id').val('');
        $('#ticketModalLabel').text('Add Ticket');
        modal.show();
    });

    $('#ticketForm').submit(function (e) {
        e.preventDefault();
        const id = $('#ticket_id').val();
        const url = id ? `/admin/crm/support-tickets/${id}` : `{{ route('admin.crm.support-tickets.store') }}`;
        const method = id ? 'PUT' : 'POST';
        const data = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.post(url, data)
            .done(res => {
                table.ajax.reload(null, false);
                modal.hide();
                Swal.fire('Success', res.message, 'success');
            })
            .fail(err => {
                Swal.fire('Error', err.responseJSON?.message || 'Something went wrong.', 'error');
            });
    });

    $(document).on('click', '.edit-ticket', function () {
        const record = $(this).data('record');
        $('#ticket_id').val(record.id);
        $('#subject').val(record.subject);
        $('#description').val(record.description);
        $('#priority').val(record.priority);
        $('#status').val(record.status);
        $('#assigned_to').val(record.assigned_to);
        $('#customer_id').val(record.customer_id);
        $('#ticketModalLabel').text('Edit Ticket');
        modal.show();
    });

    $(document).on('click', '.delete-ticket', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will permanently delete the ticket.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/crm/support-tickets/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: res => {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    }
                });
            }
        });
    });

    $('#selectAll').change(function () {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        $('#bulkDeleteBtn').prop('disabled', !$(this).prop('checked'));
    });

    $(document).on('change', '.row-checkbox', function () {
        const allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length;
        $('#selectAll').prop('checked', allChecked);
        $('#bulkDeleteBtn').prop('disabled', $('.row-checkbox:checked').length === 0);
    });

    $('#bulkDeleteBtn').click(function () {
        const ids = $('.row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (ids.length > 0) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will delete all selected tickets.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete them!'
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('admin.crm.support-tickets.bulk-delete') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ids: ids
                        },
                        success: res => {
                            table.ajax.reload(null, false);
                            $('#bulkDeleteBtn').prop('disabled', true);
                            $('#selectAll').prop('checked', false);
                            Swal.fire('Deleted!', res.message, 'success');
                        }
                    });
                }
            });
        }
    });
});
</script>
@endpush


