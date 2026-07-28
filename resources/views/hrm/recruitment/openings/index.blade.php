@extends('layouts.master')
@section('title', 'Job Openings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0"><i class="fas fa-user-plus me-2"></i>Job Openings</h1>
            <small class="text-muted">HRM / Recruitment</small>
        </div>
        @can('hrm.recruitment.openings.manage')
        <button class="btn btn-primary btn-sm" id="btnCreate">
            <i class="fas fa-plus me-1"></i> New Opening
        </button>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="card shadow mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Status</label>
                    <select id="fStatus" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="open">Open</option>
                        <option value="on_hold">On Hold</option>
                        <option value="closed">Closed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-sm-4 d-flex gap-2">
                    <button class="btn btn-primary btn-sm w-100" id="btnFilter">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <button class="btn btn-secondary btn-sm w-100" id="btnReset">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover w-100" id="tblOpenings">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Vacancies</th>
                            <th>Applicants</th>
                            <th>Posted</th>
                            <th>Closes</th>
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
<div class="modal fade" id="modalOpening" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="openingModalTitle">New Job Opening</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmOpening" novalidate>
                    @csrf
                    <input type="hidden" id="openingId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="o_title" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <select class="form-select" id="o_dept" name="department_id">
                                <option value="">— None —</option>
                                @foreach(\App\Models\Department::orderBy('name')->get() as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Job Position</label>
                            <select class="form-select" id="o_position" name="job_position_id">
                                <option value="">— None —</option>
                                @foreach(\Modules\HRM\Models\HrJobPosition::where('is_active',true)->orderBy('title')->get() as $pos)
                                    <option value="{{ $pos->id }}">{{ $pos->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Vacancies</label>
                            <input type="number" class="form-control" id="o_vacancies" name="vacancies" min="1" value="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="o_status" name="status" required>
                                <option value="draft">Draft</option>
                                <option value="open">Open</option>
                                <option value="on_hold">On Hold</option>
                                <option value="closed">Closed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Posted Date</label>
                            <input type="date" class="form-control" id="o_posted" name="posted_date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Closing Date</label>
                            <input type="date" class="form-control" id="o_closing" name="closing_date">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="o_desc" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Requirements</label>
                            <textarea class="form-control" id="o_req" name="requirements" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSaveOpening">
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
        datatable : '{{ route('admin.hrm.recruitment.openings.datatable') }}',
        store     : '{{ route('admin.hrm.recruitment.openings.store') }}',
        update    : (id) => `/admin/hrm/recruitment/openings/${id}`,
        destroy   : (id) => `/admin/hrm/recruitment/openings/${id}`,
        show      : (id) => `/admin/hrm/recruitment/openings/${id}`,
    };
    const $modal = new bootstrap.Modal(document.getElementById('modalOpening'));

    function buildTable(extra = {}) {
        if ($.fn.DataTable.isDataTable('#tblOpenings')) $('#tblOpenings').DataTable().destroy();
        $('#tblOpenings').DataTable({
            processing: true, serverSide: true, responsive: true,
            ajax: { url: URLS.datatable, data: extra, dataSrc: 'data' },
            columns: [
                { data: 'title' },
                { data: 'dept_name',      defaultContent: '—' },
                { data: 'position_title', defaultContent: '—' },
                { data: 'vacancies' },
                { data: 'applicants_count' },
                { data: 'posted_date',    defaultContent: '—' },
                { data: 'closing_date',   defaultContent: '—' },
                { data: 'status_badge',   orderable: false },
                { data: 'actions',        orderable: false, searchable: false },
            ],
            order: [[5, 'desc']],
        });
    }

    buildTable();

    $('#btnFilter').on('click', () => buildTable({ status: $('#fStatus').val() || undefined }));
    $('#btnReset').on('click', () => { $('#fStatus').val(''); buildTable(); });

    $('#btnCreate').on('click', function () {
        $('#frmOpening')[0].reset();
        $('#openingId').val('');
        $('#o_vacancies').val(1);
        $('#o_status').val('draft');
        $('#o_posted').val(new Date().toISOString().substring(0,10));
        $('#openingModalTitle').text('New Job Opening');
        $modal.show();
    });

    $('#tblOpenings').on('click', '.btn-edit-opening', function () {
        const r = $(this).data('record');
        $('#openingId').val(r.id);
        $('#o_title').val(r.title);
        $('#o_dept').val(r.department_id);
        $('#o_position').val(r.job_position_id);
        $('#o_vacancies').val(r.vacancies);
        $('#o_status').val(r.status);
        $('#o_posted').val(r.posted_date  ? r.posted_date.substring(0,10)  : '');
        $('#o_closing').val(r.closing_date ? r.closing_date.substring(0,10) : '');
        $('#o_desc').val(r.description);
        $('#o_req').val(r.requirements);
        $('#openingModalTitle').text('Edit Job Opening');
        $modal.show();
    });

    $('#btnSaveOpening').on('click', function () {
        const id   = $('#openingId').val();
        const url  = id ? URLS.update(id) : URLS.store;
        const data = $('#frmOpening').serialize() + (id ? '&_method=PUT' : '');
        $.post(url, data)
            .done(() => { $modal.hide(); buildTable();
                Swal.fire({ icon:'success', title:'Saved', timer:1400, showConfirmButton:false }); })
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error.');
                Swal.fire('Error', msg, 'error');
            });
    });

    $('#tblOpenings').on('click', '.btn-delete-opening', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete opening?', icon:'warning',
            showCancelButton:true, confirmButtonColor:'#e74a3b' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.destroy(id), { _token:CSRF, _method:'DELETE' })
                    .done(() => buildTable());
            });
    });
})();
</script>
@endpush