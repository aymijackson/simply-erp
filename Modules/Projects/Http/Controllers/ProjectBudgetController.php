<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectBudget;
use Modules\Projects\Models\ProjectBudgetLine;
use Modules\Projects\Models\ProjectMilestone;
use Modules\Projects\Models\ProjectTask;

class ProjectBudgetController extends Controller
{
    public function index()
    {
        return view('projects.budgets.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $query = ProjectBudget::query()
            ->with(['project:id,project_code,project_name'])
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('budget_start_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('budget_end_date', '<=', $request->date_to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($sub) use ($term) {
                    $sub->where('budget_code', 'like', "%{$term}%")
                        ->orWhere('budget_name', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%")
                        ->orWhereHas('project', function ($p) use ($term) {
                            $p->where('project_code', 'like', "%{$term}%")
                              ->orWhere('project_name', 'like', "%{$term}%");
                        });
                });
            });

        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);

        $recordsTotal = (clone $query)->count();

        $rows = (clone $query)
            ->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($row) use ($companyId) {
            $actual = $this->getProjectBudgetActualAmount($companyId, $row->project_id, $row->id);
            $budget = (float) $row->total_budget_amount;
            $variance = round($budget - $actual, 2);

            $statusBadge = match ($row->status) {
                'approved' => '<span class="badge bg-success">APPROVED</span>',
                'revised'  => '<span class="badge bg-warning text-dark">REVISED</span>',
                'closed'   => '<span class="badge bg-dark">CLOSED</span>',
                default    => '<span class="badge bg-secondary">DRAFT</span>',
            };

            $json = [
                'id'                 => $row->id,
                'project_id'         => $row->project_id,
                'project_label'      => ($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? ''),
                'budget_code'        => $row->budget_code,
                'budget_name'        => $row->budget_name,
                'version_no'         => $row->version_no,
                'budget_start_date'  => optional($row->budget_start_date)->format('Y-m-d'),
                'budget_end_date'    => optional($row->budget_end_date)->format('Y-m-d'),
                'currency_code'      => $row->currency_code,
                'status'             => $row->status,
                'notes'              => $row->notes,
            ];

            $actions = view('projects.budgets.partials.actions', [
                'row'  => $row,
                'json' => $json,
            ])->render();

            return [
                'id'            => $row->id,
                'project'       => e(($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? '')),
                'budget_code'   => e($row->budget_code ?? '—'),
                'budget_name'   => e($row->budget_name),
                'version_no'    => e($row->version_no),
                'period'        => e((optional($row->budget_start_date)->format('d-m-Y') ?: '—') . ' to ' . (optional($row->budget_end_date)->format('d-m-Y') ?: '—')),
                'budget_amount' => number_format($budget, 2),
                'actual_amount' => number_format($actual, 2),
                'variance'      => number_format($variance, 2),
                'status'        => $statusBadge,
                'actions'       => $actions,
            ];
        })->values();

        $totalBudget = ProjectBudget::query()
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('budget_start_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('budget_end_date', '<=', $request->date_to))
            ->sum('total_budget_amount');

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
            'meta' => [
                'total_budget_amount' => round((float) $totalBudget, 2),
            ],
        ]);
    }

    public function lines($budgetId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $budget = ProjectBudget::where('company_id', $companyId)->findOrFail($budgetId);

        $lines = ProjectBudgetLine::query()
            ->with([
                'task:id,task_name',
                'milestone:id,milestone_name',
            ])
            ->where('project_budget_id', $budget->id)
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                return [
                    'id'               => $row->id,
                    'task_id'          => $row->task_id,
                    'task_label'       => $row->task->task_name ?? null,
                    'milestone_id'     => $row->milestone_id,
                    'milestone_label'  => $row->milestone->milestone_name ?? null,
                    'cost_category'    => $row->cost_category,
                    'line_description' => $row->line_description,
                    'quantity'         => (float) $row->quantity,
                    'unit_cost'        => (float) $row->unit_cost,
                    'budget_amount'    => (float) $row->budget_amount,
                    'notes'            => $row->notes,
                ];
            })->values();

        return response()->json(['lines' => $lines]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateBudget($request);

        return DB::transaction(function () use ($companyId, $data) {
            $total = $this->computeBudgetTotal($data['lines']);

            $budget = ProjectBudget::create([
                'company_id'          => $companyId,
                'project_id'          => $data['project_id'],
                'budget_code'         => $data['budget_code'] ?? null,
                'budget_name'         => $data['budget_name'],
                'version_no'          => $data['version_no'] ?? 1,
                'budget_start_date'   => $data['budget_start_date'] ?? null,
                'budget_end_date'     => $data['budget_end_date'] ?? null,
                'currency_code'       => $data['currency_code'] ?? 'NGN',
                'total_budget_amount' => $total,
                'status'              => $data['status'],
                'notes'               => $data['notes'] ?? null,
                'created_by'          => auth()->id(),
                'updated_by'          => auth()->id(),
            ]);

            $this->syncLines($budget, $data['lines']);
            $this->syncProjectBudgetRollup($budget->project_id, $companyId);

            return response()->json([
                'message' => 'Project budget created successfully.',
                'id'      => $budget->id,
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $budget = ProjectBudget::where('company_id', $companyId)->findOrFail($id);

        $data = $this->validateBudget($request);

        return DB::transaction(function () use ($companyId, $budget, $data) {
            $oldProjectId = $budget->project_id;
            $total = $this->computeBudgetTotal($data['lines']);

            $budget->update([
                'project_id'          => $data['project_id'],
                'budget_code'         => $data['budget_code'] ?? null,
                'budget_name'         => $data['budget_name'],
                'version_no'          => $data['version_no'] ?? 1,
                'budget_start_date'   => $data['budget_start_date'] ?? null,
                'budget_end_date'     => $data['budget_end_date'] ?? null,
                'currency_code'       => $data['currency_code'] ?? 'NGN',
                'total_budget_amount' => $total,
                'status'              => $data['status'],
                'notes'               => $data['notes'] ?? null,
                'updated_by'          => auth()->id(),
                'approved_at'         => $data['status'] === 'approved' ? now() : null,
                'approved_by'         => $data['status'] === 'approved' ? auth()->id() : null,
            ]);

            $this->syncLines($budget, $data['lines']);

            $this->syncProjectBudgetRollup($oldProjectId, $companyId);
            if ((int) $oldProjectId !== (int) $budget->project_id) {
                $this->syncProjectBudgetRollup($budget->project_id, $companyId);
            }

            return response()->json([
                'message' => 'Project budget updated successfully.',
            ]);
        });
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $budget = ProjectBudget::where('company_id', $companyId)->findOrFail($id);

        $projectId = $budget->project_id;

        DB::transaction(function () use ($budget) {
            ProjectBudgetLine::where('project_budget_id', $budget->id)->delete();
            $budget->delete();
        });

        $this->syncProjectBudgetRollup($projectId, $companyId);

        return response()->json([
            'message' => 'Project budget deleted successfully.',
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

    protected function validateBudget(Request $request): array
    {
        return Validator::make($request->all(), [
            'project_id'         => ['required', 'integer', 'exists:projects,id'],
            'budget_code'        => ['nullable', 'string', 'max:50'],
            'budget_name'        => ['required', 'string', 'max:255'],
            'version_no'         => ['nullable', 'integer', 'min:1'],
            'budget_start_date'  => ['nullable', 'date'],
            'budget_end_date'    => ['nullable', 'date'],
            'currency_code'      => ['nullable', 'string', 'max:10'],
            'status'             => ['required', 'in:draft,approved,revised,closed'],
            'notes'              => ['nullable', 'string'],
            'lines'              => ['required', 'array', 'min:1'],
            'lines.*.task_id'          => ['nullable', 'integer', 'exists:project_tasks,id'],
            'lines.*.milestone_id'     => ['nullable', 'integer', 'exists:project_milestones,id'],
            'lines.*.cost_category'    => ['required', 'in:materials,labour,logistics,subcontract,overhead,expense,other'],
            'lines.*.line_description' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_cost'        => ['required', 'numeric', 'min:0'],
            'lines.*.budget_amount'    => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes'            => ['nullable', 'string'],
        ])->validate();
    }

    protected function computeBudgetTotal(array $lines): float
    {
        $total = 0.00;

        foreach ($lines as $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $unit = (float) ($line['unit_cost'] ?? 0);
            $amount = (float) ($line['budget_amount'] ?? 0);

            if ($amount <= 0) {
                $amount = $qty * $unit;
            }

            $total += $amount;
        }

        return round($total, 2);
    }

    protected function syncLines(ProjectBudget $budget, array $lines): void
    {
        ProjectBudgetLine::where('project_budget_id', $budget->id)->delete();

        $rows = [];

        foreach ($lines as $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $unit = (float) ($line['unit_cost'] ?? 0);
            $amount = (float) ($line['budget_amount'] ?? 0);

            if ($amount <= 0) {
                $amount = $qty * $unit;
            }

            $rows[] = [
                'company_id'        => $budget->company_id,
                'project_budget_id' => $budget->id,
                'project_id'        => $budget->project_id,
                'task_id'           => $line['task_id'] ?? null,
                'milestone_id'      => $line['milestone_id'] ?? null,
                'cost_category'     => $line['cost_category'],
                'line_description'  => $line['line_description'] ?? null,
                'quantity'          => $qty,
                'unit_cost'         => $unit,
                'budget_amount'     => round($amount, 2),
                'notes'             => $line['notes'] ?? null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        if (!empty($rows)) {
            ProjectBudgetLine::insert($rows);
        }
    }

    protected function getProjectBudgetActualAmount(int $companyId, int $projectId, int $budgetId): float
    {
        $budget = ProjectBudget::find($budgetId);
        if (!$budget) return 0.00;

        $query = DB::table('project_costs')
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('status', 'posted')
            ->whereNull('deleted_at');

        if ($budget->budget_start_date) {
            $query->whereDate('cost_date', '>=', $budget->budget_start_date);
        }

        if ($budget->budget_end_date) {
            $query->whereDate('cost_date', '<=', $budget->budget_end_date);
        }

        return round((float) $query->sum('amount'), 2);
    }

    protected function syncProjectBudgetRollup(int $projectId, int $companyId): void
    {
        $approvedBudget = ProjectBudget::query()
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereIn('status', ['approved', 'revised', 'closed'])
            ->orderByDesc('version_no')
            ->orderByDesc('id')
            ->first();

        $budgetAmount = (float) ($approvedBudget->total_budget_amount ?? 0);

        $actualCost = 0.00;
        if (Schema::hasColumn('projects', 'actual_cost')) {
            $actualCost = (float) Project::query()
                ->where('company_id', $companyId)
                ->where('id', $projectId)
                ->value('actual_cost');
        } else {
            $actualCost = (float) DB::table('project_costs')
                ->where('company_id', $companyId)
                ->where('project_id', $projectId)
                ->where('status', 'posted')
                ->whereNull('deleted_at')
                ->sum('amount');
        }

        $update = [];

        if (Schema::hasColumn('projects', 'budget_amount')) {
            $update['budget_amount'] = round($budgetAmount, 2);
        }
        if (Schema::hasColumn('projects', 'budget_variance')) {
            $update['budget_variance'] = round($budgetAmount - $actualCost, 2);
        }
        if (Schema::hasColumn('projects', 'remaining_budget')) {
            $update['remaining_budget'] = round($budgetAmount - $actualCost, 2);
        }

        if (!empty($update)) {
            $update['updated_at'] = now();

            Project::query()
                ->where('company_id', $companyId)
                ->where('id', $projectId)
                ->update($update);
        }
    }
}