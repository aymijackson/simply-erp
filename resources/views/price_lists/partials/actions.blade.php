<div class="btn-group" role="group">
    @can('sales.price_lists.view')
    <a href="{{ route('admin.sales.price-lists.show', $r->id) }}" class="btn btn-xs btn-outline-primary" title="View">
        <i class="fas fa-eye"></i>
    </a>
    @endcan

    @can('sales.price_lists.edit')
    <button type="button" class="btn btn-xs btn-warning btn-edit-pl"
            data-id="{{ $r->id }}" data-record="{{ json_encode($r->toArray()) }}" title="Edit">
        <i class="fas fa-pencil-alt"></i>
    </button>
    @endcan

    @can('sales.price_lists.delete')
    <button type="button" class="btn btn-xs btn-danger btn-delete-pl" data-id="{{ $r->id }}" title="Delete">
        <i class="fas fa-trash"></i>
    </button>
    @endcan
</div>
