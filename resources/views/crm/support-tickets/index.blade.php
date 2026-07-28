{{-- resources/views/crm/support-tickets/index.blade.php
   Adjust path if your module uses Modules/CRM/Resources/views/...
--}}
@extends('layouts.master')

@section('title', 'Support Tickets')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Support Tickets</h1>
            <small class="text-muted">CRM</small>
        </div>

        <div class="d-flex gap-2">
            @can('crm.support_tickets.create')
                <button class="btn btn-primary" id="addTicketBtn">
                    <i class="fas fa-plus me-1"></i> Create Ticket
                </button>
            @endcan

            @can('crm.support_tickets.delete')
                <button class="btn btn-danger d-none" id="bulkDeleteBtn">
                    <i class="fas fa-trash me-1"></i> Delete Selected
                </button>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">Customer</label>
                    <select id="filter_customer_id" class="form-control" style="width:100%"></select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Assigned To</label>
                    <select id="filter_assigned_to" class="form-control">
                        <option value="">All</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">
                                {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('Employee #'.$e->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select id="filter_status" class="form-control">
                        <option value="">All</option>
                        <option value="open">Open</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Priority</label>
                    <select id="filter_priority" class="form-control">
                        <option value="">All</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Created Between</label>
                    <div class="d-flex gap-2">
                        <input type="date" id="filter_date_from" class="form-control">
                        <input type="date" id="filter_date_to" class="form-control">
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-outline-primary" id="applyFiltersBtn">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <button class="btn btn-outline-secondary" id="resetFiltersBtn">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="ticketsTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
                            <th>Ticket No</th>
                            <th>Subject</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assigned</th>
                            <th>Created</th>
                            <th style="width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Create/Edit Modal --}}
<div class="modal fade" id="ticketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="ticketForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketModalTitle">Create Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="ticket_id" value="">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" id="subject" class="form-control" required>
                        <small class="text-danger" data-err="subject"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select id="customer_id" class="form-control" style="width:100%" required></select>
                        <small class="text-danger" data-err="customer_id"></small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="description" class="form-control" rows="4" required></textarea>
                        <small class="text-danger" data-err="description"></small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="status" class="form-control" required>
                            <option value="open">Open</option>
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <small class="text-danger" data-err="status"></small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select id="priority" class="form-control" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        <small class="text-danger" data-err="priority"></small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Channel</label>
                        <select id="channel" class="form-control">
                            <option value="">—</option>
                            <option value="web">Web</option>
                            <option value="email">Email</option>
                            <option value="phone">Phone</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="other">Other</option>
                        </select>
                        <small class="text-danger" data-err="channel"></small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select id="category" class="form-control">
                            <option value="">—</option>
                            <option value="billing">Billing</option>
                            <option value="technical">Technical</option>
                            <option value="account">Account</option>
                            <option value="other">Other</option>
                        </select>
                        <small class="text-danger" data-err="category"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Assigned To</label>
                        <select id="assigned_to" class="form-control">
                            <option value="">—</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">
                                    {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('Employee #'.$e->id) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger" data-err="assigned_to"></small>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                @can('crm.support_tickets.create')
                    <button type="submit" class="btn btn-primary" id="saveTicketBtn">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                @endcan
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
<style>
    .select2-container { width: 100% !important; }
    .select2-selection--single { height: calc(1.5em + .75rem + 2px) !important; }
    .select2-selection__rendered { line-height: calc(1.5em + .75rem) !important; }
    .select2-selection__arrow { height: calc(1.5em + .75rem + 2px) !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ---- adjust to your route names if needed ----
    const routes = {
        datatable:  @json(route('admin.crm.support_tickets.datatable')),
        store:      @json(route('admin.crm.support_tickets.store')),
        update:     @json(route('admin.crm.support_tickets.update', ['ticket' => '__ID__'])),
        destroy:    @json(route('admin.crm.support_tickets.destroy', ['ticket' => '__ID__'])),
        bulkDelete: @json(route('admin.crm.support_tickets.bulk_delete')),

        // CustomerController select2 (your standard)
        customerS2: @json(route('admin.customers.select2')),

        // Optional: if you have edit endpoint returning JSON
        // edit:    @json(route('admin.crm.support_tickets.show', ['ticket' => '__ID__'])),
    };

    const canCreate = @json(auth()->user()->can('crm.support_tickets.create'));
    const canUpdate = @json(auth()->user()->can('crm.support_tickets.update'));
    const canDelete = @json(auth()->user()->can('crm.support_tickets.delete'));

    function urlWithId(tpl, id) { return tpl.replace('__ID__', id); }

    function swalOk(title, text=''){
        Swal.fire({ icon:'success', title, text, timer:1500, showConfirmButton:false });
    }
    function swalErr(text){
        Swal.fire({ icon:'error', title:'Error', text: text || 'Something went wrong.' });
    }
    function confirmDelete(text){
        return Swal.fire({
            icon:'warning',
            title:'Confirm',
            text: text || 'Are you sure?',
            showCancelButton:true,
            confirmButtonText:'Yes, delete',
            cancelButtonText:'Cancel'
        });
    }

    function clearErrors(){
        $('[data-err]').text('');
    }
    function showErrors(errors){
        if(!errors) return;
        Object.keys(errors).forEach(k => {
            $('[data-err="'+k+'"]').text(errors[k][0] || errors[k]);
        });
    }

    // --- Select2 init (customer) ---
    function initCustomerSelect2($el, dropdownParent, selectedId = null, selectedText = null) {
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            theme: 'bootstrap4',
            dropdownParent: dropdownParent,
            placeholder: 'Search customer...',
            allowClear: true,
            ajax: {
                url: routes.customerS2,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '' }),
                processResults: data => ({ results: data }),
                cache: true
            }
        });

        if (selectedId && selectedText) {
            const opt = new Option(selectedText, selectedId, true, true);
            $el.append(opt).trigger('change');
        } else {
            $el.val(null).trigger('change');
        }
    }

    function initCustomerFilterSelect2(){
        const $el = $('#filter_customer_id');
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            theme: 'bootstrap4',
            placeholder: 'All customers',
            allowClear: true,
            ajax: {
                url: routes.customerS2,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '' }),
                processResults: data => ({ results: data }),
                cache: true
            }
        });
    }

    initCustomerFilterSelect2();

    // --- DataTable ---
    const table = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: routes.datatable,
            data: function (d) {
                d.customer_id = $('#filter_customer_id').val() || '';
                d.assigned_to = $('#filter_assigned_to').val() || '';
                d.status      = $('#filter_status').val() || '';
                d.priority    = $('#filter_priority').val() || '';
                d.date_from   = $('#filter_date_from').val() || '';
                d.date_to     = $('#filter_date_to').val() || '';
            }
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'checkbox', orderable:false, searchable:false },

            // If your datatable returns ticket_no column
            { data: 'ticket_no', name:'ticket_no', defaultContent:'—' },

            { data: 'subject', name:'subject' },
            { data: 'customer_name', name:'customer.name', defaultContent:'—' },
            { data: 'status', name:'status' },
            { data: 'priority', name:'priority' },
            { data: 'assignee_name', name:'assignee.first_name', defaultContent:'—' },

            // Controller returns created_at_fmt already formatted d-m-Y h:i a
            { data: 'created_at_fmt', name:'created_at', defaultContent:'—' },

            { data: 'actions', orderable:false, searchable:false },
        ],
        drawCallback: function () {
            syncBulkDeleteBtn();
        }
    });

    // --- Filters ---
    $('#applyFiltersBtn').on('click', function () {
        table.ajax.reload();
    });

    $('#resetFiltersBtn').on('click', function () {
        $('#filter_assigned_to').val('');
        $('#filter_status').val('');
        $('#filter_priority').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        $('#filter_customer_id').val(null).trigger('change');
        table.ajax.reload();
    });

    // --- Bulk selection ---
    function syncBulkDeleteBtn() {
        if (!canDelete) return;
        const checked = $('.row-checkbox:checked').length;
        $('#bulkDeleteBtn').toggleClass('d-none', checked === 0);
    }

    $('#checkAll').on('change', function () {
        const checked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', checked);
        syncBulkDeleteBtn();
    });

    $(document).on('change', '.row-checkbox', function () {
        const all = $('.row-checkbox').length;
        const checked = $('.row-checkbox:checked').length;
        $('#checkAll').prop('checked', all > 0 && checked === all);
        syncBulkDeleteBtn();
    });

    // --- Modal ---
    const ticketModalEl = document.getElementById('ticketModal');
    const ticketModal = new bootstrap.Modal(ticketModalEl);

    $('#addTicketBtn').on('click', function () {
        if (!canCreate) return;

        clearErrors();
        $('#ticketForm')[0].reset();
        $('#ticket_id').val('');
        $('#ticketModalTitle').text('Create Ticket');

        initCustomerSelect2($('#customer_id'), $('#ticketModal'));
        ticketModal.show();
    });

    // --- Edit (record is embedded in actions as data-record from your controller) ---
    $(document).on('click', '.edit-ticket', function () {
        if (!canUpdate) return;

        clearErrors();
        const recordStr = $(this).attr('data-record');
        const record = JSON.parse(recordStr);

        $('#ticket_id').val(record.id || '');
        $('#ticketModalTitle').text('Edit Ticket');

        $('#subject').val(record.subject || '');
        $('#description').val(record.description || '');
        $('#status').val(record.status || 'open');
        $('#priority').val(record.priority || 'low');
        $('#channel').val(record.channel || '');
        $('#category').val(record.category || '');
        $('#assigned_to').val(record.assigned_to || '');

        initCustomerSelect2(
            $('#customer_id'),
            $('#ticketModal'),
            record.customer_id,
            record.customer_name || 'Selected customer'
        );

        ticketModal.show();
    });

    // --- Save ---
    $('#ticketForm').on('submit', function (e) {
        e.preventDefault();

        clearErrors();

        const id = $('#ticket_id').val();
        const isEdit = !!id;

        const payload = {
            subject: $('#subject').val(),
            description: $('#description').val(),
            status: $('#status').val(),
            priority: $('#priority').val(),
            channel: $('#channel').val(),
            category: $('#category').val(),
            customer_id: $('#customer_id').val(),
            assigned_to: $('#assigned_to').val(),
        };

        let url = routes.store;
        let method = 'POST';

        if (isEdit) {
            url = urlWithId(routes.update, id);
            method = 'PUT';
        }

        $.ajax({
            url,
            method,
            headers: { 'X-CSRF-TOKEN': csrf },
            data: payload,
            success: function (res) {
                ticketModal.hide();
                table.ajax.reload(null, false);
                swalOk('Saved', res.message || (isEdit ? 'Ticket updated.' : 'Ticket created.'));
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showErrors(xhr.responseJSON?.errors || {});
                    return;
                }
                swalErr(xhr.responseJSON?.message || 'Failed to save ticket.');
            }
        });
    });

    // --- Delete single ---
    $(document).on('click', '.delete-ticket', async function () {
        if (!canDelete) return;

        const id = $(this).data('id');
        const res = await confirmDelete('Delete this ticket?');
        if (!res.isConfirmed) return;

        $.ajax({
            url: urlWithId(routes.destroy, id),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (res) {
                table.ajax.reload(null, false);
                swalOk('Deleted', res.message || 'Ticket deleted.');
            },
            error: function (xhr) {
                swalErr(xhr.responseJSON?.message || 'Failed to delete ticket.');
            }
        });
    });

    // --- Bulk delete ---
    $('#bulkDeleteBtn').on('click', async function () {
        if (!canDelete) return;

        const ids = $('.row-checkbox:checked').map(function () { return $(this).val(); }).get();
        if (!ids.length) return;

        const res = await confirmDelete(`Delete ${ids.length} selected ticket(s)?`);
        if (!res.isConfirmed) return;

        $.ajax({
            url: routes.bulkDelete,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: { ids },
            success: function (res) {
                $('#checkAll').prop('checked', false);
                table.ajax.reload(null, false);
                swalOk('Deleted', res.message || 'Selected tickets deleted.');
            },
            error: function (xhr) {
                swalErr(xhr.responseJSON?.message || 'Bulk delete failed.');
            }
        });
    });

})();
</script>
@endpush
