@extends('layouts.master')

@section('title','Account Mappings')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Account Mappings</h1>
      <small class="text-muted">Finance / Setup</small>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary" id="editMappingsBtn">
        <i class="fas fa-edit me-1"></i> Configure Mappings
      </button>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12 mb-3">
      <div class="card shadow-sm">
        <div class="card-header fw-bold d-flex justify-content-between align-items-center">
          <span><i class="fas fa-random me-1"></i> Current Mapping</span>
          <span class="text-muted small">   Company: {{ $company->name ?? 'Unknown Company' }}</span>
        </div>

        <div class="card-body">

          <div class="alert alert-info">
            <i class="fas fa-info-circle me-1"></i>
            These mappings tell the system which GL accounts to use when auto-posting from Sales, Purchases, Inventory, VAT, Discounts, Year-End Closing and Cash/Bank transactions.
          </div>

          @php
            $accMap = $accounts->keyBy('id');

            $fmt = function($id) use ($accMap){
              if(!$id) return '<span class="text-muted">Not set</span>';
              $a = $accMap[$id] ?? null;
              if(!$a) return '<span class="text-danger">Invalid account</span>';
              return e($a->code.' - '.$a->name);
            };
          @endphp

          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th colspan="2">Core Receivables / Payables</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="fw-bold" style="width: 260px;">Accounts Receivable (AR)</td>
                      <td>{!! $fmt($mapping->ar_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Accounts Payable (AP)</td>
                      <td>{!! $fmt($mapping->ap_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Default Bank / Cash GL</td>
                      <td>{!! $fmt($mapping->default_bank_gl_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Retained Earnings</td>
                      <td>{!! $fmt($mapping->retained_earnings_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Rounding Account</td>
                      <td>{!! $fmt($mapping->rounding_account_id ?? null) !!}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th colspan="2">Sales / Inventory / Tax</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="fw-bold" style="width: 260px;">Sales Revenue</td>
                      <td>{!! $fmt($mapping->sales_revenue_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">COGS</td>
                      <td>{!! $fmt($mapping->cogs_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Inventory Asset</td>
                      <td>{!! $fmt($mapping->inventory_asset_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">VAT Output</td>
                      <td>{!! $fmt($mapping->vat_output_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">VAT Input</td>
                      <td>{!! $fmt($mapping->vat_input_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Sales Discount</td>
                      <td>{!! $fmt($mapping->sales_discount_account_id ?? null) !!}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">Purchase Discount</td>
                      <td>{!! $fmt($mapping->purchase_discount_account_id ?? null) !!}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <small class="text-muted">
            Tip: If sales invoice posting fails, check AR, Sales Revenue and VAT Output. If supplier bill posting fails, check AP and VAT Input. If inventory postings fail, check Inventory Asset and COGS.
          </small>

        </div>
      </div>
    </div>
  </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="mappingsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Configure Finance Account Mappings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="mappingsForm">
          <div class="row">

            <div class="col-12">
              <h6 class="text-primary border-bottom pb-2 mb-3">Core Accounts</h6>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Accounts Receivable (AR) <span class="text-danger">*</span></label>
              <select class="form-control" name="ar_account_id" id="ar_account_id" required>
                <option value="">-- select AR account --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->ar_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used for customer invoices and receivables posting.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Accounts Payable (AP) <span class="text-danger">*</span></label>
              <select class="form-control" name="ap_account_id" id="ap_account_id" required>
                <option value="">-- select AP account --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->ap_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used for supplier bills and payables posting.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Default Bank / Cash GL <span class="text-danger">*</span></label>
              <select class="form-control" name="default_bank_gl_account_id" id="default_bank_gl_account_id" required>
                <option value="">-- select default bank/cash account --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->default_bank_gl_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used for receipts/payments when no specific bank account is selected.</small>
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Retained Earnings (Optional)</label>
              <select class="form-control" name="retained_earnings_account_id" id="retained_earnings_account_id">
                <option value="">-- none --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->retained_earnings_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used during year-end closing and profit transfer.</small>
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Rounding Account (Optional)</label>
              <select class="form-control" name="rounding_account_id" id="rounding_account_id">
                <option value="">-- none --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->rounding_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used for minor invoice/payment rounding differences.</small>
            </div>

            <div class="col-12 mt-2">
              <h6 class="text-primary border-bottom pb-2 mb-3">Sales, Purchases, Inventory & Tax</h6>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Sales Revenue (Optional)</label>
              <select class="form-control" name="sales_revenue_account_id" id="sales_revenue_account_id">
                <option value="">-- none --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->sales_revenue_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used for sales invoices: CR Sales Revenue.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">COGS Account (Optional)</label>
              <select class="form-control" name="cogs_account_id" id="cogs_account_id">
                <option value="">-- none --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->cogs_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used when inventory items are sold: DR COGS.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Inventory Asset Account (Optional)</label>
              <select class="form-control" name="inventory_asset_account_id" id="inventory_asset_account_id">
                <option value="">-- none --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->inventory_asset_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used for stock value and inventory purchases.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">VAT Output (Optional)</label>
              <select class="form-control" name="vat_output_account_id" id="vat_output_account_id">
                <option value="">-- none --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->vat_output_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used on sales invoices when VAT is charged.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">VAT Input (Optional)</label>
              <select class="form-control" name="vat_input_account_id" id="vat_input_account_id">
                <option value="">-- none --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->vat_input_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used on supplier bills when VAT is recoverable.</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="text-muted small">Sales Discount Account (Optional)</label>
              <select class="form-control" name="sales_discount_account_id" id="sales_discount_account_id">
                <option value="">-- none --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->sales_discount_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used for discounts given to customers.</small>
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Purchase Discount Account (Optional)</label>
              <select class="form-control" name="purchase_discount_account_id" id="purchase_discount_account_id">
                <option value="">-- none --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}" @selected(($mapping->purchase_discount_account_id ?? null) == $a->id)>
                    {{ $a->code }} - {{ $a->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Used for supplier discounts and purchase adjustments.</small>
            </div>

          </div>
        </form>

        <div class="alert alert-warning mb-0">
          <i class="fas fa-exclamation-triangle me-1"></i>
          Changing mappings affects how future postings are created. Existing journals will not be rewritten unless you run a reposting or adjustment process.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveMappingsBtn">
          <i class="fas fa-save me-1"></i> Save
        </button>
      </div>
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

if (typeof swal === 'undefined' && typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
  window.swal = function(optsOrTitle, text, icon){
    if (typeof optsOrTitle === 'string') {
      return Swal.fire({
        title: optsOrTitle,
        text: text || '',
        icon: icon || 'info'
      });
    }
    return Swal.fire(optsOrTitle || {});
  };
}

function swalOk(msg){
  if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
    return Swal.fire({
      icon: 'success',
      title: 'Success',
      text: msg || 'Done.'
    });
  }
  alert(msg || 'Done.');
}

function swalErr(msg){
  if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
    return Swal.fire({
      icon: 'error',
      title: 'Error',
      text: msg || 'Something went wrong.'
    });
  }
  alert(msg || 'Something went wrong.');
}

const upsertUrl = "{{ route('admin.finance.accounts.mappings.upsert') }}";

$('#editMappingsBtn').on('click', function(){
  const modalEl = document.getElementById('mappingsModal');
  if (window.bootstrap && bootstrap.Modal) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  } else {
    $('#mappingsModal').modal('show');
  }
});

$('#saveMappingsBtn').on('click', function(){
  const form = document.getElementById('mappingsForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const data = $('#mappingsForm').serialize();

  if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
    Swal.fire({
      icon: 'warning',
      title: 'Save mappings?',
      text: 'These mappings will be used for auto-posting across finance modules.',
      showCancelButton: true,
      confirmButtonText: 'Yes, save',
      cancelButtonText: 'Cancel'
    }).then(function(result){
      if (!result.isConfirmed) return;
      submitMappings(data);
    });
  } else if (confirm('Save these finance account mappings?')) {
    submitMappings(data);
  }
});

function submitMappings(data){
  $.post(upsertUrl, data)
    .done(function(res){
      swalOk(res.message || 'Finance account mappings saved.');
      setTimeout(function(){
        window.location.reload();
      }, 500);
    })
    .fail(function(xhr){
      let msg = xhr?.responseJSON?.message || 'Failed to save mappings.';

      if (xhr?.responseJSON?.errors) {
        const errors = xhr.responseJSON.errors;
        const firstKey = Object.keys(errors)[0];
        if (firstKey && errors[firstKey] && errors[firstKey][0]) {
          msg = errors[firstKey][0];
        }
      }

      swalErr(msg);
    });
}
</script>
@endpush