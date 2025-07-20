@extends('inventory::layouts.master')

@section('title', 'Raw Materials Management')

@section('content')
<div class="container-fluid py-4">
    <!-- Metrics -->
    <div class="row">
        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <div>
                            <h6>Total Raw Materials</h6>
                            <h4 class="mb-0" id="totalRawMaterials">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="icon icon-shape bg-warning text-white rounded-circle shadow text-center me-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h6>Low Stock</h6>
                            <h4 class="mb-0" id="lowStock">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="icon icon-shape bg-danger text-white rounded-circle shadow text-center me-3">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div>
                            <h6>Out of Stock</h6>
                            <h4 class="mb-0" id="outOfStock">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Raw Materials Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6>Raw Materials List</h6>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRawMaterialModal">
                        <i class="fas fa-plus"></i> Add Raw Material
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="rawMaterialsTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Stock Level</th>
                                    <th>Unit</th>
                                    <th>Cost</th>
                                    <th>Action</th>
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

<!-- Add Raw Material Modal -->
<div class="modal fade" id="addRawMaterialModal" tabindex="-1" aria-labelledby="addRawMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Raw Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addRawMaterialForm">
                    @csrf
                    <div class="mb-3">
                        <label for="rawMaterialCode" class="form-label">Material Code</label>
                        <input type="text" class="form-control" id="rawMaterialCode" required>
                    </div>
                    <div class="mb-3">
                        <label for="materialName" class="form-label">Material Name</label>
                        <input type="text" class="form-control" id="materialName" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryId" class="form-label">Category</label>
                        <select class="form-control" id="categoryId" required>
                            <!-- Categories should be fetched dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="groupId" class="form-label">Group</label>
                        <select class="form-control" id="groupId" required>
                            <!-- Groups should be fetched dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="materialStock" class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" id="materialStock" required>
                    </div>
                    <div class="mb-3">
                        <label for="materialPrice" class="form-label">Price per Unit</label>
                        <input type="number" step="0.01" class="form-control" id="materialPrice" required>
                    </div>
                    <div class="mb-3">
                        <label for="materialUnit" class="form-label">Unit</label>
                        <input type="text" class="form-control" id="materialUnit" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="hasInstances">
                        <label class="form-check-label" for="hasInstances">Has Instances</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="hasLots">
                        <label class="form-check-label" for="hasLots">Has Lots</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="hasAttributes">
                        <label class="form-check-label" for="hasAttributes">Has Attributes</label>
                    </div>
                    <button type="submit" class="btn btn-success">Save Raw Material</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let table = $('#rawMaterialsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('inventory.raw-materials.list') }}",
        dom: 'Bfrtip', // Include buttons in the table
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                titleAttr: 'Export to Excel'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fas fa-file-csv"></i> CSV',
                className: 'btn btn-info btn-sm',
                titleAttr: 'Export to CSV'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                titleAttr: 'Export to PDF'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-primary btn-sm',
                titleAttr: 'Print Table'
            }
        ],
        columns: [
            { data: 'id' },
            { data: 'raw_material_name' },
            { data: 'category' },
            { 
                data: 'raw_material_stock_quantity',
                render: function(data) {
                    let stockClass = data == 0 ? 'bg-danger text-white' : data < 10 ? 'bg-warning' : 'bg-success';
                    return `<span class="badge ${stockClass}">${data}</span>`;
                }
            },
            { data: 'default_uom' },
            { 
                data: 'raw_material_price',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                }
            },
            { 
                data: 'action',
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-warning btn-sm edit-btn" data-id="${row.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="${row.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        drawCallback: function() {
            // Update dashboard metrics
            $.ajax({
                url: "{{ route('inventory.raw-materials.metrics') }}",
                type: "GET",
                success: function(response) {
                    $("#totalRawMaterials").text(response.total);
                    $("#lowStock").text(response.lowStock);
                    $("#outOfStock").text(response.outOfStock);
                }
            });
        }
    });

    // Add new raw material
    $("#addRawMaterialForm").on("submit", function (event) {
        event.preventDefault();
        
        $.ajax({
            url: "{{ route('inventory.raw-materials.store') }}",
            type: "POST",
            data: {
                raw_material_code: $("#rawMaterialCode").val(),
                category_id: $("#categoryId").val(),
                group_id: $("#groupId").val(),
                brand_id: $("#brandId").val(),
                generic_id: $("#genericId").val(),
                raw_material_name: $("#materialName").val(),
                raw_material_description: $("#materialDescription").val(),
                raw_material_price: $("#materialPrice").val(),
                default_uom: $("#materialUnit").val(),
                pack_size: $("#packSize").val(),
                average_cost: $("#averageCost").val(),
                single_unit_raw_material_code: $("#singleUnitCode").val(),
                dimension_group: $("#dimensionGroup").val(),
                lot_information: $("#lotInfo").val(),
                warranty_terms: $("#warrantyTerms").val(),
                raw_material_stock_quantity: $("#materialStock").val(),
                has_instances: $("#hasInstances").is(":checked") ? 1 : 0,
                has_lots: $("#hasLots").is(":checked") ? 1 : 0,
                has_attributes: $("#hasAttributes").is(":checked") ? 1 : 0,
                _token: "{{ csrf_token() }}"
            },
            success: function (response) {
                $("#addRawMaterialModal").modal("hide");
                $('#rawMaterialsTable').DataTable().ajax.reload();
                Swal.fire('Success', response.message, 'success');
            },
            error: function (xhr) {
                let errors = xhr.responseJSON.errors;
                let errorMessage = "";
                $.each(errors, function (key, value) {
                    errorMessage += value[0] + "\n";
                });
                Swal.fire('Error', errorMessage, 'error');
            }
        });
    });$("#addRawMaterialForm").on("submit", function (event) {
        event.preventDefault();
        
        $.ajax({
            url: "{{ route('inventory.raw-materials.store') }}",
            type: "POST",
            data: {
                raw_material_code: $("#rawMaterialCode").val(),
                category_id: $("#categoryId").val(),
                group_id: $("#groupId").val(),
                brand_id: $("#brandId").val(),
                generic_id: $("#genericId").val(),
                raw_material_name: $("#materialName").val(),
                raw_material_description: $("#materialDescription").val(),
                raw_material_price: $("#materialPrice").val(),
                default_uom: $("#materialUnit").val(),
                pack_size: $("#packSize").val(),
                average_cost: $("#averageCost").val(),
                single_unit_raw_material_code: $("#singleUnitCode").val(),
                dimension_group: $("#dimensionGroup").val(),
                lot_information: $("#lotInfo").val(),
                warranty_terms: $("#warrantyTerms").val(),
                raw_material_stock_quantity: $("#materialStock").val(),
                has_instances: $("#hasInstances").is(":checked") ? 1 : 0,
                has_lots: $("#hasLots").is(":checked") ? 1 : 0,
                has_attributes: $("#hasAttributes").is(":checked") ? 1 : 0,
                _token: "{{ csrf_token() }}"
            },
            success: function (response) {
                $("#addRawMaterialModal").modal("hide");
                $('#rawMaterialsTable').DataTable().ajax.reload();
                Swal.fire('Success', response.message, 'success');
            },
            error: function (xhr) {
                let errors = xhr.responseJSON.errors;
                let errorMessage = "";
                $.each(errors, function (key, value) {
                    errorMessage += value[0] + "\n";
                });
                Swal.fire('Error', errorMessage, 'error');
            }
        });
    });

    // Handle delete button
    $('#rawMaterialsTable').on('click', '.delete-btn', function() {
        let materialId = $(this).data('id');
        if (confirm('Are you sure you want to delete this raw material?')) {
            $.ajax({
                url: "{{ url('inventory/raw-materials') }}/" + materialId,
                type: "DELETE",
                data: { _token: "{{ csrf_token() }}" },
                success: function() {
                    table.ajax.reload();
                },
                error: function(xhr) {
                    alert("Delete failed: " + xhr.responseJSON.message);
                }
            });
        }
    });

    // Handle edit button
    $('#rawMaterialsTable').on('click', '.edit-btn', function() {
        let materialId = $(this).data('id');
        $.get("{{ url('inventory/raw-materials') }}/" + materialId, function(data) {
            $("#materialName").val(data.raw_material_name);
            $("#materialStock").val(data.raw_material_stock_quantity);
            $("#materialUnit").val(data.default_uom);
            $("#materialCost").val(data.raw_material_price);
            $("#addRawMaterialModal").modal("show");
        });
    });
});

</script>
@endpush
