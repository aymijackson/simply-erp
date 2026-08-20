@extends('layouts.master')

@section('title', 'Sales Quote')

@section('content')
<div class="container-fluid">

  @php
    $badge = match($quote->status) {
        'sent' => 'bg-info',
        'won' => 'bg-success',
        'rejected' => 'bg-danger',
        'expired' => 'bg-dark',
        'converted' => 'bg-primary',
        default => 'bg-secondary',
    };
  @endphp

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-1">{{ $quote->quote_no ?: ('Quote #'.$quote->id) }}</h4>
      <div class="text-muted small">
        Customer: <b>{{ $quote->customer->name ?? ('Customer #'.$quote->customer_id) }}</b>
        &middot; Status: <span class="badge {{ $badge }}">{{ strtoupper($quote->status) }}</span>
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      @can('sales.quotes.edit')
        @if(in_array($quote->status, ['draft', 'won']))
          <a class="btn btn-outline-secondary" href="{{ route('admin.sales.quotes.edit', $quote->id) }}">
            <i class="fas fa-edit me-1"></i> Edit
          </a>
        @endif
      @endcan

      @can('sales.quotes.send')
        @if($quote->status === 'draft')
          <button class="btn btn-info text-white" id="btnSend"><i class="fas fa-paper-plane me-1"></i> Send</button>
        @endif
      @endcan

      @can('sales.quotes.win')
        @if($quote->status === 'sent')
          <button class="btn btn-success" id="btnWin"><i class="fas fa-trophy me-1"></i> Mark Won</button>
        @endif
      @endcan

      @can('sales.quotes.reject')
        @if($quote->status === 'sent')
          <button class="btn btn-outline-danger" id="btnReject"><i class="fas fa-times me-1"></i> Reject</button>
        @endif
      @endcan

      @can('sales.quotes.expire')
        @if($quote->status === 'sent')
          <button class="btn btn-outline-dark" id="btnExpire"><i class="fas fa-hourglass-end me-1"></i> Mark Expired</button>
        @endif
      @endcan

      <a class="btn btn-outline-primary" href="{{ route('admin.sales.quotes.pdf', $quote->id) }}" target="_blank">
        <i class="fas fa-file-pdf me-1"></i> PDF
      </a>

      <a class="btn btn-outline-secondary" href="{{ route('admin.sales.quotes.index') }}">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Amounts</h6>
          <div class="d-flex justify-content-between"><span>Subtotal</span><b>{{ number_format((float)$quote->subtotal, 2) }}</b></div>
          <div class="d-flex justify-content-between"><span>Discount</span><b>{{ number_format((float)$quote->discount_total, 2) }}</b></div>
          <div class="d-flex justify-content-between"><span>Tax</span><b>{{ number_format((float)$quote->tax_total, 2) }}</b></div>
          <hr>
          <div class="d-flex justify-content-between"><span>Total</span><b>{{ number_format((float)$quote->total_amount, 2) }}</b></div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Details</h6>
          <div class="d-flex justify-content-between"><span>Quote Date</span><b>{{ optional($quote->quote_date)->format('d M Y') }}</b></div>
          <div class="d-flex justify-content-between"><span>Valid Until</span><b>{{ optional($quote->valid_until)->format('d M Y') ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Currency</span><b>{{ $quote->currency_code ?: '-' }}</b></div>
          <div class="d-flex justify-content-between"><span>Reference</span><b>{{ $quote->reference ?: '-' }}</b></div>
          @if($quote->notes)
            <div class="mt-2"><span class="text-muted">Notes</span><div>{{ $quote->notes }}</div></div>
          @endif
        </div>
      </div>

      @if($quote->status === 'converted' && $quote->salesOrder)
        <div class="card mt-3">
          <div class="card-body">
            <h6 class="fw-semibold mb-2">Converted</h6>
            <p class="mb-2">This quote was converted to Sales Order
              <a href="{{ route('admin.sales.orders.show', $quote->salesOrder->id) }}">{{ $quote->salesOrder->order_no }}</a>.
            </p>
            <div class="text-muted small">
              Converted {{ optional($quote->converted_at)->format('d M Y, h:i A') }}
            </div>
          </div>
        </div>
      @endif

      @if($quote->status === 'won' || $quote->reviewed_at)
        <div class="card mt-3">
          <div class="card-body">
            <h6 class="fw-semibold mb-2">Review</h6>

            @if($quote->reviewed_at)
              <div class="text-muted small mb-2">
                Last reviewed {{ optional($quote->reviewed_at)->format('d M Y, h:i A') }} by {{ $quote->reviewer->name ?? ('User #'.$quote->reviewed_by) }}
              </div>
            @endif

            @can('sales.quotes.review')
              @if($quote->status === 'won')
                <textarea id="reviewComments" class="form-control mb-2" rows="3" placeholder="Review comments (optional)">{{ $quote->review_comments }}</textarea>
                <button class="btn btn-sm btn-primary" id="btnSaveReview">
                  <i class="fas fa-save me-1"></i> Save Review
                </button>
              @elseif($quote->review_comments)
                <div class="border rounded p-2 small">{{ $quote->review_comments }}</div>
              @endif
            @elseif($quote->review_comments)
              <div class="border rounded p-2 small">{{ $quote->review_comments }}</div>
            @endif

            @can('sales.quotes.convert')
              @if($quote->status === 'won')
                <button class="btn btn-primary w-100 mt-3" id="btnConvert">
                  <i class="fas fa-exchange-alt me-1"></i> Convert to Sales Order
                </button>
              @endif
            @endcan
          </div>
        </div>
      @endif
    </div>

    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Line Items</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Item</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Unit Price</th>
                  <th class="text-end">Disc %</th>
                  <th class="text-end">Tax</th>
                  <th class="text-end">Line Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse($quote->lines as $l)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $l->variant?->product?->product_name ?? 'Item' }}</div>
                      <div class="text-muted small">{{ $l->variant?->sku ?? ('Variant #'.$l->product_variant_id) }}</div>
                      @if($l->description)
                        <div class="text-muted small">{{ $l->description }}</div>
                      @endif
                    </td>
                    <td class="text-end">{{ number_format((float)$l->qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$l->unit_price, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$l->discount_percent, 2) }}</td>
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

