@extends('layouts.master')

@section('title', 'Create Sales Order')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Create Sales Order</h1>
            <p class="text-muted mb-0">Sales / Orders</p>
        </div>

        <a href="{{ route('admin.sales.orders.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('admin.sales.orders.store') }}" id="orderForm">
        @csrf

        <div class="card shadow mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-file-signature mr-1"></i> Order Details
                </h6>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label mb-1">Customer</label>
                        <select class="form-control" id="customer_id" name="customer_id" required style="width:100%;">
                            <option value="">-- Select Customer --</option>
                        </select>
                        @error('customer_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-1">Order Date</label>
                        <input type="date" name="order_date" class="form-control"
                               value="{{ old('order_date', date('Y-m-d')) }}" required>
                        @error('order_date') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-1">Currency</label>
                        <input type="text" name="currency_code" class="form-control"
                               value="{{ old('currency_code', 'USD') }}" maxlength="3">
                        @error('currency_code') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label mb-1">Reference (optional)</label>
                        <input type="text" name="reference" class="form-control"
                               value="{{ old('reference') }}" maxlength="100">
                        @error('reference') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label mb-1">Remarks (optional)</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                        @error('remarks') <small class="text-danger d-block">{{ $message }}</small> @enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-list mr-1"></i> Order Lines
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
                                <th style="width:40%;">Variant</th>
                                <th style="width:25%;">Description</th>
                                <th style="width:12%;">Qty</th>
                                <th style="width:13%;">Unit Price</th>
                                <th class="text-center" style="width:10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="linesBody"></tbody>
                    </table>
                </div>

                @error('lines') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>

            <div class="card-footer bg-white d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-save mr-1"></i> Save Draft
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
let lineIndex = 0;

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
                if (data.data) {
                    return {
                        results: data.data.map(x => ({
                            id: x.id,
                            text: x.text ?? x.name ?? ('Customer #' + x.id),
                        })),
                        pagination: { more: !!data.next_page_url }
                    };
                }
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
            url: "{{ route('admin.inventory.products.variants.fetch') ?? url('admin/inventory/products/variants/fetch') }}",
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
                <input type="number" step="0.01" min="0.01" class="form-control" name="lines[${idx}][qty_ordered]" value="1" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control" name="lines[${idx}][unit_price]" value="0" required>
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
    addLineRow(); // start with 1 line
    $('#addLineBtn').on('click', addLineRow);
});
</script>
@endpush
