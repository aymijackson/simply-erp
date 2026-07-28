@extends('layouts.master')

@section('title', 'Project Tasks')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Project Tasks</h1>
            <small class="text-muted">Projects / Task Management</small>
        </div>

        <button class="btn btn-primary" id="createBtn">
            <i class="fas fa-plus"></i> New Task
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
                    <label class="text-muted small">Status</label>
                    <select class="form-control" id="f_status">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="blocked">Blocked</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Priority</label>
                    <select class="form-control" id="f_priority">
                        <option value="">All</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Assigned To</label>
                    <select class="form-control" id="f_assigned_to" style="width:100%"></select>
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
                    <input type="text" class="form-control" id="f_q" placeholder="task, code, project...">
                </div>
            </div>

            <div class="d-flex gap-2 mt-2">
                <button class="btn btn-outline-primary" id="applyBtn">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <button class="btn btn-outline-secondary" id="resetBtn">
                    <i class="fas fa-undo"></i> Reset
                </button>

                <div class="alert alert-info mb-0 flex-grow-1">
                    <i class="fas fa-info-circle me-1"></i>
                    Tasks can be linked to projects, assigned to team members, and tracked by status, hours, and progress.
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="tasksTable" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th style="width:130px;">Code</th>
                            <th>Task</th>
                            <th style="width:220px;">Project</th>
                            <th style="width:180px;">Parent Task</th>
                            <th style="width:150px;">Assignee</th>
                            <th style="width:110px;">Start</th>
                            <th style="width:110px;">Due</th>
                            <th style="width:110px;" class="text-end">Est. Hrs</th>
                            <th style="width:110px;" class="text-end">Act. Hrs</th>
                            <th style="width:140px;">Progress</th>
                            <th style="width:100px;">Priority</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:210px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('projects.tasks.partials.modal')
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

const dtUrl           = "{{ route('admin.project_tasks.datatable') }}";
const baseUrl         = "{{ url('admin/project-tasks') }}";
const projectsUrl     = "{{ route('admin.project_tasks.lookups.projects') }}";
const parentTasksUrl  = "{{ route('admin.project_tasks.lookups.parent_tasks') }}";
const employeesUrl    = "{{ route('admin.project_tasks.lookups.employees') }}";

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

function initDT(){
    DT = $('#tasksTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        ajax: {
            url: dtUrl,
            data: function(d){
                d.project_id  = $('#f_project_id').val();
                d.status      = $('#f_status').val();
                d.priority    = $('#f_priority').val();
                d.assigned_to = $('#f_assigned_to').val();
                d.date_from   = $('#f_from').val();
                d.date_to     = $('#f_to').val();
                d.q           = $('#f_q').val();
            }
        },
        columns: [
            {data:'id'},
            {data:'task_code'},
            {data:'task_name'},
            {data:'project'},
            {data:'parent_task'},
            {data:'assignee'},
            {data:'start_date'},
            {data:'due_date'},
            {data:'estimated_hours', className:'text-end'},
            {data:'actual_hours', className:'text-end'},
            {data:'progress', orderable:false, searchable:false},
            {data:'priority', orderable:false, searchable:false},
            {data:'status', orderable:false, searchable:false},
            {data:'actions', orderable:false, searchable:false},
        ],
        order: [[0, 'desc']]
    });
}

function refreshDT(){
    if (DT) DT.ajax.reload(null, false);
}

$('#applyBtn').on('click', refreshDT);

$('#resetBtn').on('click', function(){
    $('#f_project_id').empty().trigger('change');
    $('#f_status').val('');
    $('#f_priority').val('');
    $('#f_assigned_to').empty().trigger('change');
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
        dropdownParent: $('#taskModal'),
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
    $('#taskForm')[0].reset();
    $('#task_id').val('');
    $('#task_status_badge').html('');
    $('#project_id').empty().trigger('change');
    $('#parent_task_id').empty().trigger('change');
    $('#assigned_to').empty().trigger('change');
    $('#progress_percent').val(0);
    $('#estimated_hours').val(0);
    $('#actual_hours').val(0);
    $('#sort_order').val(0);
}

$('#createBtn').on('click', function(){
    resetModal();
    $('#taskModalTitle').text('New Task');
    $('#taskModal').modal('show');
});

$('#project_id').on('change', function(){
    $('#parent_task_id').empty().trigger('change');
});

$('#saveTaskBtn').on('click', function(){
    const id = $('#task_id').val();
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${baseUrl}/${id}` : baseUrl;

    $.ajax({
        url,
        method,
        data: $('#taskForm').serialize()
    })
    .done(function(res){
        $('#taskModal').modal('hide');
        swalOk(res.message || 'Saved.');
        refreshDT();
    })
    .fail(function(xhr){
        swalErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-task', function(){
    resetModal();

    const task = $(this).data('json');

    $('#taskModalTitle').text('Edit Task');
    $('#task_id').val(task.id);
    $('#task_code').val(task.task_code || '');
    $('#task_name').val(task.task_name || '');
    $('#description').val(task.description || '');
    $('#status').val(task.status || 'pending');
    $('#priority').val(task.priority || 'medium');
    $('#start_date').val(task.start_date || '');
    $('#due_date').val(task.due_date || '');
    $('#estimated_hours').val(task.estimated_hours || 0);
    $('#actual_hours').val(task.actual_hours || 0);
    $('#progress_percent').val(task.progress_percent || 0);
    $('#sort_order').val(task.sort_order || 0);
    $('#notes').val(task.notes || '');

    $('#task_status_badge').html(
        task.status ? `<span class="badge bg-secondary">${String(task.status).toUpperCase()}</span>` : ''
    );

    if (task.project_id && task.project_label) {
        setSelect2Value($('#project_id'), task.project_id, task.project_label);
    }

    if (task.parent_task_id && task.parent_task_label) {
        setSelect2Value($('#parent_task_id'), task.parent_task_id, task.parent_task_label);
    }

    if (task.assigned_to && task.assigned_label) {
        setSelect2Value($('#assigned_to'), task.assigned_to, task.assigned_label);
    }

    $('#taskModal').modal('show');
});

$(document).on('click', '.btn-del-task', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Delete task?',
        text:'This will soft-delete the task.',
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
    s2($('#f_assigned_to'), employeesUrl, 'Assignee...');

    s2($('#project_id'), projectsUrl, 'Select project...');
    s2($('#parent_task_id'), parentTasksUrl, 'Select parent task...', function(){
        return { project_id: $('#project_id').val() || '' };
    });
    s2($('#assigned_to'), employeesUrl, 'Select assignee...');

    initDT();
});
</script>
@endpush