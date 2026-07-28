<div class="d-flex gap-1 flex-wrap">

    @can('finance.petty_cash.reconciliation.view')
    <button type="button"
            class="btn btn-sm btn-outline-primary btn-view-reconciliation"
            data-id="{{ $row->id }}"
            title="View">
        <i class="fas fa-eye"></i>
    </button>
    @endcan

    @can('finance.petty_cash.reconciliation.edit')
    @if(in_array($row->status, ['draft', 'rejected']))
    <button type="button"
            class="btn btn-sm btn-outline-warning btn-edit-reconciliation"
            data-id="{{ $row->id }}"
            title="Edit">
        <i class="fas fa-edit"></i>
    </button>
    @endif
    @endcan

    @can('finance.petty_cash.reconciliation.submit')
    @if(in_array($row->status, ['draft', 'rejected']))
    <button type="button"
            class="btn btn-sm btn-outline-info btn-submit-reconciliation"
            data-id="{{ $row->id }}"
            title="Submit">
        <i class="fas fa-paper-plane"></i>
    </button>
    @endif
    @endcan

    @can('finance.petty_cash.reconciliation.approve')
    @if($row->status === 'submitted')
    <button type="button"
            class="btn btn-sm btn-outline-success btn-approve-reconciliation"
            data-id="{{ $row->id }}"
            title="Approve">
        <i class="fas fa-check"></i>
    </button>

    <button type="button"
            class="btn btn-sm btn-outline-danger btn-reject-reconciliation"
            data-id="{{ $row->id }}"
            title="Reject">
        <i class="fas fa-times"></i>
    </button>
    @endif
    @endcan

    @can('finance.petty_cash.reconciliation.delete')
    @if(in_array($row->status, ['draft', 'rejected']))
    <button type="button"
            class="btn btn-sm btn-outline-danger btn-delete-reconciliation"
            data-id="{{ $row->id }}"
            title="Delete">
        <i class="fas fa-trash"></i>
    </button>
    @endif
    @endcan

</div>