@extends('layouts.master')

@section('title', 'Project Budgets')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Project Budgets</h1>
            <small class="text-muted">Projects / Budgeting / Budget vs Actual</small>
        </div>

        <button class="btn btn-primary" id="createBtn">
            <i class="fas fa-plus"></i> New Budget
        </button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="text-muted small">Project</label>
                    <select class="form-control" id="f_project_id" style="width:100%"></select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Status</label>
                    <select class="form-control" id="f_status">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="approved">Approved</option>
                        <option value="revised">Revised</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">From</label>
                    <input type="date" class="form-control" id="f_from">
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">To</label>
                    <input type="date" class="form-control" id="f_to">
                </div>

                <div class="col-md-3">
                    <label class="text-muted small">Search</label>
                    <input type="text" class="form-control" id="f_q" placeholder="budget code, name, project...">
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
                    Approved budgets form the baseline for budget vs actual comparison using posted project costs and approved timesheet labour costs.
                </div>

                <div class="fw-bold text-end">
                    Total Budget: <span id="totalBudgetLbl">0.00</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="budgetsTable" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th style="width:220px;">Project</th>
                            <th style="width:120px;">Budget Code</th>
                            <th>Budget Name</th>
                            <th style="width:80px;">Version</th>
                            <th style="width:220px;">Period</th>
                            <th style="width:120px;" class="text-end">Budget</th>
                            <th style="width:120px;" class="text-end">Actual</th>
                            <th style="width:120px;" class="text-end">Variance</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('projects.budgets.partials.modal')
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

const dtUrl         = "{{ route('admin.project_budgets.datatable') }}";
const baseUrl       = "{{ url('admin/project-budgets') }}";
const projectsUrl   = "{{ route('admin.project_budgets.lookups.projects') }}";
const tasksUrl      = "{{ route('admin.project_budgets.lookups.tasks') }}";
const milestonesUrl = "{{ route('admin.project_budgets.lookups.milestones') }}";

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
    DT = $('#budgetsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        ajax: {
            url: dtUrl,
            data: function(d){
                d.project_id = $('#f_project_id').val();
                d.status     = $('#f_status').val();
                d.date_from  = $('#f_from').val();
                d.date_to    = $('#f_to').val();
                d.q          = $('#f_q').val();
            }
        },
        columns: [
            {data:'id'},
            {data:'project'},
            {data:'budget_code'},
            {data:'budget_name'},
            {data:'version_no'},
            {data:'period'},
            {data:'budget_amount', className:'text-end'},
            {data:'actual_amount', className:'text-end'},
            {data:'variance', className:'text-end'},
            {data:'status', orderable:false, searchable:false},
            {data:'actions', orderable:false, searchable:false},
        ],
        order: [[0, 'desc']],
        drawCallback: function(settings){
            const meta = settings.json?.meta || {};
            $('#totalBudgetLbl').text(fmtMoney(meta.total_budget_amount || 0));
        }
    });
}

function refreshDT(){
    if (DT) DT.ajax.reload(null, false);
}

$('#applyBtn').on('click', refreshDT);

