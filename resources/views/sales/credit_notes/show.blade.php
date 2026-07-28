@extends('layouts.master')

@section('title', 'Credit Note '.$creditNote->credit_note_no)

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Credit Note</h1>
            <small class="text-muted">Sales / Credit Notes / {{ $creditNote->credit_note_no ?? ('CN-'.$creditNote->id) }}</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.credit-notes.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>

            @if($creditNote->status === 'posted')
                <form method="POST" action="{{ route('admin.sales.credit-notes.void', $creditNote->id) }}" class="d-inline" id="voidForm">
                    @csrf
                    <button type="button" class="btn btn-danger" id="voidBtn">
                        <i class="fas fa-ban"></i> Void
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <div>
                    <h3 class="mb-1">{{ env('APP_NAME','Thekan-ERP') }}</h3>
                    <div class="text-muted">Sales Credit Note</div>
                </div>
                <div class="text-end">
                    <div><strong>Credit Note No:</strong> {{ $creditNote->credit_note_no ?? ('CN-'.$creditNote->id) }}</div>
                    <div><strong>Date:</strong> {{ optional($creditNote->credit_note_date)->format('d M Y') }}</div>
                    <div><strong>Status:</strong>
                        <span class="badge badge-{{ $creditNote->status_badge }}">{{ strtoupper($creditNote->status) }}</span>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted">Customer</div>
                    <div class="fw-bold">{{ $creditNote->customer?->name ?? ('Customer #'.$creditNote->customer_id) }}</div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="text-muted">Invoice</div>
                    <div class="fw-bold">
                        {{ $creditNote->invoice?->invoice_no ?? ($creditNote->sales_invoice_id ? 'INV-'.$creditNote->sales_invoice_id : '-') }}
                    </div>
                </div>

                @if($creditNote->stock_return_id)
                <div class="col-md-12">
                    <div class="text-muted">Stock Return Reference</div>
                    <div class="fw-bold">#{{ $creditNote->stock_return_id }}</div>
                </div>
                @endif

                @if($creditNote->reason)
                <div class="col-md-12">
                    <div class="text-muted">Reason</div>
                    <div class="fw-bold">{{ $creditNote->reason }}</div>
                </div>
                @endif
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-end" style="width:120px;">Qty</th>
                            <th class="text-end" style="width:140px;">Unit Price</th>
                            <th class="text-end" style="width:140px;">Tax</th>
                            <th class="text-end" style="width:160px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($creditNote->lines as $l)
                            <tr>
                                <td>{{ $l->description }}</td>
                                <td class="text-end">{{ number_format((float)$l->qty, 2) }}</td>
                                <td class="text-end">{{ number_format((float)$l->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float)$l->tax_amount, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format((float)$l->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                        @if(!$creditNote->lines->count())
                            <tr><td colspan="5" class="text-center text-muted">No lines</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="row justify-content-end mt-3">
                <div class="col-md-5">
                    <table class="table table-sm">
                        <tr>
                            <th class="text-end">Subtotal:</th>
                            <td class="text-end fw-bold">{{ number_format((float)$creditNote->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="text-end">Tax Total:</th>
                            <td class="text-end fw-bold">{{ number_format((float)$creditNote->tax_total, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="text-end">Grand Total:</th>
                            <td class="text-end fw-bold">{{ number_format((float)$creditNote->grand_total, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($creditNote->remarks)
                <hr>
                <div class="text-muted">Remarks</div>
                <div class="fw-bold">{{ $creditNote->remarks }}</div>
            @endif

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$('#voidBtn').on('click', function(){
    Swal.fire({
        icon: 'warning',
        title: 'Void this credit note?',
        text: 'This will mark it as void and it should no longer affect balances.',
        showCancelButton: true,
        confirmButtonText: 'Yes, void',
        cancelButtonText: 'Cancel'
    }).then((r)=>{
        if(r.isConfirmed) $('#voidForm').submit();
    });
});
</script>
@endpush
