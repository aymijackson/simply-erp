@extends('layouts.master')

@section('title', 'Manage Stock Entries')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    {{-- ────────────── Heading + buttons ────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">
            Stock Entries <small class="text-muted">Inventory</small>
        </h1>

        <div>
            <button class="btn btn-outline-secondary me-2" id="reportBtn">
                <i class="fas fa-file-alt me-1"></i> Generate&nbsp;Report
            </button>

            <button class="btn btn-danger me-2 d-none" id="bulkDeleteBtn">
                <i class="fas fa-trash me-1"></i> Delete&nbsp;Selected
            </button>
            @can('inventory.stock.entries.create')
            <button class="btn btn-primary" id="addEntryBtn">
                <i class="fas fa-plus me-1"></i> Add&nbsp;Stock&nbsp;Entry
            </button>
            @endcan
        </div>
    </div>

    {{-- ────────────── Data-table card ────────────── --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="entryTable" class="table table-bordered w-100">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="selectAllEntries"></th>
                            <th>Reference #</th>
                            <th>Store</th>
                            <th>Entry Date</th>
                            <th>Type</th>
                            <th>Supplier / Customer</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ———————————————————— REPORT MODAL ———————————————————— --}}
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="reportForm" class="modal-content" target="_blank" method="GET" action="{{ route('admin.inventory.stock_entries.export') }}">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="reportModalLabel">Stock Report Filters</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
         <div class="row g-3 mb-3">
             <div class="col-md-6">
                 <label class="form-label">From date</label>
                 <input type="date" name="from" class="form-control">
             </div>
             <div class="col-md-6">
                 <label class="form-label">To date</label>
                 <input type="date" name="to" class="form-control">
             </div>
         </div>

         <div class="row g-3">
             <div class="col-md-6">
                 <label class="form-label">Store</label>
                 <select name="store_id" class="form-select">
                     <option value="">-- Any store --</option>
                     @foreach($stores as $s)
                         <option value="{{ $s->id }}">{{ $s->name }}</option>
                     @endforeach
                 </select>
             </div>

             <div class="col-md-6">
                 <label class="form-label">Product variant</label>
                 <select name="variant_id" class="form-select select2-report">
                     <option value="">-- Any variant --</option>
                     @foreach($variants as $v)
                         <option value="{{ $v->id }}">{{ $v->sku }} – {{ $v->product->product_name }}</option>
                     @endforeach
                 </select>
             </div>

             <div class="col-md-6">
                 <label class="form-label">Supplier</label>
                 <select name="supplier_id" id="report_supplier_id" class="form-select"></select>
             </div>

             <div class="col-md-6">
                 <label class="form-label">Customer</label>
                 <select name="customer_id" id="report_customer_id" class="form-select"></select>
             </div>
         </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <div class="ms-auto d-flex gap-2">
            <button name="type" value="excel" class="btn btn-success">
              <i class="fas fa-file-excel me-1"></i> Excel
            </button>
            <button name="type" value="pdf" class="btn btn-danger">
              <i class="fas fa-file-pdf me-1"></i> PDF
            </button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Lines Modal --}}
<div class="modal fade" id="linesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Stock Entry Lines</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="linesEntryId">

        <div class="table-responsive">
          <table id="linesTableDt" class="table table-bordered w-100">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Variant</th>
                <th>Qty</th>
                <th>Unit Cost</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>


