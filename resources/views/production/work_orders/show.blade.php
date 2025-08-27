@extends('layouts.master')
@section('title', 'Work Order · #'.$wo->id)

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
<style>
  /* Small tweaks for tasks UI */
  .checklist-row + .checklist-row { margin-top: .5rem; }
  .checklist-row .btn { padding: .25rem .5rem; }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- Header --------------------------------------------------------------- --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 text-primary mb-0">
      Work Order <small class="text-muted">#{{ $wo->id }}</small>
    </h1>
    <div class="d-print-none d-flex gap-2">
      {{-- Lifecycle buttons (toggle by status) --}}
      @php $status = $wo->status; @endphp
      <button id="btnRelease"  class="btn btn-outline-secondary {{ $status!=='draft'?'d-none':'' }}">Release</button>
      <button id="btnStart"    class="btn btn-outline-primary   {{ !in_array($status,['released','paused'])?'d-none':'' }}">Start</button>
      <button id="btnComplete" class="btn btn-outline-success   {{ $status!=='in_progress'?'d-none':'' }}">Complete</button>
      <button id="btnClose"    class="btn btn-outline-dark      {{ $status!=='completed'?'d-none':'' }}">Close</button>
      <a href="{{ route('admin.production.work-orders.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
    </div>
  </div>

  {{-- Overview card --------------------------------------------------------- --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body row g-3">
      <div class="col-md-3">
        <div class="text-muted small">Status</div>
        <div id="woStatusBadge">
          @php
            $map = ['draft'=>'secondary','released'=>'info','in_progress'=>'warning','completed'=>'success','closed'=>'dark'];
            $c   = $map[$wo->status] ?? 'secondary';
          @endphp
          <span class="badge bg-{{ $c }} text-white">{{ ucfirst($wo->status) }}</span>
        </div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Product Variant</div>
        <div>{{ $wo->variant_sku ?: '—' }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Product</div>
        <div>{{ $wo->product_name ?: '—' }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Qty to Produce</div>
        <div>{{ number_format((float)$wo->quantity_to_produce, 4) }}</div>
      </div>

      <div class="col-md-3">
        <div class="text-muted small">BOM</div>
        <div>{{ $wo->bom_code ? '#'.$wo->bom_code : '—' }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Routing</div>
        <div>{{ $wo->routing_name ?: '—' }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Start</div>
        <div>{{ $wo->start_date ? \Carbon\Carbon::parse($wo->start_date)->format('d M Y H:i') : '—' }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">End</div>
        <div>{{ $wo->end_date ? \Carbon\Carbon::parse($wo->end_date)->format('d M Y H:i') : '—' }}</div>
      </div>
    </div>
  </div>

  {{-- Tabs --------------------------------------------------------------- --}}
  <ul class="nav nav-tabs" id="woTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="materials-tab" data-bs-toggle="tab" data-bs-target="#bomItems" type="button" role="tab">BOM Items</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="materials-tab" data-bs-toggle="tab" data-bs-target="#materials" type="button" role="tab">Materials</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="steps-tab" data-bs-toggle="tab" data-bs-target="#steps" type="button" role="tab">Steps</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">Tasks</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="costs-tab" data-bs-toggle="tab" data-bs-target="#costs" type="button" role="tab">Costs</button>
    </li>
  </ul>

  <div class="tab-content pt-3">
    
    {{-- BOM Items ---------------------------------------------------------- --}}
    <div class="tab-pane fade show active" id="bomItems" role="tabpanel">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table id="bomTbl" class="table table-bordered w-100">
              <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Variant (SKU)</th>
                    <th>Product</th>
                    <th>Qty / Parent</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Materials ---------------------------------------------------------- --}}
    <div class="tab-pane fade" id="materials" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Materials</h6>
            <button id="addMatBtn" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Add Material
            </button>
        </div>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table id="matTbl" class="table table-bordered w-100">
              <thead class="table-light">
                <tr>
                  <th>SKU</th>
                  <th>Product</th>
                  <th class="text-end">Planned</th>
                  <th class="text-end">Issued</th>
                  <th class="text-end">Returned</th>
                  <th class="text-end">Remaining</th>
                  <th>Notes</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Steps -------------------------------------------------------------- --}}
    <div class="tab-pane fade" id="steps" role="tabpanel">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table id="stepTbl" class="table table-bordered w-100">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Sequence</th>
                  <th>Routing Steps</th>
                  <th>Instructions</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Tasks -------------------------------------------------------------- --}}
    <div class="tab-pane fade" id="tasks" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Tasks for this Work Order</h6>
        <div class="d-flex gap-2">
          <button id="addTaskBtn" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Add Task
          </button>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table id="taskTbl" class="table table-bordered w-100">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Task</th>
                  <th>Step</th>
                  <th>Assignees</th>
                  <th>Status</th>
                  <th class="text-end">Est</th>
                  <th class="text-end">Actual</th>
                  <th>Due</th>
                  <th>Progress</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Costs -------------------------------------------------------------- --}}
    <div class="tab-pane fade" id="costs" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Extra Costs (Labour / Logistics / Fuel / Other)</h6>
        <button id="addCostBtn" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Cost</button>
      </div>
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table id="costTbl" class="table table-bordered w-100">
              <thead class="table-light">
                <tr>
                  <th>Type</th>
                  <th>Category</th>
                  <th class="text-end">Qty</th>
                  <th>Unit</th>
                  <th class="text-end">Rate</th>
                  <th class="text-end">Amount</th>
                  <th>Note</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tfoot>
                <tr>
                  <th colspan="5" class="text-end">Total:</th>
                  <th class="text-end" id="costTotalCell">0.00</th>
                  <th colspan="2"></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

