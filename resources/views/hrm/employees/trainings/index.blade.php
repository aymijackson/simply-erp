@extends('layouts.master')

@section('title', 'Training Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-primary">Training Records</h4>
        <button class="btn btn-primary" id="addTrainingBtn">
            <i class="fas fa-plus me-1"></i> Add Training
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="trainingTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Trainer</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="trainingModal" tabindex="-1" aria-labelledby="trainingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="trainingForm">
            @csrf
            <input type="hidden" name="id" id="training_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="trainingModalLabel">Add Training</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label>Title</label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Description</label>
                    <textarea name="description" id="description" class="form-control"></textarea>
                </div>
                <div class="mb-2">
                    <label>Trainer</label>
                    <input type="text" name="trainer" id="trainer" class="form-control">
                </div>
                <div class="mb-2">
                    <label>Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control">
                </div>
                <div class="mb-2">
                    <label>Location</label>
                    <input type="text" name="location" id="location" class="form-control">
                </div>
                <div class="mb-2">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" type="submit">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const modal = new bootstrap.Modal(document.getElementById('trainingModal'));

    const table = $('#trainingTable').DataTable({
        responsive: true,
        processing: true,
        ajax: '{{ route("admin.hrm.employees.trainings.datatable") }}',
        columns: [
            { data: 'id' },
            { data: 'title' },
            { data: 'trainer' },
            { data: 'start_date' },
            { data: 'end_date' },
            { data: 'location' },
            { data: 'status', render: data => `<span class="badge bg-${data === 'scheduled' ? 'warning text-dark' : (data === 'completed' ? 'success' : 'danger')}">${data}</span>` },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#addTrainingBtn').on('click', function() {
        resetForm();
        $('#trainingModalLabel').text('Add Training');
        modal.show();
    });

    $(document).on('click', '.edit-training', function () {
        resetForm();
        const data = $(this).data('record');
        $('#training_id').val(data.id);
        $('#title').val(data.title);
        $('#description').val(data.description);
        $('#trainer').val(data.trainer);
        $('#start_date').val(data.start_date);
        $('#end_date').val(data.end_date);
        $('#location').val(data.location);
        $('#status').val(data.status);
        $('#trainingModalLabel').text('Edit Training');
        modal.show();
    });

    $('#trainingForm').submit(function(e) {
        e.preventDefault();
        const id = $('#training_id').val();
        const url = id ? `/admin/hrm/employees/trainings/${id}` : `{{ route('admin.hrm.employees.trainings.store') }}`;
        const method = id ? 'PUT' : 'POST';
        const formData = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.post(url, formData).done(res => {
            Swal.fire('Success', res.message, 'success');
            modal.hide();
            table.ajax.reload(null, false);
        }).fail(err => {
            Swal.fire('Error', err.responseJSON?.message || 'Something went wrong.', 'error');
        });
    });

    $(document).on('click', '.delete-training', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This training record will be deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/hrm/employees/trainings/${id}`,
                    type: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'}
                }).done(res => {
                    Swal.fire('Deleted', res.message, 'success');
                    table.ajax.reload(null, false);
                });
            }
        });
    });

    function resetForm() {
        $('#trainingForm')[0].reset();
        $('#training_id').val('');
    }
});
</script>
@endpush
