<div class="d-flex flex-wrap gap-1">
  @can('procurement.goods_receipts.view')
  <button type="button"
          class="btn btn-sm btn-outline-info btn-view-grn"
          data-id="{{ $row->id }}">
    <i class="fas fa-eye"></i> View
  </button>
  @endcan

  @if($row->status === 'draft')
    @can('procurement.goods_receipts.edit')
    <button type="button"
            class="btn btn-sm btn-outline-primary btn-edit-grn"
            data-id="{{ $row->id }}">
      <i class="fas fa-edit"></i> Edit
    </button>
    @endcan

    @can('procurement.goods_receipts.approve')
    <button type="button"
            class="btn btn-sm btn-outline-success btn-approve-grn"
            data-id="{{ $row->id }}">
      <i class="fas fa-check"></i> Approve
    </button>
    @endcan

    @can('procurement.goods_receipts.delete')
    <button type="button"
            class="btn btn-sm btn-outline-danger btn-del-grn"
            data-id="{{ $row->id }}">
      <i class="fas fa-trash"></i> Delete
    </button>
    @endcan

    @can('procurement.goods_receipts.cancel')
    <button type="button"
            class="btn btn-sm btn-outline-dark btn-cancel-grn"
            data-id="{{ $row->id }}">
      <i class="fas fa-ban"></i> Cancel
    </button>
    @endcan
  @endif

  @if($row->status === 'approved')
    @can('procurement.goods_receipts.post')
    <button type="button"
            class="btn btn-sm btn-success btn-post-grn"
            data-id="{{ $row->id }}">
      <i class="fas fa-boxes"></i> Post
    </button>
    @endcan

    @can('procurement.goods_receipts.cancel')
    <button type="button"
            class="btn btn-sm btn-outline-dark btn-cancel-grn"
            data-id="{{ $row->id }}">
      <i class="fas fa-ban"></i> Cancel
    </button>
    @endcan
  @endif

  @if($row->status === 'posted')
    <a href="{{ url('admin/procurement/goods-receipts/'.$row->id.'/pdf') }}"
       target="_blank"
       class="btn btn-sm btn-outline-danger">
      <i class="fas fa-file-pdf"></i> PDF
    </a>
  @endif
</div>