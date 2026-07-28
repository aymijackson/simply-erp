@extends('layouts.master')

@section('title','Finance Settings')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Finance Settings</h1>
      <small class="text-muted">Finance / Accounting Configuration</small>
    </div>

    <button class="btn btn-primary" id="saveBtn">
      <i class="fas fa-save"></i> Save Settings
    </button>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">
          <i class="fas fa-cogs me-1"></i> Posting & Closing Accounts
        </div>

        <div class="card-body">
          <form id="financeSettingsForm">

            <div class="row g-3">

              <div class="col-md-6">
                <label class="form-label">Retained Earnings Account <span class="text-danger">*</span></label>
                <select class="form-control" name="retained_earnings_account_id" required>
                  <option value="">-- Select --</option>
                  @foreach($accounts as $a)
                    <option value="{{ $a->id }}"
                      @selected(($settings['retained_earnings_account_id'] ?? null) == $a->id)>
                      {{ $a->code }} - {{ $a->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Used when transferring net profit/loss at year end.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Income Summary Account <span class="text-danger">*</span></label>
                <select class="form-control" name="income_summary_account_id" required>
                  <option value="">-- Select --</option>
                  @foreach($accounts as $a)
                    <option value="{{ $a->id }}"
                      @selected(($settings['income_summary_account_id'] ?? null) == $a->id)>
                      {{ $a->code }} - {{ $a->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Temporary account used to close income/expense.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Accounts Receivable (Control) <span class="text-danger">*</span></label>
                <select class="form-control" name="ar_control_account_id" required>
                  <option value="">-- Select --</option>
                  @foreach($accounts as $a)
                    <option value="{{ $a->id }}"
                      @selected(($settings['ar_control_account_id'] ?? null) == $a->id)>
                      {{ $a->code }} - {{ $a->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Default AR control account for customer postings.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Accounts Payable (Control) <span class="text-danger">*</span></label>
                <select class="form-control" name="ap_control_account_id" required>
                  <option value="">-- Select --</option>
                  @foreach($accounts as $a)
                    <option value="{{ $a->id }}"
                      @selected(($settings['ap_control_account_id'] ?? null) == $a->id)>
                      {{ $a->code }} - {{ $a->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Default AP control account for supplier postings.</small>
              </div>

              <hr class="my-2">

              <div class="col-md-6">
                <label class="form-label">Default Cash/Bank Account</label>
                <select class="form-control" name="default_cash_account_id">
                  <option value="">-- None --</option>
                  @foreach($accounts as $a)
                    <option value="{{ $a->id }}"
                      @selected(($settings['default_cash_account_id'] ?? null) == $a->id)>
                      {{ $a->code }} - {{ $a->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Used when receipt/payment has no bank selected.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">VAT Output Account</label>
                <select class="form-control" name="vat_output_account_id">
                  <option value="">-- None --</option>
                  @foreach($accounts as $a)
                    <option value="{{ $a->id }}"
                      @selected(($settings['vat_output_account_id'] ?? null) == $a->id)>
                      {{ $a->code }} - {{ $a->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Sales VAT liability.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">VAT Input Account</label>
                <select class="form-control" name="vat_input_account_id">
                  <option value="">-- None --</option>
                  @foreach($accounts as $a)
                    <option value="{{ $a->id }}"
                      @selected(($settings['vat_input_account_id'] ?? null) == $a->id)>
                      {{ $a->code }} - {{ $a->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Purchase VAT receivable.</small>
              </div>

            </div>

          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">
          <i class="fas fa-shield-alt me-1"></i> Safety Checks
        </div>
        <div class="card-body small text-muted">
          <ul class="mb-0">
            <li>Year-end close requires Retained Earnings + Income Summary.</li>
            <li>AR/AP controls are mandatory before auto-posting invoices & bills.</li>
            <li>All selected accounts must belong to the current company.</li>
          </ul>
        </div>
      </div>

      <div class="card shadow-sm mt-3">
        <div class="card-header fw-bold">
          <i class="fas fa-info-circle me-1"></i> Notes
        </div>
        <div class="card-body small text-muted">
          This screen saves into <code>settings</code> (scope: company). It’s designed to integrate cleanly with all finance posting services.
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

function swalOk(msg){
  Swal.fire({icon:'success', title:'Success', text: msg || 'Saved.'});
}
function swalErr(xhr, fallback){
  const msg = xhr?.responseJSON?.message || fallback || 'Something went wrong.';
  Swal.fire({icon:'error', title:'Error', text: msg});
}

$('#saveBtn').on('click', function(){
  const form = $('#financeSettingsForm');

  Swal.fire({
    title: 'Save Finance Settings?',
    text: 'These settings affect posting, reports, and year-end close.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, Save'
  }).then((r)=>{
    if(!r.isConfirmed) return;

    Swal.fire({
      title: 'Saving...',
      allowOutsideClick: false,
      didOpen: ()=>Swal.showLoading()
    });

    $.post("{{ route('admin.finance.settings.save') }}", form.serialize())
      .done(res=>{
        Swal.close();
        swalOk(res.message);
      })
      .fail(xhr=>{
        Swal.close();
        swalErr(xhr, 'Failed to save.');
      });
  });
});
</script>
@endpush