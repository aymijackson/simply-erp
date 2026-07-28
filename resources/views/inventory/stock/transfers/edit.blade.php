{{-- resources/views/inventory/stock/transfers/edit.blade.php --}}
@extends('layouts.master')
@section('title','Transfer')

@section('content')
@php
  $isEdit  = !is_null($transfer);
  $isDraft = $isEdit && ($transfer->status === 'draft');
@endphp

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="h3 mb-1">{{ $isEdit ? 'Edit' : 'New' }} Transfer</h1>
      @if($isEdit)
        <div class="text-muted">
          Transfer #: <strong>{{ $transfer->transfer_no }}</strong>
          <span class="ms-2 badge {{ $transfer->status === 'posted' ? 'bg-success' : 'bg-warning text-dark' }}">
            {{ strtoupper($transfer->status) }}
          </span>
        </div>
      @endif
    </div>
    <a href="{{ route('admin.inventory.stock.transfers.index') }}" class="btn btn-secondary">Back</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">

      {{-- =========================
           MAIN FORM (SAVE / UPDATE / POST)
           ========================= --}}
      <form id="hdrForm"
            method="POST"
            action="{{ $isEdit
              ? route('admin.inventory.stock.transfers.update', $transfer)
              : route('admin.inventory.stock.transfers.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="row g-3 mb-3">
          {{-- From store --}}
          <div class="col-md-4">
            <label class="form-label">From Store *</label>
            <select id="from_store_id"
                    name="from_store_id"
                    class="form-select"
                    {{ $isEdit ? 'disabled' : '' }}
                    required>
              <option value="">--</option>
              @foreach($stores as $s)
                <option value="{{ $s->id }}" @selected($isEdit && $transfer->from_store_id == $s->id)>
                  {{ $s->name }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- To store --}}
          <div class="col-md-4">
            <label class="form-label">To Store *</label>
            <select id="to_store_id"
                    name="to_store_id"
                    class="form-select"
                    {{ $isEdit ? 'disabled' : '' }}
                    required>
              <option value="">--</option>
              @foreach($stores as $s)
                <option value="{{ $s->id }}" @selected($isEdit && $transfer->to_store_id == $s->id)>
                  {{ $s->name }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Reason --}}
          <div class="col-md-4">
            <label class="form-label">Reason</label>
            <input type="text"
                   name="reason"
                   class="form-control"
                   value="{{ $transfer->reason ?? '' }}"
                   placeholder="Optional reason / note"
                   {{ ($isEdit && $transfer->status === 'posted') ? 'readonly' : '' }}>
          </div>
        </div>

        {{-- Lines header --}}
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h5 class="mb-0">Transfer Lines</h5>

          @if(!$isEdit || $isDraft)
            <button type="button" class="btn btn-sm btn-outline-primary" id="addLineBtn">
              <i class="fas fa-plus me-1"></i> Add Line
            </button>
          @endif
        </div>

        {{-- Lines table --}}
        <div class="table-responsive">
          <table id="linesTable" class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:50%">Variant</th>
                <th style="width:15%" class="text-end">Qty</th>
                <th style="width:20%">Availability</th>
                <th style="width:15%" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @if($isEdit && $transfer?->lines?->count())
                @foreach($transfer->lines as $i => $ln)
                  @php $variant = $ln->variant; @endphp
                  <tr>
                    <td>
                      <select class="form-select js-variant"
                              name="lines[{{ $i }}][product_variant_id]"
                              style="width:100%"
                              {{ $isDraft ? '' : 'disabled' }}>
                        @if($variant)
                          <option value="{{ $variant->id }}" selected>
                            {{ $variant->sku }} — {{ $variant->product?->product_name }}
                          </option>
                        @endif
                      </select>
                      <small class="text-muted d-block mt-1">Search by SKU or product name</small>
                    </td>

                    <td>
                      <input type="number"
                             min="0"
                             step="1"
                             class="form-control text-end js-qty"
                             name="lines[{{ $i }}][qty]"
                             value="{{ $ln->qty ?? 0 }}"
                             {{ $isDraft ? '' : 'readonly' }}>
                    </td>

                    <td>
                      <small class="text-muted js-avail">Available: -</small>
                    </td>

                    <td class="text-end">
                      @if($isDraft)
                        <button type="button" class="btn btn-sm btn-outline-danger js-remove-line">Remove</button>
                      @else
                        <span class="badge bg-secondary">Locked</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td>
                    <select class="form-select js-variant" name="lines[0][product_variant_id]" style="width:100%"></select>
                    <small class="text-muted d-block mt-1">Search by SKU or product name</small>
                  </td>
                  <td>
                    <input type="number" min="0" step="1" class="form-control text-end js-qty" name="lines[0][qty]" value="">
                  </td>
                  <td>
                    <small class="text-muted js-avail">Available: -</small>
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger js-remove-line">Remove</button>
                  </td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>

        {{-- Footer actions --}}
        <div class="mt-3 d-flex gap-2">

          {{-- Create --}}
          @if(!$isEdit)
            <button class="btn btn-success" type="submit" id="btnSaveDraft">
              Save Draft
            </button>
          @endif

          {{-- Draft edit --}}
          @if($isDraft)
            <button class="btn btn-primary" type="submit" id="btnSaveChanges">
              Save Changes
            </button>

            <button class="btn btn-success"
                    type="submit"
                    id="btnPostTransfer"
                    formaction="{{ route('admin.inventory.stock.transfers.post', $transfer) }}"
                    formmethod="POST">
              Post Transfer
            </button>
          @endif

        </div>

      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  const $from = $('#from_store_id');
  const $to   = $('#to_store_id');

  // ---------------------------
  // Store mutual exclusion
  // ---------------------------
  function syncStoreOptions() {
    const fromVal = $from.val();
    const toVal   = $to.val();

    $from.find('option').prop('disabled', false).show();
    $to.find('option').prop('disabled', false).show();

    if (fromVal) {
      $to.find(`option[value="${fromVal}"]`).prop('disabled', true).hide();
      if (toVal === fromVal) $to.val('');
    }

    if (toVal) {
      $from.find(`option[value="${toVal}"]`).prop('disabled', true).hide();
      if (fromVal === toVal) $from.val('');
    }
  }

  $from.on('change', function () {
    syncStoreOptions();
    resetLinesBecauseFromStoreChanged();
  });
  $to.on('change', syncStoreOptions);
  syncStoreOptions();

  // ---------------------------
  // Select2: variants in stock by from store
  // ---------------------------
  function initVariantSelect2($select) {
    if ($select.data('select2')) $select.select2('destroy');

    $select.select2({
      width: '100%',
      placeholder: 'Search SKU or product…',
      allowClear: true,
      ajax: {
        url: "{{ route('admin.inventory.api.store_variants') }}",
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term || '',
            store_id: $from.val() || ''
          };
        },
        processResults: function (data) {
          return { results: data };
        },
        cache: true
      }
    });

    $select.on('select2:select select2:clear change', function () {
      const $row = $select.closest('tr');
      const variantId = $select.val();
      if (!variantId) return setAvailability($row, null);
      refreshAvailability($row, variantId);
    });
  }

  function setAvailability($row, avail) {
    const $qty = $row.find('.js-qty');
    const a = parseFloat(avail);
    const ok = Number.isFinite(a) && a > 0;

    $row.find('.js-avail').text(ok ? `Available: ${a}` : 'Available: -');

    if (ok) {
      $qty.attr('max', a);
      const current = parseFloat($qty.val() || 0);
      if (current > a) $qty.val(a);
    } else {
      $qty.removeAttr('max');
    }
  }

  function refreshAvailability($row, variantId) {
    const storeId = $from.val();
    if (!storeId) return setAvailability($row, null);

    const data = $row.find('.js-variant').select2('data');
    if (data && data[0] && typeof data[0].available !== 'undefined') {
      return setAvailability($row, data[0].available);
    }

    $.get("{{ route('admin.inventory.api.store_variant_qty') }}", {
      store_id: storeId,
      product_variant_id: variantId
    })
      .done(res => setAvailability($row, res.available ?? null))
      .fail(() => setAvailability($row, null));
  }

  function resetLinesBecauseFromStoreChanged() {
    $('#linesTable tbody tr').each(function () {
      const $row = $(this);
      $row.find('.js-variant').val(null).trigger('change');
      $row.find('.js-qty').val('');
      setAvailability($row, null);
      initVariantSelect2($row.find('.js-variant'));
    });
  }

  // init existing rows
  $('#linesTable tbody .js-variant').each(function () {
    const $sel = $(this);
    initVariantSelect2($sel);

    const $row = $sel.closest('tr');
    const vid = $sel.val();
    if (vid) refreshAvailability($row, vid);
  });

  // qty enforcement only when max is valid
  $(document).on('input change', '.js-qty', function () {
    const $qty = $(this);
    const max  = parseFloat($qty.attr('max'));
    const val  = parseFloat($qty.val() || 0);

    if (Number.isFinite(max) && max > 0 && val > max) $qty.val(max);
    if (val < 0) $qty.val(0);
  });

  // ---------------------------
  // Add / Remove lines
  // ---------------------------
  function reindexLines() {
    $('#linesTable tbody tr').each(function (i) {
      $(this).find('.js-variant').attr('name', `lines[${i}][product_variant_id]`);
      $(this).find('.js-qty').attr('name', `lines[${i}][qty]`);
    });
  }

  function newLineRow() {
    const i = $('#linesTable tbody tr').length;

    const $tr = $(`
      <tr>
        <td>
          <select class="form-select js-variant" name="lines[${i}][product_variant_id]" style="width:100%"></select>
          <small class="text-muted d-block mt-1">Search by SKU or product name</small>
        </td>
        <td>
          <input type="number" min="0" step="1" class="form-control text-end js-qty" name="lines[${i}][qty]" value="">
        </td>
        <td>
          <small class="text-muted js-avail">Available: -</small>
        </td>
        <td class="text-end">
          <button type="button" class="btn btn-sm btn-outline-danger js-remove-line">Remove</button>
        </td>
      </tr>
    `);

    $('#linesTable tbody').append($tr);
    initVariantSelect2($tr.find('.js-variant'));
    setAvailability($tr, null);
  }

  $('#addLineBtn').on('click', function () {
    if (!$from.val()) {
      Swal.fire('Select From Store', 'Please select a From Store first.', 'info');
      return;
    }
    newLineRow();
  });

  $(document).on('click', '.js-remove-line', function () {
    $(this).closest('tr').remove();
    reindexLines();
  });

  // ---------------------------
  // POST confirmation + fix PUT/POST conflict
  // ---------------------------
  $('#btnPostTransfer').on('click', function (e) {
    e.preventDefault();

    Swal.fire({
      icon: 'warning',
      title: 'Post this transfer?',
      html: 'Once posted, the transfer will be <b>locked</b> and you <b>cannot edit</b> it again.',
      showCancelButton: true,
      confirmButtonText: 'Yes, Post Transfer',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (!result.isConfirmed) return;

      // If edit form has hidden _method=PUT, remove it so post goes as POST.
      $('#hdrForm').find('input[name="_method"]').remove();

      // Submit the form to the post route (formaction already on the button)
      $('#hdrForm')[0].requestSubmit(document.getElementById('btnPostTransfer'));
    });
  });

});
</script>
@endpush
