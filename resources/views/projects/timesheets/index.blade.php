@extends('layouts.master')

@section('title', 'Project Timesheets')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Project Timesheets</h1>
            <small class="text-muted">Projects / Labour Time Tracking</small>
        </div>

        <button class="btn btn-primary" id="createBtn">
            <i class="fas fa-plus"></i> New Timesheet Entry
        </button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="text-muted small">Project</label>
                    <select class="form-control" id="f_project_id" style="width:100%"></select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Employee</label>
                    <select class="form-control" id="f_employee_id" style="width:100%"></select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Status</label>
                    <select class="form-control" id="f_status">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Billable</label>
                    <select class="form-control" id="f_is_billable">
                        <option value="">All</option>
                        <option value="1">Billable</option>
                        <option value="0">Non-Billable</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="text-muted small">From</label>
                    <input type="date" class="form-control" id="f_from">
                </div>

                <div class="col-md-1">
                    <label class="text-muted small">To</label>
                    <input type="date" class="form-control" id="f_to">
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Search</label>
                    <input type="text" class="form-control" id="f_q" placeholder="description, employee...">
                </div>
            </div>

            <div class="d-flex gap-2 mt-2 align-items-center">
                <button class="btn btn-outline-primary" id="applyBtn">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <button class="btn btn-outline-secondary" id="resetBtn">
                    <i class="fas fa-undo"></i> Reset
                </button>

                <div class="alert alert-info mb-0 flex-grow-1">
                    <i class="fas fa-info-circle me-1"></i>
                    Approved timesheets automatically create labour costs in Project Costs and update project actual cost.
                </div>
            </div>

            <div class="row mt-3 g-2">
                <div class="col-md-3">
                    <div class="border rounded p-2 bg-light">
                        <div class="small text-muted">Total Hours</div>
                        <div class="fw-bold" id="sumHoursLbl">0.00</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 bg-light">
                        <div class="small text-muted">Total Labour Cost</div>
                        <div class="fw-bold" id="sumCostLbl">0.00</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 bg-light">
                        <div class="small text-muted">Billable Hours</div>
                        <div class="fw-bold" id="sumBillableHoursLbl">0.00</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 bg-light">
                        <div class="small text-muted">Billable Amount</div>
                        <div class="fw-bold" id="sumBillableAmountLbl">0.00</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="timesheetsTable" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th style="width:110px;">Date</th>
                            <th style="width:220px;">Project</th>
                            <th style="width:170px;">Task</th>
                            <th style="width:170px;">Milestone</th>
                            <th style="width:160px;">Employee</th>
                            <th style="width:90px;" class="text-end">Hours</th>
                            <th style="width:110px;" class="text-end">Hourly Rate</th>
                            <th style="width:120px;" class="text-end">Cost Amount</th>
                            <th style="width:100px;" class="text-end">Billable Hrs</th>
                            <th style="width:110px;" class="text-end">Billing Rate</th>
                            <th style="width:120px;" class="text-end">Billable Amt</th>
                            <th style="width:110px;">Billable</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:280px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('projects.timesheets.partials.modal')
@endsection

@push('styles')
<style>
    .select2-container--bootstrap-5 .select2-dropdown { z-index: 2060 !important; }
    .select2-container--open { z-index: 2060 !important; }
    .modal-open .modal { overflow: visible !important; }
    .modal .modal-body { overflow: visible !important; }
</style>
@endpush

@push('scripts')
<script>
$.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
});

const dtUrl         = "{{ route('admin.project_timesheets.datatable') }}";
const baseUrl       = "{{ url('admin/project-timesheets') }}";
const projectsUrl   = "{{ route('admin.project_timesheets.lookups.projects') }}";
const tasksUrl      = "{{ route('admin.project_timesheets.lookups.tasks') }}";
const milestonesUrl = "{{ route('admin.project_timesheets.lookups.milestones') }}";
const employeesUrl  = "{{ route('admin.project_timesheets.lookups.employees') }}";

let DT = null;

function swalOk(msg){
    if (window.Swal?.fire) return Swal.fire({icon:'success', title:'Success', text: msg || 'Done.'});
    alert(msg || 'Done.');
}

function swalErr(msg){
    if (window.Swal?.fire) return Swal.fire({icon:'error', title:'Error', text: msg || 'Something went wrong.'});
    alert(msg || 'Error');
}

function swalAsk(opts){
    if (window.Swal?.fire) return Swal.fire(opts);
    return Promise.resolve({isConfirmed: confirm(opts?.title || 'Confirm?')});
}

