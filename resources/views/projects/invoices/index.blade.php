@extends('layouts.master')

@section('title', 'Project Invoices')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Project Invoices</h1>
            <small class="text-muted">Projects / Billing / Invoicing</small>
        </div>

        <button class="btn btn-primary" id="createBtn">
            <i class="fas fa-plus"></i> New Project Invoice
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
                        <option value="posted">Posted</option>
                        <option value="part_paid">Part Paid</option>
                        <option value="paid">Paid</option>
                        <option value="voided">Voided</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Billing Method</label>
                    <select class="form-control" id="f_billing_method">
                        <option value="">All</option>
                        <option value="fixed_fee">Fixed Fee</option>
                        <option value="milestone">Milestone</option>
                        <option value="timesheet">Timesheet</option>
                        <option value="manual">Manual</option>
                        <option value="mixed">Mixed</option>
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

                <div class="col-md-3">
                    <label class="text-muted small">Search</label>
                    <input type="text" class="form-control" id="f_q" placeholder="invoice no, reference...">
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
                    Project invoices can be built from milestones, approved billable timesheets, fixed-fee lines, or manual billing lines.
                </div>
            </div>

            <div class="row mt-3 g-2">
                <div class="col-md-3">
                    <div class="border rounded p-2 bg-light">
                        <div class="small text-muted">Subtotal</div>
                        <div class="fw-bold" id="sumSubtotalLbl">0.00</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 bg-light">
                        <div class="small text-muted">Tax Total</div>
                        <div class="fw-bold" id="sumTaxLbl">0.00</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 bg-light">
                        <div class="small text-muted">Invoice Total</div>
                        <div class="fw-bold" id="sumTotalLbl">0.00</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 bg-light">
                        <div class="small text-muted">Balance Due</div>
                        <div class="fw-bold" id="sumBalanceLbl">0.00</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="projectInvoicesTable" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th style="width:220px;">Project</th>
                            <th style="width:120px;">Invoice No</th>
                            <th style="width:100px;">Invoice Date</th>
                            <th style="width:100px;">Due Date</th>
                            <th style="width:120px;">Method</th>
                            <th style="width:80px;">Curr</th>
                            <th style="width:120px;" class="text-end">Total</th>
                            <th style="width:120px;" class="text-end">Balance</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:260px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('projects.invoices.partials.modal')
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

const dtUrl         = "{{ route('admin.project_invoices.datatable') }}";
const baseUrl       = "{{ url('admin/project-invoices') }}";
const projectsUrl   = "{{ route('admin.project_invoices.lookups.projects') }}";
const tasksUrl      = "{{ route('admin.project_invoices.lookups.tasks') }}";
const milestonesUrl = "{{ route('admin.project_invoices.lookups.milestones') }}";
const timesheetsUrl = "{{ route('admin.project_invoices.lookups.timesheets') }}";

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
    DT = $('#projectInvoicesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        ajax: {
            url: dtUrl,
            data: function(d){
                d.project_id      = $('#f_project_id').val();
                d.status          = $('#f_status').val();
                d.billing_method  = $('#f_billing_method').val();
                d.date_from       = $('#f_from').val();
                d.date_to         = $('#f_to').val();
                d.q               = $('#f_q').val();
            }
        },
        columns: [
            {data:'id'},
            {data:'project'},
            {data:'invoice_no'},
            {data:'invoice_date'},
            {data:'due_date'},
            {data:'billing_method'},
            {data:'currency_code'},
            {data:'total_amount', className:'text-end'},
            {data:'balance_due', className:'text-end'},
            {data:'status', orderable:false, searchable:false},
            {data:'actions', orderable:false, searchable:false},
        ],
        order: [[0, 'desc']],
        drawCallback: function(settings){
            const meta = settings.json?.meta || {};
            $('#sumSubtotalLbl').text(fmtMoney(meta.subtotal || 0));
            $('#sumTaxLbl').text(fmtMoney(meta.tax_total || 0));
            $('#sumTotalLbl').text(fmtMoney(meta.total_amount || 0));
            $('#sumBalanceLbl').text(fmtMoney(meta.balance_due || 0));
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
    $('#f_billing_method').val('');
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
        dropdownParent: $('#projectInvoiceModal'),
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
    $('#projectInvoiceForm')[0].reset();
    $('#invoice_id').val('');
    $('#invoice_status_badge').html('');
    $('#project_id').empty().trigger('change');
    $('#project_invoice_lines_tbody').html('');
    $('#currency_code').val('NGN');
    $('#fx_rate').val('1.000000');
    $('#billing_method').val('manual');
    addLine();
    recalcInvoiceTotals();
}

