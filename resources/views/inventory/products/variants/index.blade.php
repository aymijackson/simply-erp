@extends('layouts.master')

@section('title', 'Manage Product Variants')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">Product Variants <small class="text-muted">Inventory</small></h1>
        <div>
            <button class="btn btn-danger me-2 d-none" id="deleteSelectedBtn">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addVariantBtn">
                <i class="fas fa-plus me-1"></i> Add Product Variant
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="variantTable" class="table table-bordered w-100">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="selectAllVariants"></th>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Attributes</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end">Re-Order Point</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ─────────────────────────────────────────  Modal  ───────────────────────────────────────── --}}
<div class="modal fade" id="variantModal" tabindex="-1" aria-labelledby="variantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="variantForm" class="modal-content">
            @csrf
            <input type="hidden" id="variantId">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="variantModalLabel">Add Variant</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">

                <div class="col-md-12">
                    <label class="form-label">Product *</label>
                    <select id="product_id" name="product_id" class="form-control" required>
                        <option value="">-- Select Product --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->product_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">SKU *</label>
                    <input id="sku" name="sku" type="text" class="form-control" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Product *</label>
                    <select id="item_type" name="item_type" class="form-control" required>
                        <option value="">-- Select Item Type --</option>
                        <option value="raw">Raw Material</option>
                        <option value="wip">Work In Progress</option>
                        <option value="fg">Finished Goods</option>
                        <option value="tool">Tools</option>
                        <option value="service">Service</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Price</label>
                    <input id="price" name="price" step="0.01" type="number" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Stock Quantity *</label>
                    <input id="stock_quantity" name="stock_quantity" type="number" min="0" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Re-Order Point </label>
                    <input id="reorder_point" name="reorder_point" type="number" min="0" class="form-control" required>
                </div>

                {{-- Dynamic attribute selects are injected here --}}
                <div id="attributeContainer" class="row g-3"></div>

            </div>
            <div class="modal-footer">
                <button type="button" id="cancelModalBtn" class="btn btn-secondary"  data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Variant</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Build attribute selects and preselect values.
 * @param {Number|String} productId
 * @param {Object|Array}  selected  Preferred: map {typeId: valueId}; also supports array of valueIds
 * @param {Object|Function} labelsOrDone  Either { valueId: "Label" } or the callback
 * @param {Function} done
 */
function loadAttributeSelects(productId, selected = {}, labelsOrDone = {}, done = ()=>{}) {
  if (typeof labelsOrDone === 'function') { done = labelsOrDone; labelsOrDone = {}; }

  const valueLabels = labelsOrDone || {};

  if (!productId) { $('#attributeContainer').empty(); return; }

  $('#attributeContainer').html('<p class="text-muted">Loading attributes…</p>');

  $.getJSON(`/admin/inventory/products/${productId}/attributes`, function(types){
    let html = '';
    types.forEach(t=>{
        html += `
  <div class="col-md-12 my-2">
    <label class="form-label">${t.type_name}</label>
    <select class="form-control attribute-select" name="attribute_values[${t.type_id}]" style="width:100%">
      <option value="">-- Select ${t.type_name} --</option>
      ${t.values.map(v=>`<option value="${v.id}">${v.value}</option>`).join('')}
    </select>
  </div>`;
    });
    $('#attributeContainer').html(html);

    // Init Select2 BEFORE setting values
    $('.attribute-select').select2({ width:'100%', dropdownParent: $('#variantModal') });

    // Normalize "selected" to a map {typeId: valueId}
    let map = {};
    if (Array.isArray(selected)) {
      // If array of valueIds: pick first match per select
      const arr = selected.map(String);
      $('#attributeContainer select').each(function(){
        const opts = $(this).find('option').map((_,o)=>o.value).get();
        const match = arr.find(v => opts.includes(v));
        if (match) {
          const typeId = this.name.match(/\[(\d+)\]/)?.[1];
          if (typeId) map[typeId] = match;
        }
      });
    } else if (selected && typeof selected === 'object') {
      Object.keys(selected).forEach(k => map[String(k)] = String(selected[k]));
    }

    // Preselect (inject option if missing so Select2 shows it)
    $('#attributeContainer select').each(function(){
      const typeId = this.name.match(/\[(\d+)\]/)?.[1];
      if (!typeId) return;
      const valId = map[typeId];
      if (!valId) return;

      const $sel = $(this);
      if ($sel.find(`option[value="${valId}"]`).length === 0) {
        const label = valueLabels[valId] || '— current —';
        $sel.append(new Option(label, valId, false, false));
      }
      $sel.val(valId).trigger('change');
    });

    done();
  });
}



