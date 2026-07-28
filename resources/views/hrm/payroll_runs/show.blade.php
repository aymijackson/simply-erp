@extends('layouts.master')
@section('title', 'Payroll Run: ' . $run->run_no)

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">{{ $run->run_no }}</h1>
            <small class="text-muted">
                {{ $run->period_label }} &bull; Pay Date: {{ $run->pay_date?->format('d M Y') }}
                &bull;
                @switch($run->status)
                    @case('approved') <span class="badge bg-primary">Approved</span> @break
                    @case('posted')   <span class="badge bg-info text-dark">Posted</span> @break
                    @case('paid')     <span class="badge bg-success">Paid</span> @break
                    @default          <span class="badge bg-secondary">Draft</span>
                @endswitch
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hrm.payroll-runs.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            @if($run->status === 'draft')
                @can('hrm.payroll_runs.approve')
                <button class="btn btn-success btn-sm" id="btnApprove">
                    <i class="fas fa-check me-1"></i> Approve
                </button>
                @endcan
            @endif
            @if($run->status === 'approved')
                @can('hrm.payroll_runs.post')
                <button class="btn btn-info btn-sm" id="btnPost">
                    <i class="fas fa-upload me-1"></i> Post to GL
                </button>
                @endcan
            @endif
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Employees</div>
                    <div class="fw-bold h5 mb-0">{{ $run->payslips->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Total Gross</div>
                    <div class="fw-bold h5 mb-0">{{ number_format($run->total_gross, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Total Deductions</div>
                    <div class="fw-bold h5 mb-0 text-danger">{{ number_format($run->total_deductions, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Total Net</div>
                    <div class="fw-bold h5 mb-0 text-success">{{ number_format($run->total_net, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payslips --}}
    <div class="card shadow">
        <div class="card-header bg-white">
            <h6 class="mb-0 text-primary"><i class="fas fa-list me-1"></i> Payslips</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm w-100" id="tblPayslips">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Basic Salary</th>
                            <th>Gross</th>
                            <th>Deductions</th>
                            <th>Net</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Edit Payslip Modal --}}
<div class="modal fade" id="modalPayslip" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="payslipModalTitle">Edit Payslip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmPayslip" novalidate>
                    @csrf
                    <input type="hidden" id="payslipId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Employee</label>
                            <input type="text" class="form-control" id="ps_employee" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Basic Salary</label>
                            <input type="number" class="form-control" id="ps_basic" name="basic_salary" step="0.01" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Total Allowances</label>
                            <input type="number" class="form-control" id="ps_allowances" name="total_allowances" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Total Deductions</label>
                            <input type="number" class="form-control" id="ps_deductions" name="total_deductions" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Net Salary</label>
                            <input type="number" class="form-control bg-light" id="ps_net" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="ps_notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSavePayslip">
                    <i class="fas fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const CSRF  = $('meta[name="csrf-token"]').attr('content');
    const RUN   = {{ $run->id }};
    const URLS  = {
        payslipDT  : `/admin/hrm/payroll-runs/${RUN}/payslips/datatable`,
        payslipUpd : (id) => `/admin/hrm/payroll-runs/${RUN}/payslips/${id}`,
        approve    : `/admin/hrm/payroll-runs/${RUN}/approve`,
        post       : `/admin/hrm/payroll-runs/${RUN}/post`,
    };

    const $payslipModal = new bootstrap.Modal(document.getElementById('modalPayslip'));

    const table = $('#tblPayslips').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: URLS.payslipDT, dataSrc: 'data' },
        columns: [
            { data: 'employee_name' },
            { data: 'basic_salary', render: v => parseFloat(v).toFixed(2) },
            { data: 'gross_fmt' },
            { data: 'deductions_fmt' },
            { data: 'net_fmt' },
            { data: 'status_badge', orderable: false },
            { data: 'actions',      orderable: false, searchable: false },
        ],
    });

    // Edit payslip
    $('#tblPayslips').on('click', '.btn-edit-payslip', function () {
        const r = $(this).data('record');
        $('#payslipId').val(r.id);
        $('#ps_employee').val(r.employee_name);
        $('#ps_basic').val(r.basic_salary);
        $('#ps_allowances').val(r.total_allowances);
        $('#ps_deductions').val(r.total_deductions);
        $('#ps_net').val(r.net_salary);
        $('#ps_notes').val(r.notes);
        $payslipModal.show();
    });

    // Auto-calc net
    $('#ps_basic, #ps_allowances, #ps_deductions').on('input', function () {
        const net = (parseFloat($('#ps_basic').val())||0)
                  + (parseFloat($('#ps_allowances').val())||0)
                  - (parseFloat($('#ps_deductions').val())||0);
        $('#ps_net').val(net.toFixed(2));
    });

    $('#btnSavePayslip').on('click', function () {
        const id = $('#payslipId').val();
        $.post(URLS.payslipUpd(id), $('#frmPayslip').serialize() + '&_method=PUT')
            .done(() => { $payslipModal.hide(); table.ajax.reload();
                Swal.fire({ icon:'success', title:'Updated', timer:1400, showConfirmButton:false }); })
            .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Error.', 'error'));
    });

    @if($run->status === 'draft')
    $('#btnApprove').on('click', function () {
        Swal.fire({ title:'Approve this payroll run?', icon:'question',
            showCancelButton:true, confirmButtonColor:'#28a745' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.approve, { _token:CSRF })
                    .done(() => location.reload())
                    .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message, 'error'));
            });
    });
    @endif

    @if($run->status === 'approved')
    $('#btnPost').on('click', function () {
        Swal.fire({ title:'Post payroll to GL?', icon:'warning',
            showCancelButton:true, confirmButtonColor:'#17a2b8' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.post, { _token:CSRF })
                    .done(res => { Swal.fire({ icon:'success', title:'Posted', text: res.message });
                        setTimeout(() => location.reload(), 1800); })
                    .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message, 'error'));
            });
    });
    @endif
})();
</script>
@endpush