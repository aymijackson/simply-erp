@extends('layouts.master')
@section('title', 'Job Positions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0"><i class="fas fa-briefcase me-2"></i>Job Positions</h1>
            <small class="text-muted">HRM / Job Positions</small>
        </div>
        @can('hrm.job_positions.manage')
        <button class="btn btn-primary btn-sm" id="btnCreate">
            <i class="fas fa-plus me-1"></i> New Position
        </button>
        @endcan
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover w-100" id="tblPositions">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Grade</th>
                            <th>Openings</th>
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
<div class="modal fade" id="modalPosition" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="posModalTitle">New Job Position</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmPosition" novalidate>
                    @csrf
                    <input type="hidden" id="posId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="p_title" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <select class="form-select" id="p_dept" name="department_id">
                                <option value="">— None —</option>
                                @foreach(\App\Models\Department::orderBy('name')->get() as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Job Grade</label>
                            <select class="form-select" id="p_grade" name="job_grade_id">
                                <option value="">— None —</option>
                                @foreach(\Modules\HRM\Models\HrJobGrade::orderBy('name')->get() as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="p_desc" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="p_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="p_active">Active</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSavePosition">
                    <i class="fas fa-save me-1"></i> Save
                </button>
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
        datatable : '{{ route('admin.hrm.job-positions.datatable') }}',
        store     : '{{ route('admin.hrm.job-positions.store') }}',
        update    : (id) => `/admin/hrm/job-positions/${id}`,
        destroy   : (id) => `/admin/hrm/job-positions/${id}`,
    };
    const $modal = new bootstrap.Modal(document.getElementById('modalPosition'));

    const table = $('#tblPositions').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: URLS.datatable, dataSrc: 'data' },
        columns: [
            { data: 'title' },
            { data: 'dept_name',  defaultContent: '—' },
            { data: 'grade_name', defaultContent: '—' },
            { data: 'openings_count' },
            { data: 'status_badge', orderable: false },
            { data: 'actions',      orderable: false, searchable: false },
        ],
    });

    $('#btnCreate').on('click', function () {
        $('#frmPosition')[0].reset();
        $('#posId').val('');
        $('#p_active').prop('checked', true);
        $('#posModalTitle').text('New Job Position');
        $modal.show();
    });

    $('#tblPositions').on('click', '.btn-edit-position', function () {
        const r = $(this).data('record');
        $('#posId').val(r.id);
        $('#p_title').val(r.title);
        $('#p_dept').val(r.department_id);
        $('#p_grade').val(r.job_grade_id);
        $('#p_desc').val(r.description);
        $('#p_active').prop('checked', !!r.is_active);
        $('#posModalTitle').text('Edit Job Position');
        $modal.show();
    });

    $('#btnSavePosition').on('click', function () {
        const id   = $('#posId').val();
        const url  = id ? URLS.update(id) : URLS.store;
        const data = $('#frmPosition').serialize() + (id ? '&_method=PUT' : '');
        $.post(url, data)
            .done(() => { $modal.hide(); table.ajax.reload();
                Swal.fire({ icon:'success', title:'Saved', timer:1400, showConfirmButton:false }); })
            .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Error.', 'error'));
    });

    $('#tblPositions').on('click', '.btn-delete-position', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete position?', icon:'warning',
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