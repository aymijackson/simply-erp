@extends('layouts.master')

@section('content')
<div class="container-fluid">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">
        Reconciliation #{{ $recon->id }} — {{ $recon->bankAccount->name ?? 'Bank' }}
      </h4>
      <small class="text-muted">
        {{ $recon->period_start->format('Y-m-d') }} → {{ $recon->period_end->format('Y-m-d') }}
        | Status: {{ ucfirst($recon->status) }}
      </small>
    </div>

    <div class="d-flex gap-2">
      <a href="{{route('admin.finance.bank_reconciliations.index')}}" class="btn btn-primary">
        Back
      </a>
      @can('finance.bank_reconciliation.import')
      <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
        Import CSV
      </button>
      @endcan

      @can('finance.bank_reconciliation.close')
      <button class="btn btn-success" id="btnClose" {{ $recon->status === 'closed' ? 'disabled' : '' }}>
        Close
      </button>
      @endcan

      @can('finance.bank_reconciliation.undo_close')
      <button class="btn btn-warning" id="btnUndoClose" {{ $recon->status !== 'closed' ? 'disabled' : '' }}>
        Undo Close
      </button>
      @endcan
    </div>
  </div>

  {{-- Summary Cards --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Statement Closing</div>
          <div class="h5 mb-0">{{ number_format($recon->statement_closing_balance, 2) }}</div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">System Closing</div>
          <div class="h5 mb-0">{{ number_format($systemClosing, 2) }}</div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Difference</div>
          <div class="h5 mb-0" id="diffText">{{ number_format($difference, 2) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">

    {{-- Statement Lines --}}
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Statement Lines</span>
          <button class="btn btn-sm btn-outline-primary" id="reloadLines">Reload</button>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0" id="linesTable">
              <thead class="table-light">
                <tr>
                  <th style="width: 110px;">Date</th>
                  <th>Description</th>
                  <th class="text-end" style="width: 120px;">Amount</th>
                  <th class="text-center" style="width: 120px;">Status</th>
                  <th class="text-center" style="width: 240px;">Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div class="p-2">
            <small class="text-muted">
              Tip: click “Suggestions” to auto-find matches, or use “Post Adjustment” for fees/interest.
            </small>
          </div>
        </div>
      </div>
    </div>

    {{-- Suggestions + Adjustment --}}
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header">Suggestions / Match</div>

        <div class="card-body">

          <div class="mb-2">
            <b>Selected line:</b>
            <span id="selLineText" class="text-muted">None</span>
          </div>

          <div id="suggestionsWrap" class="list-group mb-3"></div>

          <div class="border-top pt-3">
            <h6>Post Adjustment (and auto-match)</h6>

            <div class="row g-2">
              <div class="col-6">
                <label class="form-label">Type</label>
                <select class="form-select" id="adjType">
                  <option value="bank_fee">Bank Fee</option>
                  <option value="interest">Interest</option>
                  <option value="suspense">Suspense/Uncategorised</option>
                </select>
              </div>

              <div class="col-6">
                <label class="form-label">Entry Date</label>
                <input type="date" class="form-control" id="adjDate"
                       value="{{ $recon->period_end->format('Y-m-d') }}">
              </div>

              <div class="col-12">
                <label class="form-label">Offset Account ID</label>
                <input type="number" class="form-control" id="adjOffsetAccount"
                       placeholder="e.g. Bank Charges Expense account_id">
              </div>

              <div class="col-12">
                <label class="form-label">Amount (positive)</label>
                <input type="number" step="0.01" class="form-control" id="adjAmount" value="0.00">
              </div>

              <div class="col-12">
                <label class="form-label">Memo</label>
                <input type="text" class="form-control" id="adjMemo" placeholder="optional">
              </div>

              <div class="col-12">
                <button class="btn btn-outline-primary w-100" id="btnAdjustment" disabled>
                  Post Adjustment
                </button>
              </div>
            </div>

            <small class="text-muted d-block mt-2">
              Offset account is required (e.g. bank charges expense / interest income / suspense).
            </small>
          </div>

        </div>
      </div>
    </div>

  </div>

  {{-- Import Modal --}}
  <div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title">Import Bank Statement CSV</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <form id="importForm">
            <div class="mb-2">
              <input type="file" name="file" class="form-control" accept=".csv,text/csv">
            </div>
            <small class="text-muted">
              Headers: date, description, amount, reference(optional), fit_id(optional)
            </small>
          </form>
        </div>

        <div class="modal-footer">
          <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" id="btnImport">Import</button>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const CAN_UNDO_EXCLUDE = @json(auth()->user()->can('finance.bank_reconciliation.undo_exclude'));

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2000,
  timerProgressBar: true,
});

function formatDate(d) {
    if (!d) return '';
    const date = new Date(d);
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}


function setLoading(btn, isLoading, label) {
  if (!btn) return;
  if (isLoading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `
      <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${label}
    `;
  } else {
    btn.disabled = false;
    if (btn.dataset.originalText) {
      btn.innerHTML = btn.dataset.originalText;
    }
  }
}

async function api(url, options = {}) {
  const baseHeaders = {
    'X-Requested-With': 'XMLHttpRequest',
  };
  if (options.json) {
    baseHeaders['Content-Type'] = 'application/json';
  }
  const res = await fetch(url, {
    method: options.method || 'GET',
    headers: { ...baseHeaders, ...(options.headers || {}) },
    body: options.body || null,
  });
  let json;
  try {
    json = await res.json();
  } catch (e) {
    throw new Error('Invalid server response');
  }
  if (!res.ok || json.ok === false) {
    throw new Error(json.message || 'Request failed');
  }
  return json;
}

let selectedLineId = null;

function money(n) {
  return Number(n).toFixed(2);
}

function buildStatusBadge(status) {
  if (status === 'matched') {
    return '<span class="badge bg-success">Matched</span>';
  }
  if (status === 'excluded') {
    return '<span class="badge bg-secondary">Excluded</span>';
  }
  return '<span class="badge bg-secondary-subtle text-muted border">Unmatched</span>';
}

function buildActionsHtml(line) {
  const id = line.id;
  if (line.status === 'matched') {
    return `
      <button class="btn btn-sm btn-outline-warning me-1 btn-unmatch" data-id="${id}">
        Unmatch
      </button>
    `;
  }
  if (line.status === 'excluded') {
    if (!CAN_UNDO_EXCLUDE) {
      return '';
    }
    return `
      <button class="btn btn-sm btn-outline-warning me-1 btn-undo-exclude" data-id="${id}">
        Undo Exclude
      </button>
    `;
  }
  return `
    <button class="btn btn-sm btn-outline-secondary me-1 btn-select" data-id="${id}">
      Select
    </button>
    <button class="btn btn-sm btn-outline-primary me-1 btn-suggestions" data-id="${id}">
      Suggestions
    </button>
    <button class="btn btn-sm btn-outline-danger btn-exclude" data-id="${id}">
      Exclude
    </button>
  `;
}

function applyRowState(tr, line) {
  tr.classList.remove('table-success', 'table-secondary', 'table-primary');
  if (line.status === 'matched') {
    tr.classList.add('table-success');
  } else if (line.status === 'excluded') {
    tr.classList.add('table-secondary');
  }
}

async function loadLines() {
  const url = @json(route('admin.finance.bank_reconciliations.statement_lines_dt', $recon->id)) + '?per_page=200';
  const tbody = document.querySelector('#linesTable tbody');
  tbody.innerHTML = `
    <tr>
      <td colspan="5" class="text-center text-muted">
        <span class="spinner-border spinner-border-sm me-1"></span>Loading statement lines...
      </td>
    </tr>
  `;
  try {
    const json = await api(url);
    tbody.innerHTML = '';
    (json.data || []).forEach(l => {
      const tr = document.createElement('tr');
      tr.dataset.id = l.id;
      tr.innerHTML = `
        <td>${formatDate(l.txn_date)}</td>
        <td>${(l.description || '').slice(0, 60)}</td>
        <td class="text-end">${money(l.amount)}</td>
        <td class="status-cell text-center">${buildStatusBadge(l.status)}</td>
        <td class="actions-cell text-center">${buildActionsHtml(l)}</td>
      `;
      applyRowState(tr, l);
      tbody.appendChild(tr);
    });
  } catch (e) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-danger">
          Failed to load lines: ${e.message}
        </td>
      </tr>
    `;
    Swal.fire('Error', e.message || 'Failed to load lines', 'error');
  }
}

function setSelectedLine(id) {
  selectedLineId = id;
  const rows = document.querySelectorAll('#linesTable tbody tr');
  rows.forEach(r => r.classList.remove('table-primary'));
  const row = document.querySelector(`#linesTable tbody tr[data-id="${id}"]`);
  if (row) {
    row.classList.add('table-primary');
  }
  document.getElementById('selLineText').textContent = 'Statement line #' + id;
  document.getElementById('btnAdjustment').disabled = false;
  document.getElementById('suggestionsWrap').innerHTML = '';
}

async function loadSuggestionsForLine(id) {
  selectedLineId = id;
  setSelectedLine(id);
  const wrap = document.getElementById('suggestionsWrap');
  wrap.innerHTML = `
    <div class="text-muted">
      <span class="spinner-border spinner-border-sm me-1"></span>Loading suggestions...
    </div>
  `;
  const url = @json(route('admin.finance.bank_reconciliations.suggestions', $recon->id)) + '?statement_line_id=' + id;
  try {
    const json = await api(url);
    wrap.innerHTML = '';
    (json.suggestions || []).forEach(s => {
      wrap.insertAdjacentHTML('beforeend', `
        <a href="javascript:void(0)" class="list-group-item list-group-item-action suggestion-item"
           data-line-id="${id}" data-jel-id="${s.journal_entry_line_id}">
          <div class="d-flex justify-content-between">
            <div><b>${s.entry_no || ''}</b> <span class="text-muted">${s.entry_date}</span></div>
            <div>${money(s.net)}</div>
          </div>
          <div class="small text-muted">${(s.description || s.memo || '').slice(0, 80)}</div>
        </a>
      `);
    });
    if ((json.suggestions || []).length === 0) {
      wrap.innerHTML = `<div class="text-muted">No suggestions found.</div>`;
    }
  } catch (e) {
    wrap.innerHTML = `<div class="text-danger">Failed to load suggestions: ${e.message}</div>`;
    Swal.fire('Error', e.message || 'Failed to load suggestions', 'error');
  }
}

async function matchLine(statementLineId, journalEntryLineId, triggerBtn) {
  const url = @json(route('admin.finance.bank_statement_lines.match', 0)).replace('/0/', '/' + statementLineId + '/');
  setLoading(triggerBtn, true, 'Matching...');
  try {
    const json = await api(url, {
      method: 'POST',
      json: true,
      body: JSON.stringify({ journal_entry_line_id: journalEntryLineId }),
    });
    const row = document.querySelector(`#linesTable tbody tr[data-id="${statementLineId}"]`);
    if (row) {
      row.classList.remove('table-secondary', 'table-primary');
      row.classList.add('table-success');
      const statusCell = row.querySelector('.status-cell');
      const actionsCell = row.querySelector('.actions-cell');
      if (statusCell) statusCell.innerHTML = '<span class="badge bg-success">Matched</span>';
      if (actionsCell) {
        actionsCell.innerHTML = `
          <button class="btn btn-sm btn-outline-warning me-1 btn-unmatch" data-id="${statementLineId}">
            Unmatch
          </button>
        `;
      }
    }
    document.getElementById('suggestionsWrap').innerHTML = `<div class="text-success">${json.message}</div>`;
    Toast.fire({ icon: 'success', title: json.message || 'Matched' });
  } catch (e) {
    Swal.fire('Error', e.message || 'Match failed', 'error');
  } finally {
    setLoading(triggerBtn, false);
  }
}

async function unmatchLine(statementLineId, triggerBtn) {
  const url = @json(route('admin.finance.bank_statement_lines.unmatch', 0)).replace('/0/', '/' + statementLineId + '/');
  setLoading(triggerBtn, true, 'Unmatching...');
  try {
    const json = await api(url, { method: 'POST' });
    const row = document.querySelector(`#linesTable tbody tr[data-id="${statementLineId}"]`);
    if (row) {
      row.classList.remove('table-success', 'table-secondary');
      const statusCell = row.querySelector('.status-cell');
      const actionsCell = row.querySelector('.actions-cell');
      if (statusCell) {
        statusCell.innerHTML = '<span class="badge bg-secondary-subtle text-muted border">Unmatched</span>';
      }
      if (actionsCell) {
        actionsCell.innerHTML = `
          <button class="btn btn-sm btn-outline-secondary me-1 btn-select" data-id="${statementLineId}">
            Select
          </button>
          <button class="btn btn-sm btn-outline-primary me-1 btn-suggestions" data-id="${statementLineId}">
            Suggestions
          </button>
          <button class="btn btn-sm btn-outline-danger btn-exclude" data-id="${statementLineId}">
            Exclude
          </button>
        `;
      }
    }
    Toast.fire({ icon: 'success', title: json.message || 'Unmatched' });
  } catch (e) {
    Swal.fire('Error', e.message || 'Unmatch failed', 'error');
  } finally {
    setLoading(triggerBtn, false);
  }
}

async function excludeLine(statementLineId, triggerBtn) {
  const { value: reason } = await Swal.fire({
    title: 'Exclude line?',
    text: 'You can optionally enter a reason for exclusion.',
    input: 'text',
    inputPlaceholder: 'Reason (optional)',
    showCancelButton: true,
    confirmButtonText: 'Exclude',
    icon: 'warning',
  });
  if (reason === undefined) return;
  const url = @json(route('admin.finance.bank_statement_lines.exclude', 0)).replace('/0/', '/' + statementLineId + '/');
  setLoading(triggerBtn, true, 'Excluding...');
  try {
    const json = await api(url, {
      method: 'POST',
      json: true,
      body: JSON.stringify({ reason: reason || '' }),
    });
    const row = document.querySelector(`#linesTable tbody tr[data-id="${statementLineId}"]`);
    if (row) {
      row.classList.remove('table-success', 'table-primary');
      row.classList.add('table-secondary');
      const statusCell = row.querySelector('.status-cell');
      const actionsCell = row.querySelector('.actions-cell');
      if (statusCell) statusCell.innerHTML = '<span class="badge bg-secondary">Excluded</span>';
      if (actionsCell) {
        if (CAN_UNDO_EXCLUDE) {
          actionsCell.innerHTML = `
            <button class="btn btn-sm btn-outline-warning me-1 btn-undo-exclude" data-id="${statementLineId}">
              Undo Exclude
            </button>
          `;
        } else {
          actionsCell.innerHTML = '';
        }
      }
    }
    Toast.fire({ icon: 'success', title: json.message || 'Excluded' });
  } catch (e) {
    Swal.fire('Error', e.message || 'Exclude failed', 'error');
  } finally {
    setLoading(triggerBtn, false);
  }
}

async function undoExcludeLine(statementLineId, triggerBtn) {
  const url = @json(route('admin.finance.bank_statement_lines.undo_exclude', 0)).replace('/0/', '/' + statementLineId + '/');
  setLoading(triggerBtn, true, 'Restoring...');
  try {
    const json = await api(url, { method: 'POST' });
    const row = document.querySelector(`#linesTable tbody tr[data-id="${statementLineId}"]`);
    if (row) {
      row.classList.remove('table-secondary', 'table-success');
      const statusCell = row.querySelector('.status-cell');
      const actionsCell = row.querySelector('.actions-cell');
      if (statusCell) {
        statusCell.innerHTML = '<span class="badge bg-secondary-subtle text-muted border">Unmatched</span>';
      }
      if (actionsCell) {
        actionsCell.innerHTML = `
          <button class="btn btn-sm btn-outline-secondary me-1 btn-select" data-id="${statementLineId}">
            Select
          </button>
          <button class="btn btn-sm btn-outline-primary me-1 btn-suggestions" data-id="${statementLineId}">
            Suggestions
          </button>
          <button class="btn btn-sm btn-outline-danger btn-exclude" data-id="${statementLineId}">
            Exclude
          </button>
        `;
      }
    }
    Toast.fire({ icon: 'success', title: json.message || 'Exclusion undone' });
  } catch (e) {
    Swal.fire('Error', e.message || 'Undo exclude failed', 'error');
  } finally {
    setLoading(triggerBtn, false);
  }
}

document.addEventListener('click', async function (e) {
  const target = e.target.closest('button, a');
  if (!target) return;

  if (target.id === 'reloadLines') {
    setLoading(target, true, 'Reloading...');
    await loadLines().finally(() => setLoading(target, false));
    return;
  }

  if (target.classList.contains('btn-select')) {
    const id = Number(target.dataset.id);
    setSelectedLine(id);
    return;
  }

  if (target.classList.contains('btn-suggestions')) {
    const id = Number(target.dataset.id);
    await loadSuggestionsForLine(id);
    return;
  }

  if (target.classList.contains('suggestion-item')) {
    const lineId = Number(target.dataset.lineId);
    const jelId = Number(target.dataset.jelId);
    await matchLine(lineId, jelId, target);
    return;
  }

  if (target.classList.contains('btn-unmatch')) {
    const id = Number(target.dataset.id);
    const confirm = await Swal.fire({
      title: 'Unmatch line?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Unmatch',
    });
    if (!confirm.isConfirmed) return;
    await unmatchLine(id, target);
    return;
  }

  if (target.classList.contains('btn-exclude')) {
    const id = Number(target.dataset.id);
    await excludeLine(id, target);
    return;
  }

  if (target.classList.contains('btn-undo-exclude')) {
    const id = Number(target.dataset.id);
    const confirm = await Swal.fire({
      title: 'Undo exclusion?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Restore',
    });
    if (!confirm.isConfirmed) return;
    await undoExcludeLine(id, target);
    return;
  }

  if (target.id === 'btnAdjustment') {
    if (!selectedLineId) {
      Swal.fire('Select a line first', '', 'warning');
      return;
    }
    const offsetAccountId = Number(document.getElementById('adjOffsetAccount').value || 0);
    const amount = Number(document.getElementById('adjAmount').value || 0);
    if (!offsetAccountId || amount <= 0) {
      Swal.fire('Invalid adjustment', 'Offset account and positive amount are required.', 'warning');
      return;
    }
    const url = @json(route('admin.finance.bank_statement_lines.adjustment', 0)).replace('/0/', '/' + selectedLineId + '/');
    const payload = {
      type: document.getElementById('adjType').value,
      entry_date: document.getElementById('adjDate').value,
      offset_account_id: offsetAccountId,
      amount: amount,
      memo: document.getElementById('adjMemo').value,
    };
    setLoading(target, true, 'Posting...');
    try {
      const json = await api(url, {
        method: 'POST',
        json: true,
        body: JSON.stringify(payload),
      });
      const row = document.querySelector(`#linesTable tbody tr[data-id="${selectedLineId}"]`);
      if (row) {
        row.classList.remove('table-secondary', 'table-primary');
        row.classList.add('table-success');
        const statusCell = row.querySelector('.status-cell');
        const actionsCell = row.querySelector('.actions-cell');
        if (statusCell) statusCell.innerHTML = '<span class="badge bg-success">Matched</span>';
        if (actionsCell) {
          actionsCell.innerHTML = `
            <button class="btn btn-sm btn-outline-warning me-1 btn-unmatch" data-id="${selectedLineId}">
              Unmatch
            </button>
          `;
        }
      }
      document.getElementById('suggestionsWrap').innerHTML = `<div class="text-success">${json.message}</div>`;
      Toast.fire({ icon: 'success', title: json.message || 'Adjustment posted' });
    } catch (e) {
      Swal.fire('Error', e.message || 'Adjustment failed', 'error');
    } finally {
      setLoading(target, false);
    }
    return;
  }

  if (target.id === 'btnClose') {
    const confirm = await Swal.fire({
      title: 'Close reconciliation?',
      text: 'You will not be able to edit it unless you undo close.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Close',
    });
    if (!confirm.isConfirmed) return;
    const url = @json(route('admin.finance.bank_reconciliations.close', $recon->id));
    setLoading(target, true, 'Closing...');
    try {
      const json = await api(url, { method: 'POST' });
      Toast.fire({ icon: 'success', title: json.message || 'Reconciliation closed' });
      setTimeout(() => location.reload(), 800);
    } catch (e) {
      Swal.fire('Error', e.message || 'Close failed', 'error');
    } finally {
      setLoading(target, false);
    }
    return;
  }

  if (target.id === 'btnUndoClose') {
    const confirm = await Swal.fire({
      title: 'Undo close?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Reopen',
    });
    if (!confirm.isConfirmed) return;
    const url = @json(route('admin.finance.bank_reconciliations.undo_close', $recon->id));
    setLoading(target, true, 'Reopening...');
    try {
      const json = await api(url, { method: 'POST' });
      Toast.fire({ icon: 'success', title: json.message || 'Reconciliation reopened' });
      setTimeout(() => location.reload(), 800);
    } catch (e) {
      Swal.fire('Error', e.message || 'Undo close failed', 'error');
    } finally {
      setLoading(target, false);
    }
    return;
  }

  if (target.id === 'btnImport') {
    const form = document.getElementById('importForm');
    const fd = new FormData(form);
    const url = @json(route('admin.finance.bank_reconciliations.import', $recon->id));
    setLoading(target, true, 'Importing...');
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: fd,
      });
      const json = await res.json();
      if (!res.ok || json.ok === false) {
        throw new Error(json.message || 'Import failed');
      }
      Swal.fire(
        'Import complete',
        `Created: ${json.result.created}, Skipped: ${json.result.skipped}`,
        'success'
      ).then(() => location.reload());
    } catch (e) {
      Swal.fire('Error', e.message || 'Import failed', 'error');
    } finally {
      setLoading(target, false);
    }
    return;
  }
});

loadLines();
</script>
@endpush
```
