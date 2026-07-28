<div class="d-flex gap-1 flex-wrap">

    @can('finance.petty_cash.view')
    <a href="{{ route('admin.finance.petty_cash.show', $row->id) }}"
       class="btn btn-sm btn-outline-primary"
       title="View Transaction">
        <i class="fas fa-eye"></i>
    </a>
    @endcan

    @can('finance.petty_cash.edit')
    @if(!in_array($row->status, ['posted', 'cancelled']))
    <button type="button"
            class="btn btn-sm btn-outline-warning btn-edit-transaction"
            data-id="{{ $row->id }}"
            title="Edit Transaction">
        <i class="fas fa-edit"></i>
    </button>
    @endif
    @endcan

    @can('finance.petty_cash.approve')
    @if($row->status === 'pending')
    <button type="button"
            class="btn btn-sm btn-outline-success btn-approve"
            data-id="{{ $row->id }}"
            title="Approve Transaction">
        <i class="fas fa-check"></i>
    </button>

    <button type="button"
            class="btn btn-sm btn-outline-danger btn-reject"
            data-id="{{ $row->id }}"
            title="Reject Transaction">
        <i class="fas fa-times"></i>
    </button>
    @endif
    @endcan

    @can('finance.petty_cash.post')
    @if($row->status === 'approved')
    <button type="button"
            class="btn btn-sm btn-outline-dark btn-post"
            data-id="{{ $row->id }}"
            title="Post Transaction">
        <i class="fas fa-upload"></i>
    </button>
    @endif
    @endcan

    @can('finance.petty_cash.delete')
    @if(!in_array($row->status, ['posted']))
    <button type="button"
            class="btn btn-sm btn-outline-danger btn-delete"
            data-id="{{ $row->id }}"
            title="Delete Transaction">
        <i class="fas fa-trash"></i>
    </button>
    @endif
    @endcan

    @can('finance.petty_cash.print')
    <a href="{{ route('admin.finance.petty_cash.voucher', $row->id) }}"
       target="_blank"
       class="btn btn-sm btn-outline-secondary"
       title="Print Voucher">
        <i class="fas fa-print"></i>
    </a>
    @endcan

    @can('documents.view.index')
    <a href="{{ route('admin.finance.petty_cash.show', $row->id) }}#documents"
       class="btn btn-sm btn-outline-info"
       title="View Documents">
        <i class="fas fa-folder-open"></i>
    </a>
    @endcan

</div>