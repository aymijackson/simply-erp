@extends('layouts.master')
@section('title', 'Shifts')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0"><i class="fas fa-clock me-2"></i>Shifts</h1>
            <small class="text-muted">HRM / Shifts</small>
        </div>
        @can('hrm.shifts.manage')
        <button class="btn btn-primary btn-sm" id="btnCreate">
            <i class="fas fa-plus me-1"></i> New Shift
        </button>
        @endcan
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover w-100" id="tblShifts">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Break (min)</th>
                            <th>Working Hrs</th>
                            <th>Overnight</th>
                            <th>Rosters</th>
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
<div class="modal fade" id="modalShift" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="shiftModalTitle">New Shift</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmShift" novalidate>
                    @csrf
                    <input type="hidden" id="shiftId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Shift Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="s_name" name="name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="s_start" name="start_time" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="s_end" name="end_time" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Break (minutes)</label>
                            <input type="number" class="form-control" id="s_break" name="break_minutes" min="0" value="0">
                        </div>
                        <div class="col-6 d-flex align-items-end gap-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="s_overnight" name="is_overnight" value="1">
                                <label class="form-check-label" for="s_overnight">Overnight</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="s_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="s_active">Active</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSaveShift"><i class="fas fa-save me-1"></i> Save</button>
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
        datatable : '{{ route('admin.hrm.shifts.datatable') }}',
        store     : '{{ route('admin.hrm.shifts.store') }}',
        update    : (id) => `/admin/hrm/shifts/${id}`,
        destroy   : (id) => `/admin/hrm/shifts/${id}`,
    };
    const $modal = new bootstrap.Modal(document.getElementById('modalShift'));

    const table = $('#tblShifts').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: URLS.datatable, dataSrc: 'data' },
        columns: [
            { data: 'name' },
            { data: 'start_time' },
            { data: 'end_time' },
            { data: 'break_minutes' },
            { data: 'hours' },
            { data: 'overnight_badge', orderable: false },
            { data: 'rosters_count' },
            { data: 'status_badge',   orderable: false },
            { data: 'actions',        orderable: false, searchable: false },
        ],
    });

    $('#btnCreate').on('click', function () {
        $('#frmShift')[0].reset();
        $('#shiftId').val('');
        $('#s_active').prop('checked', true);
        $('#shiftModalTitle').text('New Shift');
        $modal.show();
    });

    $('#tblShifts').on('click', '.btn-edit-shift', function () {
        const r = $(this).data('record');
        $('#shiftId').val(r.id);
        $('#s_name').val(r.name);
        $('#s_start').val(r.start_time ? r.start_time.substring(0,5) : '');
        $('#s_end').val(r.end_time   ? r.end_time.substring(0,5)   : '');
        $('#s_break').val(r.break_minutes);
        $('#s_overnight').prop('checked', !!r.is_overnight);
        $('#s_active').prop('checked',    !!r.is_active);
        $('#shiftModalTitle').text('Edit Shift');
        $modal.show();
    });

    $('#btnSaveShift').on('click', function () {
        const id   = $('#shiftId').val();
        const url  = id ? URLS.update(id) : URLS.store;
        const data = $('#frmShift').serialize() + (id ? '&_method=PUT' : '');
        $.post(url, data)
            .done(() => { $modal.hide(); table.ajax.reload();
                Swal.fire({ icon:'success', title:'Saved', timer:1400, showConfirmButton:false }); })
            .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Error.', 'error'));
    });

    $('#tblShifts').on('click', '.btn-delete-shift', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete shift?', icon:'warning', showCancelButton:true, confirmButtonColor:'#e74a3b' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.destroy(id), { _token:CSRF, _method:'DELETE' })
                    .done(() => table.ajax.reload());
            });
    });
})();
</script>
@endpush