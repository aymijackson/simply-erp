@php   /** @var \Modules\Inventory\Models\StockIssue $r */  @endphp

<div class="btn-group btn-group-sm" role="group">
   {{-- edit (only while draft) --}}
   @if($r->status === 'draft')
      <button class="btn btn-warning edit-btn"
              data-json='@json($r->load("lines.variant"))'>
          <i class="fas fa-edit"></i>
      </button>
   @endif

   {{-- approve (draft → approved) --}}
   @if($r->status === 'draft')
      <button class="btn btn-info approve-btn">
          <i class="fas fa-check-circle"></i>
      </button>
   @endif

   {{-- post (approved → posted) --}}
   @if($r->status === 'approved')
      <button class="btn btn-success post-btn" data-id="{{ $r->id }}">
          <i class="fas fa-paper-plane"></i>
      </button>
   @endif

   {{-- view / print --}}
   <a href="{{ route('admin.inventory.stock_issues.show', $r) }}"
      class="btn btn-info">
      <i class="fas fa-eye"></i>
   </a>
</div>
