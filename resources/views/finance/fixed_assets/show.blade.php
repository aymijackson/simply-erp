@extends('layouts.app')

@section('content')
<div class="container-fluid">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-1">{{ $asset->asset_code }} — {{ $asset->name }}</h4>
      <div class="text-muted small">
        Category: <b>{{ $asset->category?->name }}</b>
        • Status: <b>{{ $asset->status }}</b>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.finance.fixed_assets.index') }}">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
      @can('finance.fixed_asset_transactions.create')
      <button class="btn btn-primary" id="btnNewTxn">
        <i class="fas fa-plus me-1"></i> New Transaction
      </button>
      @endcan
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Asset Summary</h6>
          <div class="d-flex justify-content-between"><span>Purchase Cost</span><b>{{ number_format((float)$asset->purchase_cost,2) }}</b></div>
          <div class="d-flex justify-content-between"><span>Salvage</span><b>{{ number_format((float)$asset->salvage_value,2) }}</b></div>
          <div class="d-flex justify-content-between"><span>Accum. Depreciation (posted)</span><b>{{ number_format((float)$deprPosted,2) }}</b></div>
          <hr>
          <div class="d-flex justify-content-between"><span>Net Book Value (NBV)</span>
            <b>{{ number_format(max(0,(float)$asset->purchase_cost-(float)$deprPosted),2) }}</b>
          </div>

          <hr>
          <div class="small text-muted">
            Depreciation method: <b>{{ $asset->depr_method }}</b> • Life: <b>{{ $asset->useful_life_months }}</b> months
          </div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Dates & Details</h6>
          <div class="d-flex justify-content-between"><span>Purchase Date</span><b>{{ $asset->purchase_date }}</b></div>
          <div class="d-flex justify-content-between"><span>In Service</span><b>{{ $asset->in_service_date }}</b></div>
          <div class="d-flex justify-content-between"><span>Location</span><b>{{ $asset->location ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Serial</span><b>{{ $asset->serial_no ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Supplier</span><b>{{ $asset->supplier_name ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Invoice</span><b>{{ $asset->invoice_no ?: '-' }}</b></div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Transactions</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Type</th>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Journal</th>
                  <th style="width:240px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($txns as $t)
                  <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->txn_type }}</td>
                    <td>{{ $t->txn_date }}</td>
                    <td>{{ number_format((float)$t->amount,2) }}</td>
                    <td>
                      <span class="badge bg-{{ $t->status=='posted'?'success':($t->status=='voided'?'danger':'secondary') }}">{{ $t->status }}</span>
                    </td>
                    <td>{{ $t->journal_entry_id ?? '-' }}</td>
                    <td>
                      @can('finance.fixed_asset_transactions.post')
                        @if($t->status=='draft')
                          <button class="btn btn-sm btn-success btnPost" data-id="{{ $t->id }}">
                            <i class="fas fa-check me-1"></i> Post
                          </button>
                        @endif
                      @endcan

                      @can('finance.fixed_asset_transactions.void')
                        @if($t->status=='posted')
                          <button class="btn btn-sm btn-outline-danger btnVoid" data-id="{{ $t->id }}">
                            <i class="fas fa-ban me-1"></i> Void
                          </button>
                        @endif
                      @endcan
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="small text-muted">
            Posting creates GL journals; voiding posts a reversal journal (audit safe).
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- Transaction Modal --}}
<div class="modal fade" id="txnModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Asset Transaction</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="txnForm">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Type</label>
              <select class="form-select" id="txn_type" required>
                <option value="acquisition">Acquisition</option>
                <option value="disposal" {{ $asset->status!='active'?'disabled':'' }}>Disposal</option>
              </select>
              <div class="small text-muted">Disposal only allowed when asset is Active.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date</label>
              <input type="date" class="form-control" id="txn_date" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Amount</label>
              <input type="number" step="0.01" class="form-control" id="amount" required value="0">
              <div class="small text-muted">
                Acquisition: cost. Disposal: proceeds (0 allowed for write-off-like disposal).
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Counter Account (COA)</label>
              <select class="form-select" id="counter_account_id">
                <option value="">-- select --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
              <div class="small text-muted">Acquisition: Bank/AP/Clearing. Disposal: Bank/Receivable for proceeds.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Bank Account ID (optional)</label>
              <input class="form-control" id="bank_account_id" placeholder="finance_bank_accounts.id (optional)">
              <div class="small text-muted">Set this if counter account represents Bank/Cash for reconciliation.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Reference</label>
              <input class="form-control" id="reference" maxlength="120">
            </div>

            <div class="col-md-12">
              <label class="form-label">Memo</label>
              <input class="form-control" id="memo" maxlength="255">
            </div>
          </div>

          <div class="alert alert-info small mt-3 mb-0">
            <b>Posting rules:</b><br>
            Acquisition: Dr Asset / Cr Counter.<br>
            Disposal: Dr Proceeds (if any) + Dr Accum Dep + Cr Asset Cost ± Gain/Loss.
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Create Draft</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

$('#btnNewTxn').on('click', function(){
  $('#txnForm')[0].reset();
  $('#amount').val(0);
  $('#txn_date').val(`{{ date('Y-m-d') }}`);
  $('#txnModal').modal('show');
});

$('#txnForm').on('submit', function(e){
  e.preventDefault();
  const payload = {
    txn_type: $('#txn_type').val(),
    txn_date: $('#txn_date').val(),
    amount: $('#amount').val(),
    counter_account_id: $('#counter_account_id').val(),
    bank_account_id: $('#bank_account_id').val(),
    reference: $('#reference').val(),
    memo: $('#memo').val(),
  };

  $.post(`{{ url('admin/finance/fixed-assets') }}/{{ $asset->id }}/transactions`, payload)
    .done(res=>Swal.fire('Created', res.message, 'success').then(()=>location.reload()))
    .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
});

$(document).on('click','.btnPost', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Post transaction?', icon:'question', showCancelButton:true, confirmButtonText:'Post'})
  .then(r=>{
    if(!r.isConfirmed) return;
    $.post(`{{ url('admin/finance/fixed-assets/transactions') }}/${id}/post`)
      .done(res=>Swal.fire('Posted', res.message, 'success').then(()=>location.reload()))
      .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
  });
});

$(document).on('click','.btnVoid', function(){
  const id = $(this).data('id');
  Swal.fire({
    title:'Void transaction?',
    input:'text',
    inputLabel:'Reason (required)',
    showCancelButton:true,
    confirmButtonText:'Void'
  }).then(r=>{
    if(!r.isConfirmed) return;
    $.post(`{{ url('admin/finance/fixed-assets/transactions') }}/${id}/void`, {reason:r.value})
      .done(res=>Swal.fire('Voided', res.message, 'success').then(()=>location.reload()))
      .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
  });
});
</script>
@endpush