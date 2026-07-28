<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMilestone;

class ProjectMilestoneController extends Controller
{
    public function index()
    {
        return view('projects.milestones.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $query = ProjectMilestone::query()
            ->with([
                'project:id,project_code,project_name',
                'owner:id,name',
            ])
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn($q) => $q->where('priority', $request->priority))
            ->when($request->filled('owner_id'), fn($q) => $q->where('owner_id', $request->owner_id))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('target_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('target_date', '<=', $request->date_to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($sub) use ($term) {
                    $sub->where('milestone_code', 'like', "%{$term}%")
                        ->orWhere('milestone_name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('project', function ($p) use ($term) {
                            $p->where('project_code', 'like', "%{$term}%")
                              ->orWhere('project_name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('owner', fn($o) => $o->where('name', 'like', "%{$term}%"));
                });
            });

        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);

        $recordsTotal = (clone $query)->count();

        $rows = $query
            ->orderByRaw("FIELD(status, 'in_progress', 'pending', 'completed', 'cancelled')")
            ->orderBy('target_date')
            ->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($row) {
            $statusBadge = match ($row->status) {
                'in_progress' => '<span class="badge bg-primary">IN PROGRESS</span>',
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
                'milestone_code'   => $row->milestone_code,
                'milestone_name'   => $row->milestone_name,
                'description'      => $row->description,
                'owner_id'         => $row->owner_id,
                'owner_label'      => $row->owner->name ?? null,
                'status'           => $row->status,
                'priority'         => $row->priority,
                'target_date'      => optional($row->target_date)->format('Y-m-d'),
                'progress_percent' => (float) $row->progress_percent,
                'weight_percent'   => (float) $row->weight_percent,
                'sort_order'       => (int) $row->sort_order,
                'notes'            => $row->notes,
            ];

            $actions = view('projects.milestones.partials.actions', [
                'row'  => $row,
                'json' => $json,
            ])->render();

            return [
                'id'              => $row->id,
                'milestone_code'  => e($row->milestone_code ?: '—'),
                'milestone_name'  => e($row->milestone_name),
                'project'         => e(($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? '')),
                'owner'           => e($row->owner->name ?? '—'),
                'target_date'     => optional($row->target_date)->format('d-m-Y') ?: '—',
                'weight_percent'  => number_format((float) $row->weight_percent, 2),
                'progress'        => $progressBar,
                'priority'        => $priorityBadge,
                'status'          => $statusBadge,
                'actions'         => $actions,
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
        $data = $this->validateMilestone($request);

        $milestone = ProjectMilestone::create([
            'company_id'       => $companyId,
            'project_id'       => $data['project_id'],
            'milestone_code'   => $data['milestone_code'] ?: $this->generateMilestoneCode(),
            'milestone_name'   => $data['milestone_name'],
            'description'      => $data['description'] ?? null,
            'owner_id'         => $data['owner_id'] ?? null,
            'status'           => $data['status'],
            'priority'         => $data['priority'],
            'target_date'      => $data['target_date'] ?? null,
            'completed_at'     => $data['status'] === 'completed' ? now() : null,
            'progress_percent' => $this->normalizeProgress($data['status'], $data['progress_percent'] ?? 0),
            'weight_percent'   => $data['weight_percent'] ?? 0,
            'sort_order'       => $data['sort_order'] ?? 0,
            'notes'            => $data['notes'] ?? null,
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Milestone created successfully.',
            'id' => $milestone->id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $milestone = ProjectMilestone::where('company_id', $companyId)->findOrFail($id);
        $data = $this->validateMilestone($request, $milestone->id);

        $status = $data['status'];
        $completedAt = $status === 'completed'
            ? ($milestone->completed_at ?: now())
            : null;

        $milestone->update([
            'project_id'       => $data['project_id'],
            'milestone_code'   => $data['milestone_code'],
            'milestone_name'   => $data['milestone_name'],
            'description'      => $data['description'] ?? null,
            'owner_id'         => $data['owner_id'] ?? null,
            'status'           => $status,
            'priority'         => $data['priority'],
            'target_date'      => $data['target_date'] ?? null,
            'completed_at'     => $completedAt,
            'progress_percent' => $this->normalizeProgress($status, $data['progress_percent'] ?? 0),
            'weight_percent'   => $data['weight_percent'] ?? 0,
            'sort_order'       => $data['sort_order'] ?? 0,
            'notes'            => $data['notes'] ?? null,
            'updated_by'       => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Milestone updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $milestone = ProjectMilestone::where('company_id', $companyId)->findOrFail($id);
        $milestone->delete();

        return response()->json([
            'message' => 'Milestone deleted successfully.',
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

    public function lookupOwners(Request $request)
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

    protected function validateMilestone(Request $request, ?int $id = null): array
    {
        return Validator::make($request->all(), [
            'project_id'       => ['required', 'integer', 'exists:projects,id'],
            'milestone_code'   => ['nullable', 'string', 'max:50', 'unique:project_milestones,milestone_code,' . ($id ?: 'NULL') . ',id,deleted_at,NULL'],
            'milestone_name'   => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'owner_id'         => ['nullable', 'integer', 'exists:users,id'],
            'status'           => ['required', 'in:pending,in_progress,completed,cancelled'],
            'priority'         => ['required', 'in:low,medium,high,critical'],
            'target_date'      => ['nullable', 'date'],
            'progress_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'weight_percent'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'notes'            => ['nullable', 'string'],
        ])->validate();
    }

    protected function generateMilestoneCode(): string
    {
        $nextId = (ProjectMilestone::max('id') ?? 0) + 1;
        return 'MS-' . now()->format('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    protected function normalizeProgress(string $status, $progress): float
    {
        $progress = max(0, min(100, (float) $progress));

        if ($status === 'completed') return 100;
        return $progress;
    }
}