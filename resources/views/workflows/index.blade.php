@extends('layouts.master')

@section('title', 'Workflow Automation')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 text-primary mb-0">Workflow Automation</h1>
            <small class="text-muted">Manage process automation rules, triggers and execution steps</small>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-danger d-none" id="bulkDeleteBtn">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="createBtn">
                <i class="fas fa-plus me-1"></i> New Workflow
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="text-muted small">Module</label>
                    <select class="form-control" id="f_module">
                        <option value="">All</option>
                        <option value="finance">Finance</option>
                        <option value="procurement">Procurement</option>
                        <option value="inventory">Inventory</option>
                        <option value="projects">Projects</option>
                        <option value="crm">CRM</option>
                        <option value="support">Support</option>
                        <option value="production">Production</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Status</label>
                    <select class="form-control" id="f_status">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="text-muted small">Trigger Event</label>
                    <input type="text" class="form-control" id="f_trigger_event" placeholder="requisition_created, low_stock...">
                </div>

                <div class="col-md-3">
                    <label class="text-muted small">Search</label>
                    <input type="text" class="form-control" id="f_q" placeholder="name, module, trigger...">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-outline-primary w-100" id="applyBtn">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <button class="btn btn-outline-secondary w-100" id="resetBtn">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Each workflow has a module, a trigger event and one or more ordered steps.
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="workflowTable" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
                            <th style="width:70px;">ID</th>
                            <th>Name</th>
                            <th style="width:120px;">Module</th>
                            <th style="width:180px;">Trigger Event</th>
                            <th style="width:100px;">Steps</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:150px;">Created</th>
                            <th style="width:280px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('workflows.partials.form-modal')
@include('workflows.partials.view-modal')
@include('workflows.partials.logs-modal')
@endsection

@push('styles')
<style>
    .workflow-step-card{
        border:1px dashed #cbd5e1;
        border-radius:.75rem;
        padding:.75rem;
        background:#f8fafc;
        margin-bottom:.75rem;
    }
</style>
@endpush

@push('scripts')
<script>
$.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
});

const dtUrl         = "{{ route('admin.workflows.datatable') }}";
const baseUrl       = "{{ url('admin/workflows') }}";
const bulkDeleteUrl = "{{ route('admin.workflows.bulkDelete') }}";

let DT = null;

function swalOk(msg){
    if (window.Swal?.fire) return Swal.fire({icon:'success', title:'Success', text:msg || 'Done.'});
    alert(msg || 'Done.');
}
function swalErr(msg){
    if (window.Swal?.fire) return Swal.fire({icon:'error', title:'Error', text:msg || 'Something went wrong.'});
    alert(msg || 'Something went wrong.');
}
function swalAsk(opts){
    if (window.Swal?.fire) return Swal.fire(opts);
    return Promise.resolve({isConfirmed: confirm(opts?.title || 'Confirm?')});
}
function swalLoading(title='Processing...'){
    if (window.Swal?.fire) {
        Swal.fire({
            title,
            allowOutsideClick:false,
            allowEscapeKey:false,
            didOpen:()=>Swal.showLoading()
        });
    }
}
function swalClose(){
    if (window.Swal?.close) Swal.close();
}

function initDT(){
    DT = $('#workflowTable').DataTable({
        processing:true,
        serverSide:true,
        responsive:true,
        pageLength:10,
        ajax:{
            url:dtUrl,
            data:function(d){
                d.module = $('#f_module').val();
                d.status = $('#f_status').val();
                d.trigger_event = $('#f_trigger_event').val();
                d.q = $('#f_q').val();
            }
        },
        columns:[
            {data:'check', orderable:false, searchable:false},
            {data:'id'},
            {data:'name'},
            {data:'module'},
            {data:'trigger_event'},
            {data:'steps_count'},
            {data:'status', orderable:false, searchable:false},
            {data:'created_at'},
            {data:'actions', orderable:false, searchable:false},
        ],
        order:[[1,'desc']],
        drawCallback:function(){
            $('#checkAll').prop('checked', false);
            toggleBulkDeleteBtn();
        },
        columnDefs:[
            {targets:[0,6,8], render:function(data){ return data; }}
        ]
    });
}

