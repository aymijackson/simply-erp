@extends('inventory::layouts.master')

@section('title', 'Manage Brands')

@section('content')
<div class="container-fluid py-4">
    <!-- Metrics -->
    <div class="row">
        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div>
                            <h6>Total Brands</h6>
                            <h4 class="mb-0" id="totalBrands">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Brands Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6>Brands</h6>
                    <button class="btn btn-primary btn-sm" id="addBrandBtn">
                        <i class="fas fa-plus"></i> Add Brand
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="brandTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Manufacturer</th>
                                    <th>Brand Name</th>
                                    <th>Brand Code</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Brand Modal -->
<div class="modal fade" id="brandModal" tabindex="-1" aria-labelledby="brandModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="brandForm">
                    @csrf
                    <input type="hidden" id="brandId">
                    
                    <div class="mb-3">
                        <label class="form-label">Manufacturer</label>
                        <select class="form-control" id="manufacturer" required>
                            <option value="">Select Manufacturer</option>
                            @foreach($manufacturers as $manufacturer)
                                <option value="{{ $manufacturer->id }}">{{ $manufacturer->manufacturer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Brand Name</label>
                        <input type="text" class="form-control" id="brandName" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Brand Code</label>
                        <input type="text" class="form-control" id="brandCode" required>
                    </div>

                    <button type="submit" class="btn btn-success">Save Brand</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let table = $('#brandTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('inventory.brands.datatable') }}",
            columns: [
                { data: 'id' },
                { data: 'manufacturer.manufacturer_name' }, // Display Manufacturer Name
                { data: 'brand_name' },
                { data: 'brand_code' },
                { data: 'action', orderable: false, searchable: false }
            ],
            drawCallback: function() {
                $.ajax({
                    url: "{{ route('inventory.brands.metrics') }}",
                    type: "GET",
                    success: function(response) {
                        $("#totalBrands").text(response.total);
                    }
                });
            }
        });

        // Open modal for adding a new brand
        $("#addBrandBtn").click(function() {
            $("#brandForm")[0].reset();
            $("#brandId").val('');
            $("#brandModal").modal('show');
        });

        // Open modal for editing a brand
        $('#brandTable').on('click', '.edit', function() {
            let data = table.row($(this).closest('tr')).data();
            $("#brandId").val(data.id);
            $("#manufacturer").val(data.manufacturer_id);
            $("#brandName").val(data.brand_name);
            $("#brandCode").val(data.brand_code);
            $("#brandModal").modal('show');
        });

        // Save or Update Brand
        $("#brandForm").on("submit", function(event) {
            event.preventDefault();
            let brandId = $("#brandId").val();
            let formData = {
                id: brandId,
                manufacturer_id: $("#manufacturer").val(),
                brand_name: $("#brandName").val(),
                brand_code: $("#brandCode").val(),
                _token: "{{ csrf_token() }}"
            };

            let url = brandId 
                ? "{{ url('inventory/brands') }}/" + brandId 
                : "{{ route('inventory.brands.store') }}";
alert(brandId)
            $.ajax({
                url: url,
                type: "POST",
                data: formData,
                success: function(response) {
                    $("#brandModal").modal("hide");
                    table.ajax.reload();
                    Swal.fire('Success', response.message, 'success');
                },
                error: function(xhr) {
                    Swal.fire('Error', 'Failed to save brand.', 'error');
                }
            });
        });

        // Delete Brand
        $('#brandTable').on('click', '.delete', function() {
            let brandId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('inventory/brands') }}/" + brandId,
                        type: "DELETE",
                        data: { _token: "{{ csrf_token() }}" },
                        success: function(response) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', response.message, 'success');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
