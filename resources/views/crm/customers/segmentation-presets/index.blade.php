{{-- resources/views/crm/analytics/customers/segmentation-presets/index.blade.php
   Adjust path if your module uses Modules/CRM/Resources/views/...
--}}
@extends('layouts.master')

@section('title', 'Segmentation Presets')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Segmentation Presets</h1>
            <small class="text-muted">CRM • Customer Segmentation</small>
        </div>

        <div class="d-flex gap-2">
            @can('crm.customers.segmentation_presets.create')
                <button class="btn btn-primary" id="addPresetBtn">
                    <i class="fas fa-plus me-1"></i> Add Preset
                </button>
            @endcan

            @can('crm.customers.segmentation_presets.delete')
                <button class="btn btn-danger d-none" id="bulkDeleteBtn">
                    <i class="fas fa-trash me-1"></i> Delete Selected
                </button>
            @endcan
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="presetsTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>High Value Min</th>
                            <th>Hot Recency</th>
                            <th>Engaged Min</th>
                            <th>Engaged Recency</th>
                            <th>Dormant</th>
                            <th>Risk Statuses</th>
                            <th>Default</th>
                            <th>Active</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <small class="text-muted d-block mt-2">
                Tip: Mark only one preset as <b>Default</b>. Setting another default will unset the previous.
            </small>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="presetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="presetForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="presetModalTitle">Add Preset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="preset_id" value="">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" class="form-control" required>
                        <small class="text-danger" data-err="name"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <input type="text" id="description" class="form-control">
                        <small class="text-danger" data-err="description"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">High Value Min <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="high_value_min" class="form-control" required>
                        <small class="text-danger" data-err="high_value_min"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Hot Recency Days <span class="text-danger">*</span></label>
                        <input type="number" min="1" id="hot_recency_days" class="form-control" required>
                        <small class="text-danger" data-err="hot_recency_days"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Dormant Days <span class="text-danger">*</span></label>
                        <input type="number" min="1" id="dormant_days" class="form-control" required>
                        <small class="text-danger" data-err="dormant_days"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Engaged Score Min <span class="text-danger">*</span></label>
                        <input type="number" min="0" id="engaged_score_min" class="form-control" required>
                        <small class="text-danger" data-err="engaged_score_min"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Engaged Recency Days <span class="text-danger">*</span></label>
                        <input type="number" min="1" id="engaged_recency_days" class="form-control" required>
                        <small class="text-danger" data-err="engaged_recency_days"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Risk Statuses (comma-separated)</label>
                        <input type="text" id="risk_statuses" class="form-control" placeholder="open,pending,in_progress">
                        <small class="text-danger" data-err="risk_statuses"></small>
                        <small class="text-muted">Stored as JSON array.</small>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="is_default" value="1">
                            <label class="form-check-label" for="is_default">Set as Default</label>
                        </div>
                        <small class="text-danger" data-err="is_default"></small>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <small class="text-danger" data-err="is_active"></small>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                @can('crm.customers.segmentation_presets.create')
                    <button type="submit" class="btn btn-primary" id="savePresetBtn">
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
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const routes = {
        datatable: @json(route('admin.crm.customers.segmentation_presets.datatable')),
        store:     @json(route('admin.crm.customers.segmentation_presets.store')),
        update:    @json(route('admin.crm.customers.segmentation_presets.update', ['preset' => '__ID__'])),
        destroy:   @json(route('admin.crm.customers.segmentation_presets.destroy', ['preset' => '__ID__'])),
        // If you want bulk delete endpoint later, we can add it
    };

    function urlWithId(tpl, id){ return tpl.replace('__ID__', id); }

    function clearErrors(){ $('[data-err]').text(''); }
    function showErrors(errors){
        if (!errors) return;
        Object.keys(errors).forEach(k => {
            $('[data-err="'+k+'"]').text(errors[k][0] || errors[k]);
        });
    }

    function swalOk(title, text=''){
        Swal.fire({ icon:'success', title, text, timer:1500, showConfirmButton:false });
    }
    function swalErr(text){
        Swal.fire({ icon:'error', title:'Error', text:text || 'Something went wrong.' });
    }
    function confirmDelete(text){
        return Swal.fire({
            icon:'warning', title:'Confirm', text: text || 'Are you sure?',
            showCancelButton:true, confirmButtonText:'Yes, delete', cancelButtonText:'Cancel'
        });
    }

    function can(permissionBool){ return !!permissionBool; }

    const CAN_CREATE = @json(auth()->user()->can('crm.customers.segmentation_presets.create'));
    const CAN_UPDATE = @json(auth()->user()->can('crm.customers.segmentation_presets.update'));
    const CAN_DELETE = @json(auth()->user()->can('crm.customers.segmentation_presets.delete'));

    // DataTable
    const table = $('#presetsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: { url: routes.datatable },
        order: [[1, 'asc']],
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description', defaultContent: '' },
            { data: 'high_value_min', name: 'high_value_min' },
            { data: 'hot_recency_days', name: 'hot_recency_days' },
            { data: 'engaged_score_min', name: 'engaged_score_min' },
            { data: 'engaged_recency_days', name: 'engaged_recency_days' },
            { data: 'dormant_days', name: 'dormant_days' },
            { data: 'risk_statuses_txt', orderable: false, searchable: false },
            { data: 'is_default', name: 'is_default' },
            { data: 'is_active', name: 'is_active' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        drawCallback: function () {
            syncBulkDeleteBtn();
        }
    });

    // Bulk selection UI (ready for bulk delete endpoint later)
    function syncBulkDeleteBtn() {
        const checked = $('.row-checkbox:checked').length;
        if (!CAN_DELETE) return;
        $('#bulkDeleteBtn').toggleClass('d-none', checked === 0);
    }

    $('#checkAll').on('change', function(){
        const checked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', checked);
        syncBulkDeleteBtn();
    });

    $(document).on('change', '.row-checkbox', function(){
        const all = $('.row-checkbox').length;
        const checked = $('.row-checkbox:checked').length;
        $('#checkAll').prop('checked', all > 0 && checked === all);
        syncBulkDeleteBtn();
    });

    // Modal
    const modalEl = document.getElementById('presetModal');
    const modal = new bootstrap.Modal(modalEl);

    function resetForm(){
        clearErrors();
        $('#presetForm')[0].reset();
        $('#preset_id').val('');
        $('#is_active').prop('checked', true);
        $('#is_default').prop('checked', false);
    }

    $('#addPresetBtn').on('click', function(){
        if (!CAN_CREATE) return;
        resetForm();
        $('#presetModalTitle').text('Add Preset');
        modal.show();
    });

    $(document).on('click', '.edit-preset', function(){
        if (!CAN_UPDATE) return;
        resetForm();

        const record = JSON.parse($(this).attr('data-record'));
        $('#preset_id').val(record.id);
        $('#presetModalTitle').text('Edit Preset');

        $('#name').val(record.name || '');
        $('#description').val(record.description || '');
        $('#high_value_min').val(record.high_value_min ?? 0);
        $('#hot_recency_days').val(record.hot_recency_days ?? 30);
        $('#engaged_score_min').val(record.engaged_score_min ?? 10);
        $('#engaged_recency_days').val(record.engaged_recency_days ?? 30);
        $('#dormant_days').val(record.dormant_days ?? 90);

        const rs = Array.isArray(record.risk_statuses) ? record.risk_statuses.join(',') : (record.risk_statuses || '');
        $('#risk_statuses').val(rs);

        $('#is_default').prop('checked', !!record.is_default);
        $('#is_active').prop('checked', record.is_active === undefined ? true : !!record.is_active);

        modal.show();
    });

    // Save (create/update)
    $('#presetForm').on('submit', function(e){
        e.preventDefault();
        clearErrors();

        const id = $('#preset_id').val();
        const isEdit = !!id;

        const riskStatuses = ($('#risk_statuses').val() || '')
            .split(',')
            .map(s => s.trim())
            .filter(Boolean);

        const payload = {
            name: $('#name').val(),
            description: $('#description').val(),
            high_value_min: $('#high_value_min').val(),
            hot_recency_days: $('#hot_recency_days').val(),
            engaged_score_min: $('#engaged_score_min').val(),
            engaged_recency_days: $('#engaged_recency_days').val(),
            dormant_days: $('#dormant_days').val(),
            risk_statuses: riskStatuses,
            is_default: $('#is_default').is(':checked') ? 1 : 0,
            is_active: $('#is_active').is(':checked') ? 1 : 0,
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
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function(res){
                modal.hide();
                table.ajax.reload(null, false);
                swalOk('Saved', res.message || (isEdit ? 'Preset updated.' : 'Preset created.'));
            },
            error: function(xhr){
                if (xhr.status === 422) {
                    showErrors(xhr.responseJSON?.errors || {});
                    return;
                }
                swalErr(xhr.responseJSON?.message || 'Failed to save.');
            }
        });
    });

    // Delete
    $(document).on('click', '.delete-preset', async function(){
        if (!CAN_DELETE) return;

        const id = $(this).data('id');
        const res = await confirmDelete('Delete this preset?');
        if (!res.isConfirmed) return;

        $.ajax({
            url: urlWithId(routes.destroy, id),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function(res){
                table.ajax.reload(null, false);
                swalOk('Deleted', res.message || 'Preset deleted.');
            },
            error: function(xhr){
                swalErr(xhr.responseJSON?.message || 'Failed to delete.');
            }
        });
    });

    // Bulk delete button placeholder (we’ll add endpoint if you want)
    $('#bulkDeleteBtn').on('click', function(){
        Swal.fire({
            icon:'info',
            title:'Bulk delete',
            text:'If you want bulk delete, tell me and I will add the route + controller + SQL audit for it.'
        });
    });

})();
</script>
@endpush
