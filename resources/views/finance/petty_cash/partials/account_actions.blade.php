<div class="d-flex gap-1">
    @can('finance.petty_cash.view')
    <a href="{{ route('admin.finance.petty_cash.accounts.show', $row->id) }}"
       class="btn btn-sm btn-outline-primary"
       title="View">
        <i class="fas fa-eye"></i>
    </a>
    @endcan

    @can('finance.petty_cash.accounts.manage')
    <button type="button"
            class="btn btn-sm btn-outline-warning btn-edit-account"
            data-id="{{ $row->id }}"
            title="Edit">
        <i class="fas fa-edit"></i>
    </button>

    <button type="button"
            class="btn btn-sm btn-outline-danger btn-delete-account"
            data-id="{{ $row->id }}"
            title="Delete">
        <i class="fas fa-trash"></i>
    </button>
    @endcan
</div>