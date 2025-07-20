@extends('layouts.master')

@section('title', 'Notes Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Notes</h1>
        <button class="btn btn-primary" id="addNoteBtn">
            <i class="fas fa-plus me-1"></i> Add Note
        </button>
        <button class="btn btn-danger d-none" id="bulkDeleteBtn">
            <i class="fas fa-trash"></i> Delete Selected
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="noteTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Title</th>
                        <th>Notable Type</th>
                        <th>Notable</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Add / Edit -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="noteForm">
            @csrf
            <input type="hidden" id="note_id" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="noteModalLabel">Add Note</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" name="subject" id="subject" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea class="form-control" name="content" id="content" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Author</label>
                    <select name="author_id" id="author_id" class="form-control" required>
                        <option value="">-- Select Author --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Noteable Type -->
                <div class="mb-3">
                    <label class="form-label">Related To (Type)</label>
                    <select name="notable_type" id="notable_type" class="form-control" required>
                        <option value="">-- Select --</option>
                        <option value="Modules\CRM\Models\Lead">Lead</option>
                        <option value="Modules\CRM\Models\Customer">Customer</option>
                        <option value="Modules\CRM\Models\Opportunity">Opportunity</option>
                    </select>
                </div>

                <!-- Noteable ID -->
                <!-- <div class="mb-3">
                    <label class="form-label">Related Record ID</label>
                    <input type="number" class="form-control" name="notable_id" id="notable_id" required>
                </div> -->
                <div class="mb-3">
                    <label for="notable_id" class="form-label">Select Entity</label>
                    <select id="notable_id" name="notable_id" class="form-control" required>
                        <option value="">-- Select an option --</option>
                    </select>
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
    const modal = new bootstrap.Modal(document.getElementById('noteModal'));
    const table = $('#noteTable').DataTable({
        ajax: '{{ route('admin.crm.notes.datatable') }}',
        responsive: true,
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'subject' },
            { data: 'notable_type' },
            { data: 'notable_value' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    // Add Note button
    $('#addNoteBtn').on('click', function() {
        $('#noteForm')[0].reset();
        $('#note_id').val('');
        $('#noteModalLabel').text('Add Note');
        modal.show();
    });

    $('#notable_type').on('change', function () {
        const type = $(this).val();
        const $noteableId = $('#notable_id');

        $noteableId.html('<option value="">Loading...</option>');

        if (!type) return;

        $.get('{{ route("admin.crm.notes.fetch-notables") }}', { type: type })
            .done(res => {
                if (res.length === 0) {
                    $noteableId.html('<option value="">No records found</option>');
                    return;
                }

                let options = '<option value="">-- Select Record --</option>';
                res.forEach(item => {
                    options += `<option value="${item.id}">${item.label}</option>`;
                });

                $noteableId.html(options);
            })
            .fail(err => {
                console.error(err);
                $noteableId.html('<option value="">Failed to load</option>');
            });
    });


    // Edit Note button
    $(document).on('click', '.edit-note', function () {
        const record = $(this).data('record');

        // Set the common fields
            $('#note_id').val(record.id);
            $('#subject').val(record.subject);
            $('#content').val(record.content);
            $('#author_id').val(record.author_id);
            $('#noteModalLabel').text('Edit Note');

        // Set noteable_type first
        $('#notable_type').val(record.notable_type).trigger('change');

        // Wait a little to ensure options are fetched before setting the value
        setTimeout(() => {
            $('#notable_id').val(record.notable_id).trigger('change');
        }, 3000); // adjust if needed

        $('#noteModalLabel').text('Edit Note');
        modal.show();
    });



    // Form Submit (Add or Edit)
    $('#noteForm').submit(function(e) {
        e.preventDefault();
        const id = $('#note_id').val();
        const url = id ? `/admin/crm/notes/${id}` : `{{ route('admin.crm.notes.store') }}`;
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

    // Delete Note
    $(document).on('click', '.delete-note', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will delete the note permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/crm/notes/${id}`,
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

    // Bulk Delete
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
            text: 'This will delete selected notes permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete them!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('admin.crm.notes.bulk-delete') }}',
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

    // Select/Deselect all checkbox
    $('#selectAll').on('change', function () {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        toggleBulkDelete();
    });

    // Toggle Bulk Delete button visibility
    $('#activityTable tbody').on('change', '.row-checkbox', function () {
        toggleBulkDelete();
    });

    function toggleBulkDelete() {
        const anyChecked = $('.row-checkbox:checked').length > 0;
        $('#bulkDeleteBtn').toggleClass('d-none', !anyChecked);
    }
});
</script>
@endpush