function postAction(url, successMsg, redirectOnSuccess){
  return $.post(url)
    .done(res => {
      Swal.fire('Success', res.message || successMsg, 'success').then(() => {
        if (redirectOnSuccess && res.redirect) {
          window.location = res.redirect;
        } else {
          location.reload();
        }
      });
    })
    .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
}

$('#btnSend').on('click', function(){
  Swal.fire({title:'Send this quote?', icon:'question', showCancelButton:true, confirmButtonText:'Send'})
    .then(r => { if (r.isConfirmed) postAction(`{{ url('admin/sales/quotes/'.$quote->id.'/send') }}`, 'Quote sent.'); });
});

$('#btnWin').on('click', function(){
  Swal.fire({title:'Mark this quote as won?', text:'A privileged user can then review it before converting.', icon:'question', showCancelButton:true, confirmButtonText:'Mark Won'})
    .then(r => { if (r.isConfirmed) postAction(`{{ url('admin/sales/quotes/'.$quote->id.'/win') }}`, 'Quote marked as won.'); });
});

$('#btnReject').on('click', function(){
  Swal.fire({title:'Reject this quote?', icon:'warning', showCancelButton:true, confirmButtonText:'Reject'})
    .then(r => { if (r.isConfirmed) postAction(`{{ url('admin/sales/quotes/'.$quote->id.'/reject') }}`, 'Quote rejected.'); });
});

$('#btnExpire').on('click', function(){
  Swal.fire({title:'Mark this quote as expired?', icon:'warning', showCancelButton:true, confirmButtonText:'Mark Expired'})
    .then(r => { if (r.isConfirmed) postAction(`{{ url('admin/sales/quotes/'.$quote->id.'/expire') }}`, 'Quote marked as expired.'); });
});

$('#btnSaveReview').on('click', function(){
  $.post(`{{ url('admin/sales/quotes/'.$quote->id.'/review') }}`, { review_comments: $('#reviewComments').val() })
    .done(res => Swal.fire('Saved', res.message || 'Review saved.', 'success').then(() => location.reload()))
    .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
});

$('#btnConvert').on('click', function(){
  Swal.fire({
    title: 'Convert to Sales Order?',
    text: 'This creates a new draft Sales Order from this quote. This cannot be undone.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Convert'
  }).then(r => { if (r.isConfirmed) postAction(`{{ url('admin/sales/quotes/'.$quote->id.'/convert') }}`, 'Converted.', true); });
});
</script>
@endpush
