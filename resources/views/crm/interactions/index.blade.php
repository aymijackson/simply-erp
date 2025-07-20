@extends('layouts.master')

@section('title', 'Interactions Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h4 class="text-primary">Interactions</h4>
        <button class="btn btn-primary" id="addInteractionBtn">
            <i class="fas fa-plus me-1"></i> Add Interaction
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <button class="btn btn-danger mb-2" id="bulkDeleteBtn" disabled>Delete Selected</button>
            <table class="table table-bordered" id="interactionTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Employee</th>
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
<div class="modal fade" id="interactionModal" tabindex="-1" aria-labelledby="interactionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="interactionForm">
            @csrf
            <input type="hidden" name="id" id="interaction_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="interactionModalLabel">Add Interaction</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" name="subject" id="subject" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Details</label>
                    <textarea class="form-control" name="details" id="details"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-control" name="interaction_type" id="interaction_type" required>
                        <option value="call">Call</option>
                        <option value="email">Email</option>
                        <option value="meeting">Meeting</option>
                        <option value="visit">Visit</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="interaction_date" id="interaction_date" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Employee</label>
                    <select class="form-control" name="employee_id" id="employee_id" required>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Related Type</label>
                    <select class="form-control" name="interactable_type" id="interactable_type" required>
                        <option value="">-- Select --</option>
                        <option value="Modules\CRM\Models\Customer">Customer</option>
                        <option value="Modules\CRM\Models\Lead">Lead</option>
                        <option value="Modules\CRM\Models\Opportunity">Opportunity</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Related Record</label>
                    <select class="form-control" name="interactable_id" id="interactable_id" required>
                        <option value="">-- Select Type First --</option>
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
const modal = new bootstrap.Modal('#interactionModal');
const table = $('#interactionTable').DataTable({
    ajax: '{{ route("admin.crm.interactions.datatable") }}',
    responsive: true,
    columns: [
        { data: 'checkbox', orderable: false, searchable: false },
        { data: 'subject' },
        { data: 'interaction_type' },
        { data: 'interaction_date' },
        { data: 'employee' },
        { data: 'interactable' },
        { data: 'actions', orderable: false, searchable: false }
    ]
});

// Add new
$('#addInteractionBtn').click(() => {
    $('#interactionForm')[0].reset();
    $('#interaction_id').val('');
    $('#interactable_id').html('<option value="">-- Select Type First --</option>');
    $('#interactionModalLabel').text('Add Interaction');
    modal.show();
});

// Populate related dropdown on type select
$('#interactable_type').change(function () {
    const type = $(this).val();
    $('#interactable_id').html('<option value="">Loading...</option>');
    if (!type) return;

    $.get("{{ route('admin.crm.interactions.fetch.interactables') }}", { type })
        .done(data => {
            let options = '<option value="">-- Select --</option>';
            data.forEach(item => options += `<option value="${item.id}">${item.label}</option>`);
            $('#interactable_id').html(options);
        });
});

// Submit
$('#interactionForm').submit(function (e) {
    e.preventDefault();
    const id = $('#interaction_id').val();
    const url = id ? `/admin/crm/interactions/${id}` : `{{ route('admin.crm.interactions.store') }}`;
    const method = id ? 'PUT' : 'POST';
    const formData = $(this).serialize() + (id ? '&_method=PUT' : '');

    $.post(url, formData)
        .done(res => {
            table.ajax.reload();
            modal.hide();
            Swal.fire('Success', res.message, 'success');
        })
        .fail(err => {
            const msg = err.responseJSON?.message || 'Something went wrong.';
            Swal.fire('Error', msg, 'error');
        });
});

// Edit
$(document).on('click', '.edit-interaction', function () {
    const record = $(this).data('record');
    $('#interaction_id').val(record.id);
    $('#subject').val(record.subject);
    $('#details').val(record.details);
    $('#interaction_type').val(record.interaction_type);
    $('#interaction_date').val(record.interaction_date);
    $('#employee_id').val(record.employee_id);
    $('#interactable_type').val(record.interactable_type).trigger('change');

    setTimeout(() => {
        $.get("{{ route('admin.crm.interactions.fetch.interactables') }}", { type: record.interactable_type })
            .done(data => {
                let options = '<option value="">-- Select --</option>';
                data.forEach(item => {
                    options += `<option value="${item.id}" ${item.id == record.interactable_id ? 'selected' : ''}>${item.label}</option>`;
                });
                $('#interactable_id').html(options);
            });
    }, 300);

    $('#interactionModalLabel').text('Edit Interaction');
    modal.show();
});

// Delete
$(document).on('click', '.delete-interaction', function () {
    const id = $(this).data('id');
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will delete the interaction permanently.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/crm/interactions/${id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: res => {
                    table.ajax.reload();
                    Swal.fire('Deleted!', res.message, 'success');
                }
            });
        }
    });
});

// Bulk Delete
$('#selectAll').on('change', function () {
    $('.row-checkbox').prop('checked', $(this).prop('checked'));
    $('#bulkDeleteBtn').prop('disabled', !$(this).prop('checked'));
});

$(document).on('change', '.row-checkbox', function () {
    $('#bulkDeleteBtn').prop('disabled', $('.row-checkbox:checked').length === 0);
});

$('#bulkDeleteBtn').on('click', function () {
    const ids = $('.row-checkbox:checked').map((_, el) => $(el).val()).get();

    Swal.fire({
        title: 'Are you sure?',
        text: 'Selected interactions will be deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete'
    }).then(result => {
        if (result.isConfirmed) {
            $.post(`{{ route('admin.crm.interactions.bulk-delete') }}`, {
                ids,
                _token: '{{ csrf_token() }}'
            }).done(res => {
                table.ajax.reload();
                Swal.fire('Deleted!', res.message, 'success');
            });
        }
    });
});
</script>
@endpush
