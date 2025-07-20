@extends('layouts.master')

@section('title', 'Leads Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Leads</h1>
        <button class="btn btn-primary" id="addLeadBtn">
            <i class="fas fa-plus me-1"></i> Add Lead
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="leadTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Company</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Follow-up Date</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button class="btn btn-danger mt-3" id="bulkDelete">Delete Selected</button>
        </div>
    </div>
</div>

<!-- Lead Modal -->
<div class="modal fade" id="leadModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="leadForm">
            @csrf
            <input type="hidden" id="lead_id" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Lead</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="lead_name" id="lead_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Company</label>
                    <select name="company_id" id="company_id" class="form-control">
                        <option value="">-- Select --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Position</label>
                    <input type="text" name="position" id="position" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Source</label>
                    <input type="text" name="source" id="source" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="">-- Select --</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="qualified">Qualified</option>
                        <option value="converted">Converted</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Follow-up Date</label>
                    <input type="date" name="follow_up_date" id="follow_up_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Assigned To</label>
                    <select name="assigned_to" id="assigned_to" class="form-control">
                        <option value="">-- Select --</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="notes" class="form-control"></textarea>
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
    const modal = new bootstrap.Modal(document.getElementById('leadModal'));
    const table = $('#leadTable').DataTable({
        ajax: '{{ route('admin.crm.leads.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'lead_name' },
            { data: 'email' },
            { data: 'phone' },
            { data: 'company' },
            { data: 'position' },
            { data: 'status' },
            { data: 'follow_up_date' },
            { data: 'assigned_to' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        responsive: true
    });

    $('#addLeadBtn').click(() => {
        $('#leadForm')[0].reset();
        $('#lead_id').val('');
        modal.show();
    });

    $('#leadForm').submit(function(e){
        e.preventDefault();
        const id = $('#lead_id').val();
        const url = id ? `/admin/crm/leads/${id}` : '{{ route('admin.crm.leads.store') }}';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: $(this).serialize(),
            success(res) {
                modal.hide();
                table.ajax.reload(null, false);
                Swal.fire('Success', res.message, 'success');
            },
            error(err) {
                Swal.fire('Error', 'Something went wrong', 'error');
            }
        });
    });

    $(document).on('click', '.edit-lead', function(){
        const data = $(this).data('record');
        $('#lead_id').val(data.id);
        $('#lead_name').val(data.lead_name);
        $('#email').val(data.email);
        $('#phone').val(data.phone);
        $('#company_id').val(data.company_id);
        $('#position').val(data.position);
        $('#source').val(data.source);
        $('#status').val(data.status);
        $('#follow_up_date').val(data.follow_up_date);
        $('#assigned_to').val(data.assigned_to);
        $('#notes').val(data.notes);
        modal.show();
    });

    $(document).on('click', '.delete-lead', function(){
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete Lead?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it'
        }).then(result => {
            if(result.isConfirmed){
                $.ajax({
                    url: `/admin/crm/leads/${id}`,
                    type: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'},
                    success: res => {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted', res.message, 'success');
                    }
                });
            }
        });
    });

    $('#bulkDelete').click(() => {
        const ids = [];
        $('.row-checkbox:checked').each(function(){
            ids.push($(this).val());
        });

        if(ids.length === 0) return Swal.fire('Info', 'No leads selected.', 'info');

        Swal.fire({
            title: 'Delete selected leads?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete all'
        }).then(result => {
            if(result.isConfirmed){
                $.post(`{{ route('admin.crm.leads.bulk-delete') }}`, {_token: '{{ csrf_token() }}', ids}, res => {
                    table.ajax.reload(null, false);
                    Swal.fire('Deleted', res.message, 'success');
                });
            }
        });
    });

    $('#select-all').click(function(){
        $('.row-checkbox').prop('checked', this.checked);
    });
});
</script>
@endpush
