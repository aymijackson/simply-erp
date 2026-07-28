@extends('layouts.master')

@section('title', 'Project Costs')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Project Costs</h1>
            <small class="text-muted">Projects / Cost Tracking</small>
        </div>

        <button class="btn btn-primary" id="createBtn">
            <i class="fas fa-plus"></i> New Project Cost
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
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Category</label>
                    <select class="form-control" id="f_cost_category">
                        <option value="">All</option>
                        <option value="materials">Materials</option>
                        <option value="labour">Labour</option>
                        <option value="logistics">Logistics</option>
                        <option value="subcontract">Subcontract</option>
                        <option value="overhead">Overhead</option>
                        <option value="expense">Expense</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Source</label>
                    <select class="form-control" id="f_source_type">
                        <option value="">All</option>
                        <option value="manual">Manual</option>
                        <option value="purchase_order">Purchase Order</option>
                        <option value="goods_receipt">Goods Receipt</option>
                        <option value="supplier_bill">Supplier Bill</option>
                        <option value="expense">Expense</option>
                        <option value="journal_entry">Journal Entry</option>
                        <option value="timesheet">Timesheet</option>
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
                    <input type="text" class="form-control" id="f_q" placeholder="reference, description...">
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
                    Track direct and indirect project costs here. Posted costs update the linked project's actual cost automatically.
                </div>

                <div class="fw-bold text-end">
                    Total: <span id="totalCostLbl">0.00</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="costsTable" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th style="width:110px;">Date</th>
                            <th style="width:220px;">Project</th>
                            <th style="width:180px;">Task</th>
                            <th style="width:180px;">Milestone</th>
                            <th style="width:120px;">Category</th>
                            <th style="width:120px;">Source</th>
                            <th style="width:130px;">Reference</th>
                            <th>Description</th>
                            <th style="width:90px;" class="text-end">Qty</th>
                            <th style="width:120px;" class="text-end">Unit Cost</th>
                            <th style="width:120px;" class="text-end">Amount</th>
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

@include('projects.costs.partials.modal')
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

const dtUrl          = "{{ route('admin.project_costs.datatable') }}";
const baseUrl        = "{{ url('admin/project-costs') }}";
const projectsUrl    = "{{ route('admin.project_costs.lookups.projects') }}";
const tasksUrl       = "{{ route('admin.project_costs.lookups.tasks') }}";
const milestonesUrl  = "{{ route('admin.project_costs.lookups.milestones') }}";

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
    DT = $('#costsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        ajax: {
            url: dtUrl,
            data: function(d){
                d.project_id     = $('#f_project_id').val();
                d.status         = $('#f_status').val();
                d.cost_category  = $('#f_cost_category').val();
                d.source_type    = $('#f_source_type').val();
                d.date_from      = $('#f_from').val();
                d.date_to        = $('#f_to').val();
                d.q              = $('#f_q').val();
            }
        },
        columns: [
            {data:'id'},
            {data:'cost_date'},
            {data:'project'},
            {data:'task'},
            {data:'milestone'},
            {data:'category'},
            {data:'source_type'},
            {data:'reference_no'},
            {data:'description'},
            {data:'quantity', className:'text-end'},
            {data:'unit_cost', className:'text-end'},
            {data:'amount', className:'text-end'},
            {data:'status', orderable:false, searchable:false},
            {data:'actions', orderable:false, searchable:false},
        ],
        order: [[0, 'desc']],
        drawCallback: function(settings){
            const meta = settings.json?.meta || {};
            $('#totalCostLbl').text(Number(meta.total_amount || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
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
    $('#f_cost_category').val('');
    $('#f_source_type').val('');
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
        dropdownParent: $('#costModal'),
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
    $('#costForm')[0].reset();
    $('#cost_id').val('');
    $('#cost_status_badge').html('');
    $('#project_id').empty().trigger('change');
    $('#task_id').empty().trigger('change');
    $('#milestone_id').empty().trigger('change');
    $('#quantity').val(1);
    $('#unit_cost').val(0);
    $('#amount').val(0);
    $('#currency_code').val('NGN');
    $('#status').val('posted');
    $('#source_type').val('manual');
    $('#cost_date').val(new Date().toISOString().slice(0,10));
}

function recalcAmount(){
    const qty = parseFloat($('#quantity').val() || '0');
    const unit = parseFloat($('#unit_cost').val() || '0');
    const amount = qty * unit;
    $('#amount').val(amount.toFixed(2));
}

$('#quantity, #unit_cost').on('input', recalcAmount);

$('#project_id').on('change', function(){
    $('#task_id').empty().trigger('change');
    $('#milestone_id').empty().trigger('change');
});

$('#createBtn').on('click', function(){
    resetModal();
    $('#costModalTitle').text('New Project Cost');
    $('#costModal').modal('show');
});

$('#saveCostBtn').on('click', function(){
    const id = $('#cost_id').val();
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${baseUrl}/${id}` : baseUrl;

    $.ajax({
        url,
        method,
        data: $('#costForm').serialize()
    })
    .done(function(res){
        $('#costModal').modal('hide');
        swalOk(res.message || 'Saved.');
        refreshDT();
    })
    .fail(function(xhr){
        swalErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-cost', function(){
    resetModal();

    const cost = $(this).data('json');

    $('#costModalTitle').text('Edit Project Cost');
    $('#cost_id').val(cost.id);
    $('#cost_date').val(cost.cost_date || '');
    $('#cost_category').val(cost.cost_category || 'other');
    $('#source_type').val(cost.source_type || 'manual');
    $('#source_id').val(cost.source_id || '');
    $('#reference_no').val(cost.reference_no || '');
    $('#description').val(cost.description || '');
    $('#quantity').val(cost.quantity || 1);
    $('#unit_cost').val(cost.unit_cost || 0);
    $('#amount').val(cost.amount || 0);
    $('#currency_code').val(cost.currency_code || 'NGN');
    $('#status').val(cost.status || 'posted');
    $('#notes').val(cost.notes || '');

    $('#cost_status_badge').html(
        cost.status ? `<span class="badge bg-secondary">${String(cost.status).toUpperCase()}</span>` : ''
    );

    if (cost.project_id && cost.project_label) {
        setSelect2Value($('#project_id'), cost.project_id, cost.project_label);
    }

    if (cost.task_id && cost.task_label) {
        setSelect2Value($('#task_id'), cost.task_id, cost.task_label);
    }

    if (cost.milestone_id && cost.milestone_label) {
        setSelect2Value($('#milestone_id'), cost.milestone_id, cost.milestone_label);
    }

    $('#costModal').modal('show');
});

$(document).on('click', '.btn-del-cost', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Delete project cost?',
        text:'This will soft-delete the project cost.',
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
    s2($('#task_id'), tasksUrl, 'Select task...', function(){
        return { project_id: $('#project_id').val() || '' };
    });
    s2($('#milestone_id'), milestonesUrl, 'Select milestone...', function(){
        return { project_id: $('#project_id').val() || '' };
    });

    initDT();
});
</script>
@endpush