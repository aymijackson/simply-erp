@extends('layouts.master')

@section('title', 'Performance Reviews')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Performance Reviews</h1>
        <button class="btn btn-primary" id="addPerformanceBtn">
            <i class="fas fa-plus me-1"></i> Add Review
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="performanceTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Reviewer</th>
                        <th>Date</th>
                        <th>Rating</th>
                        <th>Comments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button class="btn btn-danger mt-2" id="bulkDeleteBtn">Delete Selected</button>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="performanceModal" tabindex="-1" aria-labelledby="performanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="performanceForm">
            @csrf
            <input type="hidden" id="performance_id" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="performanceModalLabel">Add Review</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Employee</label>
                    <select class="form-control" name="employee_id" id="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Goal Title</label>
                    <input type="text" class="form-control" name="goal_title" id="goal_title" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">KPI Description</label>
                    <textarea class="form-control" name="kpi_description" id="kpi_description" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Review Period</label>
                    <input type="text" class="form-control" name="review_period" id="review_period">
                </div>
                <div class="mb-3">
                    <label class="form-label">Score</label>
                    <input type="number" step="0.01" class="form-control" name="score" id="score">
                </div>
                <div class="mb-3">
                    <label class="form-label">Rating</label>
                    <select class="form-control" name="rating" id="rating" required>
                        <option value="">-- Select Rating --</option>
                        <option value="Excellent">Excellent</option>
                        <option value="Good">Good</option>
                        <option value="Satisfactory">Satisfactory</option>
                        <option value="Needs Improvement">Needs Improvement</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Comments</label>
                    <textarea class="form-control" name="comments" id="comments" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Review Date</label>
                    <input type="date" class="form-control" name="review_date" id="review_date" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reviewed By</label>
                    <select class="form-control" name="reviewed_by" id="reviewed_by" required>
                        <option value="">-- Select Reviewer --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                        @endforeach
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
    const modal = new bootstrap.Modal(document.getElementById('performanceModal'));
    const table = $('#performanceTable').DataTable({
        ajax: '{{ route('admin.hrm.employees.performances.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'id' },
            { data: 'employee' },
            { data: 'reviewed_by' },
            { data: 'review_date' },
            { data: 'rating' },
            { data: 'comments' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        responsive: true
    });

    $('#addPerformanceBtn').on('click', function(){
        $('#performanceForm')[0].reset();
        $('#performance_id').val('');
        $('#performanceModalLabel').text('Add Review');
        modal.show();
    });

    $('#performanceForm').submit(function(e){
        e.preventDefault();
        const id = $('#performance_id').val();
        const url = id ? `/admin/hrm/employees/performances/${id}` : `{{ route('admin.hrm.employees.performances.store') }}`;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: res => {
                table.ajax.reload(null, false);
                modal.hide();
                Swal.fire('Success', res.message, 'success');
            },
            error: err => {
                Swal.fire('Error', 'Something went wrong.', 'error');
            }
        });
    });

    $(document).on('click', '.edit-performance', function () {
        const record = $(this).data('record');
        $('#performance_id').val(record.id);
        $('#employee_id').val(record.employee_id);
        $('#reviewed_by').val(record.reviewed_by);
        $('#review_date').val(record.review_date);
        $('#rating').val(record.rating);
        $('#comments').val(record.comments);
        $('#performanceModalLabel').text('Edit Review');
        modal.show();
    });

    $(document).on('click', '.delete-performance', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/hrm/employees/performances/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: res => {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: err => {
                        Swal.fire('Error', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });

    $('#bulkDeleteBtn').on('click', function () {
        const selected = $('.row-checkbox:checked').map(function(){ return $(this).val(); }).get();
        if (!selected.length) return Swal.fire('Warning', 'No items selected.', 'warning');

        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete selected!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('admin.hrm.employees.performances.bulk-delete') }}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}', ids: selected },
                    success: res => {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: err => {
                        Swal.fire('Error', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('change', '#selectAll', function () {
        $('.row-checkbox').prop('checked', this.checked);
    });
});
</script>
@endpush
