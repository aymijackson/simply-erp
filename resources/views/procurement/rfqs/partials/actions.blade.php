@php
  $status = strtolower($json['status'] ?? 'draft');
  $isDraft = $status === 'draft';
  $isSent = $status === 'sent';
  $isClosed = $status === 'closed';
@endphp

<div class="btn-group btn-group-sm">
  @can('procurement.rfqs.view')
    <button class="btn btn-outline-secondary btn-view-rfq" data-id="{{ $json['id'] }}" title="View">
      <i class="fas fa-eye"></i>
    </button>

    <a href="{{ route('admin.procurement.rfqs.pdf', $json['id']) }}"
       class="btn btn-outline-dark"
       target="_blank"
       title="Download PDF">
      <i class="fas fa-file-pdf"></i>
    </a>
  @endcan

  @can('procurement.rfqs.edit')
    @if($isDraft)
      <button class="btn btn-outline-primary btn-edit-rfq" data-id="{{ $json['id'] }}" title="Edit">
        <i class="fas fa-edit"></i>
      </button>
    @endif
  @endcan

  @can('procurement.rfqs.send')
    @if($isDraft)
      <button class="btn btn-outline-info btn-send-rfq" data-id="{{ $json['id'] }}" title="Send">
        <i class="fas fa-paper-plane"></i>
      </button>
    @endif
  @endcan

  @can('procurement.rfqs.close')
    @if($isSent)
      <button class="btn btn-outline-warning btn-close-rfq" data-id="{{ $json['id'] }}" title="Close">
        <i class="fas fa-lock"></i>
      </button>
    @endif
  @endcan

  @can('procurement.rfqs.award')
    @if($isSent || $isClosed)
      <button class="btn btn-outline-success btn-award-rfq" data-id="{{ $json['id'] }}" title="Award">
        <i class="fas fa-check"></i>
      </button>
    @endif
  @endcan

  @can('procurement.rfqs.delete')
    @if($isDraft)
      <button class="btn btn-outline-danger btn-del-rfq" data-id="{{ $json['id'] }}" title="Delete">
        <i class="fas fa-trash"></i>
      </button>
    @endif
  @endcan
</div>