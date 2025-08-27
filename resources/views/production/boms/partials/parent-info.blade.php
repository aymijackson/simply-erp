{{-- production/boms/partials/parent-info.blade.php --}}

<div class="card shadow-sm mb-4">
   <div class="card-body row g-3">

        {{-- Identifiers ---------------------------------------------------- --}}
        <div class="col-md-4">
            <small class="text-muted">BOM Code</small><br>
            <span class="fw-bold">{{ $bom->bom_code }}</span>
        </div>

        <div class="col-md-4">
            <small class="text-muted">FG Variant (SKU)</small><br>
            {{ $bom->product_variant->sku }}
        </div>

        <div class="col-md-4">
            <small class="text-muted">Product Name</small><br>
            {{ $bom->product_variant->product->product_name }}
        </div>

        {{-- Meta data ------------------------------------------------------ --}}

        <div class="col-md-4">
            <small class="text-muted">Yield Qty</small><br>
            {{ rtrim(rtrim(number_format($bom->yield_qty,4,'.',''),'0'),'.') }}
        </div>

        <div class="col-md-4 text-white">
            <small class="text-muted">Status</small><br>
            @if($bom->status === 'draft')
                <span class="badge bg-secondary">Draft</span>
            @else
                <span class="badge bg-success">Approved</span>
            @endif
        </div>

        <div class="col-md-4">
            <small class="text-muted">Created</small><br>
            {{ $bom->created_at->format('Y-m-d') }}
        </div>

        {{-- Optional description ------------------------------------------- --}}
        @if($bom->description)
        <div class="col-12">
            <small class="text-muted">Description</small><br>
            {{ $bom->description }}
        </div>
        @endif
   </div>
</div>
