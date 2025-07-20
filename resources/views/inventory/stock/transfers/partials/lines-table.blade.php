{{-- ----------------------------------------------
   Stock‑Transfer Lines (embedded in create/edit)
   ---------------------------------------------- --}}
<div class="card border-primary">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <strong>Transfer Lines</strong>

        {{-- Add‑line button (JS appends an empty row) --}}
        <button type="button" id="addLineBtn" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Add Line
        </button>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="lineTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:45%">Variant (SKU – Name)</th>
                        <th style="width:15%" class="text-end">Qty</th>
                        <th style="width:15%" class="text-end">Unit Cost*</th>
                        <th style="width: 5%"></th>
                    </tr>
                </thead>
                <tbody id="lineTbody">
                    @isset($transfer)
                        @foreach($transfer->lines as $idx => $l)
                            <tr>
                                <td>
                                    <select name="lines[{{ $idx }}][product_variant_id]"
                                            class="form-control variant-select" required>
                                        <option value="{{ $l->variant->id }}" selected>
                                            {{ $l->variant->sku }} – {{ $l->variant->product->product_name }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" min="1"
                                           name="lines[{{ $idx }}][qty]"
                                           class="form-control text-end"
                                           value="{{ $l->qty }}" required>
                                </td>
                                <td>
                                    <input type="number" step="0.0001" min="0"
                                           name="lines[{{ $idx }}][unit_cost]"
                                           class="form-control text-end"
                                           value="{{ $l->unit_cost ?? '' }}">
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-danger remLine">&times;</button>
                                </td>
                            </tr>
                        @endforeach
                    @endisset
                </tbody>
            </table>
        </div>
        <small class="text-muted d-block px-3 py-2">
            * Unit Cost is optional &nbsp;–&nbsp; leave blank to auto‑use last purchase price.
        </small>
    </div>
</div>

@pushOnce('scripts')
<script>
/* --------------------------------------------------------
   Variant select2 AJAX source
   ------------------------------------------------------ */
function initVariantSelect($el){
    $el.select2({
        placeholder: 'Search SKU or name…',
        minimumInputLength: 2,
        ajax:{
            url: '/admin/inventory/stock/transfers/fetch-variants',
            dataType:'json',
            delay:250,
            data: params => ({ q: params.term }),
            processResults: data => ({
                results: data.map(v => ({
                    id:   v.id,
                    text: v.sku + ' – ' + v.product_name
                }))
            })
        },
        dropdownParent: $('#variantModal').length ? $('#variantModal') : $(document.body)
    });
}

/* --------------------------------------------------------
   Add / remove line rows
   ------------------------------------------------------ */
let lineIdx = $('#lineTbody tr').length;
$('#addLineBtn').on('click', () => {
    lineIdx++;
    const row = $(`
      <tr>
        <td>
          <select name="lines[${lineIdx}][product_variant_id]" class="form-control variant-select" required></select>
        </td>
        <td><input type="number" name="lines[${lineIdx}][qty]" min="1" class="form-control text-end" required></td>
        <td><input type="number" name="lines[${lineIdx}][unit_cost]" step="0.0001" min="0" class="form-control text-end"></td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-sm btn-danger remLine">&times;</button>
        </td>
      </tr>`);
    $('#lineTbody').append(row);
    initVariantSelect(row.find('.variant-select'));
});

/* remove → */
$(document).on('click','.remLine',function(){
    $(this).closest('tr').remove();
});

/* --------------------------------------------------------
   Initialise select2 on existing rows (edit mode)
   ------------------------------------------------------ */
$(function(){
    initVariantSelect($('.variant-select'));
});
</script>
@endpushOnce
