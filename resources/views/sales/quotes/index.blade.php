@extends('layouts.master')

@section('title', 'Sales Quotes')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Quotes</h1>
            <small class="text-muted">Sales / Quotes</small>
        </div>

        @can('sales.quotes.create')
            <a href="{{ route('admin.sales.quotes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> New Quote
            </a>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h6 class="mb-0 text-primary">
                <i class="fas fa-filter mr-1"></i> Filters
            </h6>

            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-primary" id="applyFiltersBtn" type="button">
                    <i class="fas fa-filter mr-1"></i> Apply
                </button>
                <button class="btn btn-sm btn-outline-secondary" id="resetFiltersBtn" type="button">
                    <i class="fas fa-undo mr-1"></i> Reset
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select id="filter_status" class="form-control" style="width:100%;">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="won">Won</option>
                        <option value="rejected">Rejected</option>
                        <option value="expired">Expired</option>
                        <option value="converted">Converted</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Customer</label>
                    <select id="filter_customer_id" class="form-control" style="width:100%;">
                        <option value="">All</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Quote No (text)</label>
                    <input type="text" id="filter_quote_no" class="form-control" placeholder="e.g. QT-20260820-0001">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">From</label>
                    <input type="date" id="filter_date_from" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">To</label>
                    <input type="date" id="filter_date_to" class="form-control">
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table id="quotesTable" class="table table-bordered table-hover w-100">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Quote No</th>
                            <th>Customer</th>
                            <th>Quote Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Created</th>
                            <th style="width:220px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let quotesTable;

function initSelect2Ajax(selector, url, placeholder = 'All') {
    const $el = $(selector);
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        placeholder,
        allowClear: true,
        width: '100%',
        ajax: {
            url,
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '', page: params.page || 1 }),
            processResults: function (data) {
                if (Array.isArray(data)) return { results: data };
                if (data.results) return data;
                return { results: [] };
            }
        }
    });
}

function initSelect2Static(selector, placeholder = 'All') {
    const $el = $(selector);
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({ theme: 'bootstrap-5', placeholder, allowClear: true, width: '100%', minimumResultsForSearch: 10 });
}

function getFilters() {
    return {
        status: $('#filter_status').val(),
        customer_id: $('#filter_customer_id').val(),
        quote_no: $('#filter_quote_no').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val(),
    };
}

function reloadQuotes() {
    if (!quotesTable) return;
    quotesTable.ajax.reload(null, true);
}

document.addEventListener('DOMContentLoaded', function () {
    initSelect2Static('#filter_status', 'All');
    initSelect2Ajax('#filter_customer_id', "{{ route('admin.customers.select2') }}", 'All');

    quotesTable = $('#quotesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('admin.sales.quotes.datatable') }}",
            type: 'GET',
            data: d => Object.assign(d, getFilters()),
            error: xhr => console.error(xhr.responseText)
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'quote_no', name: 'quote_no' },
            { data: 'customer', name: 'customer' },
            { data: 'quote_date_fmt', name: 'quote_date' },
            { data: 'status', name: 'status' },
            { data: 'total_amount_fmt', name: 'total_amount' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
    });

    $('#applyFiltersBtn').on('click', reloadQuotes);

    $('#resetFiltersBtn').on('click', function(){
        $('#filter_status').val(null).trigger('change.select2');
        $('#filter_customer_id').val(null).trigger('change.select2');
        $('#filter_quote_no').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        reloadQuotes();
    });

    $('#filter_quote_no').on('keyup', function(e){
        if (e.key === 'Enter') reloadQuotes();
    });
});

window.doQuoteAction = function(action, id, confirmTitle, confirmText, confirmColor){
    Swal.fire({
        title: confirmTitle,
        text: confirmText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel',
        confirmButtonColor: confirmColor || '#4338CA',
        reverseButtons: true
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        try {
            const res = await fetch(`{{ url('admin/sales/quotes') }}/${id}/${action}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Action failed');

            Swal.fire({ icon: 'success', title: 'Done', text: data.message || 'Success', timer: 1400, showConfirmButton: false });
            if (quotesTable) quotesTable.ajax.reload(null, false);
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Action failed' });
        }
    });
};
</script>
@endpush