/** Reset modal form */
function resetForm(){
  $('#variantForm')[0].reset();
  $('#variantId').val('');
  $('#attributeContainer').empty();
}

/** Fill modal for EDIT */
function fillModal(res){
  const data = res.data ?? res;

  // reset & header
  $('#variantForm')[0].reset();
  $('#variantId').val(data.id);
  $('#variantModalLabel').text('Edit Variant');

  // simple fields
  $('#product_id').val(String(data.product_id || ''));
  $('#sku').val(data.sku || '');
  $('#price').val(data.price ?? '');
  $('#stock_quantity').val(data.stock_quantity ?? '');
  $('#reorder_point').val(data.reorder_point ?? '');
  $('#item_type').val(data.item_type || data.type || '');

  // Build selection maps from selected_attrs
  const selectedAttrs = Array.isArray(data.selected_attrs) ? data.selected_attrs : [];
  const selectedMap   = {};   // { type_id: value_id }
  const labelsById    = {};   // { value_id: text } for nicer injected labels
  selectedAttrs.forEach(a => {
    if (a && a.type_id != null && a.value_id != null) {
      selectedMap[String(a.type_id)] = String(a.value_id);
      if (a.value) labelsById[String(a.value_id)] = a.value;
    }
  });

  // Fallbacks (in case your API returns other shapes sometimes)
  if (!Object.keys(selectedMap).length && Array.isArray(data.attribute_values)) {
    data.attribute_values.forEach(v => {
      const t = v.attribute_type_id ?? v.type_id ?? v.attribute_type?.id;
      if (t && v.id) selectedMap[String(t)] = String(v.id);
    });
  }
  if (!Object.keys(selectedMap).length && data.selected_attribute_value_ids) {
    Object.entries(data.selected_attribute_value_ids).forEach(([k,v])=>{
      selectedMap[String(k)] = String(v);
    });
  }

  loadAttributeSelects(
    data.product_id,
    selectedMap,     // <- map of { typeId: valueId }
    labelsById,      // <- valueId -> label (e.g. Grey)
    () => {
      // make sure Select2 UI reflects the selection
      $('#attributeContainer select').each(function(){ $(this).trigger('change'); });
    }
  );

  new bootstrap.Modal('#variantModal').show();
}


$(function(){
  // DataTable
  const table = $('#variantTable').DataTable({
    serverSide:true, responsive:true,
    ajax : "{{ route('admin.inventory.products.variants.datatable') }}",
    columns:[
      {data:'checkbox', orderable:false, searchable:false},
      {data:'sku'},
      {data:'product_name'},
      {data:'type'},
      {data:'attributes'},
      {data:'price', className:'text-end'},
      {data:'stock_quantity', className:'text-end'},
      {data:'reorder_point', className:'text-end'},
      {data:'action', orderable:false, searchable:false, className:'text-end'},
    ],
  });

  // Delegated EDIT (works in responsive child rows)
  $('#variantTable').on('click', '.edit-variant', function(){
    const id = $(this).data('id');
    if (!id) return;
    $.getJSON(`/admin/inventory/products/variants/${id}`)
      .done(fillModal)
      .fail(()=> Swal.fire('Error','Failed to fetch variant','error'));
  });

  // CREATE
  $('#addVariantBtn').on('click', ()=>{
    resetForm();
    $('#variantModalLabel').text('Add Variant');
    const pid = $('#product_id').val();
    if (pid) loadAttributeSelects(pid, {}, ()=> new bootstrap.Modal('#variantModal').show());
    else     new bootstrap.Modal('#variantModal').show();
  });

  // Change product → reload attribute options
  $('#product_id').on('change', function(){
    loadAttributeSelects(this.value);
  });

  // Save (create/update)
  $('#variantForm').on('submit', function(e){
    e.preventDefault();
    const id  = $('#variantId').val();
    const url = id ? `/admin/inventory/products/variants/${id}`
                   : `{{ route('admin.inventory.products.variants.store') }}`;
    const data = $(this).serialize() + (id ? '&_method=PUT' : '');
    $.post(url, data)
      .done(r=>{
        const inst = bootstrap.Modal.getInstance(document.getElementById('variantModal'));
        if (inst) inst.hide();
        table.ajax.reload(null,false);
        Swal.fire('Success', r.message || 'Saved', 'success');
      })
      .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Save failed', 'error'));
  });
});
</script>

@endpush
