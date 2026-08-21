@php
  $status = strtolower($json['status'] ?? 'draft');
  $isDraft = $status === 'draft';
  $isApproved = $status === 'approved';
  $canClose = in_array($status, ['issued', 'partially_rcv', 'fully_rcv', 'partially_billed', 'billed'], true);
@endphp

<div class="btn-group btn-group-sm">
  @can('procurement.purchase_orders.view')
    <button class="btn btn-outline-secondary btn-view-po" data-id="{{ $json['id'] }}" title="View">
      <i class="fas fa-eye"></i>
    </button>
  @endcan

  @can('procurement.purchase_orders.pdf')
    <a href="{{ url('admin/procurement/purchase-orders/'.$json['id'].'/pdf') }}"
       class="btn btn-outline-dark"
       target="_blank"
       title="Download PDF">
      <i class="fas fa-file-pdf"></i>
    </a>
  @endcan

  @can('procurement.purchase_orders.edit')
    @if($isDraft || $isApproved)
      <button class="btn btn-outline-primary btn-edit-po" data-id="{{ $json['id'] }}" title="Edit">
        <i class="fas fa-edit"></i>
      </button>
    @endif
  @endcan

  @can('procurement.purchase_orders.approve')
    @if($isDraft)
      <button class="btn btn-outline-success btn-approve-po" data-id="{{ $json['id'] }}" title="Approve">
        <i class="fas fa-check"></i>
      </button>
    @endif
  @endcan

  @can('procurement.purchase_orders.issue')
    @if($isApproved)
      <button class="btn btn-outline-info btn-issue-po" data-id="{{ $json['id'] }}" title="Issue">
        <i class="fas fa-paper-plane"></i>
      </button>
    @endif
  @endcan

  @can('procurement.purchase_orders.close')
    @if($canClose)
      <button class="btn btn-outline-secondary btn-close-po" data-id="{{ $json['id'] }}" title="Close">
        <i class="fas fa-lock"></i>
      </button>
    @endif
  @endcan

  @can('procurement.purchase_orders.cancel')
    @if($isDraft || $isApproved)
      <button class="btn btn-outline-warning btn-cancel-po" data-id="{{ $json['id'] }}" title="Cancel">
        <i class="fas fa-ban"></i>
      </button>
    @endif
  @endcan

  @can('procurement.purchase_orders.delete')
    @if($isDraft)
      <button class="btn btn-outline-danger btn-del-po" data-id="{{ $json['id'] }}" title="Delete">
        <i class="fas fa-trash"></i>
      </button>
    @endif
  @endcan
</div>