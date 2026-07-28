{{-- resources/views/crm/leads/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Leads')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Leads</h1>
            <small class="text-muted">CRM</small>
        </div>

        <div class="d-flex gap-2">
            @can('crm.leads.create')
                <button class="btn btn-primary" id="addLeadBtn">
                    <i class="fas fa-plus me-1"></i> Add Lead
                </button>
            @endcan

            @can('crm.leads.delete')
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
                    <label class="form-label mb-1">Company</label>
                    <select id="filter_company_id" class="form-control" style="width:100%"></select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Status</label>
                    <select id="filter_status" class="form-control">
                        <option value="">All</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="qualified">Qualified</option>
                        <option value="converted">Converted</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Assigned To</label>
                    <select id="filter_assigned_to" class="form-control">
                        <option value="">All</option>
                        @if(isset($employees))
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">
                                    {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ($e->name ?? 'Employee #'.$e->id) }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-outline-primary w-100" id="applyFiltersBtn">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <button class="btn btn-outline-secondary w-100" id="resetFiltersBtn">
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
                <table id="leadsTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:35px;">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Follow-up Date</th>
                            <th>Assigned To</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <small class="text-muted d-block mt-2">
                Tip: Use filters for scalability. Company uses Select2 search.
            </small>
        </div>
    </div>
</div>

{{-- Lead Modal --}}
<div class="modal fade" id="leadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="leadForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadModalTitle">Add Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="lead_id" value="">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Lead Name <span class="text-danger">*</span></label>
                        <input type="text" name="lead_name" id="lead_name" class="form-control" required>
                        <small class="text-danger" data-err="lead_name"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Company <span class="text-danger">*</span></label>
                        <select name="company_id" id="company_id" class="form-control" style="width:100%" required></select>
                        <small class="text-danger" data-err="company_id"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control">
                        <small class="text-danger" data-err="email"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                        <small class="text-danger" data-err="phone"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" id="position" class="form-control">
                        <small class="text-danger" data-err="position"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Source</label>
                        <input type="text" name="source" id="source" class="form-control">
                        <small class="text-danger" data-err="source"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="qualified">Qualified</option>
                            <option value="converted">Converted</option>
                            <option value="closed">Closed</option>
                        </select>
                        <small class="text-danger" data-err="status"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Follow-up Date</label>
                        <input type="date" name="follow_up_date" id="follow_up_date" class="form-control">
                        <small class="text-danger" data-err="follow_up_date"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Assigned To</label>
                        <select name="assigned_to" id="assigned_to" class="form-control">
                            <option value="">—</option>
                            @if(isset($employees))
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">
                                        {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ($e->name ?? 'Employee #'.$e->id) }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <small class="text-danger" data-err="assigned_to"></small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                        <small class="text-danger" data-err="notes"></small>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                @can('crm.leads.create')
                    <button type="submit" class="btn btn-primary" id="saveLeadBtn">
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

{{-- ✅ SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const routes = {
        datatable: @json(route('admin.crm.leads.datatable')),
        store:     @json(route('admin.crm.leads.store')),
        update:    @json(route('admin.crm.leads.update', ['lead' => '__ID__'])),
        destroy:   @json(route('admin.crm.leads.destroy', ['lead' => '__ID__'])),
        bulkDel:   @json(route('admin.crm.leads.bulk_delete')),
        companyS2: @json(route('admin.companies.select2')),
    };

    // ---- SweetAlert helpers ----
    function swToast(message, icon = 'success') {
        return Swal.fire({
            toast: true,
            position: 'top-end',
            icon,
            title: message,
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });
    }

    function swConfirm({
        title = 'Are you sure?',
        text = 'This action cannot be undone.',
        confirmText = 'Yes, continue',
        cancelText = 'Cancel',
        icon = 'warning'
    } = {}) {
        return Swal.fire({
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            reverseButtons: true,
            focusCancel: true
        });
    }

    function swLoading(title = 'Processing...') {
        Swal.fire({
            title,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });
    }

    function clearErrors() { $('[data-err]').text(''); }

    function showErrors(errors) {
        if (!errors) return;
        Object.keys(errors).forEach(k => {
            $('[data-err="'+k+'"]').text(errors[k][0] || errors[k]);
        });
    }

    function urlWithId(tpl, id) {
        return tpl.replace('__ID__', id);
    }

    // ---- Select2: company (modal) ----
    function initCompanySelect2($el, dropdownParent, selectedId = null, selectedText = null) {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.select2({
            theme: 'bootstrap4',
            dropdownParent: dropdownParent,
            placeholder: 'Search company...',
            allowClear: true,
            ajax: {
                url: routes.companyS2,
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

    // ---- Select2: company (filter) ----
    function initCompanyFilterSelect2() {
        const $el = $('#filter_company_id');

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.select2({
            theme: 'bootstrap4',
            placeholder: 'All companies',
            allowClear: true,
            ajax: {
                url: routes.companyS2,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '' }),
                processResults: data => ({ results: data }),
                cache: true
            }
        });
    }

    initCompanyFilterSelect2();

    // ---- DataTable ----
    const table = $('#leadsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: routes.datatable,
            data: function (d) {
                d.company_id  = $('#filter_company_id').val() || '';
                d.status      = $('#filter_status').val() || '';
                d.assigned_to = $('#filter_assigned_to').val() || '';
            }
        },
        order: [[1, 'asc']],
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'lead_name', name: 'lead_name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'company_name', name: 'company.name', defaultContent: '—' },
            { data: 'position', name: 'position' },
            { data: 'status', name: 'status' },
            { data: 'follow_up_date', name: 'follow_up_date' },
            { data: 'assigned_to_name', name: 'assignedEmployee.first_name', defaultContent: '—' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            syncBulkDeleteBtn();
        }
    });

    // ---- Filters ----
    $('#applyFiltersBtn').on('click', function () { table.ajax.reload(); });

    $('#resetFiltersBtn').on('click', function () {
        $('#filter_status').val('');
        $('#filter_assigned_to').val('');
        $('#filter_company_id').val(null).trigger('change');
        table.ajax.reload();
    });

    // ---- Bulk select ----
    function syncBulkDeleteBtn() {
        const checked = $('.row-checkbox:checked').length;
        const canBulk = @json(auth()->user()->can('crm.leads.delete'));
        if (!canBulk) return;
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

    // ---- Modal open: add ----
    const leadModalEl = document.getElementById('leadModal');
    const leadModal = new bootstrap.Modal(leadModalEl);

    $('#addLeadBtn').on('click', function () {
        clearErrors();
        $('#leadForm')[0].reset();
        $('#lead_id').val('');
        $('#leadModalTitle').text('Add Lead');
        initCompanySelect2($('#company_id'), $('#leadModal'));
        leadModal.show();
    });

    // ---- Modal open: edit ----
    $(document).on('click', '.edit-lead', function () {
        clearErrors();

        const recordStr = $(this).attr('data-record');
        const record = JSON.parse(recordStr);

        $('#lead_id').val(record.id || '');
        $('#leadModalTitle').text('Edit Lead');

        $('#lead_name').val(record.lead_name || '');
        $('#email').val(record.email || '');
        $('#phone').val(record.phone || '');
        $('#position').val(record.position || '');
        $('#source').val(record.source || '');
        $('#status').val(record.status || 'new');
        $('#follow_up_date').val(record.follow_up_date || '');
        $('#assigned_to').val(record.assigned_to || '');
        $('#notes').val(record.notes || '');

        initCompanySelect2(
            $('#company_id'),
            $('#leadModal'),
            record.company_id,
            record.company_name || 'Selected company'
        );

        leadModal.show();
    });

    // ---- Save (create/update) ----
    $('#leadForm').on('submit', async function (e) {
        e.preventDefault();

        clearErrors();

        const id = $('#lead_id').val();
        const isEdit = !!id;

        const payload = {
            lead_name: $('#lead_name').val(),
            company_id: $('#company_id').val(),
            email: $('#email').val(),
            phone: $('#phone').val(),
            position: $('#position').val(),
            source: $('#source').val(),
            status: $('#status').val(),
            follow_up_date: $('#follow_up_date').val(),
            assigned_to: $('#assigned_to').val(),
            notes: $('#notes').val(),
        };

        let url = routes.store;
        let method = 'POST';

        if (isEdit) {
            url = urlWithId(routes.update, id);
            method = 'PUT';
        }

        swLoading(isEdit ? 'Updating lead...' : 'Creating lead...');

        $.ajax({
            url: url,
            method: method,
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (res) {
                Swal.close();
                leadModal.hide();
                table.ajax.reload(null, false);
                swToast(res.message || (isEdit ? 'Lead updated.' : 'Lead created.'), 'success');
            },
            error: function (xhr) {
                Swal.close();

                if (xhr.status === 422) {
                    showErrors(xhr.responseJSON?.errors || {});
                    swToast('Please fix the highlighted errors.', 'error');
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Something went wrong.'
                });
            }
        });
    });

    // ---- Delete single (SweetAlert confirm) ----
    $(document).on('click', '.delete-lead', async function () {
        const id = $(this).data('id');

        const confirm = await swConfirm({
            title: 'Delete this lead?',
            text: 'This will permanently remove the lead.',
            confirmText: 'Yes, delete',
        });

        if (!confirm.isConfirmed) return;

        swLoading('Deleting...');

        $.ajax({
            url: urlWithId(routes.destroy, id),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (res) {
                Swal.close();
                table.ajax.reload(null, false);
                swToast(res.message || 'Lead deleted.', 'success');
            },
            error: function (xhr) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Delete failed',
                    text: xhr.responseJSON?.message || 'Failed to delete.'
                });
            }
        });
    });

    // ---- Bulk delete (SweetAlert confirm) ----
    $('#bulkDeleteBtn').on('click', async function () {
        const ids = $('.row-checkbox:checked').map(function () { return $(this).val(); }).get();
        if (!ids.length) return;

        const confirm = await swConfirm({
            title: `Delete ${ids.length} lead(s)?`,
            text: 'This action cannot be undone.',
            confirmText: 'Yes, delete',
        });

        if (!confirm.isConfirmed) return;

        swLoading('Deleting selected leads...');

        $.ajax({
            url: routes.bulkDel,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: { ids },
            success: function (res) {
                Swal.close();
                $('#checkAll').prop('checked', false);
                table.ajax.reload(null, false);
                swToast(res.message || 'Selected leads deleted.', 'success');
            },
            error: function (xhr) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Bulk delete failed',
                    text: xhr.responseJSON?.message || 'Bulk delete failed.'
                });
            }
        });
    });

})();
</script>
@endpush
