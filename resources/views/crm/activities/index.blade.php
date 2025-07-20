@extends('layouts.master')

@section('title', 'Activities Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Activities</h1>
        <div>
            <button class="btn btn-danger me-2" id="bulkDeleteBtn">
                <i class="fas fa-trash"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addActivityBtn">
                <i class="fas fa-plus me-1"></i> Add Activity
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="activityTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Owner</th>
                        <th>Related To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="activityModal" tabindex="-1" aria-labelledby="activityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="activityForm">
            @csrf
            <input type="hidden" id="activity_id" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="activityModalLabel">Add Activity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" name="subject" id="subject" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" id="description"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="activity_type" id="activity_type" class="form-control" required>
                        <option value="call">Call</option>
                        <option value="meeting">Meeting</option>
                        <option value="email">Email</option>
                        <option value="task">Task</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" class="form-control" name="due_date" id="due_date" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Owner</label>
                    <select name="owner_id" id="owner_id" class="form-control" required>
                        <option value="">-- Select Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Related Type (optional)</label>
                    <input type="text" class="form-control" name="related_type" id="related_type">
                </div>
                <div class="mb-3">
                    <label class="form-label">Related ID (optional)</label>
                    <input type="number" class="form-control" name="related_id" id="related_id">
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
    const modal = new bootstrap.Modal(document.getElementById('activityModal'));
    const table = $('#activityTable').DataTable({
        ajax: '{{ route('admin.crm.activities.datatable') }}',
        responsive: true,
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'subject' },
            { data: 'activity_type' },
            { data: 'due_date' },
            { data: 'status' },
            { data: 'owner' },
            { data: 'related_to' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#addActivityBtn').on('click', function() {
        $('#activityForm')[0].reset();
        $('#activity_id').val('');
        $('#activityModalLabel').text('Add Activity');
        modal.show();
    });

    $(document).on('click', '.edit-activity', function () {
        const record = $(this).data('record');
        $('#activity_id').val(record.id);
        $('#subject').val(record.subject);
        $('#description').val(record.description);
        $('#activity_type').val(record.activity_type);
        const dueDate = new Date(record.due_date);
        const formatted = dueDate.toISOString().split('T')[0]; // Ensures 'YYYY-MM-DD'
        $('#due_date').val(formatted);
        //$('#due_date').val(record.due_date);
        $('#status').val(record.status);
        $('#owner_id').val(record.owner_id);
        $('#related_type').val(record.related_type);
        $('#related_id').val(record.related_id);
        $('#activityModalLabel').text('Edit Activity');
        modal.show();
    });

    $('#activityForm').submit(function(e) {
        e.preventDefault();
        const id = $('#activity_id').val();
        const url = id ? `/admin/crm/activities/${id}` : `{{ route('admin.crm.activities.store') }}`;
        const method = id ? 'PUT' : 'POST';
        const data = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.post(url, data).done(res => {
            table.ajax.reload(null, false);
            modal.hide();
            Swal.fire('Success', res.message, 'success');
        }).fail(err => {
            Swal.fire('Error', err.responseJSON?.message || 'Something went wrong.', 'error');
        });
    });

    $(document).on('click', '.delete-activity', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will delete the activity permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/crm/activities/${id}`,
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

    $('#selectAll').on('change', function () {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#bulkDeleteBtn').on('click', function () {
        const ids = $('.row-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (ids.length === 0) {
            Swal.fire('Warning', 'Please select at least one record to delete.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'This will delete selected activities permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete them!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    method: 'DELETE',
                    url: '{{ route('admin.crm.activities.bulk-delete') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: ids
                    },
                    success: res => {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    }
                });
            }
        });
    });
});
</script>
@endpush
