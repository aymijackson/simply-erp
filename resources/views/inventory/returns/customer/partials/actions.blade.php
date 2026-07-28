@php /** @var \Modules\Inventory\Models\StockReturn $r */ @endphp

<div class="btn-group btn-group-sm" role="group">
    {{-- edit (only while draft) --}}
    @if(($r->origin?->status ?? $r->status) === 'draft')
        <button class="btn btn-warning edit-btn" data-id="{{ $r->id }}">
            <i class="fas fa-edit"></i>
        </button>
    @endif

    {{-- approve --}}
    @if(($r->origin?->status ?? $r->status) === 'draft')
        <button class="btn btn-info approve-btn" data-id="{{ $r->id }}">
            <i class="fas fa-check-circle"></i>
        </button>
    @endif

    {{-- post --}}
    @if(($r->origin?->status ?? $r->status) === 'approved')
        <button class="btn btn-success post-btn" data-id="{{ $r->id }}">
            <i class="fas fa-paper-plane"></i>
        </button>
    @endif

    {{-- delete (draft only) --}}
    @if(($r->origin?->status ?? $r->status) === 'draft')
        <button class="btn btn-danger delete-btn" data-id="{{ $r->id }}">
            <i class="fas fa-trash"></i>
        </button>
    @endif
</div>
