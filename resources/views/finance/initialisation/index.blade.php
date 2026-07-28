@extends('layouts.master')

@section('title','Finance Initialisation')

@section('content')

<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-lg-12">
            <h4 class="mb-0">
                <i class="fas fa-cogs text-primary"></i>
                Finance System Initialisation
            </h4>
            <small class="text-muted">
                Initialise core finance configuration including Chart of Accounts and system mappings.
            </small>
        </div>
    </div>


    <div class="alert alert-warning">
        <strong>Important:</strong>
        This operation seeds essential financial master data such as
        <b>Account Types</b>, <b>Chart of Accounts</b>, <b>Account Mappings</b>,
        and <b>Finance Company Settings</b>.
        <br>
        Existing records will not be duplicated.
    </div>


    <div class="row">

        <div class="col-lg-6">

            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Preview Existing Finance Data</strong>
                </div>

                <div class="card-body">

                    <table class="table table-bordered table-sm">
                        <thead>
                        <tr>
                            <th>Table</th>
                            <th class="text-end">Existing Records</th>
                        </tr>
                        </thead>

                        <tbody id="previewTable">

                        <tr>
                            <td>Account Types</td>
                            <td class="text-end">-</td>
                        </tr>

                        <tr>
                            <td>Chart of Accounts</td>
                            <td class="text-end">-</td>
                        </tr>

                        <tr>
                            <td>Account Mappings</td>
                            <td class="text-end">-</td>
                        </tr>

                        <tr>
                            <td>Company Finance Settings</td>
                            <td class="text-end">-</td>
                        </tr>

                        </tbody>
                    </table>

                    <button id="btnPreview"
                            class="btn btn-outline-primary">
                        <i class="fas fa-search"></i>
                        Preview
                    </button>

                </div>

            </div>

        </div>



        <div class="col-lg-6">

            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Initialise Finance System</strong>
                </div>

                <div class="card-body">

                    <div class="form-group mb-3">
                        <label>Confirmation Phrase</label>
                        <input
                                type="text"
                                class="form-control"
                                id="confirm_phrase"
                                placeholder="Type INITIALISE FINANCE"
                        >
                        <small class="text-muted">
                            Required for safety before running the process.
                        </small>
                    </div>


                    <button id="btnRun"
                            class="btn btn-success">

                        <i class="fas fa-play"></i>
                        Run Initialisation

                    </button>

                </div>

            </div>

        </div>


    </div>



    <div class="row mt-4 d-none" id="resultSection">

        <div class="col-lg-12">

            <div class="card shadow-sm">

                <div class="card-header">
                    <strong>Initialisation Results</strong>
                </div>

                <div class="card-body">

                    <table class="table table-bordered table-sm">

                        <thead>
                        <tr>
                            <th>Table</th>
                            <th class="text-center">Created</th>
                            <th class="text-center">Skipped</th>
                        </tr>
                        </thead>

                        <tbody id="resultTable"></tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


</div>

@endsection


@push('scripts')

<script>

$.ajaxSetup({
    headers:{
        'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
    }
});



$('#btnPreview').click(function(){

    $.post("{{ route('admin.finance.initialisation.preview') }}",{})

        .done(function(res){

            const data = res.summary;

            let rows = `
            <tr>
                <td>Account Types</td>
                <td class="text-end">${data.account_types_existing}</td>
            </tr>

            <tr>
                <td>Chart of Accounts</td>
                <td class="text-end">${data.accounts_existing}</td>
            </tr>

            <tr>
                <td>Account Mappings</td>
                <td class="text-end">${data.mappings_existing}</td>
            </tr>

            <tr>
                <td>Company Finance Settings</td>
                <td class="text-end">${data.company_settings_existing}</td>
            </tr>
            `;

            $('#previewTable').html(rows);

        })

        .fail(function(xhr){

            Swal.fire(
                'Error',
                xhr.responseJSON?.message ?? 'Preview failed',
                'error'
            );

        });

});



$('#btnRun').click(function(){

    let phrase = $('#confirm_phrase').val();

    if(!phrase){

        Swal.fire(
            'Confirmation Required',
            'Type INITIALISE FINANCE to continue',
            'warning'
        );

        return;
    }

    Swal.fire({

        title:'Initialise Finance System?',
        text:'This will seed core financial configuration.',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Yes, initialise'

    }).then(function(result){

        if(!result.isConfirmed) return;

        $.post(
            "{{ route('admin.finance.initialisation.run') }}",
            {confirm_phrase:phrase}
        )

        .done(function(res){

            Swal.fire(
                'Success',
                res.message,
                'success'
            );

            $('#resultSection').removeClass('d-none');

            let html='';

            Object.entries(res.result).forEach(function([table,val]){

                html += `
                <tr>
                    <td>${table}</td>
                    <td class="text-center text-success">${val.created}</td>
                    <td class="text-center text-warning">${val.skipped}</td>
                </tr>
                `;

            });

            $('#resultTable').html(html);

        })

        .fail(function(xhr){

            Swal.fire(
                'Error',
                xhr.responseJSON?.message ?? 'Initialisation failed',
                'error'
            );

        });

    });

});

</script>

@endpush