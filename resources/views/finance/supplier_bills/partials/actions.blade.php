@php
  $id = $bill->id ?? $bill->bill_id ?? null;
  $status = $bill->status ?? 'draft';
@endphp

<div class="btn-group btn-group-sm" role="group">
  <button type="button"
    class="btn btn-outline-info btn-view-bill"
    data-id="{{ $id }}">
    <i class="fas fa-eye"></i> View
  </button>

  <a href="{{ url('admin/finance/supplier-bills/'.$id.'/pdf') }}"
     target="_blank"
     class="btn btn-outline-secondary">
    <i class="fas fa-file-pdf"></i> PDF
  </a>

  @if($status === 'draft')
    <button type="button"
      class="btn btn-outline-primary btn-edit-bill"
      data-json='@json($json)'>
      <i class="fas fa-edit"></i> Edit
    </button>

    <button type="button"
      class="btn btn-outline-success btn-post-bill"
      data-id="{{ $id }}">
      <i class="fas fa-check"></i> Post
    </button>

    <button type="button"
      class="btn btn-outline-danger btn-del-bill"
      data-id="{{ $id }}">
      <i class="fas fa-trash"></i> Delete
    </button>
  @else
    <button type="button" class="btn btn-outline-secondary" disabled>
      <i class="fas fa-lock"></i> Locked
    </button>

    @if(in_array($status, ['posted', 'part_paid', 'paid']))
      <button type="button"
        class="btn btn-outline-dark btn-void-bill"
        data-id="{{ $id }}">
        <i class="fas fa-ban"></i> Void
      </button>
    @endif
  @endif
</div>