{{-- Add/Edit Material Modal ---------------------------------------------------- --}}
{{-- Add/Edit Material Modal ----------------------------------------------- --}}
<div class="modal fade" id="matModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="matForm" class="modal-content">
      @csrf
      <input type="hidden" id="mat_id">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Add Material</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Variant *</label>
          <select id="mat_variant_id" class="form-select" required></select>
        </div>
        <div class="mb-3">
          <label class="form-label">Planned Qty *</label>
          <input type="number" step="0.000001" min="0.000001" id="mat_qty" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Note</label>
          <textarea id="mat_note" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" id="saveMatBtn">Save</button>
      </div>
    </form>
  </div>
</div>

{{-- Add/Edit Cost Modal ---------------------------------------------------- --}}
<div class="modal fade" id="costModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="costForm" class="modal-content">
      @csrf
      <input type="hidden" id="cost_id">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Add Cost</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Cost Type *</label>
          <select id="cost_type_id" name="work_order_cost_type_id" class="form-select" required></select>
        </div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Qty *</label>
            <input type="number" step="0.000001" min="0.000001" id="cost_qty" name="qty" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Unit</label>
            <select id="cost_unit_id" name="unit_id" class="form-select"></select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Rate *</label>
            <input type="number" step="0.0001" min="0" id="cost_rate" name="rate" class="form-control" required>
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label">Note</label>
          <textarea id="cost_note" name="note" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" id="saveCostBtn">Save</button>
      </div>
    </form>
  </div>
</div>

{{-- Add/Edit Task Modal ---------------------------------------------------- --}}
<div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="taskForm" class="modal-content">
      @csrf
      <input type="hidden" id="task_id">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Add Task</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Title *</label>
            <input id="task_title" name="title" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Priority</label>
            <select id="task_priority" name="priority" class="form-select">
              <option value="normal">Normal</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
              <option value="low">Low</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Step (optional)</label>
            <select id="task_step_id" name="workorder_step_id" class="form-select"></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Est. Minutes</label>
            <input type="number" min="0" id="task_est" name="estimated_minutes" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label">Due</label>
            <input type="datetime-local" id="task_due" name="due_at" class="form-control">
          </div>

          <div class="col-12">
            <label class="form-label">Assignees</label>
            <select id="task_assignees" name="assignees[]" class="form-select" multiple></select>
          </div>

          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea id="task_desc" name="description" class="form-control" rows="3"></textarea>
          </div>

          <div class="col-12">
            <label class="form-label d-flex justify-content-between align-items-center">
              <span>Checklist (optional)</span>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="addChecklistItem">
                <i class="fas fa-plus me-1"></i> Add Item
              </button>
            </label>
            <div id="checklistWrap"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" id="saveTaskBtn">Save Task</button>
      </div>
    </form>
  </div>
</div>

{{-- Dependencies Modal ---------------------------------------------------- --}}
<div class="modal fade" id="depsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title">Task Dependencies</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-2 mb-3">
          <select id="dep_task_select" class="form-select" style="flex:1"></select>
          <button class="btn btn-primary" id="addDepBtn"><i class="fas fa-plus me-1"></i> Add</button>
        </div>
        <div class="table-responsive">
          <table id="depTbl" class="table table-bordered w-100">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Depends On</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Time Logs Modal ------------------------------------------------------- --}}
<div class="modal fade" id="logsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title">Task Time Logs</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="logForm" class="border rounded p-2 mb-3">
          @csrf
          <input type="hidden" id="log_id">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">Employee *</label>
              <select id="log_employee_id" class="form-select"></select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Started *</label>
              <input type="datetime-local" id="log_started_at" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Ended</label>
              <input type="datetime-local" id="log_ended_at" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Minutes (optional)</label>
              <input type="number" min="0" id="log_minutes" class="form-control">
            </div>
            <div class="col-md-9">
              <label class="form-label">Note</label>
              <input id="log_note" class="form-control">
            </div>
          </div>
          <div class="mt-2">
            <button class="btn btn-success" id="saveLogBtn"><i class="fas fa-save me-1"></i> Save Log</button>
            <button class="btn btn-secondary" type="reset" id="resetLogBtn">Reset</button>
          </div>
        </form>

        <div class="table-responsive">
          <table id="logTbl" class="table table-bordered w-100">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Started</th>
                <th>Ended</th>
                <th class="text-end">Minutes</th>
                <th>Note</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>