{{-- ────────────────── ENTRY MODAL (your existing markup) ────────────────── --}}
@include('inventory.stock.entries.partials.modal')
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(function () {
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  /* ============================================================
   * 1) Bootstrap modal helpers (fix: must pass DOM element)
   * ============================================================ */
  const reportModalEl = document.getElementById('reportModal');
  const entryModalEl  = document.getElementById('entryModal'); // must exist in included partial

  const reportModal = reportModalEl ? new bootstrap.Modal(reportModalEl) : null;
  const entryModal  = entryModalEl  ? new bootstrap.Modal(entryModalEl)  : null;

  $('#reportBtn').on('click', function () {
    reportModal?.show();
  });

  /* ============================================================
   * 2) Select2 helpers (no duplicates; correct dropdownParent)
   * ============================================================ */
  function initAjaxSelect2($sel, url, $parentModal, placeholder='-- Any --', minLen=2) {
    $sel.select2({
      ajax: {
        url,
        dataType: 'json',
        delay: 250,
        data: p => ({ q: p.term }),
        processResults: d => ({ results: d })
      },
      placeholder,
      minimumInputLength: minLen,
      dropdownParent: $parentModal,
      width: '100'
    });
  }

  function initStaticSelect2($sel, $parentModal) {
    $sel.select2({
      dropdownParent: $parentModal,
      width: '100%'
    });
  }

  // Report modal select2 (init once)
  initAjaxSelect2($('#report_supplier_id'), "{{ route('admin.suppliers.select2') }}", $('#reportModal'));
  initAjaxSelect2($('#report_customer_id'), "{{ route('admin.customers.select2') }}", $('#reportModal'));
  initStaticSelect2($('.select2-report'), $('#reportModal'));

  // Entry modal party selects (init once)
  initAjaxSelect2($('#supplier_id'), "{{ route('admin.suppliers.select2') }}", $('#entryModal'), '-- select supplier --');
  initAjaxSelect2($('#customer_id'), "{{ route('admin.customers.select2') }}", $('#entryModal'), '-- select customer --');

  /* ============================================================
   * 3) DataTable
   * ============================================================ */
  const tbl = $('#entryTable').DataTable({
    serverSide: true,
    responsive: true,
    dom: 'Blfrtip',
    buttons: [
      { extend: 'excelHtml5', className: 'btn btn-sm btn-success', text: '<i class="fas fa-file-excel me-1"></i> Excel' },
      { extend: 'pdfHtml5',   className: 'btn btn-sm btn-danger',  text: '<i class="fas fa-file-pdf me-1"></i> PDF', orientation: 'landscape', pageSize: 'A4' },
    ],
    ajax: "{{ route('admin.inventory.stock_entries.datatable') }}",
    columns: [
      { data: 'checkbox', orderable: false, searchable: false },
      { data: 'reference' },
      { data: 'store_name' },
      { data: 'entry_date' },
      {
        data: 'entry_type',
        render: t => t === 'cust_return'
          ? '<span class="text-white badge bg-info">Return</span>'
          : '<span class="text-white badge bg-primary">Normal</span>'
      },
      { data: 'party' },
      {
        data: 'status',
        render: s => s === 'draft'
          ? '<span class="text-white badge bg-secondary">Draft</span>'
          : (s === 'posted'
              ? '<span class="text-white badge bg-success">Posted</span>'
              : '<span class="text-white badge bg-warning">Approved</span>')
      },
      { data: 'actions', orderable: false, searchable: false, className: 'text-end' },
    ],
  });

  // Use delegated events (no .off() needed, no drawCallback needed)
  $(document).on('click', '.edit-entry', function () {
    const id = $(this).data('id');
    $.getJSON(`/admin/inventory/stock-entries/${id}`)
      .done(fillForm)
      .done(() => entryModal?.show());
  });

  $(document).on('click', '.delete-entry', function () {
    deleteOne($(this).data('id'));
  });

  /* ============================================================
   * 4) Entry type: Supplier ↔ Customer switch + return columns
   * ============================================================ */
  function togglePartyFields() {
    const isRet = $('#entry_type').val() === 'cust_return';
    $('.entry-supplier').toggleClass('d-none', isRet);
    $('.entry-customer').toggleClass('d-none', !isRet);

    $('#customer_id').prop('required', isRet);
    $('#supplier_id').prop('required', !isRet);

    if (isRet) {
      $('#supplier_id').val(null).trigger('change');
    } else {
      $('#customer_id').val(null).trigger('change');
    }
  }

  function toggleReturnColumns() {
    const show = $('#entry_type').val() === 'cust_return';
    $('.return-only').toggleClass('d-none', !show);

    // Disable selects when hidden (prevents submit noise)
    $('#linesTable tbody tr').each(function () {
      $(this).find('.inv-line-select, .del-line-select').prop('disabled', !show);
    });
  }

  $('#entry_type').on('change', function () {
    togglePartyFields();
    toggleReturnColumns();
  });

  /* ============================================================
   * 5) Variant options cached once
   * ============================================================ */
  let variantOptions = `{!! collect($variants)->map(fn($v)=>
    "<option value=\"{$v->id}\">{$v->sku} – {$v->product->product_name}</option>"
  )->join('') !!}`;

  /* ============================================================
   * 6) Invoice / delivery line select2 (return only)
   * ============================================================ */
  function initInvoiceSelect($sel, variantId) {
    $sel.select2({
      ajax: {
        url: '{{ route('admin.sales.invoices.lines.select2') }}',
        dataType: 'json',
        delay: 250,
        data: p => ({
          q: p.term,
          customer_id: $('#customer_id').val(),
          variant_id: variantId
        }),
        processResults: d => ({ results: d })
      },
      placeholder: '-- invoice line --',
      dropdownParent: $('#entryModal'),
      width: '100%',
      minimumInputLength: 0
    });
  }

  function initDeliverySelect($sel, variantId) {
    $sel.select2({
      ajax: {
        url: '{{ route('admin.sales.delivery.lines.select2') }}',
        dataType: 'json',
        delay: 250,
        data: p => ({
          q: p.term,
          customer_id: $('#customer_id').val(),
          variant_id: variantId
        }),
        processResults: d => ({ results: d })
      },
      placeholder: '-- delivery line --',
      dropdownParent: $('#entryModal'),
      width: '100%',
      minimumInputLength: 0
    });
  }

  /* ============================================================
   * 7) Line row builder
   * ============================================================ */
  function lineRow(sel = {}) {
    return `
      <tr>
        <td>
          <select name="lines[variant_id][]" class="form-select variant-select" required>
            <option value="">-- Select Variant --</option>
            ${variantOptions}
          </select>
        </td>

        <td><input type="number" name="lines[qty][]" class="form-control" step="0.001" min="0.001" value="${sel.qty || 1}" required></td>
        <td><input type="number" name="lines[unit_cost][]" class="form-control" step="0.01" value="${sel.unit_cost || ''}"></td>

        <td class="return-only d-none">
          <select name="lines[invoice_line_id][]" class="form-select inv-line-select"></select>
        </td>

        <td class="return-only d-none">
          <select name="lines[delivery_line_id][]" class="form-select del-line-select"></select>
        </td>

        <td class="text-center">
          <button type="button" class="btn btn-link btn-sm text-danger rm-line"><i class="fas fa-times"></i></button>
        </td>
      </tr>
    `;
  }

  function addLine(pref = {}) {
    $('#linesTable tbody').append(lineRow(pref));

    const $tr = $('#linesTable tbody tr:last');
    $tr.find('.variant-select').val(pref.variant_id || '');

    initInvoiceSelect($tr.find('.inv-line-select'), pref.variant_id || null);
    initDeliverySelect($tr.find('.del-line-select'), pref.variant_id || null);

    toggleReturnColumns();
  }

  function resetForm() {
    $('#entryForm')[0].reset();
    $('#entryId').val('');
    $('#linesTable tbody').empty();
    addLine();
    togglePartyFields();
    toggleReturnColumns();
  }

  // Customer changes -> refresh invoice/delivery select2 per row
  $('#customer_id').on('change', function () {
    $('#linesTable tbody tr').each(function () {
      const v = $(this).find('.variant-select').val();
      initInvoiceSelect($(this).find('.inv-line-select').empty(), v);
      initDeliverySelect($(this).find('.del-line-select').empty(), v);
    });
  });

  // Variant changes in a row -> refresh invoice/delivery selects
  $('#linesTable').on('change', '.variant-select', function () {
    const v = $(this).val();
    const $row = $(this).closest('tr');
    initInvoiceSelect($row.find('.inv-line-select').empty(), v);
    initDeliverySelect($row.find('.del-line-select').empty(), v);
  });

  /* ============================================================
   * 8) Fill form on edit
   * ============================================================ */
  let originalStatus = null;

  function fillForm(d) {
    resetForm();

    $('#entryModalLabel').text('Edit Stock Entry');
    $('#entryId').val(d.id);
    $('#store_id').val(d.store_id);
    $('#entry_date').val(d.entry_date);
    $('#reference').val(d.reference);
    $('#status').val(d.status);

    originalStatus = d.status;

    $('#entry_type').val(d.entry_type || 'normal');
    togglePartyFields();
    toggleReturnColumns();

    if (d.entry_type === 'cust_return' && d.customer) {
      let o = new Option(d.customer.text, d.customer.id, true, true);
      $('#customer_id').append(o).trigger('change');
    }

    if (d.entry_type !== 'cust_return' && d.supplier) {
      let o = new Option(d.supplier.text, d.supplier.id, true, true);
      $('#supplier_id').append(o).trigger('change');
    }

    $('#linesTable tbody').empty();
    (d.lines || []).forEach(l => addLine({
      variant_id: l.product_variant_id,
      qty: l.qty,
      unit_cost: l.unit_cost
    }));
  }

  /* ============================================================
   * 9) UI actions (Add, remove row, save)
   * ============================================================ */
  $('#addEntryBtn').on('click', function () {
    resetForm();
    $('#entryModalLabel').text('Add Stock Entry');
    entryModal?.show();
  });

  $('#addLineBtn').on('click', function () {
    addLine();
  });

  $('#linesTable').on('click', '.rm-line', function () {
    $(this).closest('tr').remove();
  });

  $('#entryForm').on('submit', function (e) {
    e.preventDefault();

    const id  = $('#entryId').val();
    const url = id
      ? `/admin/inventory/stock-entries/${id}`
      : `{{ route('admin.inventory.stock_entries.store') }}`;

    let data = $(this).serialize();
    if (id) data += '&_method=PUT';

    // repost signal: posted -> posted
    const currentStatus = $('#status').val();
    if (originalStatus === 'posted' && currentStatus === 'posted') {
      data += '&repost=1';
    }

    $.post(url, data)
      .done(r => {
        entryModal?.hide();
        tbl.ajax.reload(null, false);
        Swal.fire('Success', r.message || 'Saved', 'success');
      })
      .fail(x => {
        Swal.fire('Error', x.responseJSON?.message || 'Save failed', 'error');
      });
  });

  /* ============================================================
   * 10) Delete one (FIXED URL)
   * ============================================================ */
  function deleteOne(id) {
    Swal.fire({ title: 'Delete?', icon: 'warning', showCancelButton: true })
      .then(r => {
        if (!r.isConfirmed) return;

        $.post(`/admin/inventory/stock-entries/${id}`, { _method: 'DELETE' })
          .done(res => {
            tbl.ajax.reload(null, false);
            Swal.fire('Deleted', res?.message || 'Deleted', 'success');
          })
          .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Delete failed', 'error'));
      });
  }
  
  let linesDt = null;

    $(document).on('click', '.view-lines', function () {
      const entryId = $(this).data('id');
      $('#linesEntryId').val(entryId);
    
      const modalEl = document.getElementById('linesModal');
      new bootstrap.Modal(modalEl).show();
    
      // init once, then just change ajax url + reload
      if (!linesDt) {
        linesDt = $('#linesTableDt').DataTable({
          serverSide: true,
          processing: true,
          responsive:true,
          ajax: `/admin/inventory/stock-entries/${entryId}/lines/datatable`,
          columns: [
            { data: 'id' },
            { data: 'variant' },
            { data: 'qty' },
            { data: 'unit_cost' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-end' },
          ],
        });
      } else {
        linesDt.ajax.url(`/admin/inventory/stock-entries/${entryId}/lines/datatable`).load();
      }
    });

    $(document).on('click', '.approve-entry', function () {
      const id = $(this).data('id');
      Swal.fire({title:'Approve entry?', icon:'question', showCancelButton:true})
        .then(r => {
          if(!r.isConfirmed) return;
          $.post(`/admin/inventory/stock-entries/${id}/approve`)
            .done(res => { Swal.fire('Approved', res.message, 'success'); tbl.ajax.reload(null,false); })
            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Approve failed', 'error'));
        });
    });
    
    $(document).on('click', '.post-entry', function () {
      const id = $(this).data('id');
      Swal.fire({title:'Post entry?', text:'This will update stock ledger.', icon:'warning', showCancelButton:true})
        .then(r => {
          if(!r.isConfirmed) return;
          $.post(`/admin/inventory/stock-entries/${id}/post`)
            .done(res => { Swal.fire('Posted', res.message, 'success'); tbl.ajax.reload(null,false); })
            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Post failed', 'error'));
        });
    });
    
    $(document).on('click', '.unpost-entry', function () {
      const id = $(this).data('id');
      Swal.fire({title:'Unpost entry?', text:'This will reverse ledger postings.', icon:'warning', showCancelButton:true})
        .then(r => {
          if(!r.isConfirmed) return;
          $.post(`/admin/inventory/stock-entries/${id}/unpost`)
            .done(res => { Swal.fire('Unposted', res.message, 'success'); tbl.ajax.reload(null,false); })
            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Unpost failed', 'error'));
        });
    });

});
</script>
@endpush
