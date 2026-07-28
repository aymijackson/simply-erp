@extends('layouts.master')
@section('title','Notes Analytics')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Notes Analytics</h1>
      <small class="text-muted">CRM</small>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">

        <div class="col-md-4">
          <label class="form-label mb-1">Notable Type</label>
          <select id="f_notable_type" class="form-control">
            <option value="">All</option>
            <option value="Modules\CRM\Models\Customer">Customer</option>
            <option value="Modules\CRM\Models\Lead">Lead</option>
            <option value="Modules\CRM\Models\Opportunity">Opportunity</option>
          </select>
          <small class="text-muted">Notable ID is optional.</small>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Notable ID (optional)</label>
          <input type="number" id="f_notable_id" class="form-control" placeholder="e.g. 15">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Author ID (optional)</label>
          <input type="number" id="f_author_id" class="form-control" placeholder="e.g. 7">
          <small class="text-muted">We can upgrade to Select2 later.</small>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Date From</label>
          <input type="date" id="f_date_from" class="form-control">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Date To</label>
          <input type="date" id="f_date_to" class="form-control">
        </div>

        <div class="col-md-6 d-flex gap-2">
          <button class="btn btn-outline-primary w-50" id="applyBtn">
            <i class="fas fa-filter me-1"></i> Apply
          </button>
          <button class="btn btn-outline-secondary w-50" id="resetBtn">
            <i class="fas fa-undo me-1"></i> Reset
          </button>
        </div>

      </div>
    </div>
  </div>

  {{-- KPIs --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Total Notes</div><div class="h4" id="k_total">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Created Today</div><div class="h4" id="k_today">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Created This Month</div><div class="h4" id="k_month">—</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Avg Content Length</div><div class="h4" id="k_avg_len">—</div></div></div></div>

    <div class="col-md-6"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Top Author</div>
      <div class="h4 mb-0" id="k_top_author">—</div>
      <div class="text-muted small" id="k_top_author_count"></div>
    </div></div></div>

    <div class="col-md-6"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Top Notable Type</div>
      <div class="h4 mb-0" id="k_top_type">—</div>
      <div class="text-muted small" id="k_top_type_count"></div>
    </div></div></div>
  </div>

  {{-- Charts --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Notes by Notable Type</strong></div>
        <div class="card-body"><canvas id="chartType" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Top Authors</strong></div>
        <div class="card-body"><canvas id="chartAuthors" height="140"></canvas></div>
      </div>
    </div>

    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Notes Created Trend</strong></div>
        <div class="card-body"><canvas id="chartTrend" height="120"></canvas></div>
      </div>
    </div>

    {{-- Longest Notes Table --}}
    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <strong>Top 10 Longest Notes</strong>
          <small class="text-muted">Based on content length</small>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th style="width:90px;">ID</th>
                <th>Subject</th>
                <th style="width:140px;">Author ID</th>
                <th style="width:140px;">Length</th>
                <th style="width:170px;">Created</th>
              </tr>
            </thead>
            <tbody id="longestBody">
              <tr><td colspan="5" class="text-muted">—</td></tr>
            </tbody>
          </table>
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
    kpis:   @json(route('admin.crm.notes.analytics.kpis')),
    charts: @json(route('admin.crm.notes.analytics.charts')),
  };

  function filters(){
    return {
      notable_type: $('#f_notable_type').val() || '',
      notable_id: $('#f_notable_id').val() || '',
      author_id: $('#f_author_id').val() || '',
      date_from: $('#f_date_from').val() || '',
      date_to: $('#f_date_to').val() || '',
    };
  }

  let chartType, chartAuthors, chartTrend;

  function err(msg){
    Swal.fire({ icon:'error', title:'Error', text: msg || 'Something went wrong.' });
  }

  function typeLabel(t){
    if(!t) return 'UNKNOWN';
    if(t.includes('Customer')) return 'CUSTOMER';
    if(t.includes('Lead')) return 'LEAD';
    if(t.includes('Opportunity')) return 'OPPORTUNITY';
    return t.split('\\').pop().toUpperCase();
  }

  async function loadAll(){
    try{
      const f = filters();

      const k = await $.get(routes.kpis, f);
      $('#k_total').text(k.total ?? '—');
      $('#k_today').text(k.created_today ?? '—');
      $('#k_month').text(k.created_this_month ?? '—');
      $('#k_avg_len').text(k.avg_content_length ?? '—');

      $('#k_top_author').text(k.top_author_id ? ('Employee #' + k.top_author_id) : '—');
      $('#k_top_author_count').text(k.top_author_count ? (k.top_author_count + ' notes') : '');

      $('#k_top_type').text(k.top_notable_type ? typeLabel(k.top_notable_type) : '—');
      $('#k_top_type_count').text(k.top_notable_type_count ? (k.top_notable_type_count + ' notes') : '');

      const c = await $.get(routes.charts, f);

      // By notable type
      const tLabels = (c.by_type || []).map(x => typeLabel(x.t));
      const tData   = (c.by_type || []).map(x => Number(x.c || 0));
      if (chartType) chartType.destroy();
      chartType = new Chart(document.getElementById('chartType'), {
        type:'bar',
        data:{ labels:tLabels, datasets:[{ label:'Count', data:tData }] }
      });

      // Top authors
      const aLabels = (c.top_authors || []).map(x => x.author_id ? ('Emp #' + x.author_id) : 'Unknown');
      const aData   = (c.top_authors || []).map(x => Number(x.c || 0));
      if (chartAuthors) chartAuthors.destroy();
      chartAuthors = new Chart(document.getElementById('chartAuthors'), {
        type:'bar',
        data:{ labels:aLabels, datasets:[{ label:'Count', data:aData }] }
      });

      // Trend
      const dLabels = (c.trend || []).map(x => x.d);
      const dData   = (c.trend || []).map(x => Number(x.c || 0));
      if (chartTrend) chartTrend.destroy();
      chartTrend = new Chart(document.getElementById('chartTrend'), {
        type:'line',
        data:{ labels:dLabels, datasets:[{ label:'Notes Created', data:dData }] }
      });

      // Longest
      const rows = (c.longest || []);
      const $body = $('#longestBody');
      $body.empty();

      if(!rows.length){
        $body.append('<tr><td colspan="5" class="text-muted">No data.</td></tr>');
      } else {
        rows.forEach(r => {
          $body.append(`
            <tr>
              <td>${r.id}</td>
              <td>${(r.subject || '').toString().replaceAll('<','&lt;').replaceAll('>','&gt;')}</td>
              <td>${r.author_id ?? '—'}</td>
              <td>${r.len ?? 0}</td>
              <td>${r.created_at ?? '—'}</td>
            </tr>
          `);
        });
      }

    }catch(e){
      err('Failed to load notes analytics.');
    }
  }

  $('#applyBtn').on('click', loadAll);
  $('#resetBtn').on('click', function(){
    $('#f_notable_type').val('');
    $('#f_notable_id').val('');
    $('#f_author_id').val('');
    $('#f_date_from').val('');
    $('#f_date_to').val('');
    loadAll();
  });

  loadAll();
})();
</script>
@endpush
