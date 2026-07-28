@extends('layouts.master')
@section('title', 'Rosters')

@push('styles')
<style>
/* Fix: filter Select2 dropdown z-index must be below modal backdrop (1040) */
.select2-filter-dropdown { z-index: 999 !important; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0"><i class="fas fa-calendar-alt me-2"></i>Rosters</h1>
            <small class="text-muted">HRM / Rosters</small>
        </div>
        @can('hrm.rosters.manage')
        <button class="btn btn-primary btn-sm" id="btnCreate">
            <i class="fas fa-plus me-1"></i> Assign Roster
        </button>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="card shadow mb-3" id="filterRow">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-sm-3">
                    <label class="form-label small mb-1">Employee</label>
                    <select id="fEmployee" class="form-select form-select-sm" style="width:100%;">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Shift</label>
                    <select id="fShift" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($shifts as $shift)
                            @php
                                $start = $shift->start_time ? substr($shift->start_time, 0, 5) : '';
                                $end   = $shift->end_time   ? substr($shift->end_time,   0, 5) : '';
                                $times = $start && $end ? " ({$start}–{$end})" : ($start ? " ({$start})" : '');
                            @endphp
                            <option value="{{ $shift->id }}">{{ $shift->name }}{{ $times }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Date From</label>
                    <input type="date" id="fDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Date To</label>
                    <input type="date" id="fDateTo" class="form-control form-control-sm">
                </div>
                <div class="col-sm-3 d-flex gap-2">
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
                <table class="table table-bordered table-sm table-hover w-100" id="tblRosters">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Hours</th>
                            <th>Note</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="modalRoster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Assign Roster</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmRoster" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                            <select class="form-select" id="r_employee" name="employee_id" required style="width:100%;">
                                <option value="">-- Select Employee --</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Shift <span class="text-danger">*</span></label>
                            <select class="form-select" id="r_shift" name="shift_id" required>
                                <option value="">-- Select Shift --</option>
                                @foreach($shifts as $shift)
                                    @php
                                        $start = $shift->start_time ? substr($shift->start_time, 0, 5) : '';
                                        $end   = $shift->end_time   ? substr($shift->end_time,   0, 5) : '';
                                        $times = $start && $end ? " ({$start}–{$end})" : ($start ? " ({$start})" : '');
                                    @endphp
                                    <option value="{{ $shift->id }}">{{ $shift->name }}{{ $times }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="r_date" name="roster_date" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Note</label>
                            <textarea class="form-control" id="r_note" name="note" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSaveRoster">
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
        datatable  : '{{ route('admin.hrm.rosters.datatable') }}',
        store      : '{{ route('admin.hrm.rosters.store') }}',
        destroy    : (id) => `/admin/hrm/rosters/${id}`,
        empSelect2 : '{{ route('admin.hrm.employees.select2') }}',
    };

    // ── Select2 config shared between filter and modal ────────────────────────
    const empAjax = {
        url: URLS.empSelect2, dataType: 'json', delay: 250,
        data: p => ({ q: p.term, only_active: 1 }),
        processResults: d => ({ results: d.results || [] }),
    };

    // Filter Select2 — rendered in body, will be hidden when modal opens
    $('#fEmployee').select2({
        theme: 'bootstrap-5', placeholder: 'All',
        allowClear: true, width: '100%',
        ajax: empAjax,
    });

    // Modal Select2 — dropdownParent keeps it inside modal stacking context
    $('#r_employee').select2({
        theme: 'bootstrap-5', placeholder: '-- Select Employee --',
        allowClear: true, width: '100%',
        dropdownParent: $('#modalRoster'),
        ajax: empAjax,
    });

    const $modalEl  = document.getElementById('modalRoster');
    const $modal    = new bootstrap.Modal($modalEl);
    const $filterRow = $('#filterRow');   // the filter card — hidden when modal open

    // Hide filter card when modal opens to eliminate z-index conflict entirely
    $modalEl.addEventListener('show.bs.modal',   () => {
        $('#fEmployee').select2('close');
        $filterRow.hide();
    });
    $modalEl.addEventListener('hidden.bs.modal', () => {
        $filterRow.show();
    });

    // ── DataTable ─────────────────────────────────────────────────────────────
    function buildTable(extra = {}) {
        if ($.fn.DataTable.isDataTable('#tblRosters')) {
            $('#tblRosters').DataTable().destroy();
        }
        $('#tblRosters').DataTable({
            processing: true, serverSide: true, responsive: true,
            ajax: { url: URLS.datatable, data: extra, dataSrc: 'data' },
            columns: [
                { data: 'employee_name' },
                { data: 'roster_date_fmt' },
                { data: 'shift_name' },
                { data: 'shift_hours' },
                { data: 'note', defaultContent: '—' },
                { data: 'actions', orderable: false, searchable: false },
            ],
            order: [[1, 'desc']],
        });
    }

    buildTable();

    $('#btnFilter').on('click', () => buildTable({
        employee_id: $('#fEmployee').val() || undefined,
        shift_id:    $('#fShift').val()    || undefined,
        date_from:   $('#fDateFrom').val() || undefined,
        date_to:     $('#fDateTo').val()   || undefined,
    }));

    $('#btnReset').on('click', () => {
        $('#fEmployee').val(null).trigger('change');
        $('#fShift').val('');
        $('#fDateFrom, #fDateTo').val('');
        buildTable();
    });

    // ── Roster modal ──────────────────────────────────────────────────────────
    $('#btnCreate').on('click', function () {
        $('#frmRoster')[0].reset();
        $('#r_employee').val(null).trigger('change');
        $('#r_date').val(new Date().toISOString().substring(0, 10));
        $modal.show();
    });

    $('#btnSaveRoster').on('click', function () {
        $.post(URLS.store, $('#frmRoster').serialize())
            .done(() => {
                $modal.hide();
                buildTable();
                Swal.fire({ icon: 'success', title: 'Saved', timer: 1400, showConfirmButton: false });
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error saving.');
                Swal.fire('Error', msg, 'error');
            });
    });

    $('#tblRosters').on('click', '.btn-delete-roster', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Remove roster?', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#e74a3b' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.destroy(id), { _token: CSRF, _method: 'DELETE' })
                    .done(() => buildTable());
            });
    });

})();
</script>
@endpush