@endsection

@push('scripts')
{{-- DataTables & Buttons --}}
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
{{-- Select2 --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const WO_ID = {{ (int)$wo->id }};
const CSRF  = @json(csrf_token());

// Endpoints
const URLS = {
  // Existing tables
  matVariants:  @json(route('admin.inventory.product-variants.select2')),
  materialsDT: @json(route('admin.production.work-orders.materials.datatable', $wo->id)),
  stepsDT:     @json(route('admin.production.work-orders.routings.steps.datatable', ['work_order' => $wo->id])),
  costsDT:     @json(route('admin.production.work-orders.costs.datatable', $wo->id)),
  costStore:   @json(route('admin.production.work-orders.costs.store', $wo->id)),
  costUpdate:  id => @json(route('admin.production.work-orders.costs.update', 0)).replace('/0','/'+id),
  costDelete:  id => @json(route('admin.production.work-orders.costs.destroy', 0)).replace('/0','/'+id),

  release:     @json(route('admin.production.work-orders.release', $wo->id)),
  start:       @json(route('admin.production.work-orders.start', $wo->id)),
  complete:    @json(route('admin.production.work-orders.complete', $wo->id)),
  close:       @json(route('admin.production.work-orders.close', $wo->id)),

  // --- NEW: Tasks (adjust to your routes) ---
  tasksDT:     @json(route('admin.production.work-orders.tasks.datatable', $wo->id)),
  taskStore:   @json(route('admin.production.work-orders.tasks.store', $wo->id)),
  taskUpdate:  id => @json(route('admin.production.work-orders.tasks.update', 0)).replace('/0','/'+id),
  taskDelete:  id => @json(route('admin.production.work-orders.tasks.destroy', 0)).replace('/0','/'+id),
  taskStart:   id => @json(route('admin.production.work-orders.tasks.start', 0)).replace('/0','/'+id),
  taskStop:    id => @json(route('admin.production.work-orders.tasks.stop', 0)).replace('/0','/'+id),
  taskComplete:id => @json(route('admin.production.work-orders.tasks.complete', 0)).replace('/0','/'+id),

  // Select2 sources — adjust to real routes
  costTypes:   "{{ url('admin/production/work-orders/cost-types/select2') }}",
  units:       "{{ url('admin/common/units/select2') }}",
  stepsSelect: "{{ url('admin/production/work-orders/'.$wo->id.'/steps/select2') }}",
  employees:   "{{ url('admin/hrm/employees/select2') }}",

  // --- Dependencies ---
  depsDT:     taskId => @json(route('admin.production.work-orders.tasks.dependencies.datatable', 0)).replace('/0','/'+taskId),
  depStore:   taskId => @json(route('admin.production.work-orders.tasks.dependencies.store', 0)).replace('/0','/'+taskId),
  depDelete:  depId  => @json(route('admin.production.work-orders.tasks.dependencies.destroy', 0)).replace('/0','/'+depId),
  tasksSelect:@json(route('admin.production.work-orders.tasks.select2', $wo->id)),

  // --- Time logs ---
  logsDT:     taskId => @json(route('admin.production.work-orders.tasks.timelogs.datatable', 0)).replace('/0','/'+taskId),
  logStore:   taskId => @json(route('admin.production.work-orders.tasks.timelogs.store', 0)).replace('/0','/'+taskId),
  logUpdate:  id     => @json(route('admin.production.work-orders.tasks.timelogs.update', 0)).replace('/0','/'+id),
  logDelete:  id     => @json(route('admin.production.work-orders.tasks.timelogs.destroy', 0)).replace('/0','/'+id),

};

// ---------- Materials table ----------
const bom = $('#bomTbl').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("admin.production.work-orders.boms.items.datatable", ['work_order' => $wo->id]) }}',
    columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'variant_sku', name: 'product_variant.sku' },
        { data: 'product_name', name: 'product_variant.product.product_name' },
        { data: 'qty_per_parent', name: 'qty_per_parent' },
    ],
    order: [[1, 'desc']],
    dom:'Blfrtip',
    buttons:[
        {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
        {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
    ],
    createdRow: row => row.classList.add('align-middle')
});

// ---------- Materials table ----------
const matTbl = $('#matTbl').DataTable({
  serverSide:true, responsive:true,
  ajax: { url: URLS.materialsDT },
  columns: [
    {data:'sku'},
    {data:'name'},
    {data:'qty_planned', className:'text-end'},
    {data:'qty_issued',  className:'text-end'},
    {data:'qty_returned',className:'text-end'},
    {data:'remaining',   className:'text-end'},
    {data:'notes'},
    {data:'actions', orderable:false, searchable:false, className:'text-end'}
  ],
  dom:'Blfrtip',
  buttons:[
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
  ],
  createdRow: row => row.classList.add('align-middle')
});

// Hook Issue/Return buttons (delegate => works in responsive child)
$(document).on('click', '.issue-mat', function(){
  const id = $(this).data('id');
  console.log('Issue material line', id, '(wire to your Stock Issue UI)');
});
$(document).on('click', '.return-mat', function(){
  const id = $(this).data('id');
  console.log('Return material line', id, '(wire to your Stock Return UI)');
});

