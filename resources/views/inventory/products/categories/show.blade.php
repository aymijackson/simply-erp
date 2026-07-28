@extends('layouts.master')
@section('title', 'Category · '.$category->name)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
@endpush

@section('content')
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 text-primary">Category — {{ $category->name }}</h1>
    <a href="{{ url('admin/inventory/categories') }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i> Back
    </a>
  </div>

  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Overview</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-products" type="button">Products</button>
    </li>
  </ul>

  <div class="tab-content">
    {{-- Overview --}}
    <div class="tab-pane fade show active" id="tab-overview">
      <div class="card shadow-sm">
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-md-2">Name</dt>
            <dd class="col-md-10">{{ $category->name }}</dd>

            <dt class="col-md-2">Description</dt>
            <dd class="col-md-10">{{ $category->description ?? '—' }}</dd>

            <dt class="col-md-2">Products Count</dt>
            <dd class="col-md-10">{{ number_format($category->products->count()) }}</dd>
          </dl>
        </div>
      </div>
    </div>

    {{-- Products --}}
    <div class="tab-pane fade" id="tab-products">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Products in this Category</h5>
        <button class="btn btn-primary" id="addProductsBtn">
          <i class="fas fa-plus me-1"></i> Add Products
        </button>
      </div>

      <div class="card shadow-sm">
        <div class="card-body table-responsive">
          <table class="table table-bordered align-middle mb-0" id="catProductsTbl">
            <thead class="table-light">
              <tr>
                <th style="width:15%">Code</th>
                <th>Name</th>
                <th style="width:10%">Price</th>
                <th style="width:10%" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($category->products as $p)
                <tr data-id="{{ $p->id }}">
                  <td>{{ $p->product_code }}</td>
                  <td>{{ $p->product_name }}</td>
                  <td>{{ is_null($p->product_price) ? '—' : number_format($p->product_price,2) }}</td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger detach-btn" data-id="{{ $p->id }}">
                      <i class="fas fa-unlink me-1"></i> Detach
                    </button>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No products yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- Add products modal --}}
<div class="modal fade" id="attachModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="attachForm" class="modal-content">
      @csrf
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Add Products to “{{ $category->name }}”</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Products</label>
        <select id="productPicker" name="product_ids[]" class="form-select" multiple required></select>
        <div class="form-text">Search by code or name. You can add multiple at once.</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" type="submit">Attach</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function(){
  const CSRF   = @json(csrf_token());
  const CAT_ID = {{ $category->id }};

  // Open modal
  const attachModal = new bootstrap.Modal('#attachModal');
  document.getElementById('addProductsBtn').addEventListener('click', () => attachModal.show());

  // Build exclude list (already attached)
  const exclude = @json($category->products->pluck('id')->values());

  // Select2 remote for products
  $('#productPicker').select2({
    ajax: {
      url: "{{ route('admin.inventory.products.select2') }}",
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term, exclude }),
      processResults: d => ({ results: d })
    },
    placeholder: '-- search products --',
    width: '100%',
    dropdownParent: $('#attachModal'),
    minimumInputLength: 0
  });

  // Attach submit
  $('#attachForm').on('submit', function(e){
    e.preventDefault();
    const ids = ($('#productPicker').val() || []).map(id => Number(id));
    if (!ids.length) return Swal.fire('Error','Select at least one product','error');

    $.post("{{ route('admin.inventory.products.categories.products.attach',$category) }}", {
      _token: CSRF,
      product_ids: ids
    })
    .done(res => {
      attachModal.hide();
      Swal.fire('Done', res.message || 'Attached', 'success').then(() => location.reload());
    })
    .fail(x => {
      Swal.fire('Error', x.responseJSON?.message || 'Attach failed', 'error');
    });
  });

  // Detach button
  $(document).on('click', '.detach-btn', function(){
    const pid = $(this).data('id');
    Swal.fire({title:'Detach this product?', icon:'warning', showCancelButton:true})
      .then(r=>{
        if(!r.isConfirmed) return;
        $.ajax({
          url: "{{ route('admin.inventory.products.categories.products.detach',[$category,':id']) }}".replace(':id', pid),
          type: 'POST',
          data: {_method:'DELETE', _token: CSRF}
        })
        .done(res => {
          Swal.fire('Detached', res.message || '', 'success');
          $('tr[data-id="'+pid+'"]').remove();
          if (!$('#catProductsTbl tbody tr').length) {
            $('#catProductsTbl tbody').html('<tr><td colspan="4" class="text-center text-muted py-3">No products yet.</td></tr>');
          }
        })
        .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Detach failed', 'error'));
      });
  });
})();
</script>
@endpush
