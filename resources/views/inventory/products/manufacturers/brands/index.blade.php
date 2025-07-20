@extends('layouts.master')

@section('title', 'Manage Brands')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">Brands <small class="text-muted">Inventory</small></h1>
        <div>
            <button class="btn btn-danger me-2" id="bulkDeleteBtn" style="display: none;">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected
            </button>
            <button class="btn btn-primary" id="addBrandBtn">
                <i class="fas fa-plus me-1"></i> Add Brand
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <h6>Total Brands</h6>
                        <h4 class="mb-0" id="totalBrands">{{ number_format($brands_count ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered w-100" id="brandTable">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="selectAllBrands"></th>
                            <th>Brand Name</th>
                            <th>Brand Code</th>
                            <th>Manufacturer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Brand Modal -->
<div class="modal fade" id="brandModal" tabindex="-1" aria-labelledby="brandModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="brandForm" class="modal-content">
            @csrf
            <input type="hidden" id="brandId">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="brandModalLabel">Add Brand</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="manufacturer" class="form-label">Manufacturer</label>
                    <select class="form-control" id="manufacturer" required>
                        <option value="">Select Manufacturer</option>
                        @foreach($manufacturers as $manufacturer)
                            <option value="{{ $manufacturer->id }}">{{ $manufacturer->manufacturer_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="brandName" class="form-label">Brand Name</label>
                    <input type="text" class="form-control" id="brandName" required>
                </div>
                <div class="mb-3">
                    <label for="brandCode" class="form-label">Brand Code</label>
                    <input type="text" class="form-control" id="brandCode" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Brand</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#brandTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.inventory.products.brands.datatable') }}",
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'brand_name' },
            { data: 'brand_code' },
            { data: 'manufacturer_name' },
            { data: 'action', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            $.get("{{ route('admin.inventory.products.brands.metrics') }}", function (response) {
                $('#totalBrands').text(response.total);
            });
        }
    });

    // Toggle Bulk Delete visibility
    function toggleBulkDelete() {
        const selected = $('.brand-checkbox:checked').length > 0;
        $('#bulkDeleteBtn').toggle(selected);
    }

    // Select All checkbox
    $('#selectAllBrands').on('change', function () {
        $('.brand-checkbox').prop('checked', this.checked);
        toggleBulkDelete();
    });

    // Checkbox row click
    $(document).on('change', '.brand-checkbox', toggleBulkDelete);

    // Open modal
    $('#addBrandBtn').click(function () {
        $('#brandForm')[0].reset();
        $('#brandId').val('');
        $('#brandModalLabel').text('Add Brand');
        $('#brandModal').modal('show');
    });

    // Edit brand
    $('#brandTable').on('click', '.edit-btn', function () {
        let row = $(this).closest('tr');
        let data = table.row(row).data();
        $('#brandId').val(data.id);
        $('#manufacturer').val(data.manufacturer_id);
        $('#brandName').val(data.brand_name);
        $('#brandCode').val(data.brand_code);
        $('#brandModalLabel').text('Edit Brand');
        $('#brandModal').modal('show');
    });

    // Submit form (add or update)
    $('#brandForm').submit(function (e) {
        e.preventDefault();
        const brandId = $('#brandId').val();
        const formData = {
            manufacturer_id: $('#manufacturer').val(),
            brand_name: $('#brandName').val(),
            brand_code: $('#brandCode').val(),
            _token: '{{ csrf_token() }}'
        };
        const url = brandId
            ? `{{ url('admin/inventory/products/brands') }}/${brandId}`
            : `{{ route('admin.inventory.products.brands.store') }}`;
        const method = brandId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function (response) {
                $('#brandModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Success', response.message, 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save brand.', 'error');
            }
        });
    });

    // Delete single brand
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/inventory/products/brands/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        table.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    }
                });
            }
        });
    });

    // Bulk Delete
    $('#bulkDeleteBtn').click(function () {
        const ids = $('.brand-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) return;

        Swal.fire({
            title: `Delete ${ids.length} selected brand(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete them!'
        }).then(result => {
            if (result.isConfirmed) {
                $.post(`{{ route('admin.inventory.products.brands.bulk-delete') }}`, {
                    _token: '{{ csrf_token() }}',
                    ids
                }, function (response) {
                    $('#selectAllBrands').prop('checked', false);
                    $('#bulkDeleteBtn').hide();
                    table.ajax.reload();
                    Swal.fire('Deleted!', response.message, 'success');
                }).fail(function () {
                    Swal.fire('Error', 'Failed to delete selected brands.', 'error');
                });
            }
        });
    });
});
</script>
@endpush