// ---------- Steps table ----------
const stepTbl = $('#stepTbl').DataTable({
  serverSide:true, responsive:true,
  ajax: { url: URLS.stepsDT },
  columns: [
    {data:'DT_RowIndex', orderable:false, searchable:false},
    {data:'step_name'},
    {data:'sequence'},
    {data:'step_name'},
    {data:'instructions'},
    {data:'actions', orderable:false, searchable:false, className:'text-end'}
  ],
  order:[[1,'asc']],
  dom:'Blfrtip',
  buttons:[
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
  ],
  createdRow: row => row.classList.add('align-middle')
});

// Start / Finish a step
$(document).on('click', '.step-start', function(){
  const id = $(this).data('id');
  $.post(@json(route('admin.production.work-orders.steps.start', 0)).replace('/0','/'+id), {_token:CSRF})
    .done(()=> stepTbl.ajax.reload(null,false))
    .fail(x=> Swal.fire('Error', x.responseJSON?.message||'Failed', 'error'));
});
$(document).on('click', '.step-finish', function(){
  const id = $(this).data('id');
  $.post(@json(route('admin.production.work-orders.steps.finish', 0)).replace('/0','/'+id), {_token:CSRF})
    .done(()=> stepTbl.ajax.reload(null,false))
    .fail(x=> Swal.fire('Error', x.responseJSON?.message||'Failed', 'error'));
});

// ---------- Tasks table ----------
const taskTbl = $('#taskTbl').DataTable({
  serverSide:true, responsive:true,
  ajax: { url: URLS.tasksDT },
  columns: [
    {data:'DT_RowIndex', orderable:false, searchable:false},
    {data:'title'},
    {data:'step_name'},
    {data:'assignees_html', orderable:false, searchable:false},
    {data:'status_badge',   orderable:false, searchable:false},
    {data:'est_fmt', className:'text-end'},
    {data:'act_fmt', className:'text-end'},
    {data:'due_fmt'},
    {data:'progress_html',  orderable:false, searchable:false},
    {data:'actions',        orderable:false, searchable:false, className:'text-end'}
  ],
  order:[[1,'asc']],
  dom:'Blfrtip',
  buttons:[
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
  ],
  createdRow: row => row.classList.add('align-middle')
});

// ---------- Add/Edit Task Modal ----------
const taskModal = new bootstrap.Modal('#taskModal');
const $taskId   = $('#task_id');
const $title    = $('#task_title');
const $priority = $('#task_priority');
const $stepSel  = $('#task_step_id');
const $assignees= $('#task_assignees');
const $est      = $('#task_est');
const $due      = $('#task_due');
const $desc     = $('#task_desc');
const $checkWrap= $('#checklistWrap');

function addChecklistInput(val=''){
  const rid = 'cli_' + Math.random().toString(36).slice(2,8);
  $checkWrap.append(`
    <div class="checklist-row input-group">
      <input name="checklist[]" class="form-control" placeholder="Checklist item" value="${val? $('<div>').text(val).html() : ''}">
      <button type="button" class="btn btn-outline-danger remove-check" title="Remove"><i class="fas fa-trash"></i></button>
    </div>
  `);
}
$(document).on('click', '.remove-check', function(){ $(this).closest('.checklist-row').remove(); });
$('#addChecklistItem').on('click', ()=> addChecklistInput(''));

// Select2: steps & employees
$stepSel.select2({
  ajax: { url: URLS.stepsSelect, dataType:'json', delay:250, data: p=>({q:p.term}), processResults:d=>({results:d}) },
  width:'100%', placeholder:'-- optional step --', minimumInputLength:0, allowClear:true,
  dropdownParent: $('#taskModal')
});

$assignees.select2({
  ajax: {
    url: URLS.employees,
    dataType: 'json',
    delay: 250,
    data: params => ({
      q: params.term || '',
      page: params.page || 1,
      // only_active: true, // add if you want to filter
    }),
    processResults: (data, params) => ({
      results: data.results ?? data,                // use the array
      pagination: data.pagination ?? { more: false } // pass through pagination
    }),
    cache: true
  },
  width: '100%',
  multiple: true,
  minimumInputLength: 0,
  placeholder: '-- assign employees --',
  dropdownParent: $('#taskModal')
});


// Open Add Task
$('#addTaskBtn').on('click', ()=>{
  $('#taskForm')[0].reset();
  $taskId.val('');
  $stepSel.val(null).trigger('change');
  $assignees.val(null).trigger('change');
  $checkWrap.empty();
  $('.modal-title', '#taskModal').text('Add Task');
  taskModal.show();
});

