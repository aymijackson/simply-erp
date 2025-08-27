@extends('layouts.master')
@section('title', 'BOM · '.$bom->name.' - '.$bom->bom_code)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
@endpush

@section('content')
<div class="container-fluid">

  {{-- Header --------------------------------------------------------------- --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-primary">
      Bill of Materials <small class="text-muted"># {{ $bom->bom_code }}</small>
    </h1>

    <div class="d-print-none">
      @if($canEdit)
        <button id="editBtn"  class="btn btn-warning"><i class="fas fa-edit me-1"></i> Edit</button>
        <button id="saveBtn"  class="btn btn-success d-none"><i class="fas fa-save me-1"></i> Save</button>
        <button id="cancelBtn"class="btn btn-secondary d-none">Cancel</button>
      @endif

      {{-- NEW: Send to another BOM --}}
      <button id="sendToBomBtn" class="btn btn-primary">
        <i class="fas fa-share-square me-1"></i> Send to another BOM
      </button>

      <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="fas fa-print me-1"></i> Print
      </button>
      <a href="{{ route('admin.production.boms.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
    </div>
  </div>

  {{-- Parent info ---------------------------------------------------------- --}}
  @include('production.boms.partials.parent-info')

  {{-- Items (view mode) ---------------------------------------------------- --}}
  <div id="viewCard" class="card shadow-sm">
    <div class="card-body table-responsive">
      @include('production.boms.partials.bom-items-table')
    </div>
  </div>

  {{-- SEND TO ANOTHER BOM (pre-populated) --------------------------------- --}}
  @php
    // Build rows directly from what's already on the page.
    // We assume each $ln may expose an available figure like ->available_on_bom / ->available.
    // If you already attach availability in the controller, it will be picked here.
    $sendableRows = [];
    foreach ($bom->items as $ln) {
        $v   = $ln->product_variant;
        if (!$v) continue;
        $avail =
            $ln->qty_per_parent
            ?? $ln->qty_per_parent
            ?? 0; // fallback to 0 if not present; row is still shown but qty can't exceed 0
        $sendableRows[] = [
            'id'    => $v->id,
            'sku'   => $v->sku,
            'name'  => optional($v->product)->product_name,
            'avail' => (float)$avail,
        ];
    }
    // Optionally show only rows with availability > 0:
    // $sendableRows = array_values(array_filter($sendableRows, fn($r)=>$r['avail'] > 0));
  @endphp

  <div class="modal fade" id="sendToBomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form id="sendToBomForm" class="modal-content">
        @csrf
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Send Items to Another BOM</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Destination BOM *</label>
            <select id="dest_bom_id" name="dest_bom_id" class="form-select" required></select>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Lines</h6>
            <div class="d-flex gap-2">
              <button type="button" id="fillAllBtn" class="btn btn-sm btn-outline-secondary">Fill with available</button>
              <button type="button" id="clearAllBtn" class="btn btn-sm btn-outline-secondary">Clear all</button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered align-middle" id="sendLinesTbl">
              <thead class="table-light">
                <tr>
                  <th style="width:55%">Variant</th>
                  <th style="width:20%" class="text-end">Qty to Send</th>
                  <th style="width:20%" class="text-end">Available on BOM</th>
                  <th style="width:5%"></th>
                </tr>
              </thead>
              <tbody>
                @forelse($sendableRows as $r)
                  <tr data-key="{{ $r['id'] }}">
                    <td>
                      <input type="hidden" name="lines[{{ $r['id'] }}][product_variant_id]" value="{{ $r['id'] }}">
                      {{ $r['sku'] }} — {{ $r['name'] }}
                    </td>
                    <td class="text-end">
                      <input
                        name="lines[{{ $r['id'] }}][qty]"
                        type="number"
                        step="1"
                        min="0"
                        max="{{ $r['avail'] }}"
                        value="0"
                        class="form-control text-end qty-input"
                      >
                    </td>
                    <td class="text-end">
                      {{ number_format($r['avail']) }}
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-link text-danger rm-line">
                        <i class="fas fa-times"></i>
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted py-3">No available items on this BOM.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <input type="hidden" name="source_bom_id" value="{{ $bom->id }}">
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-paper-plane me-1"></i> Send Items
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Edit form (unchanged) ----------------------------------------------- --}}
  <form id="editForm" class="card shadow-sm d-none"
        action="{{ route('admin.production.boms.update',$bom) }}" method="POST">
    @csrf @method('PUT')
    <div class="card-body">
      <table class="table table-sm table-bordered align-middle" id="linesTbl">
        <thead class="table-light">
          <tr>
            <th style="width:50%">Component *</th>
            <th style="width:15%" class="text-end">Qty / FG *</th>
            <th style="width:15%" class="text-end">Unit Cost</th>
            <th style="width:5%"  class="text-center">
              <button type="button" id="addLn" class="btn btn-success btn-sm"><i class="fas fa-plus"></i></button>
            </th>
          </tr>
        </thead>
        <tbody id="linesBody"></tbody>
      </table>
    </div>
  </form>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function(){
  const sendModal = new bootstrap.Modal('#sendToBomModal');

  // Open modal (rows are already rendered server-side)
  document.getElementById('sendToBomBtn').addEventListener('click', ()=> sendModal.show());

  // Destination BOM Select2 (exclude current)
  $('#dest_bom_id').select2({
    ajax:{ url: "{{ route('admin.production.boms.other-select2', ['bom' => $bom->id]) }}", dataType:'json', delay:250,
           data: p=>({ q:p.term, exclude: {{ (int)$bom->id }} }),
           processResults:d=>({results:d}) },
    dropdownParent: $('#sendToBomModal'), width:'100%', placeholder:'-- select BOM --', minimumInputLength:0
  });

  // Helpers
  $('#fillAllBtn').on('click', ()=> $('#sendLinesTbl .qty-input').each(function(){
    const max = parseFloat(this.max || '0');
    this.value = max.toFixed(1);
  }));
  $('#clearAllBtn').on('click', ()=> $('#sendLinesTbl .qty-input').val('0.0000'));

  // Remove a row
  $('#sendLinesTbl').on('click', '.rm-line', function(){
    $(this).closest('tr').remove();
  });

  // Clamp entered qty to max
  $('#sendLinesTbl').on('input', '.qty-input', function(){
    const max = parseFloat(this.getAttribute('max') || '0');
    let val = parseFloat(this.value || '0');
    if (val > max) { this.value = max.toFixed(1); }
    if (val < 0)   { this.value = '0.0000'; }
  });

  // Submit: send only rows with qty>0 (compact payload)
  $('#sendToBomForm').on('submit', function (e) {
  e.preventDefault();
  const dest = $('#dest_bom_id').val();
  if (!dest) return Swal.fire('Error','Destination BOM is required','error');

  const $form = $(this);

  // Disable zero-qty rows so serialize() skips them
  $('#sendLinesTbl tbody tr').each(function () {
      const $tr  = $(this);
      const $qty = $tr.find('.qty-input');
      const qty  = parseFloat($qty.val() || '0');
      const $hid = $tr.find('input[type=hidden][name^="lines["]'); // product_variant_id

      // reset disabled first
      $qty.prop('disabled', false);
      $hid.prop('disabled', false);

      if (qty <= 0) {
         $qty.prop('disabled', true);
         $hid.prop('disabled', true);
      }
   });

   // Post the regular form-encoded data (NOT JSON)
   $.post("{{ route('admin.production.boms.transfer', $bom) }}", $form.serialize())
      .done(resp => { bootstrap.Modal.getInstance('#sendToBomModal').hide(); Swal.fire('Done', resp.message || 'Items sent', 'success'); })
      .fail(xhr  => {
         const msg  = xhr.responseJSON?.message || 'Failed';
         const errs = xhr.responseJSON?.errors;
         Swal.fire('Error', errs ? Object.values(errs).flat().join('<br>') : msg, 'error');
      })
      .always(() => {
         // re-enable fields so the form keeps working if reopened
         $form.find(':disabled').prop('disabled', false);
      });
   });

  // ---------- Existing edit-mode JS (unchanged) ----------
  const canEdit = @json($canEdit);
  if (canEdit) {
    function tpl(idx){
      return `<tr data-key="${idx}">
        <td><select name="items[${idx}][product_variant_id]" class="form-select variant-select" required></select></td>
        <td><input name="items[${idx}][qty_per_parent]" type="number" step="0.0001" class="form-control text-end" required></td>
        <td><input name="items[${idx}][unit_cost]" type="number" step="0.0001" class="form-control text-end"></td>
        <td class="text-center"><button type="button" class="btn btn-danger btn-sm remLn"><i class="fas fa-trash"></i></button></td>
      </tr>`;
    }
    function addLine(prefill={}){
      const idx = Date.now();
      $('#linesBody').append(tpl(idx));
      const $row = $('#linesBody tr:last');
      $row.find('.variant-select').select2({
        ajax:{ url:"{{ route('admin.inventory.stock_issues.fetch_variants') }}", dataType:'json', delay:250,
               data: p=>({q:p.term}), processResults:d=>({results:d}) },
        dropdownParent: $('#editForm'), placeholder:'-- variant --', minimumInputLength:2, width:'100%'
      });
      if(prefill.id){
        const opt = new Option(prefill.text,prefill.id,true,true);
        $row.find('.variant-select').append(opt).trigger('change');
      }
    }
    window.populateLines = function(){
      $('#linesBody').empty();
      @foreach($bom->items as $ln)
        addLine({ id: {{ $ln->product_variant_id }}, text: "{{ $ln->product_variant->sku }}", qty: {{ $ln->qty_per_parent }} });
      @endforeach
    };
    $('#addLn').on('click',()=>addLine());
    $('#linesBody').on('click','.remLn', function(){ $(this).closest('tr').remove(); });
    $('#editBtn').on('click',()=>{ populateLines(); $('#viewCard').addClass('d-none'); $('#editForm,#saveBtn,#cancelBtn').removeClass('d-none'); });
    $('#cancelBtn').on('click',()=>{ $('#editForm,#saveBtn,#cancelBtn').addClass('d-none'); $('#viewCard,#editBtn').removeClass('d-none'); });
    $('#saveBtn').on('click',()=>$('#editForm').submit());
  }
})();
</script>
@endpush
