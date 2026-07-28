@extends('layouts.master')

@section('title', 'Payment '.$payment->payment_no)

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Payment</h1>
            <small class="text-muted">Sales / Payments / {{ $payment->payment_no ?? ('PAY-'.$payment->id) }}</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.payments.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            @if($payment->status === 'draft')
                <button class="btn btn-success" id="postPaymentBtn">
                    <i class="fas fa-check"></i> Post
                </button>
            @else
            <a href="{{ route('admin.sales.payments.print', $payment->id) }}" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-print"></i> Print
            </a>
            
            <a href="{{ route('admin.sales.payments.pdf', $payment->id) }}" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            @endif
        </div>
    </div>

    {{-- SUMMARY --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted">Customer</div>
                    <div class="fw-bold">{{ $payment->customer?->name ?? ('Customer #'.$payment->customer_id) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Date</div>
                    <div class="fw-bold">{{ $payment->payment_date?->format('d M Y') ?? '-' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted">Amount Received</div>
                    <div class="fw-bold" id="amountReceivedText">{{ number_format((float)$payment->amount_received, 2) }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted">Allocated</div>
                    <div class="fw-bold" id="allocatedTotalText">0.00</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted">Unallocated</div>
                    <div class="fw-bold" id="unallocatedTotalText">0.00</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ALLOCATIONS --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="fw-bold">
                <i class="fas fa-random me-1"></i> Payment Allocations
            </div>

            <div class="d-flex gap-2">
                @if($payment->status === 'draft')
                    <button class="btn btn-primary btn-sm" id="saveAllocationsBtn">
                        <i class="fas fa-save"></i> Save Allocations
                    </button>
                @endif
                <button class="btn btn-outline-secondary btn-sm" id="reloadAllocationsBtn">
                    <i class="fas fa-sync"></i> Reload
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="allocationsTable" style="width:100%;">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:80px;">Invoice ID</th>
                            <th>Invoice No</th>
                            <th style="width:120px;">Date</th>
                            <th class="text-end" style="width:140px;">Grand Total</th>
                            <th class="text-end" style="width:140px;">Paid Total</th>
                            <th class="text-end" style="width:140px;">Balance Due</th>
                            <th class="text-end" style="width:170px;">Allocate Now</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <small class="text-muted">
                Tip: allocation per invoice is capped by its Balance Due, and total allocations cannot exceed Amount Received.
            </small>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
/**
 * REQUIREMENT: SweetAlert2 must be loaded globally as Swal
 * (You said DT / Select2 / SweetAlert already included)
 */
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const paymentId = {{ (int)$payment->id }};
const allocationsUrl = "{{ route('admin.sales.payments.allocations', $payment->id) }}";
const saveAllocationsUrl = "{{ route('admin.sales.payments.allocations.save', $payment->id) }}";
const postUrl = "{{ route('admin.sales.payments.post', $payment->id) }}";
const isDraft = "{{ $payment->status }}" === "draft";

function fmt2(n){
    n = parseFloat(n || 0);
    return n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

function swalError(message){
    return Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message || 'Something went wrong.',
        confirmButtonText: 'OK'
    });
}

function swalSuccess(message){
    return Swal.fire({
        icon: 'success',
        title: 'Success',
        text: message || 'Done.',
        timer: 1600,
        showConfirmButton: false
    });
}

function swalInfo(message){
    return Swal.fire({
        icon: 'info',
        title: 'Info',
        text: message || '',
        confirmButtonText: 'OK'
    });
}

function withLoading(title = 'Please wait...'){
    return Swal.fire({
        title,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });
}

function loadAllocations(){
    $('#allocationsTable tbody').html('<tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>');

    $.get(allocationsUrl)
        .done(function(res){
            $('#allocatedTotalText').text(fmt2(res.payment.allocated_total));
            $('#unallocatedTotalText').text(fmt2(res.payment.unallocated_total));

            const existing = res.existing || {};
            const rows = res.invoices || [];

            if(!rows.length){
                $('#allocationsTable tbody').html('<tr><td colspan="7" class="text-center text-muted">No invoices found for this customer.</td></tr>');
                return;
            }

            let html = '';
            rows.forEach(function(r){
                const invoiceId = r.sales_invoice_id;
                const invoiceNo = r.invoice_no ? r.invoice_no : ('INV-' + invoiceId);
                const invDate = r.invoice_date ? r.invoice_date : '';
                const grand = parseFloat(r.grand_total || 0);
                const paid = parseFloat(r.paid_total || 0);
                const bal  = parseFloat(r.balance_due || 0);

                const applied = parseFloat(existing[invoiceId] || 0);

                html += `
                    <tr data-invoice-id="${invoiceId}" data-balance="${bal}">
                        <td>${invoiceId}</td>
                        <td><strong>${invoiceNo}</strong></td>
                        <td>${invDate}</td>
                        <td class="text-end">${fmt2(grand)}</td>
                        <td class="text-end">${fmt2(paid)}</td>
                        <td class="text-end"><strong>${fmt2(bal)}</strong></td>
                        <td class="text-end">
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   ${isDraft ? '' : 'disabled'}
                                   class="form-control form-control-sm text-end alloc-input"
                                   value="${applied > 0 ? applied : 0}"
                                   style="min-width:140px;"
                                   max="${bal}">
                        </td>
                    </tr>
                `;
            });

            $('#allocationsTable tbody').html(html);
            recalcTotals();
        })
        .fail(function(xhr){
            const msg = xhr?.responseJSON?.message || 'Failed to load allocations.';
            $('#allocationsTable tbody').html(`<tr><td colspan="7" class="text-center text-danger">${msg}</td></tr>`);
            swalError(msg);
        });
}

function recalcTotals(){
    let total = 0;
    $('#allocationsTable tbody tr').each(function(){
        const bal = parseFloat($(this).data('balance') || 0);
        const v = parseFloat($(this).find('.alloc-input').val() || 0);

        // cap by balance due client-side
        const capped = Math.max(0, Math.min(v, bal));
        if(capped !== v) $(this).find('.alloc-input').val(capped);

        total += capped;
    });

    const received = parseFloat($('#amountReceivedText').text().replace(/,/g,'') || 0);
    const unallocated = Math.max(0, received - total);

    $('#allocatedTotalText').text(fmt2(total));
    $('#unallocatedTotalText').text(fmt2(unallocated));
}

$(document).on('input', '.alloc-input', function(){
    recalcTotals();
});

$('#reloadAllocationsBtn').on('click', function(){
    loadAllocations();
});

$('#saveAllocationsBtn').on('click', async function(){
    if(!isDraft) return;

    const allocations = [];
    $('#allocationsTable tbody tr').each(function(){
        const invoiceId = parseInt($(this).data('invoice-id'));
        const bal = parseFloat($(this).data('balance') || 0);
        const amt = parseFloat($(this).find('.alloc-input').val() || 0);

        const capped = Math.max(0, Math.min(amt, bal));
        if(capped > 0){
            allocations.push({ sales_invoice_id: invoiceId, amount_applied: capped });
        }
    });

    if(!allocations.length){
        return swalInfo('Please allocate at least one invoice (amount greater than 0).');
    }

    const confirm = await Swal.fire({
        icon: 'question',
        title: 'Save allocations?',
        text: 'This will overwrite existing allocations for this payment.',
        showCancelButton: true,
        confirmButtonText: 'Yes, save',
        cancelButtonText: 'Cancel'
    });

    if(!confirm.isConfirmed) return;

    withLoading('Saving allocations...');

    $.ajax({
        url: saveAllocationsUrl,
        method: 'POST',
        data: { allocations }
    }).done(function(res){
        Swal.close();
        loadAllocations();
        swalSuccess(res.message || 'Allocations saved.');
    }).fail(function(xhr){
        Swal.close();
        swalError(xhr?.responseJSON?.message || 'Failed to save allocations.');
    });
});

$('#postPaymentBtn').on('click', async function(){
    if(!isDraft) return;

    const allocated = parseFloat($('#allocatedTotalText').text().replace(/,/g,'') || 0);
    if(allocated <= 0){
        return swalInfo('Allocate at least one invoice before posting.');
    }

    const confirm = await Swal.fire({
        icon: 'warning',
        title: 'Post this payment?',
        html: `
            <div class="text-start">
                <div><b>Allocated:</b> ${$('#allocatedTotalText').text()}</div>
                <div><b>Unallocated:</b> ${$('#unallocatedTotalText').text()}</div>
                <hr class="my-2">
                <div class="text-muted">After posting, you will not be able to edit this payment.</div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Yes, post',
        cancelButtonText: 'Cancel'
    });

    if(!confirm.isConfirmed) return;

    withLoading('Posting payment...');

    $.post(postUrl)
        .done(function(res){
            Swal.close();
            swalSuccess(res.message || 'Payment posted.').then(()=> window.location.reload());
        })
        .fail(function(xhr){
            Swal.close();
            swalError(xhr?.responseJSON?.message || 'Failed to post payment.');
        });
});

$(function(){
    loadAllocations();
});
</script>
@endpush
