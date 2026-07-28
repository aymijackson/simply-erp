@extends('layouts.master')
@section('title','Confirm Delivery')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Confirm Delivery #{{ $delivery->id }}</h1>
            <p class="text-muted mb-0">{{ $delivery->customer->name ?? '—' }} • Ship {{ optional($delivery->ship_date)->format('d M Y') }}</p>
        </div>
        <a href="{{ route('admin.sales.deliveries.driver.index') }}" class="btn btn-outline-secondary">
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

    <form method="POST" action="{{ route('admin.sales.deliveries.driver.update', $delivery->id) }}">
        @csrf
        @method('PUT')

        <div class="card shadow">
            <div class="card-header bg-white">
                <h6 class="mb-0 text-primary"><i class="fas fa-clipboard-check mr-1"></i> Actual Delivered</h6>
            </div>

            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Item</th>
                            <th>Store</th>
                            <th class="text-center">Planned</th>
                            <th style="width:18%;">Actual Delivered</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($delivery->lines as $i => $line)
                        <tr>
                            <td>{{ $line->variant_label ?? ('Variant #'.$line->product_variant_id) }}</td>
                            <td>{{ $line->store_name ?? '—' }}</td>
                            <td class="text-center">{{ number_format($line->qty_to_deliver,0) }}</td>
                            <td>
                                <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $line->id }}">
                                <input type="number" step="1" min="0"
                                       max="{{ $line->qty_to_deliver }}"
                                       class="form-control actual-input"
                                       name="lines[{{ $i }}][qty_delivered_actual]"
                                       value="{{ old("lines.$i.qty_delivered_actual", $line->qty_delivered_actual) }}">
                                <small class="text-muted">Max: {{ number_format($line->qty_to_deliver,0) }}</small>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white d-flex justify-content-end">
                <button class="btn btn-success" type="submit">
                    <i class="fas fa-check mr-1"></i> Submit Delivery Confirmation
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('input', function(e){
    if (!e.target.classList.contains('actual-input')) return;
    const max = parseFloat(e.target.getAttribute('max') || '0');
    const v = parseFloat(e.target.value || '0');
    if (Number.isFinite(max) && v > max) e.target.value = max;
});
</script>
@endpush