function fmtMoney(v){
    return Number(v || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function initDT(){
    DT = $('#timesheetsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        ajax: {
            url: dtUrl,
            data: function(d){
                d.project_id   = $('#f_project_id').val();
                d.employee_id  = $('#f_employee_id').val();
                d.status       = $('#f_status').val();
                d.is_billable  = $('#f_is_billable').val();
                d.date_from    = $('#f_from').val();
                d.date_to      = $('#f_to').val();
                d.q            = $('#f_q').val();
            }
        },
        columns: [
            {data:'id'},
            {data:'entry_date'},
            {data:'project'},
            {data:'task'},
            {data:'milestone'},
            {data:'employee'},
            {data:'hours', className:'text-end'},
            {data:'hourly_rate', className:'text-end'},
            {data:'cost_amount', className:'text-end'},
            {data:'billable_hours', className:'text-end'},
            {data:'billing_rate', className:'text-end'},
            {data:'billable_amount', className:'text-end'},
            {data:'billable', orderable:false, searchable:false},
            {data:'status', orderable:false, searchable:false},
            {data:'actions', orderable:false, searchable:false},
        ],
        order: [[0, 'desc']],
        drawCallback: function(settings){
            const meta = settings.json?.meta || {};
            $('#sumHoursLbl').text(fmtMoney(meta.total_hours || 0));
            $('#sumCostLbl').text(fmtMoney(meta.total_cost_amount || 0));
            $('#sumBillableHoursLbl').text(fmtMoney(meta.total_billable_hours || 0));
            $('#sumBillableAmountLbl').text(fmtMoney(meta.total_billable_amount || 0));
        }
    });
}

function refreshDT(){
    if (DT) DT.ajax.reload(null, false);
}

$('#applyBtn').on('click', refreshDT);

$('#resetBtn').on('click', function(){
    $('#f_project_id').empty().trigger('change');
    $('#f_employee_id').empty().trigger('change');
    $('#f_status').val('');
    $('#f_is_billable').val('');
    $('#f_from').val('');
    $('#f_to').val('');
    $('#f_q').val('');
    refreshDT();
});

function s2($el, url, placeholder, extraDataFn = null){
    $el.select2({
        theme:'bootstrap-5',
        width:'100%',
        placeholder,
        allowClear:true,
        dropdownParent: $('#timesheetModal'),
        ajax:{
            url,
            dataType:'json',
            delay:200,
            data:function(params){
                let payload = { q: params.term || '' };
                if (typeof extraDataFn === 'function') {
                    payload = Object.assign(payload, extraDataFn());
                }
                return payload;
            },
            processResults:d => d,
            cache:true
        }
    });
}

function setSelect2Value($el, id, text){
    if (!id) return;
    const opt = new Option(text || id, id, true, true);
    $el.append(opt).trigger('change');
}

function resetModal(){
    $('#timesheetForm')[0].reset();
    $('#timesheet_id').val('');
    $('#timesheet_status_badge').html('');
    $('#project_id').empty().trigger('change');
    $('#task_id').empty().trigger('change');
    $('#milestone_id').empty().trigger('change');
    $('#employee_id').empty().trigger('change');

    $('#entry_date').val(new Date().toISOString().slice(0,10));
    $('#hours').val(0);
    $('#hourly_rate').val(0);
    $('#cost_amount').val(0);
    $('#billable_hours').val(0);
    $('#billing_rate').val(0);
    $('#billable_amount').val(0);
    $('#is_billable').val('0');
    $('#status').val('draft');
    $('#source_type').val('manual');
    $('#rejection_reason').val('');
}

function recalcTimesheetAmounts(){
    let hours = parseFloat($('#hours').val() || '0');
    const hourlyRate = parseFloat($('#hourly_rate').val() || '0');
    const isBillable = $('#is_billable').val() === '1';
    let billableHours = parseFloat($('#billable_hours').val() || '0');
    const billingRate = parseFloat($('#billing_rate').val() || '0');

    if (hours < 0) hours = 0;
    $('#cost_amount').val((hours * hourlyRate).toFixed(2));

    if (isBillable) {
        if (billableHours <= 0) {
            billableHours = hours;
            $('#billable_hours').val(billableHours.toFixed(2));
        }
        $('#billable_amount').val((billableHours * billingRate).toFixed(2));
    } else {
        $('#billable_hours').val('0.00');
        $('#billable_amount').val('0.00');
    }
}

$('#hours, #hourly_rate, #billable_hours, #billing_rate').on('input', recalcTimesheetAmounts);

$('#is_billable').on('change', function(){
    recalcTimesheetAmounts();
    const enabled = $(this).val() === '1';
    $('#billable_hours, #billing_rate').prop('disabled', !enabled);
});

$('#project_id').on('change', function(){
    $('#task_id').empty().trigger('change');
    $('#milestone_id').empty().trigger('change');
});

$('#createBtn').on('click', function(){
    resetModal();
    $('#timesheetModalTitle').text('New Timesheet Entry');
    $('#timesheetModal').modal('show');
});

