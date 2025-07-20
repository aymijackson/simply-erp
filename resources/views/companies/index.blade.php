@extends('layouts.master')

@section('title', 'Manage Companies')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Companies</h1>
        <div>
            <button class="btn btn-primary" id="createCompany">Add Company</button>
            <button class="btn btn-danger" id="bulkDeleteCompanies">Delete Selected</button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="companiesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Website</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Company Modal -->
    <div class="modal fade" id="companyModal" tabindex="-1" role="dialog" aria-labelledby="companyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="companyModalLabel">Add Company</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="companyForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="company_id">
                        <div class="form-group mb-3">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                        <div class="form-group mb-3">
                            <label for="website">Website</label>
                            <input type="url" class="form-control" name="website" id="website">
                        </div>
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <textarea class="form-control" name="address" id="address"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    $(function () {
        // Initialize DataTable
        const table = $('#companiesTable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: '{{ route("admin.companies.index") }}',
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'name' },
                { data: 'email' },
                { data: 'website' },
                { data: 'address' },
                { data: 'actions', orderable: false, searchable: false },
            ]
        });

        // Select all checkboxes
        $('#selectAll').on('click', function () {
            $('input[name="company_checkbox[]"]').prop('checked', this.checked);
        });

        // Add Company
        $('#createCompany').on('click', function () {
            $('#companyForm')[0].reset();
            $('#company_id').val('');
            $('#companyModalLabel').text('Add Company');
            $('#companyModal').modal('show');
        });

        // Save Company (create or update)
        $('#companyForm').on('submit', function (e) {
            e.preventDefault();
            const id = $('#company_id').val();
            const url = id ? `/admin/companies/${id}` : '{{ route("admin.companies.store") }}';
            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: $(this).serialize(),
                success: function (response) {
                    $('#companyModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                }
            });
        });

        // Edit Company
        $('#companiesTable').on('click', '.edit-company', function () {
            const id = $(this).data('id');
            $.get(`/admin/companies/${id}/edit`)
             .done(res => {
                const c = res.company;
                $('#company_id').val(c.id);
                $('#name').val(c.name);
                $('#email').val(c.email);
                $('#website').val(c.website);
                $('#address').val(c.address);
                $('#companyModalLabel').text('Edit Company');
                $('#companyModal').modal('show');
             })
             .fail(() => {
                Swal.fire('Error', 'Could not load company data', 'error');
             });
        });

        // Delete single Company
        $('#companiesTable').on('click', '.delete-company', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Delete this company?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(result => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: `/admin/companies/${id}`,
                    method: 'DELETE',
                    success: function (res) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: function () {
                        Swal.fire('Error', 'Could not delete company', 'error');
                    }
                });
            });
        });

        // Bulk Delete Companies
        $('#bulkDeleteCompanies').on('click', function () {
            const ids = $('input[name="company_checkbox[]"]:checked')
                          .map(function () { return this.value; })
                          .get();

            if (!ids.length) {
                return Swal.fire({
                    icon: 'info',
                    title: 'Select at least one company'
                });
            }

            Swal.fire({
                title: `Delete ${ids.length} selected company(ies)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ route("admin.companies.bulk-delete") }}',
                    method: 'POST',
                    data: { ids, _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload();
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                    }
                });
            });
        });
    });
</script>
@endpush