function refreshDT(){
    if (DT) DT.ajax.reload(null, false);
}

function toggleBulkDeleteBtn(){
    const any = $('.row-check:checked').length > 0;
    $('#bulkDeleteBtn').toggleClass('d-none', !any);
}

$('#applyBtn').on('click', refreshDT);

$('#resetBtn').on('click', function(){
    $('#f_module').val('');
    $('#f_status').val('');
    $('#f_trigger_event').val('');
    $('#f_q').val('');
    refreshDT();
});

$('#checkAll').on('change', function(){
    $('.row-check').prop('checked', this.checked).trigger('change');
});

$(document).on('change', '.row-check', function(){
    toggleBulkDeleteBtn();
});

$('#bulkDeleteBtn').on('click', async function(){
    const ids = $('.row-check:checked').map((i,el)=>$(el).val()).get();
    if (!ids.length) return;

    const r = await swalAsk({
        icon:'warning',
        title:'Delete selected workflows?',
        text:'Steps and logs will also be deleted.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete'
    });

    if (!r.isConfirmed) return;

    $.post(bulkDeleteUrl, {ids})
        .done(res => {
            swalOk(res.message || 'Deleted.');
            refreshDT();
        })
        .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Failed to delete selected workflows.'));
});

function resetWorkflowForm(){
    $('#workflowForm')[0].reset();
    $('#workflow_id').val('');
    $('#stepsContainer').html('');
    addStepRow();
    $('#workflow_is_active').prop('checked', true);
}

function addStepRow(step = null){
    const idx = Date.now() + Math.floor(Math.random() * 1000);

    const html = `
        <div class="workflow-step-card" data-row="${idx}">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="text-muted small">Order</label>
                    <input type="number" min="1" class="form-control" name="steps[${idx}][step_order]" value="${step?.step_order ?? 1}">
                </div>

                <div class="col-md-3">
                    <label class="text-muted small">Action Type</label>
                    <select class="form-control" name="steps[${idx}][action_type]">
                        <option value="notification" ${step?.action_type === 'notification' ? 'selected' : ''}>Notification</option>
                        <option value="create_record" ${step?.action_type === 'create_record' ? 'selected' : ''}>Create Record</option>
                        <option value="update_record" ${step?.action_type === 'update_record' ? 'selected' : ''}>Update Record</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="text-muted small">Action Target</label>
                    <input type="text" class="form-control" name="steps[${idx}][action_target]" value="${step?.action_target ?? ''}" placeholder="purchase_requisition, procurement_manager...">
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Delay (mins)</label>
                    <input type="number" min="0" class="form-control" name="steps[${idx}][delay_minutes]" value="${step?.delay_minutes ?? 0}">
                </div>

                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger w-100 btn-del-step">
                        <i class="fas fa-trash me-1"></i> Remove
                    </button>
                </div>

                <div class="col-md-12">
                    <label class="text-muted small">Action Value / Message</label>
                    <textarea class="form-control" rows="2" name="steps[${idx}][action_value]" placeholder="Message, payload, or action text...">${step?.action_value ?? ''}</textarea>
                </div>
            </div>
        </div>
    `;

    $('#stepsContainer').append(html);
}

$(document).on('click', '#addStepBtn', function(){
    addStepRow();
});

$(document).on('click', '.btn-del-step', function(){
    $(this).closest('.workflow-step-card').remove();
    if ($('#stepsContainer .workflow-step-card').length < 1) addStepRow();
});

$('#createBtn').on('click', function(){
    resetWorkflowForm();
    $('#workflowModalTitle').text('New Workflow');
    $('#workflowModal').modal('show');
});

$('#saveWorkflowBtn').on('click', function(){
    const id = $('#workflow_id').val();
    const url = id ? `${baseUrl}/${id}` : baseUrl;
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url,
        method,
        data: $('#workflowForm').serialize()
    })
    .done(res => {
        $('#workflowModal').modal('hide');
        swalOk(res.message || 'Saved.');
        refreshDT();
    })
    .fail(xhr => {
        swalErr(xhr?.responseJSON?.message || 'Failed to save workflow.');
    });
});

