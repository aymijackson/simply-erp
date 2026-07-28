@extends('layouts.master')

@section('title', $mode === 'edit' ? 'Edit Payment' : 'New Payment')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">{{ $mode === 'edit' ? 'Edit Payment' : 'New Payment' }}</h1>
            <small class="text-muted">Sales / Payments</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.payments.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>

            @if($mode === 'edit')
                <span class="badge badge-{{ $payment->status_badge ?? 'secondary' }}">
                    {{ strtoupper($payment->status ?? 'draft') }}
                </span>
            @endif
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-white">
            <h6 class="mb-0 text-primary">
                <i class="fas fa-money-check-alt mr-1"></i> Payment Details
            </h6>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label mb-1">Payment No</label>
                    <input class="form-control" id="payment_no"
                           value="{{ $payment->payment_no ?? '' }}"
                           placeholder="Auto"
                           {{ $mode === 'edit' ? 'readonly' : '' }}>
                    <small class="text-muted">Usually auto-generated on save</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="payment_date"
                           value="{{ optional($payment->payment_date)->format('Y-m-d') ?? now()->format('Y-m-d') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Currency</label>
                    <input class="form-control" id="currency_code"
                           value="{{ $payment->currency_code ?? 'NGN' }}">
                </div>
                
                <div class="col-md-8">
                  <label class="form-label mb-1">Bank/Cash Account <span class="text-danger">*</span></label>
                  <select id="bank_account_id" class="form-control" style="width:100%;">
                    <option value=""></option>
                    @if(!empty($payment->bank_account_id))
                      <option value="{{ $payment->bank_account_id }}" selected>
                        {{ $payment->bankAccount?->name ?? ('Account #'.$payment->bank_account_id) }}
                      </option>
                    @endif
                  </select>
                  <small class="text-muted">This is where money was received (used for Finance posting).</small>
                </div>

                <div class="col-md-8">
                    <label class="form-label mb-1">Customer <span class="text-danger">*</span></label>
                    <select id="customer_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                        @if($payment->customer_id)
                            <option value="{{ $payment->customer_id }}" selected>
                                {{ $payment->customer?->name ?? ('Customer #'.$payment->customer_id) }}
                            </option>
                        @endif
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Method <span class="text-danger">*</span></label>
                    @php $m = strtolower($payment->method ?? 'bank_transfer'); @endphp
                    <select class="form-control" id="method">
                        <option value="cash" {{ $m==='cash'?'selected':'' }}>Cash</option>
                        <option value="bank_transfer" {{ $m==='bank_transfer'?'selected':'' }}>Bank Transfer</option>
                        <option value="card" {{ $m==='card'?'selected':'' }}>Card</option>
                        <option value="cheque" {{ $m==='cheque'?'selected':'' }}>Cheque</option>
                        <option value="other" {{ $m==='other'?'selected':'' }}>Other</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Amount Received <span class="text-danger">*</span></label>
                    <input type="number" min="0" step="0.0001" class="form-control text-end" id="amount_received"
                           value="{{ (float)($payment->amount_received ?? 0) }}">
                </div>

                <div class="col-md-8">
                    <label class="form-label mb-1">Reference</label>
                    <input class="form-control" id="reference" value="{{ $payment->reference ?? '' }}"
                           placeholder="Bank ref / POS ref / Cheque no / etc">
                </div>

                <div class="col-md-12">
                    <label class="form-label mb-1">Remarks</label>
                    <textarea class="form-control" id="remarks" rows="3"
                              placeholder="Optional notes...">{{ $payment->remarks ?? '' }}</textarea>
                </div>

            </div>

            <div class="d-flex justify-content-end mt-4">
                <button class="btn btn-primary" id="saveBtn">
                    <i class="fas fa-save mr-1"></i> Save
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const mode = @json($mode);
const paymentId = @json($payment->id ?? null);

function initSelect2Ajax(selector, url, placeholder){
    const $el = $(selector);
    if ($el.hasClass('select2-hidden-accessible')) return;
    $el.select2({
        theme:'bootstrap-5',
        width:'100%',
        placeholder,
        allowClear:true,
        ajax:{
            url, dataType:'json', delay:250,
            data: params => ({ q: params.term || '', page: params.page || 1 }),
            processResults: data => data.results ? data : ({ results: Array.isArray(data) ? data : [] })
        }
    });
}

initSelect2Ajax(
  '#bank_account_id',
  "{{ url('admin/finance/bank-transactions/bank-accounts') }}",
  'Select bank/cash account'
);

function collectPayload(){
    return {
        payment_no: $('#payment_no').val() || null,
        payment_date: $('#payment_date').val() || null,
        currency_code: $('#currency_code').val() || 'NGN',
        bank_account_id: $('#bank_account_id').val(),
        customer_id: $('#customer_id').val(),
        method: $('#method').val(),
        amount_received: Number($('#amount_received').val() || 0),
        reference: $('#reference').val() || null,
        remarks: $('#remarks').val() || null,
    };
}

document.addEventListener('DOMContentLoaded', function(){

    initSelect2Ajax('#customer_id', "{{ route('admin.customers.select2') ?? '' }}", 'Select customer');

    $('#saveBtn').on('click', async function(){
        const payload = collectPayload();

        if(!payload.customer_id){
            Swal.fire({icon:'warning', title:'Missing customer', text:'Customer is required.'});
            return;
        }
        if(!payload.payment_date){
            Swal.fire({icon:'warning', title:'Missing date', text:'Payment date is required.'});
            return;
        }
        if(!payload.method){
            Swal.fire({icon:'warning', title:'Missing method', text:'Payment method is required.'});
            return;
        }
        if(payload.amount_received <= 0){
            Swal.fire({icon:'warning', title:'Invalid amount', text:'Amount received must be greater than 0.'});
            return;
        }

        if(!payload.bank_account_id){
          Swal.fire({icon:'warning', title:'Missing bank account', text:'Bank/Cash account is required.'});
          return;
        }

        const url = (mode === 'edit')
            ? `{{ url('admin/sales/payments') }}/${paymentId}`
            : `{{ route('admin.sales.payments.store') }}`;

        const method = (mode === 'edit') ? 'PUT' : 'POST';

        try{
            const res = await fetch(url, {
                method,
                headers:{
                    'X-CSRF-TOKEN': csrf,
                    'Accept':'application/json',
                    'Content-Type':'application/json',
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json().catch(()=>({}));
            if(!res.ok) throw new Error(data.message || 'Save failed');

            Swal.fire({icon:'success', title:'Saved', text:data.message || 'Payment saved', timer:1200, showConfirmButton:false});

            if(data.redirect){
                window.location.href = data.redirect;
            }else if(mode !== 'edit' && data.id){
                window.location.href = `{{ url('admin/sales/payments') }}/${data.id}/edit`;
            }

        }catch(e){
            Swal.fire({icon:'error', title:'Error', text: e.message || 'Save failed'});
        }
    });

});
</script>
@endpush
