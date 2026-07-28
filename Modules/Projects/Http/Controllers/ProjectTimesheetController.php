<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectCost;
use Modules\Projects\Models\ProjectMilestone;
use Modules\Projects\Models\ProjectTask;
use Modules\Projects\Models\ProjectTimesheet;

class ProjectTimesheetController extends Controller
{
    public function index()
    {
        return view('projects.timesheets.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $query = ProjectTimesheet::query()
            ->with([
                'project:id,project_code,project_name',
                'task:id,task_name',
                'milestone:id,milestone_name',
                'employee:id,name',
                'approver:id,name',
            ])
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('task_id'), fn($q) => $q->where('task_id', $request->task_id))
            ->when($request->filled('milestone_id'), fn($q) => $q->where('milestone_id', $request->milestone_id))
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('is_billable'), fn($q) => $q->where('is_billable', $request->is_billable))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('entry_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('entry_date', '<=', $request->date_to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($sub) use ($term) {
                    $sub->where('description', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%")
                        ->orWhereHas('project', function ($p) use ($term) {
                            $p->where('project_code', 'like', "%{$term}%")
                              ->orWhere('project_name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('task', fn($t) => $t->where('task_name', 'like', "%{$term}%"))
                        ->orWhereHas('milestone', fn($m) => $m->where('milestone_name', 'like', "%{$term}%"))
                        ->orWhereHas('employee', fn($e) => $e->where('name', 'like', "%{$term}%"));
                });
            });

        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);

        $recordsTotal = (clone $query)->count();