// Edit Task (delegated)
$(document).on('click', '.edit-task', function(){
  const r = $(this).data('record'); // supply from server
  $('#taskForm')[0].reset();
  $taskId.val(r.id);
  $title.val(r.title || '');
  $priority.val(r.priority || 'normal').trigger('change');
  $est.val(r.estimated_minutes || '');
  $due.val(r.due_at_local || ''); // send `YYYY-MM-DDTHH:mm` for datetime-local
  $desc.val(r.description || '');

  // Preselect step
  $stepSel.val(null).trigger('change');
  if (r.step_name && r.workorder_step_id) {
    const opt = new Option(r.step_name, r.workorder_step_id, true, true);
    $stepSel.append(opt).trigger('change');
  }

  // Preselect assignees
  $assignees.val(null).trigger('change');
  if (Array.isArray(r.assignees) && r.assignees.length){
    r.assignees.forEach(a=>{
      const opt = new Option(a.text, a.id, true, true);
      $assignees.append(opt);
    });
    $assignees.trigger('change');
  }

  // Checklist
  $checkWrap.empty();
  if (Array.isArray(r.checklist) && r.checklist.length){
    r.checklist.forEach(item => addChecklistInput(item.label || item));
  }

  $('.modal-title', '#taskModal').text('Edit Task');
  taskModal.show();
});

// Save Task (create/update)
$('#saveTaskBtn').on('click', function(e){
  e.preventDefault();

  const id  = $taskId.val();
  const url = id ? URLS.taskUpdate(id) : URLS.taskStore;
  const method = id ? 'PUT' : 'POST';

  const payload = {
    _token: CSRF,
    title: $title.val(),
    priority: $priority.val(),
    workorder_step_id: $stepSel.val(),
    estimated_minutes: $est.val(),
    due_at: $due.val(),
    description: $desc.val(),
    'assignees': $assignees.val() || [],
    'checklist': $('input[name="checklist[]"]').map((i,e)=> e.value).get().filter(Boolean)
  };

  $.ajax({url, type: method, data: payload})
    .done(res => {
      taskModal.hide();
      taskTbl.ajax.reload(null,false);
      Swal.fire('Saved', res.message||'Task saved','success');
    })
    .fail(x  => {
      const msg = x.responseJSON?.message || 'Save failed';
      const errs= x.responseJSON?.errors;
      Swal.fire('Error', errs? Object.values(errs).flat().join('<br>') : msg, 'error');
    });
});

// Task actions: start/stop/complete/delete
$(document).on('click', '.task-start', function(){
  const id = $(this).data('id');
  $.post(URLS.taskStart(id), {_token:CSRF, employee_id: $(this).data('emp')})
    .done(()=> taskTbl.ajax.reload(null,false))
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
});
$(document).on('click', '.task-stop', function(){
  const id = $(this).data('id');
  $.post(URLS.taskStop(id), {_token:CSRF, employee_id: $(this).data('emp')})
    .done(()=> taskTbl.ajax.reload(null,false))
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
});
$(document).on('click', '.task-complete', function(){
  const id = $(this).data('id');
  $.post(URLS.taskComplete(id), {_token:CSRF})
    .done(()=> taskTbl.ajax.reload(null,false))
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
});
$(document).on('click', '.del-task', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Delete task?', icon:'warning', showCancelButton:true})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({url: URLS.taskDelete(id), type:'DELETE', data:{_token:CSRF}})
        .done(res => { taskTbl.ajax.reload(null,false); Swal.fire('Deleted', res.message||'Removed','success'); })
        .fail(x  => { Swal.fire('Error', x.responseJSON?.message||'Failed','error'); });
    });
});

