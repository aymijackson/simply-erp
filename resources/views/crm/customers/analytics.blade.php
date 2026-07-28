@extends('layouts.master')
@section('title','Customers Analytics')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Customers Analytics</h1>
      <small class="text-muted">CRM</small>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">

        <div class="col-md-4">
          <label class="form-label mb-1">Search (Name / Email / Phone)</label>
          <input type="text" id="f_q" class="form-control" placeholder="e.g. John, john@email.com, 080...">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Date From</label>
          <input type="date" id="f_date_from" class="form-control">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Date To</label>
          <input type="date" id="f_date_to" class="form-control">
        </div>

        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyBtn">
            <i class="fas fa-filter me-1"></i> Apply
          </button>
          <button class="btn btn-outline-secondary w-100" id="resetBtn">
            <i class="fas fa-undo me-1"></i> Reset
          </button>
        </div>

      </div>
    </div>
  </div>

  {{-- KPIs --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Total Customers</div><div class="h4" id="k_total">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Created Today</div><div class="h4" id="k_today">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Created This Month</div><div class="h4" id="k_month">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">With Email</div><div class="h4" id="k_email">—</div></div></div></div>

    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">With Phone</div><div class="h4" id="k_phone">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">With Address</div><div class="h4" id="k_address">—</div><small class="text-muted">0 if column not present</small></div></div></div>
  </div>

  {{-- Charts --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Customers Created Trend</strong></div>
        <div class="card-body"><canvas id="chartTrend" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Data Quality (Email/Phone)</strong></div>
        <div class="card-body"><canvas id="chartQuality" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <strong>Top 10 Customers (Engagement Score)</strong>
          <small class="text-muted">Notes + Interactions + Activities + Opportunities + Tickets</small>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th style="width:80px;">ID</th>
                <th>Name</th>
                <th style="width:160px;">Email</th>
                <th style="width:140px;">Phone</th>
                <th style="width:120px;">Notes</th>
                <th style="width:140px;">Interactions</th>
                <th style="width:120px;">Activities</th>
                <th style="width:150px;">Opportunities</th>
                <th style="width:110px;">Tickets</th>
                <th style="width:160px;">Score</th>
              </tr>
            </thead>
            <tbody id="topBody">
              <tr><td colspan="10" class="text-muted">—</td></tr>
            </tbody>
          </table>

          <small class="text-muted d-block mt-2" id="tablesUsed"></small>
        </div>
      </div>
    </div>

  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  const routes = {
    kpis:   @json(route('admin.crm.customers.analytics.kpis')),
    charts: @json(route('admin.crm.customers.analytics.charts')),
  };

  function filters(){
    return {
      q: $('#f_q').val() || '',
      date_from: $('#f_date_from').val() || '',
      date_to: $('#f_date_to').val() || '',
    };
  }

  let chartTrend, chartQuality;

  function err(msg){
    Swal.fire({ icon:'error', title:'Error', text: msg || 'Something went wrong.' });
  }

  async function loadAll(){
    try{
      const f = filters();

      const k = await $.get(routes.kpis, f);
      $('#k_total').text(k.total ?? '—');
      $('#k_today').text(k.created_today ?? '—');
      $('#k_month').text(k.created_this_month ?? '—');
      $('#k_email').text(k.with_email ?? '—');
      $('#k_phone').text(k.with_phone ?? '—');
      $('#k_address').text(k.with_address ?? '—');

      const c = await $.get(routes.charts, f);

      // Trend
      const dLabels = (c.trend || []).map(x => x.d);
      const dData   = (c.trend || []).map(x => Number(x.c || 0));
      if (chartTrend) chartTrend.destroy();
      chartTrend = new Chart(document.getElementById('chartTrend'), {
        type:'line',
        data:{ labels:dLabels, datasets:[{ label:'Customers Created', data:dData }] }
      });

      // Quality
      const q = c.quality || {};
      const qLabels = ['Complete', 'Email Only', 'Phone Only', 'Missing Both'];
      const qData = [
        Number(q.complete || 0),
        Number(q.email_only || 0),
        Number(q.phone_only || 0),
        Number(q.missing_both || 0),
      ];
      if (chartQuality) chartQuality.destroy();
      chartQuality = new Chart(document.getElementById('chartQuality'), {
        type:'bar',
        data:{ labels:qLabels, datasets:[{ label:'Count', data:qData }] }
      });

      // Top customers table
      const rows = (c.top_customers || []);
      const $body = $('#topBody');
      $body.empty();

      if(!rows.length){
        $body.append('<tr><td colspan="10" class="text-muted">No data.</td></tr>');
      } else {
        rows.forEach(r => {
          const safeName = (r.name || '').toString().replaceAll('<','&lt;').replaceAll('>','&gt;');
          $body.append(`
            <tr>
              <td>${r.id}</td>
              <td>${safeName}</td>
              <td>${r.email ?? '—'}</td>
              <td>${r.phone ?? '—'}</td>
              <td>${r.notes_count ?? 0}</td>
              <td>${r.interactions_count ?? 0}</td>
              <td>${r.activities_count ?? 0}</td>
              <td>${r.opportunities_count ?? 0}</td>
              <td>${r.tickets_count ?? 0}</td>
              <td><strong>${r.engagement_score ?? 0}</strong></td>
            </tr>
          `);
        });
      }

      // Tables used (debug/info)
      const t = c.tables_used || {};
      $('#tablesUsed').text(
        'Tables used: ' +
        Object.keys(t).map(k => `${k}=${t[k] ? 'yes' : 'no'}`).join(', ')
      );

    }catch(e){
      err('Failed to load customers analytics.');
    }
  }

  $('#applyBtn').on('click', loadAll);
  $('#resetBtn').on('click', function(){
    $('#f_q').val('');
    $('#f_date_from').val('');
    $('#f_date_to').val('');
    loadAll();
  });

  loadAll();
})();
</script>
@endpush
