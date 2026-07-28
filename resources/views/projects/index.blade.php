@extends('layouts.master')

@section('title', 'Projects')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Projects</h1>
            <small class="text-muted">Projects / Portfolio Management</small>
        </div>

        <button class="btn btn-primary" id="createBtn">
            <i class="fas fa-plus"></i> New Project
        </button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="text-muted small">Status</label>
                    <select class="form-control" id="f_status">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="planned">Planned</option>
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
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
                    <label class="text-muted small">From</label>
                    <input type="date" class="form-control" id="f_from">
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">To</label>
                    <input type="date" class="form-control" id="f_to">
                </div>

                <div class="col-md-3">
                    <label class="text-muted small">Search</label>
                    <input type="text" class="form-control" id="f_q" placeholder="project code, name, client, manager...">
                </div>

                <div class="col-md-1 d-flex gap-2">
                    <button class="btn btn-outline-primary w-100" id="applyBtn">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex gap-2 mt-2">
                <button class="btn btn-outline-secondary" id="resetBtn">
                    <i class="fas fa-undo"></i> Reset
                </button>

                <div class="alert alert-info mb-0 flex-grow-1">
                    <i class="fas fa-info-circle me-1"></i>
                    Create and manage projects here. Tasks, milestones, costs, and timesheets can be linked in the next phases.
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="projectsTable" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th style="width:140px;">Code</th>
                            <th>Project Name</th>
                            <th style="width:180px;">Client</th>
                            <th style="width:180px;">Manager</th>
                            <th style="width:110px;">Start</th>
                            <th style="width:110px;">End</th>
                            <th style="width:130px;" class="text-end">Budget</th>
                            <th style="width:130px;" class="text-end">Actual Cost</th>
                            <th style="width:110px;">Priority</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:210px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('projects.partials.modal')
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

const dtUrl        = "{{ route('admin.projects.datatable') }}";
const baseUrl      = "{{ url('admin/projects') }}";
const clientsUrl   = "{{ route('admin.projects.lookups.clients') }}";
const managersUrl  = "{{ route('admin.projects.lookups.managers') }}";

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
    DT = $('#projectsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        ajax: {
            url: dtUrl,
            data: function(d){
                d.status    = $('#f_status').val();
                d.priority  = $('#f_priority').val();
                d.date_from = $('#f_from').val();
                d.date_to   = $('#f_to').val();
                d.q         = $('#f_q').val();
            }
        },
        columns: [
            {data:'id'},
            {data:'project_code'},
            {data:'project_name'},
            {data:'client'},
            {data:'project_manager'},
            {data:'start_date'},
            {data:'end_date'},
            {data:'budget', className:'text-end'},
            {data:'actual_cost', className:'text-end'},
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
    $('#f_status').val('');
    $('#f_priority').val('');
    $('#f_from').val('');
    $('#f_to').val('');
    $('#f_q').val('');
    refreshDT();
});

function s2($el, url, placeholder){
    $el.select2({
        theme:'bootstrap-5',
        width:'100%',
        placeholder,
        allowClear:true,
        dropdownParent: $('#projectModal'),
        ajax:{
            url,
            dataType:'json',
            delay:200,
            data: p => ({ q: p.term || '' }),
            processResults: d => d,
            cache:true
        }
    });
}

function setSelect2Value($el, id, text){
    if(!id) return;
    const opt = new Option(text || id, id, true, true);
    $el.append(opt).trigger('change');
}

function resetModal(){
    $('#projectForm')[0].reset();
    $('#project_id').val('');
    $('#project_status_badge').html('');
    $('#client_id').empty().trigger('change');
    $('#project_manager_id').empty().trigger('change');
}

$('#createBtn').on('click', function(){
    resetModal();
    $('#projectModalTitle').text('New Project');
    $('#projectModal').modal('show');
});

$('#saveProjectBtn').on('click', function(){
    const id = $('#project_id').val();
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${baseUrl}/${id}` : baseUrl;

    $.ajax({
        url,
        method,
        data: $('#projectForm').serialize()
    })
    .done(function(res){
        $('#projectModal').modal('hide');
        swalOk(res.message || 'Saved.');
        refreshDT();
    })
    .fail(function(xhr){
        swalErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-project', function(){
    resetModal();

    const project = $(this).data('json');

    $('#projectModalTitle').text('Edit Project');
    $('#project_id').val(project.id);
    $('#project_code').val(project.project_code || '');
    $('#project_name').val(project.project_name || '');
    $('#status').val(project.status || 'draft');
    $('#priority').val(project.priority || 'medium');
    $('#start_date').val(project.start_date || '');
    $('#end_date').val(project.end_date || '');
    $('#budget').val(project.budget || 0);
    $('#actual_cost').val(project.actual_cost || 0);
    $('#description').val(project.description || '');
    $('#notes').val(project.notes || '');

    $('#project_status_badge').html(
        project.status ? `<span class="badge bg-secondary">${String(project.status).toUpperCase()}</span>` : ''
    );

    if (project.client_id && project.client_label) {
        setSelect2Value($('#client_id'), project.client_id, project.client_label);
    }

    if (project.project_manager_id && project.project_manager_label) {
        setSelect2Value($('#project_manager_id'), project.project_manager_id, project.project_manager_label);
    }

    $('#projectModal').modal('show');
});

$(document).on('click', '.btn-del-project', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Delete project?',
        text:'This will soft-delete the project.',
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
    s2($('#client_id'), clientsUrl, 'Select client...');
    s2($('#project_manager_id'), managersUrl, 'Select project manager...');
    initDT();
});
</script>
@endpush