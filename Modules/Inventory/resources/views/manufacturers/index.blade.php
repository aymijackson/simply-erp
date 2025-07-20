@extends('inventory::layouts.master')

@section('title', 'Manage Item Manufacturers')

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
                            <h6>Total Manufacturers</h6>
                            <h4 class="mb-0" id="totalManufacturers">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manufacturers Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6>Item Manufacturers</h6>
                    <button class="btn btn-primary btn-sm" id="addManufacturerBtn">
                        <i class="fas fa-plus"></i> Add Manufacturer
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="manufacturerTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Manufacturer Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Manufacturer Modal -->
<div class="modal fade" id="manufacturerModal" tabindex="-1" aria-labelledby="manufacturerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add/Edit Item Manufacturer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="manufacturerForm">
                    @csrf
                    <input type="hidden" id="manufacturerId">
                    <div class="mb-3">
                        <label class="form-label">Manufacturer Name</label>
                        <input type="text" class="form-control" id="manufacturerName" required>
                    </div>
                    <button type="submit" class="btn btn-success">Save Manufacturer</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let table = $('#manufacturerTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('inventory.manufacturers.datatable') }}",
            columns: [
                { data: 'id' },
                { data: 'manufacturer_name' },
                { data: 'action', orderable: false, searchable: false }
            ],
            drawCallback: function() {
                $.ajax({
                    url: "{{ route('inventory.manufacturers.datatable') }}",
                    type: "GET",
                    success: function(response) {
                        $("#totalManufacturers").text(response.recordsTotal);
                    }
                });
            }
        });

        // Open modal for adding new manufacturer
        $("#addManufacturerBtn").click(function() {
            $("#manufacturerForm")[0].reset();
            $("#manufacturerId").val('');
            $("#manufacturerModal").modal('show');
        });

        // Open modal for editing manufacturer
        $('#manufacturerTable').on('click', '.edit', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
alert(id)
            $("#manufacturerId").val(id);
            $("#manufacturerName").val(name);
            $("#manufacturerModal").modal('show');
        });

        // Save manufacturer
        $("#manufacturerForm").on("submit", function(event) {
            event.preventDefault();

            let manufacturerId = $("#manufacturerId").val();
            let formData = {
                id: manufacturerId,
                manufacturer_name: $("#manufacturerName").val(),
                _token: "{{ csrf_token() }}"
            };

            $.ajax({
                url: "{{ route('inventory.manufacturers.store') }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    $("#manufacturerModal").modal("hide");
                    table.ajax.reload();

                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while saving.',
                    });
                }
            });
        });

        // Delete manufacturer
        $('#manufacturerTable').on('click', '.delete', function() {
            let id = $(this).data('id');

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
                        url: "{{ url('inventory/manufacturers') }}/" + id,
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