        $rows = (clone $query)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($row) {
            $statusBadge = match ($row->status) {
                'submitted' => '<span class="badge bg-info">SUBMITTED</span>',
                'approved'  => '<span class="badge bg-success">APPROVED</span>',
                'rejected'  => '<span class="badge bg-danger">REJECTED</span>',
                default     => '<span class="badge bg-secondary">DRAFT</span>',
            };

            $billableBadge = $row->is_billable
                ? '<span class="badge bg-primary">BILLABLE</span>'
                : '<span class="badge bg-light text-dark border">NON-BILLABLE</span>';

            $json = [
                'id'              => $row->id,
                'project_id'      => $row->project_id,
                'project_label'   => ($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? ''),
                'task_id'         => $row->task_id,
                'task_label'      => $row->task->task_name ?? null,
                'milestone_id'    => $row->milestone_id,
                'milestone_label' => $row->milestone->milestone_name ?? null,
                'employee_id'     => $row->employee_id,
                'employee_label'  => $row->employee->name ?? null,
                'entry_date'      => optional($row->entry_date)->format('Y-m-d'),
                'start_time'      => $row->start_time,
                'end_time'        => $row->end_time,
                'hours'           => (float) $row->hours,
                'hourly_rate'     => (float) $row->hourly_rate,
                'cost_amount'     => (float) $row->cost_amount,
                'billable_hours'  => (float) $row->billable_hours,
                'billing_rate'    => (float) $row->billing_rate,
                'billable_amount' => (float) $row->billable_amount,
                'is_billable'     => (int) $row->is_billable,
                'status'          => $row->status,
                'description'     => $row->description,
                'notes'           => $row->notes,
                'rejection_reason'=> $row->rejection_reason,
                'source_type'     => $row->source_type,
                'source_id'       => $row->source_id,
            ];

            $actions = view('projects.timesheets.partials.actions', [
                'row'  => $row,
                'json' => $json,
            ])->render();

            return [
                'id'              => $row->id,
                'entry_date'      => optional($row->entry_date)->format('d-m-Y') ?: '—',
                'project'         => e(($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? '')),
                'task'            => e($row->task->task_name ?? '—'),
                'milestone'       => e($row->milestone->milestone_name ?? '—'),
                'employee'        => e($row->employee->name ?? '—'),
                'hours'           => number_format((float) $row->hours, 2),
                'hourly_rate'     => number_format((float) $row->hourly_rate, 2),
                'cost_amount'     => number_format((float) $row->cost_amount, 2),
                'billable_hours'  => number_format((float) $row->billable_hours, 2),
                'billing_rate'    => number_format((float) $row->billing_rate, 2),
                'billable_amount' => number_format((float) $row->billable_amount, 2),
                'billable'        => $billableBadge,
                'status'          => $statusBadge,
                'actions'         => $actions,
            ];
        })->values();

        $summaryQuery = ProjectTimesheet::query()
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('task_id'), fn($q) => $q->where('task_id', $request->task_id))
            ->when($request->filled('milestone_id'), fn($q) => $q->where('milestone_id', $request->milestone_id))
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('is_billable'), fn($q) => $q->where('is_billable', $request->is_billable))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('entry_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('entry_date', '<=', $request->date_to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($sub) use ($term) {
                    $sub->where('description', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%");
                });
            });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
            'meta' => [
                'total_hours'          => round((float) (clone $summaryQuery)->sum('hours'), 2),
                'total_cost_amount'    => round((float) (clone $summaryQuery)->sum('cost_amount'), 2),
                'total_billable_hours' => round((float) (clone $summaryQuery)->sum('billable_hours'), 2),
                'total_billable_amount'=> round((float) (clone $summaryQuery)->sum('billable_amount'), 2),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateTimesheet($request);

        $computed = $this->computeAmounts($data);

        $row = ProjectTimesheet::create([
            'company_id'       => $companyId,
            'project_id'       => $data['project_id'],
            'task_id'          => $data['task_id'] ?? null,
            'milestone_id'     => $data['milestone_id'] ?? null,
            'employee_id'      => $data['employee_id'],
            'entry_date'       => $data['entry_date'],
            'start_time'       => $data['start_time'] ?? null,
            'end_time'         => $data['end_time'] ?? null,
            'hours'            => $computed['hours'],
            'hourly_rate'      => $data['hourly_rate'] ?? 0,
            'cost_amount'      => $computed['cost_amount'],
            'billable_hours'   => $computed['billable_hours'],
            'billing_rate'     => $data['billing_rate'] ?? 0,
            'billable_amount'  => $computed['billable_amount'],
            'is_billable'      => !empty($data['is_billable']) ? 1 : 0,
            'status'           => $data['status'],
            'description'      => $data['description'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'source_type'      => $data['source_type'] ?? 'manual',
            'source_id'        => $data['source_id'] ?? null,
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
        ]);

        if ($row->status === 'approved') {
            $this->syncApprovedTimesheetToProjectCost($row);
        }

        return response()->json([
            'message' => 'Timesheet entry created successfully.',
            'id' => $row->id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $row = ProjectTimesheet::where('company_id', $companyId)->findOrFail($id);

        $oldApproved = $row->status === 'approved';
        $oldProjectId = $row->project_id;
        $oldTimesheetId = $row->id;

        $data = $this->validateTimesheet($request);
        $computed = $this->computeAmounts($data);

        $row->update([
            'project_id'       => $data['project_id'],
            'task_id'          => $data['task_id'] ?? null,
            'milestone_id'     => $data['milestone_id'] ?? null,
            'employee_id'      => $data['employee_id'],
            'entry_date'       => $data['entry_date'],
            'start_time'       => $data['start_time'] ?? null,
            'end_time'         => $data['end_time'] ?? null,
            'hours'            => $computed['hours'],
            'hourly_rate'      => $data['hourly_rate'] ?? 0,
            'cost_amount'      => $computed['cost_amount'],
            'billable_hours'   => $computed['billable_hours'],
            'billing_rate'     => $data['billing_rate'] ?? 0,
            'billable_amount'  => $computed['billable_amount'],
            'is_billable'      => !empty($data['is_billable']) ? 1 : 0,
            'status'           => $data['status'],
            'description'      => $data['description'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'source_type'      => $data['source_type'] ?? 'manual',
            'source_id'        => $data['source_id'] ?? null,
            'updated_by'       => auth()->id(),
        ]);

        if ($oldApproved) {
            $this->removeApprovedTimesheetProjectCost($oldTimesheetId, $companyId, $oldProjectId);
        }

        if ($row->status === 'approved') {
            $this->syncApprovedTimesheetToProjectCost($row);
        }

        return response()->json([
            'message' => 'Timesheet entry updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $row = ProjectTimesheet::where('company_id', $companyId)->findOrFail($id);

        if ($row->status === 'approved') {
            $this->removeApprovedTimesheetProjectCost($row->id, $companyId, $row->project_id);
        }

        $row->delete();

        return response()->json([
            'message' => 'Timesheet entry deleted successfully.',
        ]);
    }

    public function submit($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $row = ProjectTimesheet::where('company_id', $companyId)->findOrFail($id);

        if ($row->status !== 'draft' && $row->status !== 'rejected') {
            return response()->json(['message' => 'Only draft or rejected entries can be submitted.'], 422);
        }

        $row->update([
            'status' => 'submitted',
            'updated_by' => auth()->id(),
            'rejection_reason' => null,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);

        return response()->json(['message' => 'Timesheet submitted successfully.']);
    }

    public function approve($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $row = ProjectTimesheet::where('company_id', $companyId)->findOrFail($id);

        if ($row->status !== 'submitted' && $row->status !== 'draft') {
            return response()->json(['message' => 'Only draft or submitted entries can be approved.'], 422);
        }

        $row->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_reason' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'updated_by' => auth()->id(),
        ]);

        $this->syncApprovedTimesheetToProjectCost($row->fresh());

        return response()->json(['message' => 'Timesheet approved successfully.']);
    }

    public function reject(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $row = ProjectTimesheet::where('company_id', $companyId)->findOrFail($id);

        if ($row->status !== 'submitted' && $row->status !== 'draft') {
            return response()->json(['message' => 'Only draft or submitted entries can be rejected.'], 422);
        }

        $data = Validator::make($request->all(), [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ])->validate();

        $row->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $data['rejection_reason'],
            'approved_at' => null,
            'approved_by' => null,
            'updated_by' => auth()->id(),
        ]);

        $this->removeApprovedTimesheetProjectCost($row->id, $companyId, $row->project_id);

        return response()->json(['message' => 'Timesheet rejected successfully.']);
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

    public function lookupEmployees(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = User::query()
            ->when($q !== '', fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn($u) => [
                'id'   => $u->id,
                'text' => $u->name,
            ]);

        return response()->json(['results' => $rows]);
    }

    protected function validateTimesheet(Request $request): array
    {
        return Validator::make($request->all(), [
            'project_id'       => ['required', 'integer', 'exists:projects,id'],
            'task_id'          => ['nullable', 'integer', 'exists:project_tasks,id'],
            'milestone_id'     => ['nullable', 'integer', 'exists:project_milestones,id'],
            'employee_id'      => ['required', 'integer', 'exists:users,id'],
            'entry_date'       => ['required', 'date'],
            'start_time'       => ['nullable', 'date_format:H:i'],
            'end_time'         => ['nullable', 'date_format:H:i'],
            'hours'            => ['nullable', 'numeric', 'min:0.01'],
            'hourly_rate'      => ['nullable', 'numeric', 'min:0'],
            'billable_hours'   => ['nullable', 'numeric', 'min:0'],
            'billing_rate'     => ['nullable', 'numeric', 'min:0'],
            'is_billable'      => ['nullable', 'in:0,1'],
            'status'           => ['required', 'in:draft,submitted,approved,rejected'],
            'description'      => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
            'source_type'      => ['nullable', 'in:manual,timer,import'],
            'source_id'        => ['nullable', 'integer'],
        ])->validate();
    }

    protected function computeAmounts(array $data): array
    {
        $hours = (float) ($data['hours'] ?? 0);

        if ($hours <= 0 && !empty($data['start_time']) && !empty($data['end_time'])) {
            $start = strtotime($data['start_time']);
            $end   = strtotime($data['end_time']);
            if ($end > $start) {
                $hours = round(($end - $start) / 3600, 2);
            }
        }

        if ($hours <= 0) {
            $hours = 0.01;
        }

        $hourlyRate = (float) ($data['hourly_rate'] ?? 0);
        $costAmount = round($hours * $hourlyRate, 2);

        $isBillable = !empty($data['is_billable']);
        $billableHours = $isBillable ? (float) ($data['billable_hours'] ?? $hours) : 0;
        $billingRate = $isBillable ? (float) ($data['billing_rate'] ?? 0) : 0;
        $billableAmount = round($billableHours * $billingRate, 2);

        return [
            'hours'           => round($hours, 2),
            'cost_amount'     => $costAmount,
            'billable_hours'  => round($billableHours, 2),
            'billable_amount' => $billableAmount,
        ];
    }

    protected function syncApprovedTimesheetToProjectCost(ProjectTimesheet $row): void
    {
        ProjectCost::updateOrCreate(
            [
                'company_id'  => $row->company_id,
                'source_type' => 'timesheet',
                'source_id'   => $row->id,
            ],
            [
                'project_id'    => $row->project_id,
                'task_id'       => $row->task_id,
                'milestone_id'  => $row->milestone_id,
                'cost_date'     => $row->entry_date,
                'cost_category' => 'labour',
                'reference_no'  => 'TS-' . $row->id,
                'description'   => $row->description ?: 'Labour cost from approved timesheet',
                'quantity'      => $row->hours,
                'unit_cost'     => $row->hourly_rate,
                'amount'        => $row->cost_amount,
                'currency_code' => 'NGN',
                'status'        => 'posted',
                'notes'         => $row->notes,
                'created_by'    => $row->created_by,
                'updated_by'    => auth()->id(),
            ]
        );

        $this->syncProjectActualCost($row->project_id, $row->company_id);
    }

    protected function removeApprovedTimesheetProjectCost(int $timesheetId, int $companyId, int $projectId): void
    {
        ProjectCost::query()
            ->where('company_id', $companyId)
            ->where('source_type', 'timesheet')
            ->where('source_id', $timesheetId)
            ->delete();

        $this->syncProjectActualCost($projectId, $companyId);
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