$(document).on('click', '.btn-edit-workflow', function(){
    const id = $(this).data('id');

    resetWorkflowForm();
    swalLoading('Loading workflow...');

    $.get(`${baseUrl}/${id}`)
        .done(function(res){
            swalClose();

            $('#workflowModalTitle').text('Edit Workflow');
            $('#workflow_id').val(res.id);
            $('#workflow_name').val(res.name || '');
            $('#workflow_module').val(res.module || '');
            $('#workflow_trigger_event').val(res.trigger_event || '');
            $('#workflow_is_active').prop('checked', parseInt(res.is_active) === 1);

            $('#stepsContainer').html('');
            (res.steps || []).forEach(step => addStepRow(step));
            if ($('#stepsContainer .workflow-step-card').length < 1) addStepRow();

            $('#workflowModal').modal('show');
        })
        .fail(function(xhr){
            swalClose();
            swalErr(xhr?.responseJSON?.message || 'Failed to load workflow.');
        });
});

$(document).on('click', '.btn-view-workflow', function(){
    const id = $(this).data('id');

    swalLoading('Loading workflow...');
    $.get(`${baseUrl}/${id}`)
        .done(function(res){
            swalClose();

            $('#view_workflow_name').text(res.name || '—');
            $('#view_workflow_module').text(res.module || '—');
            $('#view_workflow_trigger_event').text(res.trigger_event || '—');
            $('#view_workflow_status').html(
                parseInt(res.is_active) === 1
                    ? '<span class="badge bg-success">ACTIVE</span>'
                    : '<span class="badge bg-secondary">INACTIVE</span>'
            );
            $('#view_workflow_created_at').text(res.created_at || '—');

            let html = '';
            (res.steps || []).forEach(step => {
                html += `
                    <tr>
                        <td>${step.step_order ?? ''}</td>
                        <td>${step.action_type ?? ''}</td>
                        <td>${step.action_target ?? ''}</td>
                        <td>${step.delay_minutes ?? 0}</td>
                        <td>${step.action_value ?? ''}</td>
                    </tr>
                `;
            });

            if (!html) {
                html = `<tr><td colspan="5" class="text-center text-muted">No steps found</td></tr>`;
            }

            $('#view_workflow_steps').html(html);
            $('#workflowViewModal').modal('show');
        })
        .fail(function(xhr){
            swalClose();
            swalErr(xhr?.responseJSON?.message || 'Failed to load workflow.');
        });
});

$(document).on('click', '.btn-toggle-workflow', function(){
    const id = $(this).data('id');

    $.post(`${baseUrl}/${id}/toggle`)
        .done(res => {
            swalOk(res.message || 'Status updated.');
            refreshDT();
        })
        .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Failed to update workflow status.'));
});

$(document).on('click', '.btn-del-workflow', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Delete workflow?',
        text:'Steps and logs will also be deleted.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete'
    });

    if (!r.isConfirmed) return;

    $.ajax({
        url: `${baseUrl}/${id}`,
        method: 'DELETE'
    })
    .done(res => {
        swalOk(res.message || 'Deleted.');
        refreshDT();
    })
    .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Failed to delete workflow.'));
});

$(document).on('click', '.btn-logs-workflow', function(){
    const id = $(this).data('id');

    swalLoading('Loading workflow logs...');
    $.get(`${baseUrl}/${id}/logs`)
        .done(function(res){
            swalClose();

            $('#logs_workflow_name').text(res.workflow?.name || 'Workflow Logs');

            let html = '';
            (res.logs || []).forEach(log => {
                html += `
                    <tr>
                        <td>${log.id}</td>
                        <td>${log.reference_type ?? ''}${log.reference_id ? ' #' + log.reference_id : ''}</td>
                        <td>${log.status ?? ''}</td>
                        <td>${log.message ?? ''}</td>
                        <td>${log.created_at ?? ''}</td>
                    </tr>
                `;
            });

            if (!html) {
                html = `<tr><td colspan="5" class="text-center text-muted">No logs found</td></tr>`;
            }

            $('#workflow_logs_tbody').html(html);
            $('#workflowLogsModal').modal('show');
        })
        .fail(function(xhr){
            swalClose();
            swalErr(xhr?.responseJSON?.message || 'Failed to load workflow logs.');
        });
});

$(function(){
    initDT();
});
</script>
@endpush