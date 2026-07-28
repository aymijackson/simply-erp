{{-- ============================================================
|  3) BLADE (resources/views/crm/opportunities/index.blade.php)
|============================================================ --}}
@extends('layouts.master')

@section('title', 'Opportunities')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Opportunities</h1>
            <small class="text-muted">CRM</small>
        </div>

        <div class="d-flex gap-2">
            @can('crm.opportunities.create')
            <button class="btn btn-primary" id="addOpportunityBtn">
                <i class="fas fa-plus me-1"></i> Add Opportunity
            </button>
            @endcan

            @can('crm.opportunities.delete')
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
                    <label class="form-label mb-1">Stage</label>
                    <select id="filter_stage" class="form-control">
                        <option value="">All</option>
                        @foreach($stages as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Owner</label>
                    <select id="filter_owner_id" class="form-control">
                        <option value="">All</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) }}</option>
                        @endforeach
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
                <table id="oppsTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
                            <th>Title</th>
                            <th>Customer</th>
                            <th>Value</th>
                            <th>Stage</th>
                            <th>Probability</th>
                            <th>Close Date</th>
                            <th>Owner</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <small class="text-muted d-block mt-2">
                Customer uses Select2 search (scalable).
            </small>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="oppModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="oppForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="oppModalTitle">Add Opportunity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="opp_id" value="">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" required>
                        <small class="text-danger" data-err="title"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-control" style="width:100%" required></select>
                        <small class="text-danger" data-err="customer_id"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Value (NGN) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="value" id="value" class="form-control" required>
                        <small class="text-danger" data-err="value"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Stage <span class="text-danger">*</span></label>
                        <select name="stage" id="stage" class="form-control" required>
                            @foreach($stages as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                        <small class="text-danger" data-err="stage"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Probability (%)</label>
                        <input type="number" min="0" max="100" name="probability" id="probability" class="form-control">
                        <small class="text-danger" data-err="probability"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Close Date</label>
                        <input type="date" name="close_date" id="close_date" class="form-control">
                        <small class="text-danger" data-err="close_date"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Owner <span class="text-danger">*</span></label>
                        <select name="owner_id" id="owner_id" class="form-control" required>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) }}</option>
                            @endforeach
                        </select>
                        <small class="text-danger" data-err="owner_id"></small>
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
                @can('crm.opportunities.create')
                <button type="submit" class="btn btn-primary" id="saveOppBtn">
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
.select2-container { width:100%!important; }
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
        datatable:  @json(route('admin.crm.opportunities.datatable')),
        store:      @json(route('admin.crm.opportunities.store')),
        update:     @json(route('admin.crm.opportunities.update', ['opportunity' => '__ID__'])),
        destroy:    @json(route('admin.crm.opportunities.destroy', ['opportunity' => '__ID__'])),
        bulkDel:    @json(route('admin.crm.opportunities.bulk_delete')),

        // IMPORTANT: using CustomerController@select2
        customerS2: @json(route('admin.customers.select2')),
    };

    const canDelete = @json(auth()->user()->can('crm.opportunities.delete'));

    function urlWithId(tpl, id){ return tpl.replace('__ID__', id); }

    function clearErrors(){ $('[data-err]').text(''); }

    function showErrors(errors){
        if(!errors) return;
        Object.keys(errors).forEach(k => $('[data-err="'+k+'"]').text(errors[k][0] || errors[k]));
    }

    function swalToast(title, icon='success'){
        Swal.fire({ toast:true, position:'top-end', icon, title, showConfirmButton:false, timer:2500 });
    }

    // ---- Select2: customer (modal) ----
    function initCustomerSelect2($el, dropdownParent, selectedId=null, selectedText=null){
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

        if(selectedId && selectedText){
            const opt = new Option(selectedText, selectedId, true, true);
            $el.append(opt).trigger('change');
        } else {
            $el.val(null).trigger('change');
        }
    }

    // ---- Select2: customer (filter) ----
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

    // ---- DataTable ----
    const table = $('#oppsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: routes.datatable,
            data: function(d){
                d.customer_id = $('#filter_customer_id').val() || '';
                d.stage       = $('#filter_stage').val() || '';
                d.owner_id    = $('#filter_owner_id').val() || '';
            }
        },
        order: [[1,'asc']],
        columns: [
            { data:'checkbox', orderable:false, searchable:false },
            { data:'title', name:'title' },
            { data:'customer_name', name:'customer.name', defaultContent:'—' },
            { data:'value_fmt', orderable:false, searchable:false },
            { data:'stage', name:'stage' },
            { data:'probability_fmt', orderable:false, searchable:false },
            { data:'close_date_fmt', orderable:false, searchable:false },
            { data:'owner_name', name:'owner.first_name', defaultContent:'—' },
            { data:'actions', orderable:false, searchable:false },
        ],
        drawCallback: function(){ syncBulkDeleteBtn(); }
    });

    // Filters
    $('#applyFiltersBtn').on('click', () => table.ajax.reload());
    $('#resetFiltersBtn').on('click', function(){
        $('#filter_stage').val('');
        $('#filter_owner_id').val('');
        $('#filter_customer_id').val(null).trigger('change');
        table.ajax.reload();
    });

    // Bulk select
    function syncBulkDeleteBtn(){
        if(!canDelete) return;
        const checked = $('.row-checkbox:checked').length;
        $('#bulkDeleteBtn').toggleClass('d-none', checked === 0);
    }

    $('#checkAll').on('change', function(){
        $('.row-checkbox').prop('checked', $(this).is(':checked'));
        syncBulkDeleteBtn();
    });

    $(document).on('change','.row-checkbox', function(){
        const all = $('.row-checkbox').length;
        const checked = $('.row-checkbox:checked').length;
        $('#checkAll').prop('checked', all>0 && checked===all);
        syncBulkDeleteBtn();
    });

    // Modal open
    const modalEl = document.getElementById('oppModal');
    const modal = new bootstrap.Modal(modalEl);

    $('#addOpportunityBtn').on('click', function(){
        clearErrors();
        $('#oppForm')[0].reset();
        $('#opp_id').val('');
        $('#oppModalTitle').text('Add Opportunity');
        initCustomerSelect2($('#customer_id'), $('#oppModal'));
        modal.show();
    });

    $(document).on('click','.edit-opportunity', function(){
        clearErrors();
        const record = JSON.parse($(this).attr('data-record'));

        $('#opp_id').val(record.id || '');
        $('#oppModalTitle').text('Edit Opportunity');

        $('#title').val(record.title || '');
        $('#value').val(record.value || '');
        $('#stage').val(record.stage || 'prospecting');
        $('#probability').val(record.probability ?? '');
        $('#close_date').val(record.close_date || '');
        $('#owner_id').val(record.owner_id || '');
        $('#notes').val(record.notes || '');

        initCustomerSelect2($('#customer_id'), $('#oppModal'), record.customer_id, record.customer_name || 'Selected customer');
        modal.show();
    });

    // Save
    $('#oppForm').on('submit', function(e){
        e.preventDefault();
        clearErrors();

        const id = $('#opp_id').val();
        const isEdit = !!id;

        const payload = {
            title: $('#title').val(),
            customer_id: $('#customer_id').val(),
            value: $('#value').val(),
            stage: $('#stage').val(),
            probability: $('#probability').val(),
            close_date: $('#close_date').val(),
            owner_id: $('#owner_id').val(),
            notes: $('#notes').val(),
        };

        const url = isEdit ? urlWithId(routes.update, id) : routes.store;
        const method = isEdit ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function(res){
                modal.hide();
                table.ajax.reload(null,false);
                swalToast(res.message || (isEdit ? 'Opportunity updated.' : 'Opportunity created.'), 'success');
            },
            error: function(xhr){
                if(xhr.status === 422){
                    showErrors(xhr.responseJSON?.errors || {});
                    return;
                }
                swalToast(xhr.responseJSON?.message || 'Something went wrong.', 'error');
            }
        });
    });

    // Delete single (SweetAlert)
    $(document).on('click','.delete-opportunity', function(){
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete this opportunity?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if(!result.isConfirmed) return;

            $.ajax({
                url: urlWithId(routes.destroy, id),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
                success: function(res){
                    table.ajax.reload(null,false);
                    swalToast(res.message || 'Opportunity deleted.', 'success');
                },
                error: function(xhr){
                    swalToast(xhr.responseJSON?.message || 'Failed to delete.', 'error');
                }
            });
        });
    });

    // Bulk delete (SweetAlert)
    $('#bulkDeleteBtn').on('click', function(){
        const ids = $('.row-checkbox:checked').map(function(){ return $(this).val(); }).get();
        if(!ids.length) return;

        Swal.fire({
            title: `Delete ${ids.length} selected opportunity(ies)?`,
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if(!result.isConfirmed) return;

            $.ajax({
                url: routes.bulkDel,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: { ids },
                success: function(res){
                    $('#checkAll').prop('checked', false);
                    table.ajax.reload(null,false);
                    swalToast(res.message || 'Selected opportunities deleted.', 'success');
                },
                error: function(xhr){
                    swalToast(xhr.responseJSON?.message || 'Bulk delete failed.', 'error');
                }
            });
        });
    });

})();
</script>
@endpush
