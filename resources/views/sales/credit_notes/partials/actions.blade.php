<div class="btn-group" role="group">
    <a href="{{ route('admin.sales.credit-notes.show', $creditNote->id) }}"
       class="btn btn-sm btn-outline-dark" title="View">
        <i class="fas fa-eye"></i>
    </a>

    @if($creditNote->status === 'draft')
        <a href="{{ route('admin.sales.credit-notes.edit', $creditNote->id) }}"
           class="btn btn-sm btn-outline-primary" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
    @endif
</div>
