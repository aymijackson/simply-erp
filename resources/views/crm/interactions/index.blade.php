@extends('layouts.master')

@section('title', 'Interactions')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Interactions</h1>
            <small class="text-muted">CRM</small>
        </div>

        <div class="d-flex gap-2">
            @can('crm.interactions.create')
                <button class="btn btn-primary" id="addInteractionBtn">
                    <i class="fas fa-plus me-1"></i> Add Interaction
                </button>
            @endcan

            @can('crm.interactions.delete')
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

                <div class="col-md-3">
                    <label class="form-label mb-1">Interacable Type</label>
                    <select id="filter_interactable_type" class="form-control">
                        <option value="">All</option>
                        <option value="Modules\CRM\Models\Customer">Customer</option>
                        <option value="Modules\CRM\Models\Lead">Lead</option>
                        <option value="Modules\CRM\Models\Opportunity">Opportunity</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label mb-1">Interacable</label>
                    <select id="filter_interactable_id" class="form-control" style="width:100%"></select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Type</label>
                    <select id="filter_interaction_type" class="form-control">
                        <option value="">All</option>
                        <option value="call">Call</option>
                        <option value="email">Email</option>
                        <option value="meeting">Meeting</option>
                        <option value="message">Message</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Employee</label>
                    <select id="filter_employee_id" class="form-control">
                        <option value="">All</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">
                                {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('Employee #'.$e->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end mt-2">
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
                <table id="interactionsTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
                            <th>Interacable Type</th>
                            <th>Interacable</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Employee</th>
                            <th>Date</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <small class="text-muted d-block mt-2">
                Tip: Interactable uses Select2 search for scalability.
            </small>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="interactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="interactionForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="interactionModalTitle">Add Interaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="interaction_id" value="">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Interacable Type <span class="text-danger">*</span></label>
                        <select id="interactable_type" class="form-control" required>
                            <option value="">Select type...</option>
                            <option value="Modules\CRM\Models\Customer">Customer</option>
                            <option value="Modules\CRM\Models\Lead">Lead</option>
                            <option value="Modules\CRM\Models\Opportunity">Opportunity</option>
                        </select>
                        <small class="text-danger" data-err="interactable_type"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Interacable <span class="text-danger">*</span></label>
                        <select id="interactable_id" class="form-control" style="width:100%" required></select>
                        <small class="text-danger" data-err="interactable_id"></small>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" id="subject" class="form-control" required>
                        <small class="text-danger" data-err="subject"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select id="interaction_type" class="form-control" required>
                            <option value="call">Call</option>
                            <option value="email">Email</option>
                            <option value="meeting">Meeting</option>
                            <option value="message">Message</option>
                            <option value="other">Other</option>
                        </select>
                        <small class="text-danger" data-err="interaction_type"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="interaction_date" class="form-control" required>
                        <small class="text-danger" data-err="interaction_date"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select id="employee_id" class="form-control" required>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">
                                    {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('Employee #'.$e->id) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger" data-err="employee_id"></small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Details</label>
                        <textarea id="details" class="form-control" rows="4"></textarea>
                        <small class="text-danger" data-err="details"></small>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                @can('crm.interactions.create')
                    <button type="submit" class="btn btn-primary">
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

    const routes = {
        datatable: @json(route('admin.crm.interactions.datatable')),
        store:     @json(route('admin.crm.interactions.store')),
        update:    @json(route('admin.crm.interactions.update', ['interaction' => '__ID__'])),
        destroy:   @json(route('admin.crm.interactions.destroy', ['interaction' => '__ID__'])),
        bulkDel:   @json(route('admin.crm.interactions.bulk_delete')),

        fetchInteractables: @json(route('admin.crm.interactions.fetch_interactables')),
        customerS2: @json(route('admin.customers.select2')),
    };

    function urlWithId(tpl, id){ return tpl.replace('__ID__', id); }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });
    function toast(msg, icon='success'){ Toast.fire({icon, title: msg}); }

    function clearErrors(){ $('[data-err]').text(''); }
    function showErrors(errors){
        if(!errors) return;
        Object.keys(errors).forEach(k => $('[data-err="'+k+'"]').text(errors[k][0] || errors[k]));
    }

    function interactableSelect2Url(type){
        if(type === 'Modules\\CRM\\Models\\Customer') return routes.customerS2;
        return routes.fetchInteractables;
    }
    function interactableAjaxData(type, params){
        if(type === 'Modules\\CRM\\Models\\Customer') return { q: params.term || '' };
        return { type: type, q: params.term || '' };
    }

    function initInteractableSelect2($el, dropdownParent, type, selectedId=null, selectedText=null){
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            theme: 'bootstrap4',
            dropdownParent: dropdownParent || null,
            placeholder: 'Search interactable...',
            allowClear: true,
            ajax: {
                url: interactableSelect2Url(type),
                dataType: 'json',
                delay: 250,
                data: params => interactableAjaxData(type, params),
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

    // Filter interactable depends on type
    function initFilterInteractable(){
        const type = $('#filter_interactable_type').val() || 'Modules\\CRM\\Models\\Customer';
        initInteractableSelect2($('#filter_interactable_id'), null, type);
    }
    $('#filter_interactable_type').on('change', function(){
        $('#filter_interactable_id').val(null).trigger('change');
        initFilterInteractable();
    });
    initFilterInteractable();

    // DataTable
    const table = $('#interactionsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: routes.datatable,
            data: function(d){
                d.interactable_type = $('#filter_interactable_type').val() || '';
                d.interactable_id   = $('#filter_interactable_id').val() || '';
                d.interaction_type  = $('#filter_interaction_type').val() || '';
                d.employee_id       = $('#filter_employee_id').val() || '';
            }
        },
        order: [[6, 'desc']],
        columns: [
            { data:'checkbox', orderable:false, searchable:false },
            { data:'interactable_type_short', name:'interactable_type', defaultContent:'—' },
            { data:'interactable_label', name:'interactable_id', defaultContent:'—' },
            { data:'subject', name:'subject' },
            { data:'interaction_type', name:'interaction_type' },
            { data:'employee_name', name:'employee_id', defaultContent:'—' },
            { data:'interaction_date_fmt', name:'interaction_date' },
            { data:'actions', orderable:false, searchable:false },
        ],
        drawCallback: function(){ syncBulkDeleteBtn(); }
    });

    // Filters
    $('#applyFiltersBtn').on('click', () => table.ajax.reload());
    $('#resetFiltersBtn').on('click', function(){
        $('#filter_interactable_type').val('');
        $('#filter_interaction_type').val('');
        $('#filter_employee_id').val('');
        $('#filter_interactable_id').val(null).trigger('change');
        table.ajax.reload();
    });

    // Bulk
    function syncBulkDeleteBtn(){
        const checked = $('.row-checkbox:checked').length;
        const canBulk = @json(auth()->user()->can('crm.interactions.delete'));
        if(!canBulk) return;
        $('#bulkDeleteBtn').toggleClass('d-none', checked === 0);
    }
    $('#checkAll').on('change', function(){
        $('.row-checkbox').prop('checked', $(this).is(':checked'));
        syncBulkDeleteBtn();
    });
    $(document).on('change', '.row-checkbox', function(){
        const all = $('.row-checkbox').length;
        const checked = $('.row-checkbox:checked').length;
        $('#checkAll').prop('checked', all > 0 && checked === all);
        syncBulkDeleteBtn();
    });

    // Modal
    const modalEl = document.getElementById('interactionModal');
    const modal = new bootstrap.Modal(modalEl);

    $('#interactable_type').on('change', function(){
        initInteractableSelect2($('#interactable_id'), $('#interactionModal'), $(this).val());
    });

    $('#addInteractionBtn').on('click', function(){
        clearErrors();
        $('#interactionForm')[0].reset();
        $('#interaction_id').val('');
        $('#interactionModalTitle').text('Add Interaction');
        $('#interactable_type').val('Modules\\CRM\\Models\\Customer').trigger('change');
        modal.show();
    });

    $(document).on('click', '.edit-interaction', function(){
        clearErrors();
        const record = JSON.parse($(this).attr('data-record'));

        $('#interaction_id').val(record.id);
        $('#interactionModalTitle').text('Edit Interaction');
        $('#subject').val(record.subject || '');
        $('#details').val(record.details || '');
        $('#interaction_type').val(record.interaction_type || 'other');
        $('#interaction_date').val(record.interaction_date || '');
        $('#employee_id').val(record.employee_id || '');

        $('#interactable_type').val(record.interactable_type || '').trigger('change');

        initInteractableSelect2(
            $('#interactable_id'),
            $('#interactionModal'),
            record.interactable_type,
            record.interactable_id,
            record.interactable_label || 'Selected'
        );

        modal.show();
    });

    // Save
    $('#interactionForm').on('submit', function(e){
        e.preventDefault();
        clearErrors();

        const id = $('#interaction_id').val();
        const isEdit = !!id;

        const payload = {
            subject: $('#subject').val(),
            details: $('#details').val(),
            interaction_type: $('#interaction_type').val(),
            interaction_date: $('#interaction_date').val(),
            employee_id: $('#employee_id').val(),
            interactable_type: $('#interactable_type').val(),
            interactable_id: $('#interactable_id').val(),
        };

        let url = routes.store;
        let method = 'POST';
        if(isEdit){
            url = urlWithId(routes.update, id);
            method = 'PUT';
        }

        $.ajax({
            url, method, data: payload,
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function(res){
                modal.hide();
                table.ajax.reload(null, false);
                toast(res.message || (isEdit ? 'Interaction updated.' : 'Interaction created.'));
            },
            error: function(xhr){
                if(xhr.status === 422){
                    showErrors(xhr.responseJSON?.errors || {});
                    toast(xhr.responseJSON?.message || 'Validation error', 'error');
                    return;
                }
                toast(xhr.responseJSON?.message || 'Something went wrong.', 'error');
            }
        });
    });

    // Delete single
    $(document).on('click', '.delete-interaction', function(){
        const id = $(this).data('id');

        Swal.fire({
            icon: 'warning',
            title: 'Delete interaction?',
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((r) => {
            if(!r.isConfirmed) return;

            $.ajax({
                url: urlWithId(routes.destroy, id),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
                success: function(res){
                    table.ajax.reload(null, false);
                    toast(res.message || 'Interaction deleted.');
                },
                error: function(xhr){
                    toast(xhr.responseJSON?.message || 'Failed to delete.', 'error');
                }
            });
        });
    });

    // Bulk delete
    $('#bulkDeleteBtn').on('click', function(){
        const ids = $('.row-checkbox:checked').map(function(){ return $(this).val(); }).get();
        if(!ids.length) return;

        Swal.fire({
            icon: 'warning',
            title: `Delete ${ids.length} interaction(s)?`,
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((r) => {
            if(!r.isConfirmed) return;

            $.ajax({
                url: routes.bulkDel,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: { ids },
                success: function(res){
                    $('#checkAll').prop('checked', false);
                    table.ajax.reload(null, false);
                    toast(res.message || 'Selected interactions deleted.');
                },
                error: function(xhr){
                    toast(xhr.responseJSON?.message || 'Bulk delete failed.', 'error');
                }
            });
        });
    });

})();
</script>
@endpush
