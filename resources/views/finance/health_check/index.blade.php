@extends('layouts.master')

@section('title','Finance Health Check')

@section('content')
<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-lg-12">
            <h4 class="mb-0">
                <i class="fas fa-stethoscope text-primary"></i>
                Finance System Health Check
            </h4>
            <small class="text-muted">
                Scan the finance system for structural issues, missing setup, and posting inconsistencies.
            </small>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>What this checks:</strong>
        Chart of Accounts setup, account mappings, finance settings, orphaned journals,
        unbalanced entries, bank GL links, and fixed asset configuration.
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <button id="btnRunHealthCheck" class="btn btn-primary">
                <i class="fas fa-play"></i> Run Health Check
            </button>
        </div>
    </div>

    <div class="row d-none" id="summarySection">
        <div class="col-md-4">
            <div class="card border-success shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-success" id="okCount">0</h5>
                    <small>OK Checks</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-warning shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-warning" id="warningCount">0</h5>
                    <small>Warnings</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-danger shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-danger" id="errorCount">0</h5>
                    <small>Errors</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3 d-none" id="resultSection">
        <div class="card-header">
            <strong>Health Check Results</strong>
        </div>
        <div class="card-body">

            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Status</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody id="resultTable"></tbody>
            </table>

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

$('#btnRunHealthCheck').click(function(){

    Swal.fire({
        title:'Run Finance Health Check?',
        text:'The system will scan key finance setup and records.',
        icon:'question',
        showCancelButton:true,
        confirmButtonText:'Run Check'
    }).then(function(result){

        if(!result.isConfirmed) return;

        $.post("{{ route('admin.finance.health_check.run') }}", {})
        .done(function(res){

            const summary = res.result.summary;
            const checks = res.result.checks;

            $('#summarySection').removeClass('d-none');
            $('#resultSection').removeClass('d-none');

            $('#okCount').text(summary.ok_count);
            $('#warningCount').text(summary.warning_count);
            $('#errorCount').text(summary.error_count);

            let html = '';

            checks.forEach(function(check){

                let badge = '';

                if(check.status === 'ok'){
                    badge = '<span class="badge bg-success">OK</span>';
                }else if(check.status === 'warning'){
                    badge = '<span class="badge bg-warning text-dark">Warning</span>';
                }else{
                    badge = '<span class="badge bg-danger">Error</span>';
                }

                html += `
                    <tr>
                        <td>${check.name}</td>
                        <td>${badge}</td>
                        <td>${check.message}</td>
                    </tr>
                `;
            });

            $('#resultTable').html(html);

            Swal.fire(
                'Completed',
                'Finance health check finished successfully.',
                'success'
            );

        })
        .fail(function(xhr){
            Swal.fire(
                'Error',
                xhr.responseJSON?.message ?? 'Health check failed',
                'error'
            );
        });

    });

});
</script>
@endpush