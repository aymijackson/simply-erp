@extends('inventory::layouts.master')

@section('title', 'Manage Item Categories')

@section('content')
<div class="container-fluid py-4">
    <!-- Metrics -->
    <div class="row">
        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-3">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <h6>Total Categories</h6>
                            <h4 class="mb-0" id="totalCategories">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Categories Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6>Item Categories</h6>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="categoryTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Category Name</th>
                                    <th>Category Code</th>
                                    <th>Type</th>
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

<!-- Add/Edit Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    @csrf
                    <input type="hidden" id="categoryId">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="name" id="categoryName" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryCode" class="form-label">Category Code</label>
                        <input type="text" class="form-control" id="categoryCode" name="code" required>
                    </div>
                    <button type="submit" class="btn btn-success">Save Category</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        let table = $('#categoryTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('inventory.raw-materials.categories.datatable') }}",
            columns: [
                { data: 'id' },
                { data: 'category_name' },
                { data: 'category_code' },
                { data: 'item_type' },
                { data: 'action', orderable: false, searchable: false }
            ],
            drawCallback: function() {
                $.ajax({
                    url: "{{ route('inventory.raw-materials.categories.metrics') }}",
                    type: "GET",
                    success: function(response) {
                        $("#totalCategories").text(response.total);
                    }
                });
            }
        });

        // Event handler for opening the modal in "Add" mode
        $("#addCategoryModal").on("show.bs.modal", function(event) {
            // If the button that triggered the modal does not have an edit data attribute,
            // we assume it is for adding a new category.
            var button = $(event.relatedTarget);
            if (!button.data('id')) {
                // Clear the form for adding new category
                $("#categoryForm")[0].reset();
                $("#categoryId").val('');
                $(this).find('.modal-title').text('Add Item Category');
            }
        });

        // Event delegation for edit buttons
        $('#categoryTable').on('click', '.edit', function() {
            // Retrieve the row data (assuming your table returns these keys)
            let data = table.row($(this).closest('tr')).data();
            // Populate the form fields with the row data
            $("#categoryId").val(data.id);
            $("#categoryName").val(data.category_name);
            $("#categoryCode").val(data.category_code);
            // Change modal title to indicate edit mode
            $("#addCategoryModal").find('.modal-title').text('Edit Item Category');
            // Show the modal
            $("#addCategoryModal").modal('show');
        });

        // Add / Edit Category form submission
        $("#categoryForm").on("submit", function(event) {
            event.preventDefault();
            let categoryId = $("#categoryId").val();
            // Determine URL and method based on whether categoryId exists
            let url = categoryId 
                ? "{{ url('inventory/raw-materials/categories') }}/" + categoryId 
                : "{{ route('inventory.raw-materials.categories.store') }}";
            let method = categoryId ? "POST" : "POST"; // We still send POST for update
            // Prepare form data; include _method if updating
            let formData = {
                name: $("#categoryName").val(),
                code: $("#categoryCode").val(),
                type: $("#categoryType").val(),
                _token: "{{ csrf_token() }}"
            };
            if (categoryId) {
                formData._method = "PUT";
            }

            $.ajax({
                url: url,
                type: method,
                data: formData,
                success: function(response) {
                    $("#addCategoryModal").modal("hide");
                    table.ajax.reload();

                    // Success Alert with SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Category saved successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr, status, error) {
                    // Extract error message if available
                    let errMsg = "An error occurred while saving the category.";
                    if(xhr.responseJSON && xhr.responseJSON.message){
                        errMsg = xhr.responseJSON.message;
                    }
                    // Error Alert with SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errMsg
                    });
                }
            });
        });
    });
</script>
@endpush
