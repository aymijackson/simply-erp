{{-- 
    Expected in BomController@datatable

    @var  \App\Models\Production\Bom  $b
--}}
<div class="btn-group btn-group-sm" role="group">
    {{-- Edit (opens modal via JS) --}}
    <button type="button"
            class="btn btn-warning edit-btn"
            data-id="{{ $b->id }}"
            title="Edit">
        <i class="fas fa-edit"></i>
    </button>

    {{-- Delete (sweet-alert confirmation in JS) --}}
    <button type="button"
            class="btn btn-danger delete-btn"
            data-id="{{ $b->id }}"
            title="Delete">
        <i class="fas fa-trash"></i>
    </button>

    {{-- View / Print --}}
    <a href="{{ route('admin.production.boms.show', $b) }}"
       class="btn btn-info"
       title="Show / Print">
        <i class="fas fa-eye"></i>
    </a>
</div>
