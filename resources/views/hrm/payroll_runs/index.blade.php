@extends('layouts.master')
@section('title', 'Payroll Runs')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0"><i class="fas fa-money-check-alt me-2"></i>Payroll Runs</h1>
            <small class="text-muted">HRM / Payroll Runs</small>
        </div>
        @can('hrm.payroll_runs.create')
        <button class="btn btn-primary btn-sm" id="btnCreate">
            <i class="fas fa-plus me-1"></i> New Payroll Run
        </button>
        @endcan
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover w-100" id="tblRuns">
                    <thead class="table-light">
                        <tr>
                            <th>Run No</th>
                            <th>Period</th>
                            <th>Pay Date</th>
                            <th>Employees</th>
                            <th>Total Net</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- CREATE MODAL --}}
<div class="modal fade" id="modalRun" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">New Payroll Run</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmRun" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                            <select class="form-select" id="run_month" name="period_month" required>
                                @foreach(range(1,12) as $m)
                                <option value="{{ $m }}">{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                            <select class="form-select" id="run_year" name="period_year" required>
                                @foreach(range(date('Y')-2, date('Y')+1) as $y)
                                <option value="{{ $y }}" @selected($y == date('Y'))>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Pay Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="run_pay_date" name="pay_date"
                                   value="{{ date('Y-m-t') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Employees</label>
                            <select class="form-select" id="run_employees" name="employee_ids[]" multiple>
                            </select>
                            <div class="form-text">Leave blank to include all active employees with contracts.</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSaveRun">
                    <i class="fas fa-cog me-1"></i> Generate Run
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');
    const URLS = {
        datatable : '{{ route('admin.hrm.payroll-runs.datatable') }}',
        store     : '{{ route('admin.hrm.payroll-runs.store') }}',
        approve   : (id) => `/admin/hrm/payroll-runs/${id}/approve`,
        post      : (id) => `/admin/hrm/payroll-runs/${id}/post`,
        destroy   : (id) => `/admin/hrm/payroll-runs/${id}`,
        empSelect2: '{{ route('admin.hrm.employees.select2') }}',
    };

    const $modal = new bootstrap.Modal(document.getElementById('modalRun'));

    const table = $('#tblRuns').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: URLS.datatable, dataSrc: 'data' },
        columns: [
            { data: 'run_no' },
            { data: 'period' },
            { data: 'pay_date' },
            { data: 'payslips_count' },
            { data: 'total_net_fmt' },
            { data: 'status_badge', orderable: false },
            { data: 'actions',      orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
    });

    // Employee multi-select2
    $('#run_employees').select2({
        theme: 'bootstrap-5',
        placeholder: 'All active employees',
        allowClear: true,
        dropdownParent: $('#modalRun'),
        ajax: {
            url: URLS.empSelect2,
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term, only_active: 1 }),
            processResults: data => ({ results: data.results || [] }),
        },
    });

    $('#btnCreate').on('click', function () {
        $('#frmRun')[0].reset();
        $('#run_month').val(new Date().getMonth() + 1);
        $('#run_year').val(new Date().getFullYear());
        $modal.show();
    });

    $('#btnSaveRun').on('click', function () {
        $.post(URLS.store, $('#frmRun').serialize())
            .done(res => {
                $modal.hide(); table.ajax.reload();
                Swal.fire({ icon:'success', title:'Run Generated', text: res.message,
                            timer:2000, showConfirmButton:false });
            })
            .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Error generating run.', 'error'));
    });

    // Approve
    $('#tblRuns').on('click', '.btn-approve-run', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Approve payroll run?', icon:'question',
            showCancelButton:true, confirmButtonColor:'#28a745' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.approve(id), { _token:CSRF })
                    .done(res => { table.ajax.reload();
                        Swal.fire({ icon:'success', title:'Approved', text: res.message, timer:1400, showConfirmButton:false }); })
                    .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Error.', 'error'));
            });
    });

    // Post to GL
    $('#tblRuns').on('click', '.btn-post-run', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Post to GL?', text:'This will create journal entries for this payroll run.',
            icon:'warning', showCancelButton:true, confirmButtonColor:'#17a2b8' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.post(id), { _token:CSRF })
                    .done(res => { table.ajax.reload();
                        Swal.fire({ icon:'success', title:'Posted', text: res.message, timer:2000, showConfirmButton:false }); })
                    .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Error.', 'error'));
            });
    });

    // Delete
    $('#tblRuns').on('click', '.btn-delete-run', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete run?', icon:'warning',
            showCancelButton:true, confirmButtonColor:'#e74a3b' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.destroy(id), { _token:CSRF, _method:'DELETE' })
                    .done(() => table.ajax.reload());
            });
    });
})();
</script>
@endpush