// ---------- Costs table ----------
const costTbl = $('#costTbl').DataTable({
  serverSide:true, responsive:true,
  ajax: { url: URLS.costsDT },
  columns: [
    {data:'type_name'},
    {data:'category'},
    {data:'qty_fmt',   className:'text-end'},
    {data:'unit_name'},
    {data:'rate_fmt',  className:'text-end'},
    {data:'amount_fmt',className:'text-end'},
    {data:'note'},
    {data:'actions', orderable:false, searchable:false, className:'text-end'}
  ],
  dom:'Blfrtip',
  buttons:[
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
  ],
  drawCallback: function(settings){
    const json = settings.json || {};
    if (json.totals && json.totals.amount != null) {
      $('#costTotalCell').text(Number(json.totals.amount).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
    }
  },
  createdRow: row => row.classList.add('align-middle')
});

// ---------- Add/Edit Cost Modal ----------
const costModal = new bootstrap.Modal('#costModal');
const $costId   = $('#cost_id');
const $ctype    = $('#cost_type_id');
const $cunit    = $('#cost_unit_id');
const $cqty     = $('#cost_qty');
const $crate    = $('#cost_rate');
const $cnote    = $('#cost_note');

// Select2 sources (adjust endpoints if yours differ)
$ctype.select2({
  ajax: { url: URLS.costTypes, dataType:'json', delay:250, data: p=>({q:p.term}), processResults:d=>({results:d}) },
  dropdownParent: $('#costModal'), width:'100%', placeholder:'-- select type --', minimumInputLength:0
});
$cunit.select2({
  ajax: { url: URLS.units, dataType:'json', delay:250, data: p=>({q:p.term}), processResults:d=>({results:d}) },
  dropdownParent: $('#costModal'), width:'100%', placeholder:'-- optional unit --', minimumInputLength:0, allowClear:true
});

$('#addCostBtn').on('click', ()=>{
  $('#costForm')[0].reset();
  $ctype.val(null).trigger('change');
  $cunit.val(null).trigger('change');
  $costId.val('');
  $('.modal-title', '#costModal').text('Add Cost');
  costModal.show();
});

// Edit cost (delegated)
$(document).on('click', '.edit-cost', function(){
  const r = $(this).data('record'); // provided by controller
  $('#costForm')[0].reset();
  $costId.val(r.id);

  if (r.type_name) {
    const opt = new Option(r.type_name, r.work_order_cost_type_id || '', true, true);
    $ctype.append(opt).trigger('change');
  } else { $ctype.val(null).trigger('change'); }

  if (r.unit_name && r.unit_id) {
    const opt2 = new Option(r.unit_name, r.unit_id, true, true);
    $cunit.append(opt2).trigger('change');
  } else { $cunit.val(null).trigger('change'); }

  $cqty.val(r.qty ?? '');
  $crate.val(r.rate ?? '');
  $cnote.val(r.note ?? '');

  $('.modal-title', '#costModal').text('Edit Cost');
  costModal.show();
});

// Save cost
$('#saveCostBtn').on('click', function(e){
  e.preventDefault();

  const id  = $costId.val();
  const url = id ? URLS.costUpdate(id) : URLS.costStore;
  const method = id ? 'PUT' : 'POST';

  const payload = {
    _token: CSRF,
    work_order_cost_type_id: $ctype.val(),
    unit_id: $cunit.val(),
    qty: $cqty.val(),
    rate: $crate.val(),
    note: $cnote.val()
  };

  $.ajax({url, type: method, data: payload})
    .done(res => { costModal.hide(); costTbl.ajax.reload(null,false); Swal.fire('Saved', res.message||'Cost saved','success'); })
    .fail(x  => {
      const msg = x.responseJSON?.message || 'Save failed';
      const errs= x.responseJSON?.errors;
      Swal.fire('Error', errs? Object.values(errs).flat().join('<br>') : msg, 'error');
    });
});

// Delete cost
$(document).on('click', '.del-cost', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Delete cost line?', icon:'warning', showCancelButton:true})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({url: URLS.costDelete(id), type:'DELETE', data:{_token:CSRF}})
        .done(res => { costTbl.ajax.reload(null,false); Swal.fire('Deleted', res.message||'Removed','success'); })
        .fail(x  => { Swal.fire('Error', x.responseJSON?.message||'Failed','error'); });
    });
});

// ---------- Lifecycle buttons ----------
function updateStatusBadge(newStatus){
  const map = {draft:'secondary', released:'info', in_progress:'warning', completed:'success', closed:'dark'};
  $('#woStatusBadge').html(`<span class="badge bg-${map[newStatus]||'secondary'} text-white">${newStatus.charAt(0).toUpperCase()+newStatus.slice(1)}</span>`);
  // Toggle buttons
  $('#btnRelease').toggleClass('d-none', newStatus!=='draft');
  $('#btnStart').toggleClass('d-none', !(newStatus==='released' || newStatus==='paused'));
  $('#btnComplete').toggleClass('d-none', newStatus!=='in_progress');
  $('#btnClose').toggleClass('d-none', newStatus!=='completed');
}

$('#btnRelease').on('click', ()=>{
  $.post(URLS.release, {_token:CSRF})
    .done(()=>{ updateStatusBadge('released'); matTbl.ajax.reload(null,false); stepTbl.ajax.reload(null,false); taskTbl.ajax.reload(null,false); })
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
});

$('#btnStart').on('click', ()=>{
  $.post(URLS.start, {_token:CSRF})
    .done(()=>{ updateStatusBadge('in_progress'); stepTbl.ajax.reload(null,false); taskTbl.ajax.reload(null,false); })
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
});
$('#btnComplete').on('click', ()=>{
  $.post(URLS.complete, {_token:CSRF})
    .done(res=>{
      updateStatusBadge('completed');
      Swal.fire('Completed', `Total Cost: ${Number(res.summary.total_cost).toLocaleString()} (Unit: ${Number(res.summary.unit_cost).toLocaleString(undefined,{minimumFractionDigits:4})})`, 'success');
      taskTbl.ajax.reload(null,false);
    })
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
});
$('#btnClose').on('click', ()=>{
  $.post(URLS.close, {_token:CSRF})
    .done(()=>{ updateStatusBadge('closed'); })
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
});

$(document).on('click', '.issue-mat', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Issue qty', input:'number', inputAttributes:{min:0.000001,step:0.000001}})
    .then(r=>{
      if(!r.value) return;
      $.post(@json(route('admin.production.work-orders.materials.issue', 0)).replace('/0','/'+id),
             {_token:CSRF, qty:r.value, note:'Issued via WO screen'})
        .done(()=> matTbl.ajax.reload(null,false))
        .fail(x=> Swal.fire('Error', x.responseJSON?.message||'Failed', 'error'));
    });
});

