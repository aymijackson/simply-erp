<div class="btn-group" role="group">
    @can('sales.orders.show')
    <a href="{{ route('admin.sales.orders.show', $r->id) }}" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-eye"></i>
    </a>
    @endcan

    @can('sales.orders.edit')
    @if($r->status == 'draft')
    <a href="{{ route('admin.sales.orders.edit', $r->id) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-edit"></i>
    </a>
    @endif
    @endcan

    @can('sales.orders.confirm')
    @if($r->status === 'draft')
    <button type="button"
            class="btn btn-sm btn-outline-success"
            onclick="confirmOrder({{ $r->id }})">
        <i class="fas fa-check"></i>
    </button>
    @else
    <button type="button"
            class="btn btn-sm btn-outline-danger"
            onclick="unconfirmOrder({{ $r->id }})">
        <i class="fas fa-times"></i>
    </button>
    @endif
    @endcan
</div>
