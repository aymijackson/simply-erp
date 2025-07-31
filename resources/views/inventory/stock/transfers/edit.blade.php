@extends('layouts.master')
@section('title','Transfer')

@section('content')
@php $isEdit = isset($transfer); @endphp
<div class="container-fluid">
  <h1 class="h3 mb-3">{{ $isEdit ? 'Edit' : 'New' }} Transfer</h1>

  {{-- ---------- HEADER FORM ---------- --}}
  <form id="hdrForm"
        method="POST"
        action="{{ $isEdit
                 ? route('admin.inventory.stock.transfers.post', $transfer)
                 : route('admin.inventory.stock.transfers.store') }}">
      @csrf

      @if($isEdit)
         <p class="mb-2">Transfer #: <strong>{{ $transfer->transfer_no }}</strong></p>
      @endif

      <div class="row g-3 mb-3">
          {{-- from --}}
          <div class="col-md-4">
              <label class="form-label">From Store *</label>
              <select name="from_store_id" class="form-select" {{ $isEdit ? 'disabled' : '' }} required>
                  <option value="">--</option>
                  @foreach($stores as $s)
                      <option value="{{ $s->id }}" @selected($isEdit && $transfer->from_store_id==$s->id)>
                          {{ $s->name }}
                      </option>
                  @endforeach
              </select>
          </div>

          {{-- to --}}
          <div class="col-md-4">
              <label class="form-label">To Store *</label>
              <select name="to_store_id" class="form-select" {{ $isEdit ? 'disabled' : '' }} required>
                  <option value="">--</option>
                  @foreach($stores as $s)
                      <option value="{{ $s->id }}" @selected($isEdit && $transfer->to_store_id==$s->id)>
                          {{ $s->name }}
                      </option>
                  @endforeach
              </select>
          </div>

          {{-- reason --}}
          <div class="col-md-4">
              <label class="form-label">Reason</label>
              <input type="text" name="reason" class="form-control" value="{{ $transfer->reason ?? '' }}">
          </div>
      </div>

      {{-- ---------- LINES TABLE PARTIAL ---------- --}}
      @include('inventory.stock.transfers.partials.lines-table')

      {{-- ---------- FOOTER BUTTONS ---------- --}}
      <div class="mt-3">
          @unless($isEdit)
              <button class="btn btn-success" type="submit">Save Draft</button>
          @else
              @if($transfer->status === 'draft')
                  <button class="btn btn-primary"
                          formaction="{{ route('admin.inventory.stock.transfers.post',$transfer) }}">
                      Post Transfer
                  </button>
              @endif
          @endunless
          <a href="{{ route('admin.inventory.stock.transfers.index') }}" class="btn btn-secondary">Back</a>
      </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    /* ------------------------------------------------------------------
     *  Submit header form via Ajax so we can show SweetAlert feedback
     * ------------------------------------------------------------------ */
    $('#hdrForm').on('submit', function (e) {
        e.preventDefault();

        const $f   = $(this);
        const url  = $f.attr('action');
        const data = $f.serialize();

        $.post(url, data)
          .done(resp => {
              Swal.fire('Success', resp.message ?? 'Transfer saved', 'success')
                  .then(() => window.location = "{{ route('admin.inventory.stock.transfers.index') }}");
          })
          .fail(xhr => {
              /* Friendly validation message coming from TransferService */
              let msg = 'Save failed';
              if (xhr.status === 422 && xhr.responseJSON?.errors) {
                  msg = Object.values(xhr.responseJSON.errors)[0][0];
              } else if (xhr.responseJSON?.message) {
                  msg = xhr.responseJSON.message;
              }
              Swal.fire('Error', msg, 'error');
          });
    });

    /* Example: add line row (if your partial needs JS) ---------------- */
    $('#addLineBtn').on('click', () => newLineRow());

    function newLineRow(data = {}) {
        // build <tr> … same as other screens
    }
});
</script>
@endpush