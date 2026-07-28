@extends('layouts.master')

@section('content')
<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Account Types</h4>
            <div class="text-muted small">
                Manage chart of account type definitions.
            </div>
        </div>
        @can('finance.account_type.create')
        <div>
            <button class="btn btn-primary" id="btnAdd">
                <i class="fas fa-plus me-1"></i> New Account Type
            </button>
        </div>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped w-100" id="accountTypesTable">
                    <thead>
                        <tr>
                            <th style="width:80px;">ID</th>
                            <th style="width:140px;">Code</th>
                            <th>Name</th>
                            <th style="width:140px;">Category</th>
                            <th style="width:140px;">Normal Balance</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Modal --}}
<div class="modal fade" id="accountTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="accountTypeForm">
                @csrf
                <input type="hidden" id="row_id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">New Account Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" maxlength="50" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" maxlength="150" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" required>
                                <option value="">-- Select Category --</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="cogs">COGS</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Normal Balance <span class="text-danger">*</span></label>
                            <select class="form-select" id="normal_balance" required>
                                <option value="">-- Select Balance --</option>
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>

                    </div>

                    <div class="alert alert-info mt-3 mb-0 small">
                        <strong>Guide:</strong>
                        Assets, Expenses and COGS are usually <b>debit</b> balance.
                        Liabilities, Equity and Income are usually <b>credit</b> balance.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="btnSave">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let accountTypesTable;
    let modal = new bootstrap.Modal(document.getElementById('accountTypeModal'));

    function resetForm() {
        $('#accountTypeForm')[0].reset();
        $('#row_id').val('');
        $('#modalTitle').text('New Account Type');
    }

    function loadTable() {
        accountTypesTable = $('#accountTypesTable').DataTable({
            processing: true,
            destroy: true,
            ajax: "{{ route('admin.finance.account_types.datatable') }}",
            columns: [
                { data: 'id' },
                { data: 'code' },
                { data: 'name' },
                {
                    data: 'category',
                    render: function(data) {
                        return `<span class="badge bg-info text-dark text-uppercase">${data}</span>`;
                    }
                },
                {
                    data: 'normal_balance',
                    render: function(data) {
                        let cls = data === 'debit' ? 'success' : 'primary';
                        return `<span class="badge bg-${cls} text-uppercase">${data}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(row) {
                        return `
                            @can('finance.account_type.edit')
                            <button class="btn btn-sm btn-warning btn-edit" data-id="${row.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcan
                            @can('finance.account_type.delete')
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${row.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endcan
                        `;
                    }
                }
            ]
        });
    }

    $(document).ready(function() {
        loadTable();

        $('#btnAdd').on('click', function() {
            resetForm();
            modal.show();
        });

        $('#category').on('change', function() {
            const category = $(this).val();

            if (category === 'asset' || category === 'expense' || category === 'cogs') {
                $('#normal_balance').val('debit');
            } else if (category === 'liability' || category === 'equity' || category === 'income') {
                $('#normal_balance').val('credit');
            }
        });

        $('#accountTypeForm').on('submit', function(e) {
            e.preventDefault();

            const id = $('#row_id').val();
            const url = id
                ? `{{ url('admin/finance/account-types') }}/${id}`
                : `{{ route('admin.finance.account_types.store') }}`;

            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: {
                    code: $('#code').val(),
                    name: $('#name').val(),
                    category: $('#category').val(),
                    normal_balance: $('#normal_balance').val(),
                },
                success: function(res) {
                    modal.hide();
                    accountTypesTable.ajax.reload(null, false);

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: res.message
                    });
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.message || 'Something went wrong.';
                    if (xhr.responseJSON?.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: msg
                    });
                }
            });
        });

        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');

            $.get(`{{ url('admin/finance/account-types') }}/${id}`, function(res) {
                const d = res.data;

                $('#row_id').val(d.id);
                $('#code').val(d.code);
                $('#name').val(d.name);
                $('#category').val(d.category);
                $('#normal_balance').val(d.normal_balance);
                $('#modalTitle').text('Edit Account Type');

                modal.show();
            });
        });

        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Delete account type?',
                text: 'This action cannot be easily undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/finance/account-types') }}/${id}`,
                        method: 'DELETE',
                        success: function(res) {
                            accountTypesTable.ajax.reload(null, false);

                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: res.message
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'Delete failed.'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush