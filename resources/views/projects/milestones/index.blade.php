@extends('layouts.master')

@section('title', 'Project Milestones')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Project Milestones</h1>
            <small class="text-muted">Projects / Milestone Management</small>
        </div>

        <button class="btn btn-primary" id="createBtn">
            <i class="fas fa-plus"></i> New Milestone
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
                    <label class="text-muted small">Owner</label>
                    <select class="form-control" id="f_owner_id" style="width:100%"></select>
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
                    <input type="text" class="form-control" id="f_q" placeholder="milestone, code, project...">
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
                    Milestones help track key checkpoints, ownership, timeline, and completion progress across projects.
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="milestonesTable" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th style="width:130px;">Code</th>
                            <th>Milestone</th>
                            <th style="width:220px;">Project</th>
                            <th style="width:160px;">Owner</th>
                            <th style="width:110px;">Target Date</th>
                            <th style="width:110px;" class="text-end">Weight %</th>
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

@include('projects.milestones.partials.modal')
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

const dtUrl         = "{{ route('admin.project_milestones.datatable') }}";
const baseUrl       = "{{ url('admin/project-milestones') }}";
const projectsUrl   = "{{ route('admin.project_milestones.lookups.projects') }}";
const ownersUrl     = "{{ route('admin.project_milestones.lookups.owners') }}";

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
    DT = $('#milestonesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        ajax: {
            url: dtUrl,
            data: function(d){
                d.project_id = $('#f_project_id').val();
                d.status     = $('#f_status').val();
                d.priority   = $('#f_priority').val();
                d.owner_id   = $('#f_owner_id').val();
                d.date_from  = $('#f_from').val();
                d.date_to    = $('#f_to').val();
                d.q          = $('#f_q').val();
            }
        },
        columns: [
            {data:'id'},
            {data:'milestone_code'},
            {data:'milestone_name'},
            {data:'project'},
            {data:'owner'},
            {data:'target_date'},
            {data:'weight_percent', className:'text-end'},
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
    $('#f_owner_id').empty().trigger('change');
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
        dropdownParent: $('#milestoneModal'),
        ajax:{
            url,
            dataType:'json',
            delay:200,
            data: p => ({ q: p.term || '' }),
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
    $('#milestoneForm')[0].reset();
    $('#milestone_id').val('');
    $('#milestone_status_badge').html('');
    $('#project_id').empty().trigger('change');
    $('#owner_id').empty().trigger('change');
    $('#progress_percent').val(0);
    $('#weight_percent').val(0);
    $('#sort_order').val(0);
}

$('#createBtn').on('click', function(){
    resetModal();
    $('#milestoneModalTitle').text('New Milestone');
    $('#milestoneModal').modal('show');
});

$('#saveMilestoneBtn').on('click', function(){
    const id = $('#milestone_id').val();
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${baseUrl}/${id}` : baseUrl;

    $.ajax({
        url,
        method,
        data: $('#milestoneForm').serialize()
    })
    .done(function(res){
        $('#milestoneModal').modal('hide');
        swalOk(res.message || 'Saved.');
        refreshDT();
    })
    .fail(function(xhr){
        swalErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-milestone', function(){
    resetModal();

    const milestone = $(this).data('json');

    $('#milestoneModalTitle').text('Edit Milestone');
    $('#milestone_id').val(milestone.id);
    $('#milestone_code').val(milestone.milestone_code || '');
    $('#milestone_name').val(milestone.milestone_name || '');
    $('#description').val(milestone.description || '');
    $('#status').val(milestone.status || 'pending');
    $('#priority').val(milestone.priority || 'medium');
    $('#target_date').val(milestone.target_date || '');
    $('#progress_percent').val(milestone.progress_percent || 0);
    $('#weight_percent').val(milestone.weight_percent || 0);
    $('#sort_order').val(milestone.sort_order || 0);
    $('#notes').val(milestone.notes || '');

    $('#milestone_status_badge').html(
        milestone.status ? `<span class="badge bg-secondary">${String(milestone.status).toUpperCase()}</span>` : ''
    );

    if (milestone.project_id && milestone.project_label) {
        setSelect2Value($('#project_id'), milestone.project_id, milestone.project_label);
    }

    if (milestone.owner_id && milestone.owner_label) {
        setSelect2Value($('#owner_id'), milestone.owner_id, milestone.owner_label);
    }

    $('#milestoneModal').modal('show');
});

$(document).on('click', '.btn-del-milestone', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Delete milestone?',
        text:'This will soft-delete the milestone.',
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
    s2($('#f_owner_id'), ownersUrl, 'Owner...');
    s2($('#project_id'), projectsUrl, 'Select project...');
    s2($('#owner_id'), ownersUrl, 'Select owner...');

    initDT();
});
</script>
@endpush