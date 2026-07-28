<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectCost;
use Modules\Projects\Models\ProjectMilestone;
use Modules\Projects\Models\ProjectTask;

class ProjectCostController extends Controller
{
    public function index()
    {
        return view('projects.costs.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $query = ProjectCost::query()
            ->with([
                'project:id,project_code,project_name',
                'task:id,task_name',
                'milestone:id,milestone_name',
            ])
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('task_id'), fn($q) => $q->where('task_id', $request->task_id))
            ->when($request->filled('milestone_id'), fn($q) => $q->where('milestone_id', $request->milestone_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('cost_category'), fn($q) => $q->where('cost_category', $request->cost_category))
            ->when($request->filled('source_type'), fn($q) => $q->where('source_type', $request->source_type))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('cost_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('cost_date', '<=', $request->date_to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($sub) use ($term) {
                    $sub->where('reference_no', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%")
                        ->orWhereHas('project', function ($p) use ($term) {
                            $p->where('project_code', 'like', "%{$term}%")
                              ->orWhere('project_name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('task', fn($t) => $t->where('task_name', 'like', "%{$term}%"))
                        ->orWhereHas('milestone', fn($m) => $m->where('milestone_name', 'like', "%{$term}%"));
                });
            });
    
        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);
    
        $recordsTotal = (clone $query)->count();
    
        $rows = (clone $query)
            ->orderByDesc('cost_date')
            ->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();
    
        $data = $rows->map(function ($row) {
            $statusBadge = match ($row->status) {
                'draft'     => '<span class="badge bg-secondary">DRAFT</span>',
                'cancelled' => '<span class="badge bg-danger">CANCELLED</span>',
                default     => '<span class="badge bg-success">POSTED</span>',
            };
    
            $json = [
                'id'              => $row->id,
                'project_id'      => $row->project_id,
                'project_label'   => ($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? ''),
                'task_id'         => $row->task_id,
                'task_label'      => $row->task->task_name ?? null,
                'milestone_id'    => $row->milestone_id,
                'milestone_label' => $row->milestone->milestone_name ?? null,
                'cost_date'       => optional($row->cost_date)->format('Y-m-d'),
                'cost_category'   => $row->cost_category,
                'source_type'     => $row->source_type,
                'source_id'       => $row->source_id,
                'reference_no'    => $row->reference_no,
                'description'     => $row->description,
                'quantity'        => (float) $row->quantity,
                'unit_cost'       => (float) $row->unit_cost,
                'amount'          => (float) $row->amount,
                'currency_code'   => $row->currency_code,
                'status'          => $row->status,
                'notes'           => $row->notes,
            ];
    
            $actions = view('projects.costs.partials.actions', [
                'row'  => $row,
                'json' => $json,
            ])->render();
    
            return [
                'id'           => $row->id,
                'cost_date'    => optional($row->cost_date)->format('d-m-Y') ?: '—',
                'project'      => e(($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? '')),
                'task'         => e($row->task->task_name ?? '—'),
                'milestone'    => e($row->milestone->milestone_name ?? '—'),
                'category'     => e(ucwords(str_replace('_', ' ', $row->cost_category))),
                'source_type'  => e(ucwords(str_replace('_', ' ', $row->source_type))),
                'reference_no' => e($row->reference_no ?? '—'),
                'description'  => e($row->description ?? '—'),
                'quantity'     => number_format((float) $row->quantity, 2),
                'unit_cost'    => number_format((float) $row->unit_cost, 2),
                'amount'       => number_format((float) $row->amount, 2),
                'status'       => $statusBadge,
                'actions'      => $actions,
            ];
        })->values();
    
        $totalAmount = ProjectCost::query()
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('task_id'), fn($q) => $q->where('task_id', $request->task_id))
            ->when($request->filled('milestone_id'), fn($q) => $q->where('milestone_id', $request->milestone_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('cost_category'), fn($q) => $q->where('cost_category', $request->cost_category))
            ->when($request->filled('source_type'), fn($q) => $q->where('source_type', $request->source_type))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('cost_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('cost_date', '<=', $request->date_to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($sub) use ($term) {
                    $sub->where('reference_no', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%")
                        ->orWhereHas('project', function ($p) use ($term) {
                            $p->where('project_code', 'like', "%{$term}%")
                              ->orWhere('project_name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('task', fn($t) => $t->where('task_name', 'like', "%{$term}%"))
                        ->orWhereHas('milestone', fn($m) => $m->where('milestone_name', 'like', "%{$term}%"));
                });
            })
            ->sum('amount');
    
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
            'meta' => [
                'total_amount' => round((float) $totalAmount, 2),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateCost($request);

        $amount = $this->resolveAmount($data);

        $cost = ProjectCost::create([
            'company_id'    => $companyId,
            'project_id'    => $data['project_id'],
            'task_id'       => $data['task_id'] ?? null,
            'milestone_id'  => $data['milestone_id'] ?? null,
            'cost_date'     => $data['cost_date'],
            'cost_category' => $data['cost_category'],
            'source_type'   => $data['source_type'],
            'source_id'     => $data['source_id'] ?? null,
            'reference_no'  => $data['reference_no'] ?? null,
            'description'   => $data['description'] ?? null,
            'quantity'      => $data['quantity'] ?? 1,
            'unit_cost'     => $data['unit_cost'] ?? 0,
            'amount'        => $amount,
            'currency_code' => $data['currency_code'] ?? 'NGN',
            'status'        => $data['status'],
            'notes'         => $data['notes'] ?? null,
            'created_by'    => auth()->id(),
            'updated_by'    => auth()->id(),
        ]);

        $this->syncProjectActualCost($cost->project_id, $companyId);

        return response()->json([
            'message' => 'Project cost created successfully.',
            'id'      => $cost->id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $cost = ProjectCost::where('company_id', $companyId)->findOrFail($id);

        $data = $this->validateCost($request);
        $amount = $this->resolveAmount($data);

        $oldProjectId = $cost->project_id;

        $cost->update([
            'project_id'    => $data['project_id'],
            'task_id'       => $data['task_id'] ?? null,
            'milestone_id'  => $data['milestone_id'] ?? null,
            'cost_date'     => $data['cost_date'],
            'cost_category' => $data['cost_category'],
            'source_type'   => $data['source_type'],
            'source_id'     => $data['source_id'] ?? null,
            'reference_no'  => $data['reference_no'] ?? null,
            'description'   => $data['description'] ?? null,
            'quantity'      => $data['quantity'] ?? 1,
            'unit_cost'     => $data['unit_cost'] ?? 0,
            'amount'        => $amount,
            'currency_code' => $data['currency_code'] ?? 'NGN',
            'status'        => $data['status'],
            'notes'         => $data['notes'] ?? null,
            'updated_by'    => auth()->id(),
        ]);

        $this->syncProjectActualCost($oldProjectId, $companyId);
        if ((int) $oldProjectId !== (int) $cost->project_id) {
            $this->syncProjectActualCost($cost->project_id, $companyId);
        }

        return response()->json([
            'message' => 'Project cost updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $cost = ProjectCost::where('company_id', $companyId)->findOrFail($id);

        $projectId = $cost->project_id;
        $cost->delete();

        $this->syncProjectActualCost($projectId, $companyId);

        return response()->json([
            'message' => 'Project cost deleted successfully.',
        ]);
    }

    public function lookupProjects(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));

        $rows = Project::query()
            ->where('company_id', $companyId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('project_code', 'like', "%{$q}%")
                        ->orWhere('project_name', 'like', "%{$q}%");
                });
            })
            ->orderBy('project_name')
            ->limit(30)
            ->get()
            ->map(fn($p) => [
                'id'   => $p->id,
                'text' => trim(($p->project_code ?? '') . ' - ' . ($p->project_name ?? '')),
            ]);

        return response()->json(['results' => $rows]);
    }

    public function lookupTasks(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));
        $projectId = $request->get('project_id');

        $rows = ProjectTask::query()
            ->where('company_id', $companyId)
            ->when($projectId, fn($query) => $query->where('project_id', $projectId))
            ->when($q !== '', fn($query) => $query->where('task_name', 'like', "%{$q}%"))
            ->orderBy('task_name')
            ->limit(30)
            ->get()
            ->map(fn($t) => [
                'id'   => $t->id,
                'text' => trim(($t->task_code ?? '') . ' - ' . ($t->task_name ?? '')),
            ]);

        return response()->json(['results' => $rows]);
    }

