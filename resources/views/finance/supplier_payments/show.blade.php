@extends('layouts.master')

@section('content')
<div class="container-fluid">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-1">{{ $payment->payment_no ?: ('Payment #'.$payment->id) }}</h4>
      <div class="text-muted small">
        Supplier: <b>{{ $payment->supplier_name ?: '-' }}</b>
        &middot; Status:
        <span class="badge bg-{{ $payment->status == 'posted' ? 'success' : ($payment->status == 'voided' ? 'dark' : 'secondary') }}">
          {{ strtoupper($payment->status) }}
        </span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ url('admin/finance/supplier-payments') }}">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
      @if($payment->status === 'draft')
        <button class="btn btn-success" id="btnPostPayment">
          <i class="fas fa-check me-1"></i> Post
        </button>
      @elseif($payment->status !== 'voided')
        <button class="btn btn-outline-danger" id="btnVoidPayment">
          <i class="fas fa-ban me-1"></i> Void
        </button>
      @endif
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Payment Details</h6>
          <div class="d-flex justify-content-between"><span>Date</span><b>{{ $payment->payment_date }}</b></div>
          <div class="d-flex justify-content-between"><span>Amount</span><b>{{ number_format((float)$payment->amount, 2) }}</b></div>
          <div class="d-flex justify-content-between"><span>Currency</span><b>{{ $payment->currency_code ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Bank Account</span><b>{{ $payment->bank_name ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>AP Control Account</span><b>{{ trim(($payment->ap_code ?? '').' - '.($payment->ap_name ?? '')) ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Reference</span><b>{{ $payment->reference ?: '-' }}</b></div>
          @if($payment->memo)
            <div class="mt-2"><span class="text-muted">Memo</span><div>{{ $payment->memo }}</div></div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Applied To</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Bill</th>
                  <th>Bill Date</th>
                  <th class="text-end">Bill Total</th>
                  <th class="text-end">Allocated</th>
                </tr>
              </thead>
              <tbody>
                @forelse($allocations as $a)
                  <tr>
                    <td>{{ $a->bill_no ?: ('Bill #'.$a->supplier_bill_id) }}</td>
                    <td>{{ $a->bill_date }}</td>
                    <td class="text-end">{{ number_format((float)$a->total_amount, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$a->allocated_amount, 2) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted py-3">No allocations.</td></tr>
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

$('#btnPostPayment').on('click', function(){
  Swal.fire({title:'Post this payment?', icon:'question', showCancelButton:true, confirmButtonText:'Post'})
    .then(r => {
      if (!r.isConfirmed) return;
      $.post(`{{ url('admin/finance/supplier-payments/'.$payment->id.'/post') }}`)
        .done(res => Swal.fire('Posted', res.message || 'Payment posted.', 'success').then(() => location.reload()))
        .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
});

$('#btnVoidPayment').on('click', function(){
  Swal.fire({title:'Void this payment?', icon:'warning', showCancelButton:true, confirmButtonText:'Void'})
    .then(r => {
      if (!r.isConfirmed) return;
      $.post(`{{ url('admin/finance/supplier-payments/'.$payment->id.'/void') }}`)
        .done(res => Swal.fire('Voided', res.message || 'Payment voided.', 'success').then(() => location.reload()))
        .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
});
</script>
@endpush