function addLine(line = null){
    const idx = Date.now() + Math.floor(Math.random() * 1000);

    const tr = $(`
        <tr data-row="${idx}">
            <td style="width:12%">
                <select class="form-control" name="lines[${idx}][source_type]">
                    <option value="manual">Manual</option>
                    <option value="milestone">Milestone</option>
                    <option value="timesheet">Timesheet</option>
                    <option value="fixed_fee">Fixed Fee</option>
                </select>
                <input type="hidden" name="lines[${idx}][source_id]" class="line-source-id">
                <input type="hidden" name="lines[${idx}][milestone_id]" class="line-milestone-id">
                <input type="hidden" name="lines[${idx}][timesheet_id]" class="line-timesheet-id">
                <input type="hidden" name="lines[${idx}][task_id]" class="line-task-id">
            </td>
            <td style="width:18%">
                <select class="form-control line-source-select" style="width:100%"></select>
            </td>
            <td>
                <input type="text" class="form-control line-description" name="lines[${idx}][description]" value="${line?.description ?? ''}" placeholder="Description">
            </td>
            <td style="width:9%">
                <input type="number" step="0.01" min="0.01" class="form-control text-end line-qty" name="lines[${idx}][quantity]" value="${line?.quantity ?? 1}">
            </td>
            <td style="width:11%">
                <input type="number" step="0.01" min="0" class="form-control text-end line-unit" name="lines[${idx}][unit_price]" value="${line?.unit_price ?? 0}">
            </td>
            <td style="width:9%">
                <input type="number" step="0.0001" min="0" class="form-control text-end line-taxrate" name="lines[${idx}][tax_rate]" value="${line?.tax_rate ?? 0}">
            </td>
            <td style="width:11%">
                <input type="number" step="0.01" min="0" class="form-control text-end line-total" value="${line?.line_total ?? 0}" readonly>
            </td>
            <td style="width:6%">
                <button type="button" class="btn btn-outline-danger btn-sm btn-del-line"><i class="fas fa-times"></i></button>
            </td>
        </tr>
    `);

    $('#project_invoice_lines_tbody').append(tr);

    if (line?.source_type) {
        tr.find(`select[name="lines[${idx}][source_type]"]`).val(line.source_type);
    }

    initDynamicSourceSelect(tr, line);
    recalcInvoiceTotals();
}

function initDynamicSourceSelect($tr, line = null){
    const $type = $tr.find('select[name$="[source_type]"]');
    const $sourceSelect = $tr.find('.line-source-select');

    if ($sourceSelect.hasClass('select2-hidden-accessible')) {
        $sourceSelect.select2('destroy');
    }
    $sourceSelect.empty();

    const sourceType = $type.val();
    let url = null;

    if (sourceType === 'milestone') {
        url = milestonesUrl;
    } else if (sourceType === 'timesheet') {
        url = timesheetsUrl;
    } else {
        url = projectsUrl;
    }

    $sourceSelect.select2({
        theme:'bootstrap-5',
        width:'100%',
        placeholder:'Select source...',
        allowClear:true,
        dropdownParent: $('#projectInvoiceModal'),
        ajax:{
            url,
            dataType:'json',
            delay:200,
            data:function(params){
                return {
                    q: params.term || '',
                    project_id: $('#project_id').val() || ''
                };
            },
            processResults:d => d,
            cache:true
        }
    });

    $sourceSelect.off('select2:select').on('select2:select', function(e){
        const data = e.params.data || {};
        const currentType = $type.val();

        $tr.find('.line-source-id').val(data.id || '');
        $tr.find('.line-milestone-id').val('');
        $tr.find('.line-timesheet-id').val('');
        $tr.find('.line-task-id').val('');

        if (currentType === 'milestone') {
            $tr.find('.line-milestone-id').val(data.id || '');
            $tr.find('.line-description').val(data.text || '');
            $tr.find('.line-qty').val('1.00');
            $tr.find('.line-unit').val(Number(data.billing_amount || 0).toFixed(2));
        } else if (currentType === 'timesheet') {
            $tr.find('.line-timesheet-id').val(data.id || '');
            $tr.find('.line-description').val(data.description || data.text || '');
            $tr.find('.line-qty').val(Number(data.billable_hours || 0).toFixed(2));
            $tr.find('.line-unit').val(Number(data.billing_rate || 0).toFixed(2));
        } else {
            $tr.find('.line-description').val($tr.find('.line-description').val() || (data.text || ''));
        }

        recalcInvoiceTotals();
    });

    if (line?.source_id) {
        const label = line.description || ('Source #' + line.source_id);
        const opt = new Option(label, line.source_id, true, true);
        $sourceSelect.append(opt).trigger('change');

        $tr.find('.line-source-id').val(line.source_id || '');
        $tr.find('.line-milestone-id').val(line.milestone_id || '');
        $tr.find('.line-timesheet-id').val(line.timesheet_id || '');
        $tr.find('.line-task-id').val(line.task_id || '');
    }
}

