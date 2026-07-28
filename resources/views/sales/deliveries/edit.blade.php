@extends('layouts.master')
@section('title', 'Edit Delivery Note')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<style>
.select2-container { width:100% !important; }
.select2-container--open { z-index:9999; }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Edit Delivery Note</h1>
            <p class="text-muted mb-0">Update planned quantities and stores</p>
        </div>
        <a href="{{ route('admin.sales.deliveries.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.sales.deliveries.update', $delivery->id) }}" id="deliveryEditForm">
        @csrf
        @method('PUT')

        <div class="card shadow mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0 text-primary"><i class="fas fa-truck mr-1"></i> Delivery Note</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-1">Customer</label>
                        <input class="form-control" value="{{ $delivery->customer->name ?? '—' }}" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-1">Ship Date</label>
                        <input type="date" class="form-control" name="ship_date"
                               value="{{ old('ship_date', optional($delivery->ship_date)->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-1">Status</label>
                        <input class="form-control" value="{{ $delivery->status ?? 'draft' }}" readonly>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label mb-1">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2">{{ old('remarks', $delivery->remarks) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-white">
                <h6 class="mb-0 text-primary"><i class="fas fa-list mr-1"></i> Lines</h6>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>Item</th>
                                <th style="width:22%;">Store</th>
                                <th class="text-center">SO Remaining</th>
                                <th class="text-center">Planned</th>
                                <th class="text-center">Actual</th>
                                <th style="width:14%;">Qty to Deliver</th>
                            </tr>
                        </thead>
                        <tbody id="editLinesBody">
                        @foreach($delivery->lines as $i => $line)
                            @php
                                $remaining = $line->so_remaining_qty ?? null; // pass this from controller (recommended)
                            @endphp
                            <tr data-row="{{ $i }}">
                                <td>
                                    <div class="font-weight-bold">{{ $line->variant_label ?? ('Variant #'.$line->product_variant_id) }}</div>
                                    <div class="text-muted small">{{ $line->description ?? '' }}</div>
                                    <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $line->id }}">
                                    <input type="hidden" name="lines[{{ $i }}][product_variant_id]" value="{{ $line->product_variant_id }}">
                                    <input type="hidden" name="lines[{{ $i }}][sales_order_line_id]" value="{{ $line->sales_order_line_id }}">
                                </td>

                                <td>
                                    <select class="form-control line-store-select" name="lines[{{ $i }}][location_store_id]" style="width:100%;">
                                        <option value=""></option>
                                        @if($line->location_store_id)
                                            <option value="{{ $line->location_store_id }}" selected>{{ $line->store_name ?? 'Selected Store' }}</option>
                                        @endif
                                    </select>
                                </td>

                                <td class="text-center">{{ is_null($remaining) ? '—' : number_format($remaining,0) }}</td>
                                <td class="text-center">{{ number_format($line->qty_to_deliver,0) }}</td>
                                <td class="text-center">{{ number_format($line->qty_delivered_actual,0) }}</td>

                                <td>
                                    <input type="number" step="1" min="0"
                                           class="form-control qty-input"
                                           name="lines[{{ $i }}][qty_to_deliver]"
                                           value="{{ old("lines.$i.qty_to_deliver", $line->qty_to_deliver) }}"
                                           data-remaining="{{ is_null($remaining) ? '' : $remaining }}"
                                           max="{{ is_null($remaining) ? '' : $remaining }}">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i> Update Draft</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const STORE_SELECT2_URL   = `{{ url('admin/location_stores/fetch') }}`;
const STOCK_AVAILABLE_URL = `{{ url('admin/location_stores/stock/available') }}`;

function getCSRF(){ return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''; }

function initSelect2Ajax($el, url, placeholder){
    if ($el.hasClass('select2-hidden-accessible')) return;
    $el.select2({
        theme:'bootstrap-5', width:'100%', allowClear:true, placeholder,
        ajax:{
            url, dataType:'json', delay:250,
            data: p => ({ q:p.term||'', page:p.page||1 }),
            processResults: d => Array.isArray(d) ? ({results:d}) : (d.results ? d : ({results:[]}))
        }
    });
}

async function applyStoreCap($row){
    const storeId = $row.find('.line-store-select').val();
    const qtyInput = $row.find('.qty-input');
    const remainingAttr = qtyInput.attr('data-remaining');
    const remaining = remainingAttr ? parseFloat(remainingAttr) : null;
    const hasRemaining = Number.isFinite(remaining);

    if (!storeId) {
        if (hasRemaining) { qtyInput.attr('max', remaining); if (parseFloat(qtyInput.val()||0) > remaining) qtyInput.val(remaining); }
        else qtyInput.removeAttr('max');
        return;
    }

    const variantId = $row.find('input[name*="[product_variant_id]"]').val();
    if (!variantId) return;

    const url = `${STOCK_AVAILABLE_URL}?location_store_id=${encodeURIComponent(storeId)}&product_variant_id=${encodeURIComponent(variantId)}`;
    const res = await fetch(url, { headers:{'Accept':'application/json','X-CSRF-TOKEN':getCSRF()} });
    const data = await res.json().catch(()=>({}));
    if (!res.ok) return;

    const available = parseFloat(data.available ?? '0');
    const cap = hasRemaining ? Math.min(remaining, available) : available;

    qtyInput.attr('max', cap);
    const v = parseFloat(qtyInput.val()||0);
    if (v > cap) qtyInput.val(cap);
}

document.addEventListener('DOMContentLoaded', function(){
    $('#editLinesBody .line-store-select').each(function(){
        initSelect2Ajax($(this), STORE_SELECT2_URL, 'Select store...');
    });

    $(document).on('change', '.line-store-select', function(){
        applyStoreCap($(this).closest('tr'));
    });

    $(document).on('input', '.qty-input', function(){
        const max = parseFloat($(this).attr('max') || '0');
        const v = parseFloat($(this).val() || '0');
        if (Number.isFinite(max) && v > max) $(this).val(max);
    });
});
</script>
@endpush
