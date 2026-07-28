{{-- File: Modules/Finance/Resources/views/finance/budgets/edit.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">{{ $budget->name }}</h4>
      <small class="text-muted">
        {{ $budget->start_date->format('Y-m-d') }} → {{ $budget->end_date->format('Y-m-d') }}
        | {{ $budget->period_type }}
        | Status:
        <b id="budgetStatus">{{ $budget->status }}</b>
      </small>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('admin.finance.budgets.report', $budget->id) }}" class="btn btn-outline-secondary">
        Budget vs Actual
      </a>

      @can('finance.budgets.approve')
        <button class="btn btn-outline-primary" id="btnApprove" {{ $budget->status!=='draft' ? 'disabled':'' }}>
          Approve
        </button>
      @endcan
      @can('finance.budgets.lock')
        <button class="btn btn-outline-danger" id="btnLock" {{ $budget->status!=='approved' ? 'disabled':'' }}>
          Lock
        </button>
      @endcan

      @can('finance.budgets.update')
        <button class="btn btn-success" id="btnSave" {{ $budget->status==='locked' ? 'disabled':'' }}>
          Save
        </button>
      @endcan
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
      <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal" {{ $budget->status==='locked' ? 'disabled':'' }}>
        Add Account
      </button>
      <button class="btn btn-sm btn-outline-secondary" id="btnCopyAcross" {{ $budget->status==='locked' ? 'disabled':'' }}>
        Copy first period across (selected row)
      </button>
      <button class="btn btn-sm btn-outline-secondary" id="btnEvenSpread" {{ $budget->status==='locked' ? 'disabled':'' }}>
        Even spread row total (selected row)
      </button>
      <span class="text-muted ms-2">Click a row to select it.</span>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-sm table-bordered" id="budgetGrid">
        <thead>
          <tr>
            <th style="min-width:280px">Account</th>
            @foreach($periods as $p)
              <th class="text-end" style="min-width:140px">{{ $p['label'] }}</th>
            @endforeach
            <th class="text-end" style="min-width:140px">Row Total</th>
            <th style="min-width:120px">Actions</th>
          </tr>
        </thead>
        <tbody>
        @foreach($grid as $r)
          <tr class="grid-row" data-account-id="{{ $r['account_id'] }}">
            <td>
              <div class="fw-semibold">{{ $r['account_code'] }} - {{ $r['account_name'] }}</div>
              <div class="small text-muted">Account ID: {{ $r['account_id'] }}</div>
            </td>

            @php($rowTotal = 0)
            @foreach($periods as $p)
              @php($ps = $p['period_start'])
              @php($val = $r['amounts'][$ps] ?? 0)
              @php($rowTotal += (float)$val)
              <td class="text-end">
                <input
                  type="number" step="0.01"
                  class="form-control form-control-sm text-end grid-cell"
                  data-period-start="{{ $ps }}"
                  value="{{ number_format((float)$val, 2, '.', '') }}"
                  {{ $budget->status==='locked' ? 'disabled':'' }}
                >
              </td>
            @endforeach

            <td class="text-end">
              <span class="row-total fw-semibold">{{ number_format($rowTotal,2) }}</span>
            </td>

            <td>
              <button class="btn btn-sm btn-outline-danger btn-remove" {{ $budget->status==='locked' ? 'disabled':'' }}>
                Remove
              </button>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Add Account Modal --}}
  <div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Account to Budget</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Account</label>
          <select class="form-select" id="addAccountSelect">
            <option value="">-- select --</option>
            @foreach($allAccounts as $a)
              <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
            @endforeach
          </select>
          <small class="text-muted">If the account is already in the grid, it won’t be duplicated.</small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" id="btnAddAccount">Add</button>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
{{-- SweetAlert2 should be loaded in your layout: --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const periods = @json($periods);
let budgetStatus = @json($budget->status);
let selectedRow = null;

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2000,
  timerProgressBar: true
});

