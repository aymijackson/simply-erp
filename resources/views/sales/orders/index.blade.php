@extends('layouts.master')

@section('title', 'Sales Orders')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Orders</h1>
            <small class="text-muted">Sales / Orders</small>
        </div>

        @can('sales.orders.create')
            <a href="{{ route('admin.sales.orders.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> New Sales Order
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
                        <option value="confirmed">Confirmed</option>
                        <option value="partial">Partial</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Customer</label>
                    <select id="filter_customer_id" class="form-control" style="width:100%;">
                        <option value="">All</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Order No (text)</label>
                    <input type="text" id="filter_order_no" class="form-control" placeholder="e.g. SO-20260201-0001">
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
                <table id="ordersTable" class="table table-bordered table-hover w-100">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Order No</th>
                            <th>Customer</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Created</th>
                            <th style="width:170px;">Action</th>
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
{{-- SweetAlert2 (needed for confirm/unconfirm dialogs) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let ordersTable;

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
                if (data.data) {
                    return {
                        results: data.data.map(x => ({
                            id: x.id,
                            text: x.text ?? x.name ?? ('Item #' + x.id)
                        })),
                        pagination: { more: !!data.next_page_url }
                    };
                }
                return { results: [] };
            }
        }
    });
}

function initSelect2Static(selector, placeholder = 'All') {
    const $el = $(selector);
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        placeholder,
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 10
    });
}

function getFilters() {
    return {
        status: $('#filter_status').val(),
        customer_id: $('#filter_customer_id').val(),
        order_no: $('#filter_order_no').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val(),
    };
}

function reloadOrders() {
    if (!ordersTable) return;
    ordersTable.ajax.reload(null, true);
}

document.addEventListener('DOMContentLoaded', function () {

    initSelect2Static('#filter_status', 'All');
    initSelect2Ajax('#filter_customer_id', "{{ route('admin.customers.select2') }}", 'All');

    ordersTable = $('#ordersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('admin.sales.orders.datatable') }}",
            type: 'GET',
            data: d => Object.assign(d, getFilters()),
            error: xhr => console.error(xhr.responseText)
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'order_no', name: 'order_no' },
            { data: 'customer', name: 'customer' },
            { data: 'order_date_fmt', name: 'order_date' },
            { data: 'status', name: 'status' },
            { data: 'grand_total_fmt', name: 'grand_total' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        drawCallback: function(){
            // Optional: keep table stable after redraws
        }
    });

    $('#applyFiltersBtn').on('click', reloadOrders);

    $('#resetFiltersBtn').on('click', function(){
        $('#filter_status').val(null).trigger('change.select2');
        $('#filter_customer_id').val(null).trigger('change.select2');
        $('#filter_order_no').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        reloadOrders();
    });

    $('#filter_order_no').on('keyup', function(e){
        if (e.key === 'Enter') reloadOrders();
    });
});

window.confirmOrder = function(id){
    Swal.fire({
        title: 'Confirm order?',
        text: 'This will mark the order as confirmed.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, confirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        reverseButtons: true
    }).then(async (result) => {

        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Confirming...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const res = await fetch(`{{ url('admin/sales/orders') }}/${id}/confirm`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json'
                }
            });

            const data = await res.json().catch(()=>({}));

            if(!res.ok){
                throw new Error(data.message || 'Confirm failed');
            }

            Swal.fire({
                icon: 'success',
                title: 'Confirmed',
                text: data.message || 'Order confirmed successfully',
                timer: 1400,
                showConfirmButton: false
            });

            if (ordersTable) {
                ordersTable.ajax.reload(null,false);
            }

        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: e.message || 'Confirm failed'
            });
        }
    });
};

window.unconfirmOrder = function(id){
    Swal.fire({
        title: 'Unconfirm order?',
        text: 'This will revert the order back to draft.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, unconfirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        reverseButtons: true
    }).then(async (result) => {

        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Processing...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const res = await fetch(`{{ url('admin/sales/orders') }}/${id}/unconfirm`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json'
                }
            });

            // ✅ read body ONCE, safely
            const contentType = res.headers.get('content-type') || '';
            let data = {};
            if (contentType.includes('application/json')) {
                data = await res.json();          // <-- only place we consume body
            } else {
                const text = await res.text();    // <-- fallback for HTML/errors
                data = { message: text };
            }

            if(!res.ok){
                throw new Error(data.message || `Unconfirm failed (HTTP ${res.status})`);
            }

            Swal.fire({
                icon: 'success',
                title: 'Reverted',
                text: data.message || 'Order reverted to draft',
                timer: 1400,
                showConfirmButton: false
            });

            if (typeof ordersTable !== 'undefined' && ordersTable) {
                ordersTable.ajax.reload(null,false);
            }

        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: e.message || 'Unconfirm failed'
            });
        }
    });
};

</script>
@endpush
