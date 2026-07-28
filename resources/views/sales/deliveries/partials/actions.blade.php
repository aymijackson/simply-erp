<div class="btn-group" role="group">
    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.sales.deliveries.show', $d->id) }}">
        <i class="fas fa-eye"></i>
    </a>

    @if($d->status === 'draft')
    <a class="btn btn-sm btn-primary" href="{{ route('admin.sales.deliveries.edit', $d->id) }}">
        <i class="fas fa-edit"></i>
    </a>
    <button class="btn btn-sm btn-danger" onclick="deleteDelivery({{ $d->id }})">
        <i class="fas fa-trash"></i>
    </button>
    @elseif($d->status == 'posted')
    <a href="{{ route('admin.sales.deliveries.pdf', $d->id) }}"
       target="_blank"
       class="dropdown-item">
       <i class="fas fa-file-pdf text-danger mr-1"></i> Print PDF
    </a>
    @endif
</div>