$('#resetBtn').on('click', function(){
    $('#f_project_id').empty().trigger('change');
    $('#f_status').val('');
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
        dropdownParent: $('#budgetModal'),
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
    $('#budgetForm')[0].reset();
    $('#budget_id').val('');
    $('#budget_status_badge').html('');
    $('#project_id').empty().trigger('change');
    $('#budget_lines_tbody').html('');
    $('#currency_code').val('NGN');
    $('#status').val('draft');
    $('#version_no').val(1);
    addLine();
    recalcBudgetTotal();
}

function addLine(line = null){
    const idx = Date.now() + Math.floor(Math.random() * 1000);

    const tr = $(`
        <tr data-row="${idx}">
            <td style="width:18%">
                <select class="form-control task-line" name="lines[${idx}][task_id]"></select>
            </td>
            <td style="width:18%">
                <select class="form-control milestone-line" name="lines[${idx}][milestone_id]"></select>
            </td>
            <td style="width:14%">
                <select class="form-control" name="lines[${idx}][cost_category]">
                    <option value="materials">Materials</option>
                    <option value="labour">Labour</option>
                    <option value="logistics">Logistics</option>
                    <option value="subcontract">Subcontract</option>
                    <option value="overhead">Overhead</option>
                    <option value="expense">Expense</option>
                    <option value="other">Other</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control" name="lines[${idx}][line_description]" value="${line?.line_description ?? ''}" placeholder="Description">
            </td>
            <td style="width:9%">
                <input type="number" step="0.01" min="0.01" class="form-control text-end line-qty" name="lines[${idx}][quantity]" value="${line?.quantity ?? 1}">
            </td>
            <td style="width:11%">
                <input type="number" step="0.01" min="0" class="form-control text-end line-unit" name="lines[${idx}][unit_cost]" value="${line?.unit_cost ?? 0}">
            </td>
            <td style="width:11%">
                <input type="number" step="0.01" min="0" class="form-control text-end line-amount" name="lines[${idx}][budget_amount]" value="${line?.budget_amount ?? 0}">
            </td>
            <td style="width:7%">
                <button type="button" class="btn btn-outline-danger btn-sm btn-del-line"><i class="fas fa-times"></i></button>
            </td>
        </tr>
    `);

    $('#budget_lines_tbody').append(tr);

    const $task = tr.find('select.task-line');
    const $milestone = tr.find('select.milestone-line');

    s2($task, tasksUrl, 'Task...', function(){
        return { project_id: $('#project_id').val() || '' };
    });

    s2($milestone, milestonesUrl, 'Milestone...', function(){
        return { project_id: $('#project_id').val() || '' };
    });

    if (line?.task_id && line?.task_label) {
        setSelect2Value($task, line.task_id, line.task_label);
    }
    if (line?.milestone_id && line?.milestone_label) {
        setSelect2Value($milestone, line.milestone_id, line.milestone_label);
    }
    if (line?.cost_category) {
        tr.find(`select[name="lines[${idx}][cost_category]"]`).val(line.cost_category);
    }

    recalcBudgetTotal();
}

function recalcBudgetTotal(){
    let total = 0;

    $('#budget_lines_tbody tr').each(function(){
        const $tr = $(this);
        const qty = parseFloat($tr.find('.line-qty').val() || '0');
        const unit = parseFloat($tr.find('.line-unit').val() || '0');
        let amount = parseFloat($tr.find('.line-amount').val() || '0');

        if (amount <= 0) {
            amount = qty * unit;
            $tr.find('.line-amount').val(amount.toFixed(2));
        }

        total += amount;
    });

    $('#budget_total_lbl').text(fmtMoney(total));
}

$(document).on('input', '.line-qty,.line-unit,.line-amount', recalcBudgetTotal);

$(document).on('click', '#addBudgetLineBtn', function(){
    addLine();
});

$(document).on('click', '.btn-del-line', function(){
    $(this).closest('tr').remove();
    if ($('#budget_lines_tbody tr').length < 1) addLine();
    recalcBudgetTotal();
});

$('#project_id').on('change', function(){
    $('#budget_lines_tbody .task-line').each(function(){
        $(this).empty().trigger('change');
    });
    $('#budget_lines_tbody .milestone-line').each(function(){
        $(this).empty().trigger('change');
    });
});

$('#createBtn').on('click', function(){
    resetModal();
    $('#budgetModalTitle').text('New Project Budget');
    $('#budgetModal').modal('show');
});

$('#saveBudgetBtn').on('click', function(){
    const id = $('#budget_id').val();
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${baseUrl}/${id}` : baseUrl;

    $.ajax({
        url,
        method,
        data: $('#budgetForm').serialize()
    })
    .done(function(res){
        $('#budgetModal').modal('hide');
        swalOk(res.message || 'Saved.');
        refreshDT();
    })
    .fail(function(xhr){
        swalErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-budget', function(){
    resetModal();

    const row = $(this).data('json');

    $('#budgetModalTitle').text('Edit Project Budget');
    $('#budget_id').val(row.id);
    $('#budget_code').val(row.budget_code || '');
    $('#budget_name').val(row.budget_name || '');
    $('#version_no').val(row.version_no || 1);
    $('#budget_start_date').val(row.budget_start_date || '');
    $('#budget_end_date').val(row.budget_end_date || '');
    $('#currency_code').val(row.currency_code || 'NGN');
    $('#status').val(row.status || 'draft');
    $('#notes').val(row.notes || '');

    $('#budget_status_badge').html(
        row.status ? `<span class="badge bg-secondary">${String(row.status).toUpperCase()}</span>` : ''
    );

    if (row.project_id && row.project_label) {
        setSelect2Value($('#project_id'), row.project_id, row.project_label);
    }

    $.get(`${baseUrl}/${row.id}/lines`)
        .done(function(res){
            $('#budget_lines_tbody').html('');
            (res.lines || []).forEach(line => addLine(line));
            if ($('#budget_lines_tbody tr').length < 1) addLine();
            recalcBudgetTotal();
            $('#budgetModal').modal('show');
        })
        .fail(function(){
            swalErr('Could not load budget lines.');
        });
});

$(document).on('click', '.btn-del-budget', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Delete project budget?',
        text:'This will delete the budget and its lines.',
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

$(function(){
    s2($('#f_project_id'), projectsUrl, 'Project...');
    s2($('#project_id'), projectsUrl, 'Select project...');
    initDT();
});
</script>
@endpush