function setLoading(btn, isLoading, label) {
  if (!btn) return;
  if (isLoading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${label}`;
  } else {
    btn.disabled = false;
    if (btn.dataset.originalText) {
      btn.innerHTML = btn.dataset.originalText;
    }
  }
}

function recalcRowTotal($tr){
  let total = 0;
  $tr.find('.grid-cell').each(function(){
    total += Number($(this).val() || 0);
  });
  $tr.find('.row-total').text(total.toFixed(2));
}

function buildPayload(){
  const rows = [];
  $('#budgetGrid tbody tr').each(function(){
    const $tr = $(this);
    const accountId = Number($tr.data('account-id'));
    const amounts = {};
    $tr.find('.grid-cell').each(function(){
      const ps = $(this).data('period-start');
      amounts[ps] = Number($(this).val() || 0);
    });
    rows.push({account_id: accountId, amounts});
  });
  return {rows};
}

function addRowToGrid(accountId, accountCode, accountName) {
  const $tbody = $('#budgetGrid tbody');
  let rowHtml = `
    <tr class="grid-row" data-account-id="${accountId}">
      <td>
        <div class="fw-semibold">${accountCode} - ${accountName}</div>
        <div class="small text-muted">Account ID: ${accountId}</div>
      </td>
  `;
  let rowTotal = 0;
  periods.forEach(p => {
    const ps = p.period_start;
    const val = 0;
    rowTotal += val;
    rowHtml += `
      <td class="text-end">
        <input
          type="number" step="0.01"
          class="form-control form-control-sm text-end grid-cell"
          data-period-start="${ps}"
          value="${val.toFixed(2)}"
          ${budgetStatus === 'locked' ? 'disabled' : ''}
        >
      </td>
    `;
  });
  rowHtml += `
      <td class="text-end">
        <span class="row-total fw-semibold">${rowTotal.toFixed(2)}</span>
      </td>
      <td>
        <button class="btn btn-sm btn-outline-danger btn-remove" ${budgetStatus === 'locked' ? 'disabled' : ''}>
          Remove
        </button>
      </td>
    </tr>
  `;
  $tbody.append(rowHtml);
}

function updateStatusUI(newStatus) {
  budgetStatus = newStatus;
  document.getElementById('budgetStatus').textContent = newStatus;

  const btnApprove = document.getElementById('btnApprove');
  const btnLock = document.getElementById('btnLock');
  const btnSave = document.getElementById('btnSave');
  const btnCopyAcross = document.getElementById('btnCopyAcross');
  const btnEvenSpread = document.getElementById('btnEvenSpread');
  const btnAddAccount = document.getElementById('btnAddAccount');

  if (btnApprove) btnApprove.disabled = (newStatus !== 'draft');
  if (btnLock) btnLock.disabled = (newStatus !== 'approved');
  if (btnSave) btnSave.disabled = (newStatus === 'locked');
  if (btnCopyAcross) btnCopyAcross.disabled = (newStatus === 'locked');
  if (btnEvenSpread) btnEvenSpread.disabled = (newStatus === 'locked');
  if (btnAddAccount) btnAddAccount.disabled = (newStatus === 'locked');

  $('#budgetGrid .grid-cell').prop('disabled', newStatus === 'locked');
  $('#budgetGrid .btn-remove').prop('disabled', newStatus === 'locked');
}

$('#budgetGrid').on('click', 'tr.grid-row', function(e){
  selectedRow = $(this);
  $('#budgetGrid tr').removeClass('table-active');
  selectedRow.addClass('table-active');
});

$('#budgetGrid').on('input', '.grid-cell', function(){
  recalcRowTotal($(this).closest('tr'));
});

$('#btnCopyAcross').on('click', function(){
  if(!selectedRow){
    Swal.fire("Select a row first.", "", "warning");
    return;
  }
  const $cells = selectedRow.find('.grid-cell');
  if($cells.length < 2) return;
  const firstVal = Number($cells.first().val() || 0);
  $cells.each(function(i){
    if(i === 0) return;
    $(this).val(firstVal.toFixed(2)).trigger('input');
  });
});

$('#btnEvenSpread').on('click', async function(){
  if(!selectedRow){
    Swal.fire("Select a row first.", "", "warning");
    return;
  }

  const { value: total } = await Swal.fire({
    title: "Even spread",
    text: "Enter total for the entire row:",
    input: "number",
    inputAttributes: { step: "0.01" },
    showCancelButton: true,
    confirmButtonText: "Spread"
  });

  if (total === undefined) return;

  const num = Number(total || 0);
  const $cells = selectedRow.find('.grid-cell');
  if ($cells.length === 0) return;
  const each = num / $cells.length;

  $cells.each(function(){
    $(this).val(each.toFixed(2)).trigger('input');
  });
});

$('#btnSave').on('click', async function(){
  const btn = this;
  const url = @json(route('admin.finance.budgets.save_grid', $budget->id));
  const payload = buildPayload();

  setLoading(btn, true, 'Saving...');

  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'X-Requested-With':'XMLHttpRequest',
        'X-CSRF-TOKEN': CSRF_TOKEN
      },
      body: JSON.stringify(payload)
    });

    const json = await res.json();

    if(!res.ok || !json.ok){
      Swal.fire("Error", json.message || "Save failed", "error");
      return;
    }

    Toast.fire({ icon: 'success', title: json.message });
  } catch (e) {
    Swal.fire("Error", "Save failed", "error");
  } finally {
    setLoading(btn, false);
  }
});

$('#btnApprove').on('click', async function(){
  const btn = this;
  const url = @json(route('admin.finance.budgets.approve', $budget->id));

  const confirm = await Swal.fire({
    title: "Approve budget?",
    text: "Once approved, it can be locked.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Approve"
  });

  if (!confirm.isConfirmed) return;

  setLoading(btn, true, 'Approving...');

  try {
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'X-Requested-With':'XMLHttpRequest',
        'X-CSRF-TOKEN': CSRF_TOKEN
      }
    });

    const json = await res.json();

    if(!res.ok || !json.ok){
      Swal.fire("Error", json.message || "Approve failed", "error");
      return;
    }

    updateStatusUI('approved');
    Swal.fire("Approved", json.message, "success");
  } catch (e) {
    Swal.fire("Error", "Approve failed", "error");
  } finally {
    setLoading(btn, false);
  }
});

$('#btnLock').on('click', async function(){
  const btn = this;
  const url = @json(route('admin.finance.budgets.lock', $budget->id));

  const confirm = await Swal.fire({
    title: "Lock budget?",
    text: "Locked budgets cannot be edited.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Lock",
    confirmButtonColor: "#d33"
  });

  if (!confirm.isConfirmed) return;

  setLoading(btn, true, 'Locking...');

  try {
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'X-Requested-With':'XMLHttpRequest',
        'X-CSRF-TOKEN': CSRF_TOKEN
      }
    });

    const json = await res.json();

    if(!res.ok || !json.ok){
      Swal.fire("Error", json.message || "Lock failed", "error");
      return;
    }

    updateStatusUI('locked');
    Swal.fire("Locked", json.message, "success");
  } catch (e) {
    Swal.fire("Error", "Lock failed", "error");
  } finally {
    setLoading(btn, false);
  }
});

$('#btnAddAccount').on('click', async function(){
  const btn = this;
  const select = document.getElementById('addAccountSelect');
  const accountId = Number(select.value || 0);

  if(!accountId){
    Swal.fire("Error", "Select an account.", "error");
    return;
  }

  // prevent duplicates in the grid
  const existing = $('#budgetGrid tbody tr[data-account-id="'+accountId+'"]');
  if (existing.length > 0) {
    Swal.fire("Error", "That account is already in the budget.", "error");
    return;
  }

  const optionText = select.options[select.selectedIndex].text;
  const parts = optionText.split(' - ');
  const accountCode = parts[0] || '';
  const accountName = parts.slice(1).join(' - ') || '';

  const url = @json(route('admin.finance.budgets.add_account', $budget->id));

  setLoading(btn, true, 'Adding...');

  try {
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'X-Requested-With':'XMLHttpRequest',
        'X-CSRF-TOKEN': CSRF_TOKEN
      },
      body: JSON.stringify({account_id: accountId})
    });

    const json = await res.json();

    if(!res.ok || !json.ok){
      Swal.fire("Error", json.message || "Add failed", "error");
      return;
    }

    addRowToGrid(accountId, accountCode, accountName);
    $('#addAccountModal').modal('hide');
    select.value = '';
    Toast.fire({ icon: 'success', title: json.message });
  } catch (e) {
    Swal.fire("Error", "Add failed", "error");
  } finally {
    setLoading(btn, false);
  }
});

$('#budgetGrid').on('click', '.btn-remove', async function(e){
  e.stopPropagation();

  const btn = this;
  const $tr = $(this).closest('tr');
  const accountId = Number($tr.data('account-id'));

  const confirm = await Swal.fire({
    title: "Remove account?",
    text: "This will remove the account from the budget.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Remove",
    confirmButtonColor: "#d33"
  });

  if(!confirm.isConfirmed) return;

  const url = @json(route('admin.finance.budgets.remove_account', $budget->id));

  setLoading(btn, true, 'Removing...');

  try {
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'X-Requested-With':'XMLHttpRequest',
        'X-CSRF-TOKEN': CSRF_TOKEN
      },
      body: JSON.stringify({account_id: accountId})
    });

    const json = await res.json();

    if(!res.ok || !json.ok){
      Swal.fire("Error", json.message || "Remove failed", "error");
      return;
    }

    $tr.remove();
    Toast.fire({ icon: 'success', title: json.message });
  } catch (e) {
    Swal.fire("Error", "Remove failed", "error");
  } finally {
    setLoading(btn, false);
  }
});
</script>
@endpush
```
