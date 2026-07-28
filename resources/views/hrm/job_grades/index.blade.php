@extends('layouts.master')
@section('title', 'Job Grades')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0"><i class="fas fa-layer-group me-2"></i>Job Grades</h1>
            <small class="text-muted">HRM / Job Grades</small>
        </div>
        @can('hrm.job_grades.manage')
        <button class="btn btn-primary btn-sm" id="btnCreate">
            <i class="fas fa-plus me-1"></i> New Grade
        </button>
        @endcan
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover w-100" id="tblGrades">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Salary Range</th>
                            <th>Positions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="modalGrade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="gradeModalTitle">New Job Grade</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmGrade" novalidate>
                    @csrf
                    <input type="hidden" id="gradeId">
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="g_name" name="name" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Code</label>
                            <input type="text" class="form-control text-uppercase" id="g_code" name="code">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Min Salary</label>
                            <input type="number" class="form-control" id="g_min" name="min_salary" min="0" step="0.01">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Max Salary</label>
                            <input type="number" class="form-control" id="g_max" name="max_salary" min="0" step="0.01">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSaveGrade">
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
        datatable : '{{ route('admin.hrm.job-grades.datatable') }}',
        store     : '{{ route('admin.hrm.job-grades.store') }}',
        update    : (id) => `/admin/hrm/job-grades/${id}`,
        destroy   : (id) => `/admin/hrm/job-grades/${id}`,
    };
    const $modal = new bootstrap.Modal(document.getElementById('modalGrade'));

    const table = $('#tblGrades').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: URLS.datatable, dataSrc: 'data' },
        columns: [
            { data: 'name' },
            { data: 'code', defaultContent: '—' },
            { data: 'salary_range' },
            { data: 'positions_count' },
            { data: 'actions', orderable: false, searchable: false },
        ],
    });

    $('#btnCreate').on('click', function () {
        $('#frmGrade')[0].reset();
        $('#gradeId').val('');
        $('#gradeModalTitle').text('New Job Grade');
        $modal.show();
    });

    $('#tblGrades').on('click', '.btn-edit-grade', function () {
        const r = $(this).data('record');
        $('#gradeId').val(r.id);
        $('#g_name').val(r.name);
        $('#g_code').val(r.code);
        $('#g_min').val(r.min_salary);
        $('#g_max').val(r.max_salary);
        $('#gradeModalTitle').text('Edit Job Grade');
        $modal.show();
    });

    $('#btnSaveGrade').on('click', function () {
        const id   = $('#gradeId').val();
        const url  = id ? URLS.update(id) : URLS.store;
        const data = $('#frmGrade').serialize() + (id ? '&_method=PUT' : '');
        $.post(url, data)
            .done(() => { $modal.hide(); table.ajax.reload();
                Swal.fire({ icon:'success', title:'Saved', timer:1400, showConfirmButton:false }); })
            .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Error.', 'error'));
    });

    $('#tblGrades').on('click', '.btn-delete-grade', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete grade?', icon:'warning',
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