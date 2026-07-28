@extends('layouts.master')

@section('title', 'Audit Logs')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
<style>
  td { vertical-align: top; }
  .small-muted { font-size: 12px; color: #6c757d; }
  pre.jsonbox {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    padding: 12px;
    border-radius: 8px;
    max-height: 280px;
    overflow: auto;
    margin-bottom: 0;
  }
</style>
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="mb-0">Audit Logs</h5>
      <small class="text-muted">Read-only system trail. Purge is retention-based only.</small>
    </div>

    <div class="d-flex gap-2">
      @can('audit.view_analytics')
        <a href="{{ route('admin.audit.analytics') }}" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-chart-line me-1"></i> Analytics
        </a>
      @endcan

      @can('audit.export')
        <button type="button" id="exportBtn" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-file-export me-1"></i> Export CSV
        </button>
      @endcan

      @can('audit.purge')
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#purgeModal">
          <i class="fas fa-trash-alt me-1"></i> Purge (Retention)
        </button>
      @endcan
    </div>
  </div>

  <div class="card-body">

    {{-- Filters --}}
    <div class="row g-2 mb-3">
      <div class="col-md-2">
        <label class="form-label small mb-1">From</label>
        <input type="date" id="f_from" class="form-control form-control-sm">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">To</label>
        <input type="date" id="f_to" class="form-control form-control-sm">
      </div>

      <div class="col-md-2">
        <label class="form-label small mb-1">Module</label>
        <select id="f_module" class="form-select form-select-sm">
          <option value="">All</option>
          @foreach($modules as $m)
            <option value="{{ $m }}">{{ $m }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small mb-1">Action</label>
        <select id="f_action" class="form-select form-select-sm">
          <option value="">All</option>
          @foreach($actions as $a)
            <option value="{{ $a }}">{{ $a }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small mb-1">User (name/email)</label>
        <input type="text" id="f_user" class="form-control form-control-sm" placeholder="e.g. John">
      </div>

      <div class="col-md-2">
        <label class="form-label small mb-1">Keyword</label>
        <input type="text" id="f_q" class="form-control form-control-sm" placeholder="desc/route/ip/url">
      </div>

      <div class="col-12 d-flex gap-2 mt-1">
        <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
          <i class="fas fa-filter me-1"></i> Apply
        </button>
        <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
          Reset
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table id="auditTable" class="table table-striped table-bordered">
        <thead>
          <tr>
            <th style="width:160px;">Date/Time</th>
            <th style="width:220px;">User</th>
            <th style="width:120px;">Module</th>
            <th style="width:140px;">Action</th>
            <th>Description</th>
            <th style="width:160px;">Subject</th>
            <th style="width:120px;">IP</th>
            <th style="width:100px;">View</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

  </div>
</div>

{{-- View Details Modal --}}
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Audit Log Details</h5>
          <small class="text-muted" id="logDetailsSub">—</small>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-lg-4">
            <div class="border rounded p-3">
              <div class="fw-semibold mb-2">Summary</div>
              <div class="small-muted">Date</div>
              <div id="d_dt" class="mb-2">—</div>

              <div class="small-muted">User</div>
              <div id="d_user" class="mb-2">—</div>

              <div class="small-muted">Module / Action</div>
              <div id="d_ma" class="mb-2">—</div>

              <div class="small-muted">Subject</div>
              <div id="d_subject" class="mb-2">—</div>

              <div class="small-muted">IP / Method</div>
              <div id="d_ipm" class="mb-2">—</div>

              <div class="small-muted">Route</div>
              <div id="d_route" class="mb-2">—</div>

              <div class="small-muted">URL</div>
              <div id="d_url" class="text-break">—</div>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="border rounded p-3">
              <div class="fw-semibold mb-2">Description</div>
              <div id="d_desc" class="mb-3">—</div>

              <div class="fw-semibold mb-2">Properties (JSON)</div>
              <pre class="jsonbox"><code id="d_props">{}</code></pre>

              <div class="fw-semibold mt-3 mb-2">User Agent</div>
              <div id="d_ua" class="small text-muted text-break">—</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Purge Modal --}}
@can('audit.purge')
<div class="modal fade" id="purgeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="purgeForm">
      @csrf
      @method('DELETE')
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0">Retention Purge</h5>
            <small class="text-muted">Deletes logs older than X days. This action is logged.</small>
          </div>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="alert alert-warning">
            Best practice: Audit logs are append-only. Purge is retention-based only.
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Delete logs older than (days)</label>
              <input type="number" class="form-control" name="days" value="365" min="30" max="3650" required>
              <small class="text-muted">Min 30, max 3650.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Type <span class="fw-bold">PURGE</span> to confirm</label>
              <input type="text" class="form-control" name="confirm" placeholder="PURGE" required>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-danger" type="submit">
            <i class="fas fa-trash-alt me-1"></i> Purge
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
@endcan

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(function(){

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

  function showModal(id){
    const el = document.getElementById(id);
    (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).show();
  }
  function hideModal(id){
    const el = document.getElementById(id);
    const m = bootstrap.Modal.getInstance(el);
    if (m) m.hide();
  }

  function currentFilters(){
    return {
      from: $('#f_from').val(),
      to: $('#f_to').val(),
      module: $('#f_module').val(),
      action: $('#f_action').val(),
      user: $('#f_user').val(),
      q: $('#f_q').val(),
    };
  }

  const table = $('#auditTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: '{{ route("admin.audit.list") }}',
      data: function(d){
        Object.assign(d, currentFilters());
      }
    },
    pageLength: 25,
    order: [[0,'desc']],
    columns: [
      { data: 'dt', name: 'created_at' },
      { data: 'user', orderable: false, searchable: false },
      { data: 'module', name: 'module' },
      { data: 'action', name: 'action' },
      { data: 'desc', orderable: false, searchable: false },
      { data: 'subject', orderable: false, searchable: false },
      { data: 'ip', name: 'ip', defaultContent: '' },
      { data: 'actions', orderable: false, searchable: false }
    ]
  });

  $('#applyFilters').on('click', function(){
    table.ajax.reload();
  });

  $('#resetFilters').on('click', function(){
    $('#f_from,#f_to,#f_module,#f_action,#f_user,#f_q').val('');
    table.ajax.reload();
  });

  // Export CSV (keep current filters)
  $('#exportBtn').on('click', function(){
    const q = $.param(currentFilters());
    window.location.href = '{{ route("admin.audit.export") }}' + (q ? ('?' + q) : '');
  });

  // View details
  $(document).on('click', '.view-log', function(){
    const id = $(this).data('id');
    $.get(`/admin/audit/${id}`, function(res){
      $('#logDetailsSub').text(`#${res.id} • ${res.module}.${res.action}`);

      $('#d_dt').text(res.created_at || '—');
      $('#d_user').html(res.user ? `<div class="fw-semibold">${res.user.name}</div><div class="small-muted">${res.user.email}</div>` : '<span class="badge bg-secondary">System</span>');
      $('#d_ma').html(`<span class="badge bg-light text-dark">${res.module}</span> <span class="badge bg-light text-dark">${res.action}</span>`);
      $('#d_subject').html(res.subject?.type ? `<span class="badge bg-light text-dark">${(res.subject.type || '').split('\\\\').pop()} #${res.subject.id}</span>` : '<span class="text-muted">—</span>');
      $('#d_ipm').text(`${res.ip || '—'} / ${res.method || '—'}`);
      $('#d_route').text(res.route || '—');
      $('#d_url').text(res.url || '—');
      $('#d_desc').text(res.description || '—');
      $('#d_ua').text(res.user_agent || '—');

      const pretty = JSON.stringify(res.properties || {}, null, 2);
      $('#d_props').text(pretty);

      showModal('logDetailsModal');
    }).fail(function(){
      Swal.fire('Error','Failed to load log details.','error');
    });
  });

  // Purge
  $(document).on('submit', '#purgeForm', function(e){
    e.preventDefault();
    const form = $(this);

    Swal.fire({
      title: 'Confirm purge?',
      text: 'This will delete logs older than the selected days.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, purge'
    }).then(({isConfirmed}) => {
      if (!isConfirmed) return;

      $.ajax({
        url: '{{ route("admin.audit.purge") }}',
        type: 'DELETE',
        data: form.serialize(),
      }).done(res => {
        hideModal('purgeModal');
        Swal.fire('Done', res.message || 'Purge completed.', 'success');
        table.ajax.reload();
        form[0].reset();
      }).fail(xhr => {
        Swal.fire('Error', xhr.responseJSON?.message || 'Purge failed.', 'error');
      });
    });
  });

});
</script>
@endpush