$('#saveTimesheetBtn').on('click', function(){
    const id = $('#timesheet_id').val();
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${baseUrl}/${id}` : baseUrl;

    $.ajax({
        url,
        method,
        data: $('#timesheetForm').serialize()
    })
    .done(function(res){
        $('#timesheetModal').modal('hide');
        swalOk(res.message || 'Saved.');
        refreshDT();
    })
    .fail(function(xhr){
        swalErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-timesheet', function(){
    resetModal();

    const row = $(this).data('json');

    $('#timesheetModalTitle').text('Edit Timesheet Entry');
    $('#timesheet_id').val(row.id);
    $('#entry_date').val(row.entry_date || '');
    $('#start_time').val(row.start_time || '');
    $('#end_time').val(row.end_time || '');
    $('#hours').val(row.hours || 0);
    $('#hourly_rate').val(row.hourly_rate || 0);
    $('#cost_amount').val(row.cost_amount || 0);
    $('#billable_hours').val(row.billable_hours || 0);
    $('#billing_rate').val(row.billing_rate || 0);
    $('#billable_amount').val(row.billable_amount || 0);
    $('#is_billable').val(String(row.is_billable || 0));
    $('#status').val(row.status || 'draft');
    $('#description').val(row.description || '');
    $('#notes').val(row.notes || '');
    $('#rejection_reason').val(row.rejection_reason || '');
    $('#source_type').val(row.source_type || 'manual');
    $('#source_id').val(row.source_id || '');

    $('#timesheet_status_badge').html(
        row.status ? `<span class="badge bg-secondary">${String(row.status).toUpperCase()}</span>` : ''
    );

    if (row.project_id && row.project_label) {
        setSelect2Value($('#project_id'), row.project_id, row.project_label);
    }
    if (row.task_id && row.task_label) {
        setSelect2Value($('#task_id'), row.task_id, row.task_label);
    }
    if (row.milestone_id && row.milestone_label) {
        setSelect2Value($('#milestone_id'), row.milestone_id, row.milestone_label);
    }
    if (row.employee_id && row.employee_label) {
        setSelect2Value($('#employee_id'), row.employee_id, row.employee_label);
    }

    $('#is_billable').trigger('change');
    $('#timesheetModal').modal('show');
});

$(document).on('click', '.btn-del-timesheet', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Delete timesheet entry?',
        text:'This will soft-delete the timesheet entry.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete'
    });

    if (!r.isConfirmed) return;

    $.ajax({
        url: `${baseUrl}/${id}`,
        method: 'DELETE'
    })
    .done(function(res){
        swalOk(res.message || 'Deleted.');
        refreshDT();
    })
    .fail(function(xhr){
        swalErr(xhr?.responseJSON?.message || 'Delete failed.');
    });
});

$(document).on('click', '.btn-submit-timesheet', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'question',
        title:'Submit timesheet?',
        text:'This will send the timesheet for approval.',
        showCancelButton:true,
        confirmButtonText:'Yes, submit'
    });

    if (!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/submit`)
        .done(function(res){
            swalOk(res.message || 'Submitted.');
            refreshDT();
        })
        .fail(function(xhr){
            swalErr(xhr?.responseJSON?.message || 'Submit failed.');
        });
});

$(document).on('click', '.btn-approve-timesheet', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'question',
        title:'Approve timesheet?',
        text:'Approving will create/update labour cost against the linked project.',
        showCancelButton:true,
        confirmButtonText:'Yes, approve'
    });

    if (!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/approve`)
        .done(function(res){
            swalOk(res.message || 'Approved.');
            refreshDT();
        })
        .fail(function(xhr){
            swalErr(xhr?.responseJSON?.message || 'Approve failed.');
        });
});

$(document).on('click', '.btn-reject-timesheet', async function(){
    const id = $(this).data('id');

    const result = await Swal.fire({
        title: 'Reject timesheet',
        input: 'textarea',
        inputLabel: 'Reason',
        inputPlaceholder: 'Enter rejection reason...',
        inputAttributes: {
            'aria-label': 'Enter rejection reason'
        },
        showCancelButton: true,
        confirmButtonText: 'Reject',
        inputValidator: (value) => {
            if (!value) return 'Rejection reason is required.';
        }
    });

    if (!result.isConfirmed) return;

    $.post(`${baseUrl}/${id}/reject`, {
        rejection_reason: result.value
    })
    .done(function(res){
        swalOk(res.message || 'Rejected.');
        refreshDT();
    })
    .fail(function(xhr){
        swalErr(xhr?.responseJSON?.message || 'Reject failed.');
    });
});

$(function(){
    s2($('#f_project_id'), projectsUrl, 'Project...');
    s2($('#f_employee_id'), employeesUrl, 'Employee...');

    s2($('#project_id'), projectsUrl, 'Select project...');
    s2($('#task_id'), tasksUrl, 'Select task...', function(){
        return { project_id: $('#project_id').val() || '' };
    });
    s2($('#milestone_id'), milestonesUrl, 'Select milestone...', function(){
        return { project_id: $('#project_id').val() || '' };
    });
    s2($('#employee_id'), employeesUrl, 'Select employee...');

    resetModal();
    $('#is_billable').trigger('change');

    initDT();
});
</script>
@endpush