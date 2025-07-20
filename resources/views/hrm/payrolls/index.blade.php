@extends('layouts.master')

@section('title', 'Payroll Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Payroll Records</h1>
        <button class="btn btn-primary" id="addPayrollBtn">
            <i class="fas fa-plus me-1"></i> Add Payroll
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-2">
                <a href="{{ route('admin.hrm.payroll.export', 'excel') }}" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </a>
                <a href="{{ route('admin.hrm.payroll.export', 'pdf') }}" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> Export to PDF
                </a>
            </div>
            <table class="table table-bordered" id="payrollTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Month</th>
                        <th>Basic</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net</th>
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
<div class="modal fade" id="payrollModal" tabindex="-1" aria-labelledby="payrollModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="payrollForm">
            @csrf
            <input type="hidden" id="payroll_id" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="payrollModalLabel">Add Payroll</h5>
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
                    <label class="form-label">Month</label>
                    <input type="month" class="form-control" name="month" id="month" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Basic Salary</label>
                    <input type="number" step="0.01" class="form-control" name="basic_salary" id="basic_salary" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Allowances</label>
                    <div id="allowances-wrapper"></div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="addAllowance">+ Add Allowance</button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deductions</label>
                    <div id="deductions-wrapper"></div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="addDeduction">+ Add Deduction</button>
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
    const modal = new bootstrap.Modal(document.getElementById('payrollModal'));
    let allowanceIndex = 0;
    let deductionIndex = 0;

    const table = $('#payrollTable').DataTable({
        ajax: '{{ route('admin.hrm.payroll.datatable') }}',
        responsive: true,
        columns: [
            { data: 'id' },
            { data: 'employee' },
            { data: 'pay_date' },
            { data: 'basic_salary' },
            { data: 'total_allowances' },
            { data: 'total_deductions' },
            { data: 'net_salary' },
            {
                data: 'payment_status',
                render: function(data) {
                    return data === 'paid'
                        ? '<span class="badge bg-success">Paid</span>'
                        : '<span class="badge bg-warning text-dark">Unpaid</span>';
                }
            },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#addPayrollBtn').on('click', function(){
        resetForm();
        $('#payrollModalLabel').text('Add Payroll');
        modal.show();
    });

    $(document).on('click', '.edit-payroll', function () {
        resetForm();
        const payroll = $(this).data('record');
        $('#payroll_id').val(payroll.id);
        $('#employee_id').val(payroll.employee_id);
        $('#month').val(payroll.pay_date?.substring(0, 7));
        $('#basic_salary').val(payroll.basic_salary);

        payroll.allowances.forEach((a, i) => {
            $('#allowances-wrapper').append(`
                <div class="row mb-2 allowance-row">
                    <div class="col-6"><input type="text" name="allowances[${i}][type]" class="form-control" value="${a.type}" placeholder="Type"></div>
                    <div class="col-4"><input type="number" step="0.01" name="allowances[${i}][amount]" class="form-control" value="${a.amount}" placeholder="Amount"></div>
                    <div class="col-2"><button type="button" class="btn btn-danger remove-row">x</button></div>
                </div>
            `);
        });
        allowanceIndex = payroll.allowances.length;

        payroll.deductions.forEach((d, i) => {
            $('#deductions-wrapper').append(`
                <div class="row mb-2 deduction-row">
                    <div class="col-6"><input type="text" name="deductions[${i}][type]" class="form-control" value="${d.type}" placeholder="Type"></div>
                    <div class="col-4"><input type="number" step="0.01" name="deductions[${i}][amount]" class="form-control" value="${d.amount}" placeholder="Amount"></div>
                    <div class="col-2"><button type="button" class="btn btn-danger remove-row">x</button></div>
                </div>
            `);
        });
        deductionIndex = payroll.deductions.length;

        $('#payrollModalLabel').text('Edit Payroll');
        modal.show();
    });

    $('#addAllowance').on('click', function () {
        $('#allowances-wrapper').append(`
            <div class="row mb-2 allowance-row">
                <div class="col-6"><input type="text" name="allowances[${allowanceIndex}][type]" class="form-control" placeholder="Type"></div>
                <div class="col-4"><input type="number" step="0.01" name="allowances[${allowanceIndex}][amount]" class="form-control" placeholder="Amount"></div>
                <div class="col-2"><button type="button" class="btn btn-danger remove-row">x</button></div>
            </div>
        `);
        allowanceIndex++;
    });

    $('#addDeduction').on('click', function () {
        $('#deductions-wrapper').append(`
            <div class="row mb-2 deduction-row">
                <div class="col-6"><input type="text" name="deductions[${deductionIndex}][type]" class="form-control" placeholder="Type"></div>
                <div class="col-4"><input type="number" step="0.01" name="deductions[${deductionIndex}][amount]" class="form-control" placeholder="Amount"></div>
                <div class="col-2"><button type="button" class="btn btn-danger remove-row">x</button></div>
            </div>
        `);
        deductionIndex++;
    });

    $(document).on('click', '.remove-row', function () {
        $(this).closest('.row').remove();
    });

    $('#payrollForm').submit(function(e){
        e.preventDefault();
        const id = $('#payroll_id').val();
        const url = id ? `/admin/hrm/payroll/${id}` : `{{ route('admin.hrm.payroll.store') }}`;
        const data = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.post(url, data).done(res => {
            table.ajax.reload(null, false);
            modal.hide();
            Swal.fire('Success', res.message, 'success');
        }).fail(err => {
            Swal.fire('Error', err.responseJSON?.message || 'Something went wrong.', 'error');
        });
    });

    $('#payrollTable').on('click', '.delete-payroll', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete this payroll?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/hrm/payroll/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to delete payroll.', 'error');
                    }
                });
            }
        });
    });

    function resetForm() {
        $('#payrollForm')[0].reset();
        $('#payroll_id').val('');
        $('#allowances-wrapper, #deductions-wrapper').empty();
        allowanceIndex = deductionIndex = 0;
    }

    // Handle toggle paid status
    $(document).on('click', '.toggle-paid', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This will toggle the payroll payment status.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, toggle it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/hrm/payroll/${id}/toggle-paid`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (res) {
                        $('#payrollTable').DataTable().ajax.reload(null, false);
                        Swal.fire('Success', res.message, 'success');
                    },
                    error: function () {
                        Swal.fire('Error', 'Unable to update payment status.', 'error');
                    }
                });
            }
        });
    });

});
</script>
@endpush
