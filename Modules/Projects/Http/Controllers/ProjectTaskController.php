<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectTask;

class ProjectTaskController extends Controller
{
    public function index()
    {
        return view('projects.tasks.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $query = ProjectTask::query()
            ->with([
                'project:id,project_code,project_name',
                'parentTask:id,task_name',
                'assignee:id,name',
            ])
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn($q) => $q->where('priority', $request->priority))
            ->when($request->filled('assigned_to'), fn($q) => $q->where('assigned_to', $request->assigned_to))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('start_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('due_date', '<=', $request->date_to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($sub) use ($term) {
                    $sub->where('task_code', 'like', "%{$term}%")
                        ->orWhere('task_name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('project', function ($p) use ($term) {
                            $p->where('project_code', 'like', "%{$term}%")
                              ->orWhere('project_name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('assignee', fn($a) => $a->where('name', 'like', "%{$term}%"));
                });
            });

        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);

        $recordsTotal = (clone $query)->count();

        $rows = $query
            ->orderByRaw("FIELD(status, 'in_progress', 'pending', 'blocked', 'completed', 'cancelled')")
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($row) {
            $statusBadge = match ($row->status) {
                'in_progress' => '<span class="badge bg-primary">IN PROGRESS</span>',
                'blocked'     => '<span class="badge bg-warning text-dark">BLOCKED</span>',
                'completed'   => '<span class="badge bg-success">COMPLETED</span>',
                'cancelled'   => '<span class="badge bg-danger">CANCELLED</span>',
                default       => '<span class="badge bg-secondary">PENDING</span>',
            };

            $priorityBadge = match ($row->priority) {
                'low'      => '<span class="badge bg-light text-dark border">LOW</span>',
                'high'     => '<span class="badge bg-warning text-dark">HIGH</span>',
                'critical' => '<span class="badge bg-danger">CRITICAL</span>',
                default    => '<span class="badge bg-info">MEDIUM</span>',
            };

            $progress = (float) ($row->progress_percent ?? 0);
            $progressBar = '
                <div class="progress" style="height: 16px;">
                    <div class="progress-bar" role="progressbar" style="width: ' . $progress . '%;">
                        ' . number_format($progress, 0) . '%
                    </div>
                </div>
            ';

            $json = [
                'id'               => $row->id,
                'project_id'       => $row->project_id,
                'project_label'    => ($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? ''),
                'parent_task_id'   => $row->parent_task_id,
                'parent_task_label'=> $row->parentTask->task_name ?? null,
                'task_code'        => $row->task_code,
                'task_name'        => $row->task_name,
                'description'      => $row->description,
                'assigned_to'      => $row->assigned_to,
                'assigned_label'   => $row->assignee->name ?? null,
                'status'           => $row->status,
                'priority'         => $row->priority,
                'start_date'       => optional($row->start_date)->format('Y-m-d'),
                'due_date'         => optional($row->due_date)->format('Y-m-d'),
                'estimated_hours'  => (float) $row->estimated_hours,
                'actual_hours'     => (float) $row->actual_hours,
                'progress_percent' => (float) $row->progress_percent,
                'sort_order'       => (int) $row->sort_order,
                'notes'            => $row->notes,
            ];

            $actions = view('projects.tasks.partials.actions', [
                'row'  => $row,
                'json' => $json,
            ])->render();

            return [
                'id'            => $row->id,
                'task_code'     => e($row->task_code ?: '—'),
                'task_name'     => e($row->task_name),
                'project'       => e(($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? '')),
                'parent_task'   => e($row->parentTask->task_name ?? '—'),
                'assignee'      => e($row->assignee->name ?? '—'),
                'start_date'    => optional($row->start_date)->format('d-m-Y') ?: '—',
                'due_date'      => optional($row->due_date)->format('d-m-Y') ?: '—',
                'estimated_hours' => number_format((float) $row->estimated_hours, 2),
                'actual_hours'  => number_format((float) $row->actual_hours, 2),
                'progress'      => $progressBar,
                'priority'      => $priorityBadge,
                'status'        => $statusBadge,
                'actions'       => $actions,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateTask($request);

        $task = ProjectTask::create([
            'company_id'       => $companyId,
            'project_id'       => $data['project_id'],
            'parent_task_id'   => $data['parent_task_id'] ?? null,
            'task_code'        => $data['task_code'] ?: $this->generateTaskCode(),
            'task_name'        => $data['task_name'],
            'description'      => $data['description'] ?? null,
            'assigned_to'      => $data['assigned_to'] ?? null,
            'status'           => $data['status'],
            'priority'         => $data['priority'],
            'start_date'       => $data['start_date'] ?? null,
            'due_date'         => $data['due_date'] ?? null,
            'completed_at'     => $data['status'] === 'completed' ? now() : null,
            'estimated_hours'  => $data['estimated_hours'] ?? 0,
            'actual_hours'     => $data['actual_hours'] ?? 0,
            'progress_percent' => $this->normalizeProgress($data['status'], $data['progress_percent'] ?? 0),
            'sort_order'       => $data['sort_order'] ?? 0,
            'notes'            => $data['notes'] ?? null,
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'id' => $task->id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $task = ProjectTask::where('company_id', $companyId)->findOrFail($id);
        $data = $this->validateTask($request, $task->id);

        $status = $data['status'];
        $completedAt = $status === 'completed'
            ? ($task->completed_at ?: now())
            : null;

        $task->update([
            'project_id'       => $data['project_id'],
            'parent_task_id'   => $data['parent_task_id'] ?? null,
            'task_code'        => $data['task_code'],
            'task_name'        => $data['task_name'],
            'description'      => $data['description'] ?? null,
            'assigned_to'      => $data['assigned_to'] ?? null,
            'status'           => $status,
            'priority'         => $data['priority'],
            'start_date'       => $data['start_date'] ?? null,
            'due_date'         => $data['due_date'] ?? null,
            'completed_at'     => $completedAt,
            'estimated_hours'  => $data['estimated_hours'] ?? 0,
            'actual_hours'     => $data['actual_hours'] ?? 0,
            'progress_percent' => $this->normalizeProgress($status, $data['progress_percent'] ?? 0),
            'sort_order'       => $data['sort_order'] ?? 0,
            'notes'            => $data['notes'] ?? null,
            'updated_by'       => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Task updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $task = ProjectTask::where('company_id', $companyId)->findOrFail($id);
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
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

    public function lookupParentTasks(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));
        $projectId = $request->get('project_id');

        $rows = ProjectTask::query()
            ->where('company_id', $companyId)
            ->when($projectId, fn($query) => $query->where('project_id', $projectId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('task_code', 'like', "%{$q}%")
                        ->orWhere('task_name', 'like', "%{$q}%");
                });
            })
            ->orderBy('task_name')
            ->limit(30)
            ->get()
            ->map(fn($t) => [
                'id'   => $t->id,
                'text' => trim(($t->task_code ?? '') . ' - ' . ($t->task_name ?? '')),
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

    protected function validateTask(Request $request, ?int $id = null): array
    {
        $data = Validator::make($request->all(), [
            'project_id'       => ['required', 'integer', 'exists:projects,id'],
            'parent_task_id'   => ['nullable', 'integer', 'exists:project_tasks,id'],
            'task_code'        => ['nullable', 'string', 'max:50', 'unique:project_tasks,task_code,' . ($id ?: 'NULL') . ',id,deleted_at,NULL'],
            'task_name'        => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'assigned_to'      => ['nullable', 'integer', 'exists:users,id'],
            'status'           => ['required', 'in:pending,in_progress,blocked,completed,cancelled'],
            'priority'         => ['required', 'in:low,medium,high,critical'],
            'start_date'       => ['nullable', 'date'],
            'due_date'         => ['nullable', 'date'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0'],
            'actual_hours'     => ['nullable', 'numeric', 'min:0'],
            'progress_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'notes'            => ['nullable', 'string'],
        ])->validate();

        if (!empty($data['parent_task_id']) && $id && (int)$data['parent_task_id'] === (int)$id) {
            abort(response()->json(['message' => 'A task cannot be its own parent task.'], 422));
        }

        return $data;
    }

    protected function generateTaskCode(): string
    {
        $nextId = (ProjectTask::max('id') ?? 0) + 1;
        return 'TSK-' . now()->format('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    protected function normalizeProgress(string $status, $progress): float
    {
        $progress = max(0, min(100, (float) $progress));

        if ($status === 'completed') return 100;
        if ($status === 'pending' && $progress > 0) return $progress;
        if ($status === 'cancelled') return $progress;

        return $progress;
    }
}