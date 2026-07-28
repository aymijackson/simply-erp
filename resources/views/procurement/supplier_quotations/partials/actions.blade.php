@php
  $status = strtolower($json['status'] ?? 'draft');
  $isDraft = $status === 'draft';
  $isSubmitted = $status === 'submitted';
  $isReviewed = $status === 'reviewed';
@endphp

<div class="btn-group btn-group-sm">
  @can('procurement.supplier_quotations.view')
    <button class="btn btn-outline-secondary btn-view-quotation" data-id="{{ $json['id'] }}" title="View">
      <i class="fas fa-eye"></i>
    </button>
  @endcan

  @can('procurement.supplier_quotations.pdf')
    <a href="{{ url('admin/procurement/supplier-quotations/'.$json['id'].'/pdf') }}"
       class="btn btn-outline-dark"
       target="_blank"
       title="Download PDF">
      <i class="fas fa-file-pdf"></i>
    </a>
  @endcan

  @can('procurement.supplier_quotations.edit')
    @if($isDraft || $status === 'rejected')
      <button class="btn btn-outline-primary btn-edit-quotation" data-id="{{ $json['id'] }}" title="Edit">
        <i class="fas fa-edit"></i>
      </button>
    @endif
  @endcan

  @can('procurement.supplier_quotations.submit')
    @if($isDraft)
      <button class="btn btn-outline-info btn-submit-quotation" data-id="{{ $json['id'] }}" title="Submit">
        <i class="fas fa-paper-plane"></i>
      </button>
    @endif
  @endcan

  @can('procurement.supplier_quotations.review')
    @if($isSubmitted)
      <button class="btn btn-outline-primary btn-review-quotation" data-id="{{ $json['id'] }}" title="Review">
        <i class="fas fa-search"></i>
      </button>
    @endif
  @endcan

  @can('procurement.supplier_quotations.accept')
    @if($isReviewed)
      <button class="btn btn-outline-success btn-accept-quotation" data-id="{{ $json['id'] }}" title="Accept">
        <i class="fas fa-check"></i>
      </button>
    @endif
  @endcan

  @can('procurement.supplier_quotations.reject')
    @if($isReviewed)
      <button class="btn btn-outline-warning btn-reject-quotation" data-id="{{ $json['id'] }}" title="Reject">
        <i class="fas fa-times"></i>
      </button>
    @endif
  @endcan

  @can('procurement.supplier_quotations.delete')
    @if($isDraft)
      <button class="btn btn-outline-danger btn-del-quotation" data-id="{{ $json['id'] }}" title="Delete">
        <i class="fas fa-trash"></i>
      </button>
    @endif
  @endcan
</div>