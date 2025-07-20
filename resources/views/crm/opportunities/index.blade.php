@extends('layouts.master')

@section('title', 'Opportunities Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Opportunities</h1>
        <button class="btn btn-primary" id="addOpportunityBtn">
            <i class="fas fa-plus me-1"></i> Add Opportunity
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="opportunityTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Title</th>
                        <th>Customer</th>
                        <th>Value</th>
                        <th>Stage</th>
                        <th>Probability</th>
                        <th>Close Date</th>
                        <th>Owner</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button class="btn btn-danger mt-3" id="bulkDelete">Delete Selected</button>
        </div>
    </div>
</div>

<!-- Opportunity Modal -->
<div class="modal fade" id="opportunityModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="opportunityForm">
            @csrf
            <input type="hidden" name="id" id="opportunity_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Opportunity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" id="customer_id" class="form-control" required>
                        <option value="">-- Select --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Value</label>
                    <input type="number" name="value" id="value" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stage</label>
                    <select name="stage" id="stage" class="form-control" required>
                        <option value="">-- Select --</option>
                        <option value="prospecting">Prospecting</option>
                        <option value="qualification">Qualification</option>
                        <option value="proposal">Proposal</option>
                        <option value="negotiation">Negotiation</option>
                        <option value="closed_won">Closed Won</option>
                        <option value="closed_lost">Closed Lost</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Probability (%)</label>
                    <input type="number" name="probability" id="probability" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Expected Close Date</label>
                    <input type="date" name="close_date" id="close_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Owner</label>
                    <select name="owner_id" id="owner_id" class="form-control" required>
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
    const modal = new bootstrap.Modal(document.getElementById('opportunityModal'));
    const table = $('#opportunityTable').DataTable({
        ajax: '{{ route('admin.crm.opportunities.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'title' },
            { data: 'customer' },
            { data: 'value' },
            { data: 'stage' },
            { data: 'probability' },
            { data: 'close_date' },
            { data: 'owner' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        responsive: true
    });

    $('#addOpportunityBtn').click(() => {
        $('#opportunityForm')[0].reset();
        $('#opportunity_id').val('');
        modal.show();
    });

    $('#opportunityForm').submit(function(e){
        e.preventDefault();
        const id = $('#opportunity_id').val();
        const url = id ? `/admin/crm/opportunities/${id}` : '{{ route('admin.crm.opportunities.store') }}';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url,
            method,
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

    $(document).on('click', '.edit-opportunity', function(){
        const data = $(this).data('record');
        $('#opportunity_id').val(data.id);
        $('#title').val(data.title);
        $('#customer_id').val(data.customer_id);
        $('#value').val(data.value);
        $('#stage').val(data.stage);
        $('#probability').val(data.probability);
        $('#close_date').val(data.close_date);
        $('#owner_id').val(data.owner_id);
        $('#notes').val(data.notes);
        modal.show();
    });

    $(document).on('click', '.delete-opportunity', function(){
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete Opportunity?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it'
        }).then(result => {
            if(result.isConfirmed){
                $.ajax({
                    url: `/admin/crm/opportunities/${id}`,
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

        if(ids.length === 0) return Swal.fire('Info', 'No opportunities selected.', 'info');

        Swal.fire({
            title: 'Delete selected opportunities?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete all'
        }).then(result => {
            if(result.isConfirmed){
                $.post(`{{ route('admin.crm.opportunities.bulk-delete') }}`, {_token: '{{ csrf_token() }}', ids}, res => {
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
