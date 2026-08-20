@extends('layouts.master')

@section('content')
<div class="container-fluid">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-1">{{ $bill->bill_no ?: ('Bill #'.$bill->id) }}</h4>
      <div class="text-muted small">
        Supplier: <b>{{ $bill->supplier_name ?: ($bill->vendor_name ?: '-') }}</b>
        &middot; Status:
        <span class="badge bg-{{ $bill->status == 'paid' ? 'success' : ($bill->status == 'voided' ? 'dark' : ($bill->status == 'posted' ? 'primary' : ($bill->status == 'part_paid' ? 'warning' : 'secondary'))) }}">
          {{ strtoupper(str_replace('_', ' ', $bill->status)) }}
        </span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.finance.supplier_bills.index') }}">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
      @if($bill->status === 'draft')
        <button class="btn btn-success" id="btnPostBill">
          <i class="fas fa-check me-1"></i> Post
        </button>
      @elseif($bill->status !== 'voided')
        <button class="btn btn-outline-danger" id="btnVoidBill">
          <i class="fas fa-ban me-1"></i> Void
        </button>
      @endif
      <a class="btn btn-outline-primary" href="{{ url('admin/finance/supplier-bills/'.$bill->id.'/pdf') }}" target="_blank">
        <i class="fas fa-file-pdf me-1"></i> PDF
      </a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Amounts</h6>
          <div class="d-flex justify-content-between"><span>Subtotal</span><b>{{ number_format((float)$bill->subtotal, 2) }}</b></div>
          <div class="d-flex justify-content-between"><span>Tax</span><b>{{ number_format((float)$bill->tax_total, 2) }}</b></div>
          <div class="d-flex justify-content-between"><span>Total</span><b>{{ number_format((float)$bill->total_amount, 2) }}</b></div>
          <hr>
          <div class="d-flex justify-content-between"><span>Paid</span><b>{{ number_format((float)$bill->amount_paid, 2) }}</b></div>
          <div class="d-flex justify-content-between"><span>Balance Due</span><b>{{ number_format((float)$bill->balance_due, 2) }}</b></div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Details</h6>
          <div class="d-flex justify-content-between"><span>Bill Date</span><b>{{ $bill->bill_date }}</b></div>
          <div class="d-flex justify-content-between"><span>Due Date</span><b>{{ $bill->due_date ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Reference</span><b>{{ $bill->reference ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Currency</span><b>{{ $bill->currency_code ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Payable Account</span><b>{{ trim(($bill->payable_code ?? '').' - '.($bill->payable_name ?? '')) ?: '-' }}</b></div>
          @if($bill->memo)
            <div class="mt-2"><span class="text-muted">Memo</span><div>{{ $bill->memo }}</div></div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Line Items</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Description</th>
                  <th>GL Account</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Unit Cost</th>
                  <th class="text-end">Tax</th>
                  <th class="text-end">Line Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse($lines as $l)
                  <tr>
                    <td>{{ $l->description ?: '-' }}{{ $l->memo ? ' — '.$l->memo : '' }}</td>
                    <td>{{ trim(($l->gl_code ?? '').' - '.($l->gl_name ?? '')) ?: '-' }}</td>
                    <td class="text-end">{{ number_format((float)$l->qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$l->unit_cost, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$l->tax_amount, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$l->line_total, 2) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center text-muted py-3">No lines.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

$('#btnPostBill').on('click', function(){
  Swal.fire({title:'Post this bill?', icon:'question', showCancelButton:true, confirmButtonText:'Post'})
    .then(r => {
      if (!r.isConfirmed) return;
      $.post(`{{ url('admin/finance/supplier-bills/'.$bill->id.'/post') }}`)
        .done(res => Swal.fire('Posted', res.message || 'Bill posted.', 'success').then(() => location.reload()))
        .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
});

$('#btnVoidBill').on('click', function(){
  Swal.fire({title:'Void this bill?', icon:'warning', showCancelButton:true, confirmButtonText:'Void'})
    .then(r => {
      if (!r.isConfirmed) return;
      $.post(`{{ url('admin/finance/supplier-bills/'.$bill->id.'/void') }}`)
        .done(res => Swal.fire('Voided', res.message || 'Bill voided.', 'success').then(() => location.reload()))
        .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
});
</script>
@endpush
