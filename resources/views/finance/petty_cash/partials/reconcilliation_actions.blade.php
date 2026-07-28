<div class="d-flex gap-1">

    @can('finance.petty_cash.reconciliation.approve')
    @if($row->status === 'submitted')
    <button class="btn btn-sm btn-success btn-approve-recon" data-id="{{ $row->id }}">
        Approve
    </button>

    <button class="btn btn-sm btn-danger btn-reject-recon" data-id="{{ $row->id }}">
        Reject
    </button>
    @endif
    @endcan

    @can('finance.petty_cash.reconciliation.post')
    @if($row->status === 'approved')
    <button class="btn btn-sm btn-dark btn-post-recon" data-id="{{ $row->id }}">
        Post
    </button>
    @endif
    @endcan

</div>