$(document).on('click', '.return-mat', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Return qty', input:'number', inputAttributes:{min:0.000001,step:0.000001}})
    .then(r=>{
      if(!r.value) return;
      $.post(@json(route('admin.production.work-orders.materials.return', 0)).replace('/0','/'+id),
             {_token:CSRF, qty:r.value, note:'Return via WO screen'})
        .done(()=> matTbl.ajax.reload(null,false))
        .fail(x=> Swal.fire('Error', x.responseJSON?.message||'Failed', 'error'));
    });
});

// ------------------- Dependencies Modal -------------------
const depsModal = new bootstrap.Modal('#depsModal');
let CURRENT_TASK_ID = null;

const $depSelect = $('#dep_task_select').select2({
  ajax: {
    url: URLS.tasksSelect,
    dataType: 'json',
    delay: 250,
    data: p => ({ q: p.term || '', except: CURRENT_TASK_ID }), // optional exclude current task
    processResults: d => ({ results: d.results, pagination: d.pagination || { more:false } }),
  },
  dropdownParent: $('#depsModal'),
  width: '100%',
  placeholder: '-- select a blocking task --',
  minimumInputLength: 0
});

let depTbl = null;

$(document).on('click', '.deps-btn', function(){
  CURRENT_TASK_ID = $(this).data('task');
  $depSelect.val(null).trigger('change');

  if (depTbl) { depTbl.destroy(); $('#depTbl').empty().append(`
    <thead class="table-light"><tr><th>#</th><th>Depends On</th><th class="text-end">Actions</th></tr></thead>`); }

  depTbl = $('#depTbl').DataTable({
    serverSide:true, responsive:true,
    ajax: { url: URLS.depsDT(CURRENT_TASK_ID) },
    columns: [
      {data:'DT_RowIndex', orderable:false, searchable:false},
      {data:'depends_on_title'},
      {data:'actions', orderable:false, searchable:false, className:'text-end'}
    ],
    createdRow: row => row.classList.add('align-middle')
  });

  depsModal.show();
});

$('#addDepBtn').on('click', function(){
  if (!CURRENT_TASK_ID) return;
  const depId = $depSelect.val();
  if (!depId) return Swal.fire('Select a task first', '', 'info');
  $.post(URLS.depStore(CURRENT_TASK_ID), {_token:CSRF, depends_on_task_id: depId})
    .done(()=> depTbl.ajax.reload(null,false))
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed','error'));
});

$(document).on('click', '.del-dep', function(){
  const id = $(this).data('id');
  $.ajax({url: URLS.depDelete(id), type:'DELETE', data:{_token:CSRF}})
    .done(()=> depTbl.ajax.reload(null,false))
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed','error'));
});

// ------------------- Time Logs Modal -------------------
const logsModal = new bootstrap.Modal('#logsModal');
let CURRENT_TASK_FOR_LOGS = null;
let logTbl = null;

const $logEmp = $('#log_employee_id').select2({
  ajax: {
    url: URLS.employees, dataType:'json', delay:250,
    data: p => ({q:p.term||'', page:p.page||1}),
    processResults: d => ({results: d.results ?? d, pagination: d.pagination ?? {more:false}})
  },
  dropdownParent: $('#logsModal'), width:'100%', placeholder:'-- employee --', minimumInputLength:0
});

function resetLogForm(){
  $('#logForm')[0].reset();
  $('#log_id').val('');
  $logEmp.val(null).trigger('change');
}
$('#resetLogBtn').on('click', resetLogForm);

$(document).on('click', '.logs-btn', function(){
  CURRENT_TASK_FOR_LOGS = $(this).data('task');
  resetLogForm();

  if (logTbl) { logTbl.destroy(); $('#logTbl').empty().append(`
    <thead class="table-light"><tr>
      <th>#</th><th>Employee</th><th>Started</th><th>Ended</th>
      <th class="text-end">Minutes</th><th>Note</th><th class="text-end">Actions</th>
    </tr></thead>`); }

  logTbl = $('#logTbl').DataTable({
    serverSide:true, responsive:true,
    ajax: { url: URLS.logsDT(CURRENT_TASK_FOR_LOGS) },
    columns: [
      {data:'DT_RowIndex', orderable:false, searchable:false},
      {data:'emp_name'},
      {data:'started_at'},
      {data:'ended_at', orderable:false, searchable:false},
      {data:'minutes', className:'text-end'},
      {data:'note'},
      {data:'actions', orderable:false, searchable:false, className:'text-end'}
    ],
    createdRow: row => row.classList.add('align-middle')
  });

  logsModal.show();
});