    public function lookupMilestones(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));
        $projectId = $request->get('project_id');

        $rows = ProjectMilestone::query()
            ->where('company_id', $companyId)
            ->when($projectId, fn($query) => $query->where('project_id', $projectId))
            ->when($q !== '', fn($query) => $query->where('milestone_name', 'like', "%{$q}%"))
            ->orderBy('milestone_name')
            ->limit(30)
            ->get()
            ->map(fn($m) => [
                'id'   => $m->id,
                'text' => trim(($m->milestone_code ?? '') . ' - ' . ($m->milestone_name ?? '')),
            ]);

        return response()->json(['results' => $rows]);
    }

    protected function validateCost(Request $request): array
    {
        return Validator::make($request->all(), [
            'project_id'    => ['required', 'integer', 'exists:projects,id'],
            'task_id'       => ['nullable', 'integer', 'exists:project_tasks,id'],
            'milestone_id'  => ['nullable', 'integer', 'exists:project_milestones,id'],
            'cost_date'     => ['required', 'date'],
            'cost_category' => ['required', 'in:materials,labour,logistics,subcontract,overhead,expense,other'],
            'source_type'   => ['required', 'in:manual,purchase_order,goods_receipt,supplier_bill,expense,journal_entry,timesheet'],
            'source_id'     => ['nullable', 'integer'],
            'reference_no'  => ['nullable', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'quantity'      => ['nullable', 'numeric', 'min:0.01'],
            'unit_cost'     => ['nullable', 'numeric', 'min:0'],
            'amount'        => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'status'        => ['required', 'in:draft,posted,cancelled'],
            'notes'         => ['nullable', 'string'],
        ])->validate();
    }

    protected function resolveAmount(array $data): float
    {
        $qty = (float) ($data['quantity'] ?? 1);
        $unitCost = (float) ($data['unit_cost'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            $amount = $qty * $unitCost;
        }

        return round($amount, 2);
    }

    protected function syncProjectActualCost(int $projectId, int $companyId): void
    {
        $total = (float) ProjectCost::query()
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('status', 'posted')
            ->sum('amount');

        Project::query()
            ->where('company_id', $companyId)
            ->where('id', $projectId)
            ->update([
                'actual_cost' => round($total, 2),
                'updated_at'  => now(),
            ]);
    }
}