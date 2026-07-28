@extends('layouts.master')

@section('title', 'Sales Credit Notes')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Credit Notes</h1>
            <small class="text-muted">Sales / Credit Notes</small>
        </div>

        <a href="{{ route('admin.sales.credit-notes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Credit Note
        </a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Credit Note No</label>
                    <input type="text" id="f_credit_note_no" class="form-control" placeholder="CN-...">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">Customer</label>
                    <select id="f_customer_id" class="form-control">
                        <option value="">All customers</option>
                        {{-- optionally populate with customers --}}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Status</label>
                    <select id="f_status" class="form-control">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="void">Void</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-outline-primary w-100" id="applyBtn">
                        <i class="fas fa-search"></i> Apply
                    </button>
                    <button class="btn btn-outline-secondary w-100" id="resetBtn">
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="cnTable" style="width:100%;">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th>Credit Note No</th>
                            <th>Customer</th>
                            <th>Invoice</th>
                            <th style="width:130px;">Date</th>
                            <th class="text-end" style="width:150px;">Amount</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:90px;">Actions</th>
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
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dtUrl = "{{ route('admin.sales.credit-notes.datatable') }}";

let table;

function initTable(){
    table = $('#cnTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthMenu: [10,25,50,100],
        pageLength: 10,
        ajax: {
            url: dtUrl,
            data: function(d){
                d.credit_note_no = $('#f_credit_note_no').val();
                d.customer_id = $('#f_customer_id').val();
                d.status = $('#f_status').val();
            }
        },
        columns: [
            {data:'id', name:'id'},
            {data:'credit_note_no', name:'credit_note_no'},
            {data:'customer', name:'customer'},
            {data:'invoice_no', name:'invoice_no', orderable:false},
            {data:'date', name:'credit_note_date'},
            {data:'amount', name:'grand_total', className:'text-end'},
            {data:'status', name:'status', orderable:false, searchable:false},
            {data:'actions', orderable:false, searchable:false},
        ],
        order: [[0,'desc']],
        drawCallback: function(){ /* attach handlers if needed */ }
    });
}

$('#applyBtn').on('click', function(){ table.ajax.reload(); });
$('#resetBtn').on('click', function(){
    $('#f_credit_note_no').val('');
    $('#f_customer_id').val('');
    $('#f_status').val('');
    table.ajax.reload();
});

$(function(){
    initTable();
});
</script>
@endpush
