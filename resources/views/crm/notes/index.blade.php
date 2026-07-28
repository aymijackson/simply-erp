@extends('layouts.master')

@section('title', 'Notes')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Notes</h1>
            <small class="text-muted">CRM</small>
        </div>

        <div class="d-flex gap-2">
            @can('crm.notes.create')
                <button class="btn btn-primary" id="addNoteBtn">
                    <i class="fas fa-plus me-1"></i> Add Note
                </button>
            @endcan

            @can('crm.notes.delete')
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
                    <label class="form-label mb-1">Notable Type</label>
                    <select id="filter_notable_type" class="form-control">
                        <option value="">All</option>
                        <option value="Modules\CRM\Models\Customer">Customer</option>
                        <option value="Modules\CRM\Models\Lead">Lead</option>
                        <option value="Modules\CRM\Models\Opportunity">Opportunity</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label mb-1">Notable</label>
                    <select id="filter_notable_id" class="form-control" style="width:100%"></select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Author</label>
                    <select id="filter_author_id" class="form-control">
                        <option value="">All</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">
                                {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('Employee #'.$e->id) }}
                            </option>
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
                <table id="notesTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:35px;">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>Notable Type</th>
                            <th>Notable</th>
                            <th>Subject</th>
                            <th>Author</th>
                            <th>Created</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <small class="text-muted d-block mt-2">
                Tip: Notable uses Select2 search for scalability.
            </small>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="noteForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="noteModalTitle">Add Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="note_id" value="">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Notable Type <span class="text-danger">*</span></label>
                        <select name="notable_type" id="notable_type" class="form-control" required>
                            <option value="">Select type...</option>
                            <option value="Modules\CRM\Models\Customer">Customer</option>
                            <option value="Modules\CRM\Models\Lead">Lead</option>
                            <option value="Modules\CRM\Models\Opportunity">Opportunity</option>
                        </select>
                        <small class="text-danger" data-err="notable_type"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Notable <span class="text-danger">*</span></label>
                        <select name="notable_id" id="notable_id" class="form-control" style="width:100%" required></select>
                        <small class="text-danger" data-err="notable_id"></small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="subject" class="form-control" required>
                        <small class="text-danger" data-err="subject"></small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea name="content" id="content" class="form-control" rows="5" required></textarea>
                        <small class="text-danger" data-err="content"></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Author <span class="text-danger">*</span></label>
                        <select name="author_id" id="author_id" class="form-control" required>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">
                                    {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('Employee #'.$e->id) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger" data-err="author_id"></small>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                @can('crm.notes.create')
                    <button type="submit" class="btn btn-primary" id="saveNoteBtn">
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

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ✅ routes (adjust names if yours differ)
    const routes = {
        datatable: @json(route('admin.crm.notes.datatable')),
        store:     @json(route('admin.crm.notes.store')),
        update:    @json(route('admin.crm.notes.update', ['note' => '__ID__'])),
        destroy:   @json(route('admin.crm.notes.destroy', ['note' => '__ID__'])),
        bulkDel:   @json(route('admin.crm.notes.bulk_delete')),

        // Leads/Opportunities notables:
        fetchNotables: @json(route('admin.crm.notes.fetch_notables')),

        // ✅ CustomerController select2 (you requested this)
        customerS2: @json(route('admin.customers.select2')),
    };

    function urlWithId(tpl, id) {
        return tpl.replace('__ID__', id);
    }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });

    function toast(msg, icon='success') {
        Toast.fire({ icon, title: msg });
    }

    function clearErrors() { $('[data-err]').text(''); }
    function showErrors(errors) {
        if (!errors) return;
        Object.keys(errors).forEach(k => $('[data-err="'+k+'"]').text(errors[k][0] || errors[k]));
    }

    // ---------- Select2: Notable (Modal + Filters) ----------
    function notableSelect2Url(type) {
        if (type === 'Modules\\CRM\\Models\\Customer') return routes.customerS2;
        return routes.fetchNotables;
    }

    function notableSelect2AjaxData(type, params) {
        if (type === 'Modules\\CRM\\Models\\Customer') {
            return { q: params.term || '' };
        }
        return { type: type, q: params.term || '' };
    }

    function initNotableSelect2($el, dropdownParent, type, selectedId=null, selectedText=null) {
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            theme: 'bootstrap4',
            dropdownParent: dropdownParent || null,
            placeholder: 'Search notable...',
            allowClear: true,
            ajax: {
                url: notableSelect2Url(type),
                dataType: 'json',
                delay: 250,
                data: params => notableSelect2AjaxData(type, params),
                processResults: function (data) {
                    // CustomerController select2 might return: [{id,text}] already
                    // fetchNotables returns: [{id,text}] by our controller update
                    return { results: data };
                },
                cache: true
            }
        });

        // ✅ Preselect on edit so it shows name, not ID
        if (selectedId && selectedText) {
            const opt = new Option(selectedText, selectedId, true, true);
            $el.append(opt).trigger('change');
        } else {
            $el.val(null).trigger('change');
        }
    }

    // Filter notable select2 depends on filter_notable_type
    function initFilterNotable() {
        const type = $('#filter_notable_type').val() || 'Modules\\CRM\\Models\\Customer';
        initNotableSelect2($('#filter_notable_id'), null, type);
    }

    $('#filter_notable_type').on('change', function () {
        $('#filter_notable_id').val(null).trigger('change');
        initFilterNotable();
    });

    initFilterNotable();

    // Modal notable select2 depends on notable_type
    $('#notable_type').on('change', function () {
        const type = $(this).val();
        initNotableSelect2($('#notable_id'), $('#noteModal'), type);
    });

    // ---------- DataTable ----------
    const table = $('#notesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: routes.datatable,
            data: function (d) {
                d.notable_type = $('#filter_notable_type').val() || '';
                d.notable_id   = $('#filter_notable_id').val() || '';
                d.author_id    = $('#filter_author_id').val() || '';
            }
        },
        order: [[5, 'desc']],
        columns: [
            { data: 'checkbox', orderable:false, searchable:false },
            { data: 'notable_type_short', name:'notable_type', defaultContent:'—' },
            { data: 'notable_label', name:'notable_id', defaultContent:'—' },
            { data: 'subject', name:'subject' },
            { data: 'author_name', name:'author_id', defaultContent:'—' },
            { data: 'created_at_formatted', name:'created_at' },
            { data: 'actions', orderable:false, searchable:false },
        ],
        drawCallback: function () {
            syncBulkDeleteBtn();
        }
    });

    // ---------- Filters ----------
    $('#applyFiltersBtn').on('click', function () {
        table.ajax.reload();
    });

    $('#resetFiltersBtn').on('click', function () {
        $('#filter_notable_type').val('');
        $('#filter_author_id').val('');
        $('#filter_notable_id').val(null).trigger('change');
        table.ajax.reload();
    });

    // ---------- Bulk select ----------
    function syncBulkDeleteBtn() {
        const checked = $('.row-checkbox:checked').length;
        const canBulk = @json(auth()->user()->can('crm.notes.delete'));
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

    // ---------- Modal ----------
    const noteModalEl = document.getElementById('noteModal');
    const noteModal = new bootstrap.Modal(noteModalEl);

    $('#addNoteBtn').on('click', function () {
        clearErrors();
        $('#noteForm')[0].reset();
        $('#note_id').val('');
        $('#noteModalTitle').text('Add Note');

        // default notable type
        $('#notable_type').val('Modules\\CRM\\Models\\Customer').trigger('change');

        // author default to current user's employee id if you want (optional)
        // $('#author_id').val('{{ auth()->user()->employee_id ?? "" }}');

        noteModal.show();
    });

    $(document).on('click', '.edit-note', function () {
        clearErrors();

        const record = JSON.parse($(this).attr('data-record'));

        $('#note_id').val(record.id);
        $('#noteModalTitle').text('Edit Note');

        $('#subject').val(record.subject || '');
        $('#content').val(record.content || '');
        $('#author_id').val(record.author_id || '');

        // set type then init notable select2 with preselected name
        $('#notable_type').val(record.notable_type || '').trigger('change');

        initNotableSelect2(
            $('#notable_id'),
            $('#noteModal'),
            record.notable_type,
            record.notable_id,
            record.notable_label || 'Selected'
        );

        noteModal.show();
    });

    // ---------- Save ----------
    $('#noteForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const id = $('#note_id').val();
        const isEdit = !!id;

        const payload = {
            subject: $('#subject').val(),
            content: $('#content').val(),
            author_id: $('#author_id').val(),
            notable_type: $('#notable_type').val(),
            notable_id: $('#notable_id').val(),
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
            success: function (res) {
                noteModal.hide();
                table.ajax.reload(null, false);
                toast(res.message || (isEdit ? 'Note updated.' : 'Note created.'));
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showErrors(xhr.responseJSON?.errors || {});
                    toast(xhr.responseJSON?.message || 'Validation error', 'error');
                    return;
                }
                toast(xhr.responseJSON?.message || 'Something went wrong.', 'error');
            }
        });
    });

    // ---------- Delete single (SweetAlert) ----------
    $(document).on('click', '.delete-note', function () {
        const id = $(this).data('id');

        Swal.fire({
            icon: 'warning',
            title: 'Delete note?',
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: urlWithId(routes.destroy, id),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
                success: function (res) {
                    table.ajax.reload(null, false);
                    toast(res.message || 'Note deleted.');
                },
                error: function (xhr) {
                    toast(xhr.responseJSON?.message || 'Failed to delete.', 'error');
                }
            });
        });
    });

    // ---------- Bulk delete (SweetAlert) ----------
    $('#bulkDeleteBtn').on('click', function () {
        const ids = $('.row-checkbox:checked').map(function () { return $(this).val(); }).get();
        if (!ids.length) return;

        Swal.fire({
            icon: 'warning',
            title: `Delete ${ids.length} note(s)?`,
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: routes.bulkDel,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: { ids },
                success: function (res) {
                    $('#checkAll').prop('checked', false);
                    table.ajax.reload(null, false);
                    toast(res.message || 'Selected notes deleted.');
                },
                error: function (xhr) {
                    toast(xhr.responseJSON?.message || 'Bulk delete failed.', 'error');
                }
            });
        });
    });

})();
</script>
@endpush
