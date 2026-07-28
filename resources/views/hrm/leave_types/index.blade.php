@extends('layouts.master')
@section('title', 'Leave Types')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0"><i class="fas fa-calendar-times me-2"></i>Leave Types</h1>
            <small class="text-muted">HRM / Leave Types</small>
        </div>
        @can('hrm.leave_types.create')
        <button class="btn btn-primary btn-sm" id="btnCreate">
            <i class="fas fa-plus me-1"></i> New Leave Type
        </button>
        @endcan
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover w-100" id="tblLeaveTypes">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Days Allowed</th>
                            <th>Carry Over</th>
                            <th>Paid</th>
                            <th>Gender</th>
                            <th>Approval</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="modalLeaveType" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">New Leave Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmLeaveType" novalidate>
                    @csrf
                    <input type="hidden" id="ltId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lt_name" name="name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="lt_code" name="code" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Days Allowed <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="lt_days" name="days_allowed" min="0" step="0.5" value="0" required>
                            <div class="form-text">0 = unlimited</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Carry Over Days</label>
                            <input type="number" class="form-control" id="lt_carry" name="carry_over_days" min="0" step="0.5" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Gender Restriction</label>
                            <select class="form-select" id="lt_gender" name="gender_restriction">
                                <option value="all">All</option>
                                <option value="male">Male Only</option>
                                <option value="female">Female Only</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex gap-4 align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="lt_is_paid" name="is_paid" value="1" checked>
                                <label class="form-check-label" for="lt_is_paid">Paid Leave</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="lt_requires_approval" name="requires_approval" value="1" checked>
                                <label class="form-check-label" for="lt_requires_approval">Requires Approval</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="lt_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="lt_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSave"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');
    const URLS = {
        datatable : '{{ route('admin.hrm.leave-types.datatable') }}',
        store     : '{{ route('admin.hrm.leave-types.store') }}',
        update    : (id) => `/admin/hrm/leave-types/${id}`,
        destroy   : (id) => `/admin/hrm/leave-types/${id}`,
    };

    const $modal = new bootstrap.Modal(document.getElementById('modalLeaveType'));

    const table = $('#tblLeaveTypes').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: URLS.datatable, dataSrc: 'data' },
        columns: [
            { data: 'name' },
            { data: 'code' },
            { data: 'days_allowed', render: v => v == 0 ? '<span class="text-muted">Unlimited</span>' : v },
            { data: 'carry_over_days' },
            { data: 'paid_badge',   orderable: false },
            { data: 'gender_restriction', render: v => v.charAt(0).toUpperCase() + v.slice(1) },
            { data: 'requires_approval', render: v => v ? 'Yes' : 'No' },
            { data: 'status_badge', orderable: false },
            { data: 'actions',      orderable: false, searchable: false },
        ],
    });

    function openCreate() {
        $('#frmLeaveType')[0].reset();
        $('#ltId').val('');
        $('#lt_is_paid, #lt_requires_approval, #lt_is_active').prop('checked', true);
        $('#modalTitle').text('New Leave Type');
        $modal.show();
    }

    function openEdit(r) {
        $('#ltId').val(r.id);
        $('#lt_name').val(r.name);
        $('#lt_code').val(r.code);
        $('#lt_days').val(r.days_allowed);
        $('#lt_carry').val(r.carry_over_days);
        $('#lt_gender').val(r.gender_restriction);
        $('#lt_is_paid').prop('checked', !!r.is_paid);
        $('#lt_requires_approval').prop('checked', !!r.requires_approval);
        $('#lt_is_active').prop('checked', !!r.is_active);
        $('#modalTitle').text('Edit Leave Type');
        $modal.show();
    }

    $('#btnCreate').on('click', openCreate);

    $('#btnSave').on('click', function () {
        const id   = $('#ltId').val();
        const url  = id ? URLS.update(id) : URLS.store;
        const data = $('#frmLeaveType').serialize() + (id ? '&_method=PUT' : '');
        $.post(url, data)
            .done(() => { $modal.hide(); table.ajax.reload();
                Swal.fire({ icon:'success', title:'Saved', timer:1400, showConfirmButton:false }); })
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error.');
                Swal.fire('Error', msg, 'error');
            });
    });

    $('#tblLeaveTypes').on('click', '.btn-edit', function () {
        openEdit($(this).data('record'));
    });

    $('#tblLeaveTypes').on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete leave type?', icon:'warning',
            showCancelButton:true, confirmButtonColor:'#e74a3b' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.destroy(id), { _token:CSRF, _method:'DELETE' })
                    .done(() => table.ajax.reload())
                    .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Cannot delete.', 'error'));
            });
    });
})();
</script>
@endpush