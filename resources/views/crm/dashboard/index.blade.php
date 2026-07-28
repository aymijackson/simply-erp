@extends('layouts.master')
@section('title','CRM Executive Dashboard')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">CRM Executive Dashboard</h1>
      <small class="text-muted">All CRM Modules</small>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">Date From</label>
          <input type="date" id="f_date_from" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1">Date To</label>
          <input type="date" id="f_date_to" class="form-control">
        </div>
        <div class="col-md-3">
          <button class="btn btn-outline-primary w-100" id="applyBtn">
            <i class="fas fa-filter me-1"></i> Apply
          </button>
        </div>
        <div class="col-md-3">
          <button class="btn btn-outline-secondary w-100" id="resetBtn">
            <i class="fas fa-undo me-1"></i> Reset
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- KPIs --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Customers</div><div class="h4" id="k_customers">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Leads</div><div class="h4" id="k_leads">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Opportunities</div><div class="h4" id="k_opps">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Support Tickets</div><div class="h4" id="k_tickets">—</div></div></div></div>

    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Activities</div><div class="h4" id="k_activities">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Interactions</div><div class="h4" id="k_interactions">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Notes</div><div class="h4" id="k_notes">—</div></div></div></div>
  </div>

  <div class="row g-3">
    {{-- Trend --}}
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <strong>Trend (Customers / Leads / Tickets)</strong>
          <small class="text-muted">Created per day</small>
        </div>
        <div class="card-body">
          <canvas id="chartTrend" height="130"></canvas>
        </div>
      </div>
    </div>

    {{-- Ticket Status --}}
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Tickets by Status</strong></div>
        <div class="card-body">
          <canvas id="chartTicketStatus" height="160"></canvas>
        </div>
      </div>
    </div>

    {{-- Opportunity Pipeline --}}
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Opportunity Pipeline (Count & Value)</strong></div>
        <div class="card-body">
          <canvas id="chartOppPipeline" height="140"></canvas>
        </div>
      </div>
    </div>

    {{-- Top Customers --}}
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <strong>Top Customers (Engagement)</strong>
          <small class="text-muted">Score = Notes+Interactions+Activities+Opps+Tickets</small>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th style="width:80px;">ID</th>
                <th>Name</th>
                <th style="width:120px;">Score</th>
              </tr>
            </thead>
            <tbody id="topCustomersBody">
              <tr><td colspan="3" class="text-muted">—</td></tr>
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
    summary: @json(route('admin.crm.dashboard.summary')),
    charts:  @json(route('admin.crm.dashboard.charts')),
  };

  let chartTrend, chartTicketStatus, chartOppPipeline;

  function filters(){
    return {
      date_from: $('#f_date_from').val() || '',
      date_to: $('#f_date_to').val() || '',
    };
  }

  function err(msg){
    Swal.fire({ icon:'error', title:'Error', text: msg || 'Something went wrong.' });
  }

  async function loadAll(){
    try{
      const f = filters();

      const s = await $.get(routes.summary, f);

      $('#k_customers').text(s.counts?.customers ?? '—');
      $('#k_leads').text(s.counts?.leads ?? '—');
      $('#k_opps').text(s.counts?.opportunities ?? '—');
      $('#k_tickets').text(s.counts?.tickets ?? '—');
      $('#k_activities').text(s.counts?.activities ?? '—');
      $('#k_interactions').text(s.counts?.interactions ?? '—');
      $('#k_notes').text(s.counts?.notes ?? '—');

      // Ticket status pie
      const tb = s.ticket_by_status || {};
      const tLabels = Object.keys(tb);
      const tData = tLabels.map(k => Number(tb[k] || 0));
      if(chartTicketStatus) chartTicketStatus.destroy();
      chartTicketStatus = new Chart(document.getElementById('chartTicketStatus'), {
        type:'doughnut',
        data:{ labels:tLabels, datasets:[{ label:'Tickets', data:tData }] }
      });

      // Opp pipeline (bar for count, line for value)
      const opp = s.opportunity_by_stage || [];
      const oLabels = opp.map(x => x.stage ?? '—');
      const oCount = opp.map(x => Number(x.c || 0));
      const oValue = opp.map(x => Number(x.v || 0));
      if(chartOppPipeline) chartOppPipeline.destroy();
      chartOppPipeline = new Chart(document.getElementById('chartOppPipeline'), {
        data:{
          labels:oLabels,
          datasets:[
            { type:'bar', label:'Count', data:oCount },
            { type:'line', label:'Value', data:oValue },
          ]
        }
      });

      // Charts endpoint (trend + top)
      const c = await $.get(routes.charts, f);

      const series = c.series || {};
      const cust = series.customers || [];
      const leads = series.leads || [];
      const tickets = series.tickets || [];

      // unify labels from customers trend (fallback to leads/tickets)
      const labels = (cust.length ? cust : (leads.length ? leads : tickets)).map(x => x.d);

      const mapSeries = (arr) => {
        const m = {};
        (arr || []).forEach(x => m[x.d] = Number(x.c || 0));
        return labels.map(d => m[d] ?? 0);
      };

      if(chartTrend) chartTrend.destroy();
      chartTrend = new Chart(document.getElementById('chartTrend'), {
        type:'line',
        data:{
          labels,
          datasets:[
            { label:'Customers', data: mapSeries(cust) },
            { label:'Leads', data: mapSeries(leads) },
            { label:'Tickets', data: mapSeries(tickets) },
          ]
        }
      });

      // Top customers
      const top = c.top_customers || [];
      const $b = $('#topCustomersBody');
      $b.empty();
      if(!top.length){
        $b.append('<tr><td colspan="3" class="text-muted">No data.</td></tr>');
      } else {
        top.forEach(r => {
          const safeName = (r.name || '').toString().replaceAll('<','&lt;').replaceAll('>','&gt;');
          $b.append(`<tr><td>${r.id}</td><td>${safeName}</td><td><strong>${r.score ?? 0}</strong></td></tr>`);
        });
      }

      const t = c.tables || {};
      $('#tablesUsed').text(
        'Tables used: ' + Object.keys(t).map(k => `${k}=${t[k] ? 'yes':'no'}`).join(', ')
      );

    }catch(e){
      err('Failed to load dashboard.');
    }
  }

  $('#applyBtn').on('click', loadAll);
  $('#resetBtn').on('click', function(){
    $('#f_date_from').val('');
    $('#f_date_to').val('');
    loadAll();
  });

  loadAll();
})();
</script>
@endpush
