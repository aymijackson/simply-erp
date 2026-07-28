@php
  $status = strtolower($json['status'] ?? 'draft');
  $isDraft = $status === 'draft';
  $isSubmitted = $status === 'submitted';
@endphp

<div class="btn-group btn-group-sm">
  @can('procurement.requisitions.view')
    <button class="btn btn-outline-secondary btn-view-req" data-id="{{ $json['id'] }}" title="View">
      <i class="fas fa-eye"></i>
    </button>

    <a href="{{ route('admin.procurement.purchase_requisitions.pdf', $json['id']) }}"
       class="btn btn-outline-dark"
       target="_blank"
       title="Download PDF">
      <i class="fas fa-file-pdf"></i>
    </a>
  @endcan

  @can('procurement.requisitions.edit')
    @if($isDraft || $status === 'rejected')
      <button class="btn btn-outline-primary btn-edit-req" data-id="{{ $json['id'] }}" title="Edit">
        <i class="fas fa-edit"></i>
      </button>
    @endif
  @endcan

  @can('procurement.requisitions.submit')
    @if($isDraft)
      <button class="btn btn-outline-info btn-submit-req" data-id="{{ $json['id'] }}" title="Submit">
        <i class="fas fa-paper-plane"></i>
      </button>
    @endif
  @endcan

  @can('procurement.requisitions.approve')
    @if($isSubmitted)
      <button class="btn btn-outline-success btn-approve-req" data-id="{{ $json['id'] }}" title="Approve">
        <i class="fas fa-check"></i>
      </button>
      <button class="btn btn-outline-warning btn-reject-req" data-id="{{ $json['id'] }}" title="Reject">
        <i class="fas fa-times"></i>
      </button>
    @endif
  @endcan

  @can('procurement.requisitions.delete')
    @if($isDraft)
      <button class="btn btn-outline-danger btn-del-req" data-id="{{ $json['id'] }}" title="Delete">
        <i class="fas fa-trash"></i>
      </button>
    @endif
  @endcan
</div>