@extends('layouts.master')
@section('title', $opening->title)

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">{{ $opening->title }}</h1>
            <small class="text-muted">
                {{ $opening->department?->name ?? '—' }}
                @if($opening->jobPosition) &bull; {{ $opening->jobPosition->title }} @endif
                &bull;
                @switch($opening->status)
                    @case('open')      <span class="badge bg-success">Open</span>      @break
                    @case('on_hold')   <span class="badge bg-warning text-dark">On Hold</span> @break
                    @case('closed')    <span class="badge bg-secondary">Closed</span>  @break
                    @case('cancelled') <span class="badge bg-danger">Cancelled</span>  @break
                    @default           <span class="badge bg-light text-dark">Draft</span>
                @endswitch
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hrm.recruitment.openings.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            @can('hrm.recruitment.applicants.manage')
            <button class="btn btn-primary btn-sm" id="btnAddApplicant">
                <i class="fas fa-user-plus me-1"></i> Add Applicant
            </button>
            @endcan
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Vacancies</div>
                    <div class="fw-bold h5 mb-0">{{ $opening->vacancies }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Applicants</div>
                    <div class="fw-bold h5 mb-0">{{ $opening->applicants->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">In Interview</div>
                    <div class="fw-bold h5 mb-0">{{ $opening->applicants->where('stage','interview')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Hired</div>
                    <div class="fw-bold h5 mb-0 text-success">{{ $opening->applicants->where('stage','hired')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Posted</div>
                    <div class="fw-bold">{{ $opening->posted_date?->format('d M Y') ?? '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Closes</div>
                    <div class="fw-bold">{{ $opening->closing_date?->format('d M Y') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs" id="openingTabs">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabApplicants">
                        <i class="fas fa-users me-1"></i> Applicants
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDetails">
                        <i class="fas fa-info-circle me-1"></i> Details
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">

                {{-- APPLICANTS --}}
                <div class="tab-pane fade show active" id="tabApplicants">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm w-100" id="tblApplicants">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Source</th>
                                    <th>Stage</th>
                                    <th>Rating</th>
                                    <th>Interviews</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- DETAILS --}}
                <div class="tab-pane fade" id="tabDetails">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tr><th width="140">Title</th><td>{{ $opening->title }}</td></tr>
                                <tr><th>Department</th><td>{{ $opening->department?->name ?? '—' }}</td></tr>
                                <tr><th>Position</th><td>{{ $opening->jobPosition?->title ?? '—' }}</td></tr>
                                <tr><th>Vacancies</th><td>{{ $opening->vacancies }}</td></tr>
                                <tr><th>Status</th><td>{{ ucfirst($opening->status) }}</td></tr>
                                <tr><th>Posted</th><td>{{ $opening->posted_date?->format('d M Y') ?? '—' }}</td></tr>
                                <tr><th>Closes</th><td>{{ $opening->closing_date?->format('d M Y') ?? '—' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <p class="fw-semibold mb-1">Description</p>
                            <p class="text-muted small">{{ $opening->description ?? '—' }}</p>
                            <p class="fw-semibold mb-1 mt-3">Requirements</p>
                            <p class="text-muted small">{{ $opening->requirements ?? '—' }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ADD APPLICANT MODAL --}}
<div class="modal fade" id="modalApplicant" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="applicantModalTitle">Add Applicant</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmApplicant" novalidate>
                    @csrf
                    <input type="hidden" id="applicantId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="a_first" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" class="form-control" id="a_last" name="last_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="a_email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" class="form-control" id="a_phone" name="phone">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Source <span class="text-danger">*</span></label>
                            <select class="form-select" id="a_source" name="source" required>
                                <option value="direct">Direct</option>
                                <option value="referral">Referral</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="job_board">Job Board</option>
                                <option value="agency">Agency</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Stage</label>
                            <select class="form-select" id="a_stage" name="stage">
                                <option value="applied">Applied</option>
                                <option value="screening">Screening</option>
                                <option value="interview">Interview</option>
                                <option value="offer">Offer</option>
                                <option value="hired">Hired</option>
                                <option value="rejected">Rejected</option>
                                <option value="withdrawn">Withdrawn</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Rating (1–5)</label>
                            <input type="number" class="form-control" id="a_rating" name="rating" min="1" max="5">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="a_notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSaveApplicant">
                    <i class="fas fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

{{-- SCHEDULE INTERVIEW MODAL --}}
<div class="modal fade" id="modalInterview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Schedule Interview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmInterview" novalidate>
                    @csrf
                    <input type="hidden" id="interviewApplicantId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Interviewer <span class="text-danger">*</span></label>
                            <select class="form-select" id="i_interviewer" name="interviewer_id" required style="width:100%;"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="i_scheduled" name="scheduled_at" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Type</label>
                            <select class="form-select" id="i_type" name="type">
                                <option value="in_person">In Person</option>
                                <option value="phone">Phone</option>
                                <option value="video">Video</option>
                                <option value="panel">Panel</option>
                                <option value="technical">Technical</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Feedback / Notes</label>
                            <textarea class="form-control" id="i_feedback" name="feedback" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-info text-white" id="btnSaveInterview">
                    <i class="fas fa-calendar-check me-1"></i> Schedule
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const CSRF      = $('meta[name="csrf-token"]').attr('content');
    const OPENING   = {{ $opening->id }};
    const URLS = {
        applicantsDT   : '{{ route('admin.hrm.recruitment.openings.applicants.datatable', $opening) }}',
        storeApplicant : '{{ route('admin.hrm.recruitment.openings.applicants.store', $opening) }}',
        updateApplicant: (id) => `/admin/hrm/recruitment/applicants/${id}`,
        deleteApplicant: (id) => `/admin/hrm/recruitment/applicants/${id}`,
        storeInterview : (applicantId) => `/admin/hrm/recruitment/applicants/${applicantId}/interviews`,
        userSelect2    : '/admin/users/select2',
    };

    const $applicantModal = new bootstrap.Modal(document.getElementById('modalApplicant'));
    const $interviewModal = new bootstrap.Modal(document.getElementById('modalInterview'));

    // Interviewer Select2
    $('#i_interviewer').select2({
        theme: 'bootstrap-5', placeholder: '-- Select Interviewer --',
        allowClear: true, width: '100%', dropdownParent: $('#modalInterview'),
        ajax: {
            url: URLS.userSelect2, dataType: 'json', delay: 250,
            data: p => ({ q: p.term }),
            processResults: d => ({ results: Array.isArray(d) ? d : (d.results || []) }),
        },
    });

    // Applicants DataTable
    const applicantsDT = $('#tblApplicants').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: URLS.applicantsDT, dataSrc: 'data' },
        columns: [
            { data: 'full_name' },
            { data: 'email', defaultContent: '—' },
            { data: 'source', render: v => v.replace('_',' ') },
            { data: 'stage_badge',   orderable: false },
            { data: 'rating_stars',  orderable: false, defaultContent: '—' },
            { data: 'interview_count' },
            { data: 'actions',       orderable: false, searchable: false },
        ],
    });

    // Add applicant
    $('#btnAddApplicant').on('click', function () {
        $('#frmApplicant')[0].reset();
        $('#applicantId').val('');
        $('#a_stage').val('applied');
        $('#applicantModalTitle').text('Add Applicant');
        $applicantModal.show();
    });

    // Edit applicant
    $('#tblApplicants').on('click', '.btn-edit-applicant', function () {
        const r = $(this).data('record');
        $('#applicantId').val(r.id);
        $('#a_first').val(r.first_name);
        $('#a_last').val(r.last_name);
        $('#a_email').val(r.email);
        $('#a_phone').val(r.phone);
        $('#a_source').val(r.source);
        $('#a_stage').val(r.stage);
        $('#a_rating').val(r.rating);
        $('#a_notes').val(r.notes);
        $('#applicantModalTitle').text('Edit Applicant');
        $applicantModal.show();
    });

    $('#btnSaveApplicant').on('click', function () {
        const id   = $('#applicantId').val();
        const url  = id ? URLS.updateApplicant(id) : URLS.storeApplicant;
        const data = $('#frmApplicant').serialize() + (id ? '&_method=PUT' : '');
        $.post(url, data)
            .done(() => { $applicantModal.hide(); applicantsDT.ajax.reload();
                Swal.fire({ icon:'success', title:'Saved', timer:1400, showConfirmButton:false }); })
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error.');
                Swal.fire('Error', msg, 'error');
            });
    });

    // Schedule interview
    $('#tblApplicants').on('click', '.btn-schedule-interview', function () {
        $('#interviewApplicantId').val($(this).data('applicant-id'));
        $('#frmInterview')[0].reset();
        $('#i_interviewer').val(null).trigger('change');
        $interviewModal.show();
    });

    $('#btnSaveInterview').on('click', function () {
        const applicantId = $('#interviewApplicantId').val();
        $.post(URLS.storeInterview(applicantId), $('#frmInterview').serialize())
            .done(() => { $interviewModal.hide(); applicantsDT.ajax.reload();
                Swal.fire({ icon:'success', title:'Interview Scheduled', timer:1400, showConfirmButton:false }); })
            .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Error.', 'error'));
    });

    // Delete applicant
    $('#tblApplicants').on('click', '.btn-delete-applicant', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Remove applicant?', icon:'warning',
            showCancelButton:true, confirmButtonColor:'#e74a3b' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.deleteApplicant(id), { _token:CSRF, _method:'DELETE' })
                    .done(() => applicantsDT.ajax.reload());
            });
    });
})();
</script>
@endpush