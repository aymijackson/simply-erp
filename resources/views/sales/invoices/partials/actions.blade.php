<div class="btn-group" role="group">
    @can('sales.invoices.view')
    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.sales.invoices.show', $inv->id) }}">
        <i class="fas fa-eye"></i>
    </a>
    @endcan

    @can('sales.invoices.edit')
    @if($inv->status === 'draft')
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.sales.invoices.edit', $inv->id) }}">
        <i class="fas fa-edit"></i>
    </a>
    @endif
    @endcan

    @can('sales.invoices.view')
    <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.sales.invoices.pdf', $inv->id) }}" target="_blank" title="Print PDF">
        <i class="fas fa-print"></i>
    </a>
    @endcan

    @can('sales.invoices.delete')
    @if($inv->status === 'draft')
    <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteInvoice({{ $inv->id }})">
        <i class="fas fa-trash"></i>
    </button>
    @endif
    @endcan
</div>
