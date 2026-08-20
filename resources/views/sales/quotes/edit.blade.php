@extends('layouts.master')

@section('title', 'Edit Sales Quote')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Edit Sales Quote</h1>
            <p class="text-muted mb-0">Sales / Quotes</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.quotes.show', $quote->id) }}" class="btn btn-outline-primary">
                <i class="fas fa-eye mr-1"></i> View
            </a>
            <a href="{{ route('admin.sales.quotes.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    @if($quote->status === 'won')
        <div class="alert alert-info">This quote is <strong>won</strong> — you're editing it as part of the review step.</div>
    @endif

    <form method="POST" action="{{ route('admin.sales.quotes.update', $quote->id) }}" id="quoteForm">
        @csrf
        @method('PUT')

        <div class="card shadow mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-file-signature mr-1"></i> Quote Details
                </h6>
                <span class="badge bg-secondary">{{ strtoupper($quote->status) }}</span>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Quote No</label>
                        <input type="text" class="form-control" value="{{ $quote->quote_no }}" readonly>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label mb-1">Customer</label>
                        <select class="form-control" id="customer_id" name="customer_id" required style="width:100%;">
                            @if($quote->customer_id)
                                <option value="{{ $quote->customer_id }}" selected>
                                    {{ $quote->customer->name ?? ('Customer #'.$quote->customer_id) }}
                                </option>
                            @endif
                        </select>
                        @error('customer_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label mb-1">Quote Date</label>
                        <input type="date" name="quote_date" class="form-control"
                               value="{{ old('quote_date', optional($quote->quote_date)->format('Y-m-d')) }}" required>
                        @error('quote_date') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-1">Valid Until</label>
                        <input type="date" name="valid_until" class="form-control"
                               value="{{ old('valid_until', optional($quote->valid_until)->format('Y-m-d')) }}">
                        @error('valid_until') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-1">Currency</label>
                        <input type="text" name="currency_code" class="form-control"
                               value="{{ old('currency_code', $quote->currency_code) }}" maxlength="3">
                        @error('currency_code') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label mb-1">Reference (optional)</label>
                        <input type="text" name="reference" class="form-control"
                               value="{{ old('reference', $quote->reference) }}" maxlength="100">
                        @error('reference') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label mb-1">Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $quote->notes) }}</textarea>
                        @error('notes') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-list mr-1"></i> Quote Lines
                </h6>

                <button type="button" class="btn btn-sm btn-outline-primary" id="addLineBtn">
                    <i class="fas fa-plus mr-1"></i> Add Line
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="linesTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:26%;">Variant</th>
                                <th style="width:16%;">Description</th>
                                <th style="width:10%;">Qty</th>
                                <th style="width:12%;">Unit Price</th>
                                <th style="width:11%;">Disc %</th>
                                <th style="width:11%;">Tax %</th>
                                <th class="text-center" style="width:11%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="linesBody">
                            @php $idx = 0; @endphp
                            @foreach(($quote->lines ?? []) as $ln)
                                <tr data-row="{{ $idx }}">
                                    <td>
                                        <select class="form-control variant-select" name="lines[{{ $idx }}][product_variant_id]" required style="width:100%;">
                                            <option value="{{ $ln->product_variant_id }}" selected>
                                                {{ $ln->variant->product->product_name ?? 'Item' }} — {{ $ln->variant->sku ?? ('Variant #'.$ln->product_variant_id) }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="lines[{{ $idx }}][description]" value="{{ $ln->description }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" class="form-control" name="lines[{{ $idx }}][qty]" value="{{ (float)$ln->qty }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control" name="lines[{{ $idx }}][unit_price]" value="{{ (float)$ln->unit_price }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control" name="lines[{{ $idx }}][discount_percent]" value="{{ (float)$ln->discount_percent }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control" name="lines[{{ $idx }}][tax_rate]" value="{{ (float)$ln->tax_rate }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger removeLineBtn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @php $idx++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('lines') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>

            <div class="card-footer bg-white d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-save mr-1"></i> Update Quote
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
let lineIndex = {{ isset($idx) ? (int)$idx : 0 }};

function initCustomerSelect2(){
    const $el = $('#customer_id');
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Customer --',
        allowClear: true,
        width: '100%',
        ajax: {
            url: "{{ route('admin.customers.select2') }}",
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '', page: params.page || 1 }),
            processResults: (data) => {
                if (Array.isArray(data)) return { results: data };
                if (data.results) return data;
                return { results: [] };
            }
        }
    });
}

function initVariantSelect2($el){
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Variant --',
        allowClear: true,
        width: '100%',
        minimumInputLength: 1,
        ajax: {
            url: "{{ route('admin.inventory.products.variants.fetch') }}",
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '' }),
            processResults: (data) => ({ results: data || [] })
        }
    });
}

function addLineRow(){
    const idx = lineIndex++;

    const row = `
        <tr data-row="${idx}">
            <td>
                <select class="form-control variant-select" name="lines[${idx}][product_variant_id]" required style="width:100%;">
                    <option value="">-- Select Variant --</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control" name="lines[${idx}][description]" placeholder="optional">
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control" name="lines[${idx}][qty]" value="1" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control" name="lines[${idx}][unit_price]" value="0" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control" name="lines[${idx}][discount_percent]" value="0">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control" name="lines[${idx}][tax_rate]" value="0">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger removeLineBtn">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#linesBody').append(row);
    initVariantSelect2($(`tr[data-row="${idx}"] .variant-select`));
}

$(document).on('click', '.removeLineBtn', function(){
    $(this).closest('tr').remove();
});

$(document).ready(function(){
    initCustomerSelect2();
    $('.variant-select').each(function(){ initVariantSelect2($(this)); });
    $('#addLineBtn').on('click', addLineRow);
});
</script>
@endpush