// Save (create or update) log
function toIsoLocal(v){
  if (!v) return v;

  // 20/08/2025 01:00  ->  2025-08-20T01:00
  let m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})$/);
  if (m) return `${m[3]}-${m[2]}-${m[1]}T${m[4]}:${m[5]}`;

  // 20-08-2025 01:00  ->  2025-08-20T01:00
  m = v.match(/^(\d{2})-(\d{2})-(\d{4})\s+(\d{2}):(\d{2})$/);
  if (m) return `${m[3]}-${m[2]}-${m[1]}T${m[4]}:${m[5]}`;

  // 2025-08-20 01:00  ->  2025-08-20T01:00
  m = v.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})$/);
  if (m) return `${m[1]}-${m[2]}-${m[3]}T${m[4]}:${m[5]}`;

  // Already ISO-like or empty
  return v;
}

$('#saveLogBtn').on('click', function(e){
  e.preventDefault();
  const id  = $('#log_id').val();
  const url = id ? URLS.logUpdate(id) : URLS.logStore(CURRENT_TASK_FOR_LOGS);
  const type= id ? 'PUT' : 'POST';

  const startedRaw = $('#log_started_at').val();
  const endedRaw   = $('#log_ended_at').val();

  const payload = {
    _token: CSRF,
    employee_id: $('#log_employee_id').val(),
    started_at:  toIsoLocal(startedRaw),
    ended_at:    toIsoLocal(endedRaw),
    minutes:     ($('#log_minutes').val() === '' ? null : $('#log_minutes').val()),
    note:        $('#log_note').val()
  };

  // helpful while testing
  console.log('posting log payload:', payload);

  $.ajax({ url, type, data: payload })
    .done(()=> { resetLogForm(); logTbl.ajax.reload(null,false); taskTbl.ajax.reload(null,false); })
    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
});


// Edit/Delete handlers
$(document).on('click', '.edit-log', function(){
  const r = $(this).data('record'); // from controller
  $('#log_id').val(r.id || '');
  $logEmp.val(null).trigger('change');
  if (r.employee_id && r.emp_name) {
    $logEmp.append(new Option(r.emp_name, r.employee_id, true, true)).trigger('change');
  }
  $('#log_started_at').val(r.started_at || '');
  $('#log_ended_at').val(r.ended_at || '');
  $('#log_minutes').val(r.minutes || '');
  $('#log_note').val(r.note || '');
});

$(document).on('click', '.del-log', function(){
  const id = $(this).data('id');
  $.ajax({url: URLS.logDelete(id), type:'DELETE', data:{_token:CSRF}})
    .done(()=> { logTbl.ajax.reload(null,false); taskTbl.ajax.reload(null,false); })
    .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed','error'));
});

// ------------ Materials Add/Edit ------------
const matModal = new bootstrap.Modal('#matModal');
const $matId   = $('#mat_id');
const $mVar    = $('#mat_variant_id');
const $mQty    = $('#mat_qty');
const $mNote   = $('#mat_note');

$mVar.select2({
  ajax: { url: URLS.matVariants, dataType:'json', delay:250, data:p=>({q:p.term}), processResults:d=>({results:d}) },
  dropdownParent: $('#matModal'), width:'100%', placeholder:'-- select variant --', minimumInputLength:0
});

$('#addMatBtn').on('click', ()=>{
  $('#matForm')[0].reset();
  $matId.val('');
  $mVar.val(null).trigger('change');
  $('.modal-title','#matModal').text('Add Material');
  matModal.show();
});

$(document).on('click', '.edit-mat', function(){
  const r = $(this).data('record');
  $('#matForm')[0].reset();
  $matId.val(r.id);
  // preselect variant
  $mVar.val(null).trigger('change');
  if (r.product_variant_id && r.variant_label) {
    $mVar.append(new Option(r.variant_label, r.product_variant_id, true, true)).trigger('change');
  }
  $mQty.val(r.qty_planned || '');
  $mNote.val(r.note || '');
  $('.modal-title','#matModal').text('Edit Material');
  matModal.show();
});

$('#saveMatBtn').on('click', function(e){
  e.preventDefault();
  const id = $matId.val();
  const url = id ? URLS.matUpdate(id) : URLS.matStore;
  const type = id ? 'PUT' : 'POST';

  $.ajax({
    url, type,
    data: {
      _token: CSRF,
      product_variant_id: $mVar.val(),
      qty_planned: $mQty.val(),
      note: $mNote.val()
    }
  })
  .done(res => { matModal.hide(); matTbl.ajax.reload(null,false); Swal.fire('Saved', res.message || 'Material saved', 'success'); })
  .fail(x  => {
    const msg = x.responseJSON?.message || 'Save failed';
    const errs= x.responseJSON?.errors;
    Swal.fire('Error', errs? Object.values(errs).flat().join('<br>') : msg, 'error');
  });
});

// Delete material
$(document).on('click', '.del-mat', function(){
  const id = $(this).data('id');
  Swal.fire({title:'Delete material line?', icon:'warning', showCancelButton:true})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({url: URLS.matDelete(id), type:'DELETE', data:{_token:CSRF}})
        .done(res => { matTbl.ajax.reload(null,false); Swal.fire('Deleted', res.message||'Removed','success'); })
        .fail(x  => { Swal.fire('Error', x.responseJSON?.message||'Failed','error'); });
    });
});

</script>
@endpush
