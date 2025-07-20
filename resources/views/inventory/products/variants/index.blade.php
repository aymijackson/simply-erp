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
                <button type="button" id="cancelModalBtn" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-success">Save Variant</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<link  href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ───── global CSRF header for jQuery Ajax ───── */
$.ajaxSetup({ headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }});

/* ───── Attribute select builder ───── */
function loadAttributeSelects(productId, selectedIds = [], callback = () => {}) {
    if (!productId) {
        $('#attributeContainer').empty();
        return;
    }

    $('#attributeContainer').html('<p class="text-muted">Loading attributes…</p>');

    $.getJSON(`/admin/inventory/products/${productId}/attributes`, function (types) {
        let html = '';
        types.forEach(t => {
            html += `
                <div class="col-md-6">
                    <label class="form-label">${t.type_name}</label>
                    <select class="form-control attribute-select" name="attribute_values[${t.type_id}]" data-type-id="${t.type_id}">
                        <option value="">-- Select ${t.type_name} --</option>`;
            t.values.forEach(v => {
                html += `<option value="${v.id}">${v.value}</option>`;
            }); 
            html += `</select></div>`;
        });

        $('#attributeContainer').html(html);

// Apply selected values
selectedIds.forEach(id => { 
    $(`#attributeContainer select option[value="${id}"]`).prop('selected', true);
});

// Initialize Select2 if used
$('.attribute-select').select2({
    width: '100%',
    dropdownParent: $('#variantModal')
});

callback();
    });
}


/* ───── Reset modal form ───── */
function resetForm(){
    $('#variantForm')[0].reset();
    $('#variantId').val('');
    $('#attributeContainer').empty();
}

/* ───── Fill modal (edit) ───── */
function fillModal(res){
    const data = res.data ?? res;
    resetForm();

    $('#variantModalLabel').text('Edit Variant');
    $('#variantId').val(data.id);
    $('#product_id').val(data.product_id);
    $('#sku').val(data.sku);
    $('#price').val(data.price);
    $('#stock_quantity').val(data.stock_quantity);
    $('#reorder_point').val(data.reorder_point);

    const selected = (data.attribute_values || []).map(v => String(v.id));
    loadAttributeSelects(data.product_id, selected);

    new bootstrap.Modal('#variantModal').show();
}


$(function () {

    /* ───── DataTable ───── */
    const table = $('#variantTable').DataTable({
        responsive:true,
        serverSide:true,
        ajax : "{{ route('admin.inventory.products.variants.datatable') }}",
        columns:[
            {data:'checkbox', orderable:false, searchable:false},
            {data:'sku'},
            {data:'product_name'},
            {data:'attributes'},  
            {data:'price', className:'text-end'},
            {data:'stock_quantity', className:'text-end'},
            {data:'reorder_point', className:'text-end'},
            {data:'action', orderable:false, searchable:false, className:'text-end'},
        ],
        drawCallback:function(){
            $('.edit-btn').on('click', e=>{
                $.getJSON(`/admin/inventory/products/variants/${$(e.currentTarget).data('id')}`, fillModal);
            });
            $('.delete-btn').on('click', deleteOne);
        }
    });

    /* ───── Open modal: create ───── */
    $('#addVariantBtn').click(()=>{
        resetForm();
        $('#variantModalLabel').text('Add Variant');
        const currentProduct = $('#product_id').val();
        if(currentProduct) loadAttributeSelects(currentProduct);
        new bootstrap.Modal('#variantModal').show();
    });

    /* load attributes when product changes */
    $('#product_id').on('change', function(){ loadAttributeSelects(this.value); });

    $('#cancelModalBtn').click(()=>bootstrap.Modal.getInstance('#variantModal').hide());

    /* ───── save (create / update) ───── */
    $('#variantForm').submit(function(e){
        e.preventDefault();
        const id  = $('#variantId').val();
        const url = id ? `/admin/inventory/products/variants/${id}` 
                       : `{{ route('admin.inventory.products.variants.store') }}`;
        const formData = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.post(url, formData)
         .done(r=>{
             bootstrap.Modal.getInstance('#variantModal').hide();
             table.ajax.reload(null,false);
             Swal.fire('Success', r.message,'success');
         })
         .fail(x=>Swal.fire('Error', x.responseJSON?.message || 'Save failed','error'));
    });

    /* ───── delete single ───── */
    function deleteOne(){
        const id = $(this).data('id');
        Swal.fire({title:'Delete?', icon:'warning', showCancelButton:true})
            .then(res=>{
                if(res.isConfirmed){
                    $.post(`/admin/inventory/products/variants/${id}`,
                           {_method:'DELETE'})
                     .done(()=>table.ajax.reload(null,false));
                }
            });
    }

});
</script>
@endpush