function recalcInvoiceTotals(){
    let subtotal = 0;
    let taxTotal = 0;
    let grandTotal = 0;

    $('#project_invoice_lines_tbody tr').each(function(){
        const $tr = $(this);

        const qty = parseFloat($tr.find('.line-qty').val() || '0');
        const unit = parseFloat($tr.find('.line-unit').val() || '0');
        const taxRate = parseFloat($tr.find('.line-taxrate').val() || '0');

        const base = qty * unit;
        const tax = base * (taxRate / 100);
        const lineTotal = base + tax;

        $tr.find('.line-total').val(lineTotal.toFixed(2));

        subtotal += base;
        taxTotal += tax;
        grandTotal += lineTotal;
    });

    $('#invoice_subtotal_lbl').text(fmtMoney(subtotal));
    $('#invoice_tax_lbl').text(fmtMoney(taxTotal));
    $('#invoice_total_lbl').text(fmtMoney(grandTotal));
}

$(document).on('input', '.line-qty,.line-unit,.line-taxrate', recalcInvoiceTotals);
$(document).on('click', '#addProjectInvoiceLineBtn', function(){ addLine(); });

$(document).on('change', 'select[name$="[source_type]"]', function(){
    const $tr = $(this).closest('tr');
    $tr.find('.line-source-id,.line-milestone-id,.line-timesheet-id,.line-task-id').val('');
    initDynamicSourceSelect($tr);
});

$(document).on('click', '.btn-del-line', function(){
    $(this).closest('tr').remove();
    if ($('#project_invoice_lines_tbody tr').length < 1) addLine();
    recalcInvoiceTotals();
});

$('#createBtn').on('click', function(){
    resetModal();
    $('#projectInvoiceModalTitle').text('New Project Invoice');
    $('#projectInvoiceModal').modal('show');
});

$('#saveProjectInvoiceBtn').on('click', function(){
    const id = $('#invoice_id').val();
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${baseUrl}/${id}` : baseUrl;

    $.ajax({
        url,
        method,
        data: $('#projectInvoiceForm').serialize()
    })
    .done(function(res){
        $('#projectInvoiceModal').modal('hide');
        swalOk(res.message || 'Saved.');
        refreshDT();
    })
    .fail(function(xhr){
        swalErr(xhr?.responseJSON?.message || 'Save failed.');
    });
});

$(document).on('click', '.btn-edit-project-invoice', function(){
    resetModal();

    const row = $(this).data('json');

    $('#projectInvoiceModalTitle').text('Edit Project Invoice');
    $('#invoice_id').val(row.id);
    $('#invoice_no').val(row.invoice_no || '');
    $('#invoice_date').val(row.invoice_date || '');
    $('#due_date').val(row.due_date || '');
    $('#billing_method').val(row.billing_method || 'manual');
    $('#currency_code').val(row.currency_code || 'NGN');
    $('#fx_rate').val(row.fx_rate || '1.000000');
    $('#reference').val(row.reference || '');
    $('#memo').val(row.memo || '');

    $('#invoice_status_badge').html(
        row.status ? `<span class="badge bg-secondary">${String(row.status).toUpperCase()}</span>` : ''
    );

    if (row.project_id && row.project_label) {
        setSelect2Value($('#project_id'), row.project_id, row.project_label);
    }

    $.get(`${baseUrl}/${row.id}/lines`)
        .done(function(res){
            $('#project_invoice_lines_tbody').html('');
            (res.lines || []).forEach(line => addLine(line));
            if ($('#project_invoice_lines_tbody tr').length < 1) addLine();
            recalcInvoiceTotals();
            $('#projectInvoiceModal').modal('show');
        })
        .fail(function(){
            swalErr('Could not load invoice lines.');
        });
});

$(document).on('click', '.btn-del-project-invoice', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Delete project invoice?',
        text:'Only draft invoices can be deleted.',
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

$(document).on('click', '.btn-post-project-invoice', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'question',
        title:'Post project invoice?',
        text:'Posting will lock the invoice and make it part of billed revenue.',
        showCancelButton:true,
        confirmButtonText:'Yes, post'
    });

    if (!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/post`)
        .done(function(res){
            swalOk(res.message || 'Posted.');
            refreshDT();
        })
        .fail(function(xhr){
            swalErr(xhr?.responseJSON?.message || 'Post failed.');
        });
});

$(document).on('click', '.btn-void-project-invoice', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Void project invoice?',
        text:'Voiding will release milestone/timesheet billing flags.',
        showCancelButton:true,
        confirmButtonText:'Yes, void'
    });

    if (!r.isConfirmed) return;

    $.post(`${baseUrl}/${id}/void`)
        .done(function(res){
            swalOk(res.message || 'Voided.');
            refreshDT();
        })
        .fail(function(xhr){
            swalErr(xhr?.responseJSON?.message || 'Void failed.');
        });
});

$(function(){
    s2($('#f_project_id'), projectsUrl, 'Project...');
    s2($('#project_id'), projectsUrl, 'Select project...');
    initDT();
});
</script>
@endpush