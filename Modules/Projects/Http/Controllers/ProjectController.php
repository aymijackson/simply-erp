<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Projects\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        return view('projects.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $query = Project::query()
            ->with(['client:id,name', 'projectManager:id,name'])
            ->where('company_id', $companyId)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn($q) => $q->where('priority', $request->priority))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('start_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('start_date', '<=', $request->date_to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($sub) use ($term) {
                    $sub->where('project_code', 'like', "%{$term}%")
                        ->orWhere('project_name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('projectManager', fn($m) => $m->where('name', 'like', "%{$term}%"));
                });
            });

        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);

        $recordsTotal = (clone $query)->count();

        $rows = $query
            ->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($row) {
            $statusBadge = match ($row->status) {
                'planned'   => '<span class="badge bg-info">PLANNED</span>',
                'active'    => '<span class="badge bg-success">ACTIVE</span>',
                'on_hold'   => '<span class="badge bg-warning text-dark">ON HOLD</span>',
                'completed' => '<span class="badge bg-primary">COMPLETED</span>',
                'cancelled' => '<span class="badge bg-danger">CANCELLED</span>',
                default     => '<span class="badge bg-secondary">DRAFT</span>',
            };

            $priorityBadge = match ($row->priority) {
                'low'      => '<span class="badge bg-light text-dark border">LOW</span>',
                'high'     => '<span class="badge bg-warning text-dark">HIGH</span>',
                'critical' => '<span class="badge bg-danger">CRITICAL</span>',
                default    => '<span class="badge bg-info">MEDIUM</span>',
            };

            $json = [
                'id'                 => $row->id,
                'project_code'       => $row->project_code,
                'project_name'       => $row->project_name,
                'client_id'          => $row->client_id,
                'client_label'       => $row->client->name ?? null,
                'project_manager_id' => $row->project_manager_id,
                'project_manager_label' => $row->projectManager->name ?? null,
                'status'             => $row->status,
                'priority'           => $row->priority,
                'start_date'         => optional($row->start_date)->format('Y-m-d'),
                'end_date'           => optional($row->end_date)->format('Y-m-d'),
                'budget'             => (float) $row->budget,
                'actual_cost'        => (float) $row->actual_cost,
                'description'        => $row->description,
                'notes'              => $row->notes,
            ];

            $actions = view('projects.partials.actions', [
                'row'  => $row,
                'json' => $json,
            ])->render();

            return [
                'id'             => $row->id,
                'project_code'   => e($row->project_code),
                'project_name'   => e($row->project_name),
                'client'         => e($row->client->name ?? '—'),
                'project_manager'=> e($row->projectManager->name ?? '—'),
                'start_date'     => optional($row->start_date)->format('d-m-Y') ?: '—',
                'end_date'       => optional($row->end_date)->format('d-m-Y') ?: '—',
                'budget'         => number_format((float) $row->budget, 2),
                'actual_cost'    => number_format((float) $row->actual_cost, 2),
                'priority'       => $priorityBadge,
                'status'         => $statusBadge,
                'actions'        => $actions,
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

        $data = $this->validateProject($request);

        $project = Project::create([
            'company_id'         => $companyId,
            'project_code'       => $data['project_code'] ?: $this->generateProjectCode(),
            'project_name'       => $data['project_name'],
            'client_id'          => $data['client_id'] ?? null,
            'project_manager_id' => $data['project_manager_id'] ?? null,
            'status'             => $data['status'],
            'priority'           => $data['priority'],
            'start_date'         => $data['start_date'] ?? null,
            'end_date'           => $data['end_date'] ?? null,
            'budget'             => $data['budget'] ?? 0,
            'actual_cost'        => $data['actual_cost'] ?? 0,
            'description'        => $data['description'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'created_by'         => auth()->id(),
            'updated_by'         => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'id'      => $project->id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $project = Project::where('company_id', $companyId)->findOrFail($id);

        $data = $this->validateProject($request, $project->id);

        $project->update([
            'project_code'       => $data['project_code'],
            'project_name'       => $data['project_name'],
            'client_id'          => $data['client_id'] ?? null,
            'project_manager_id' => $data['project_manager_id'] ?? null,
            'status'             => $data['status'],
            'priority'           => $data['priority'],
            'start_date'         => $data['start_date'] ?? null,
            'end_date'           => $data['end_date'] ?? null,
            'budget'             => $data['budget'] ?? 0,
            'actual_cost'        => $data['actual_cost'] ?? 0,
            'description'        => $data['description'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'updated_by'         => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Project updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $project = Project::where('company_id', $companyId)->findOrFail($id);
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    public function lookupClients(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = Customer::query()
            ->when($q !== '', fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'text' => $c->name,
            ]);

        return response()->json(['results' => $rows]);
    }

    public function lookupManagers(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = User::query()
            ->when($q !== '', fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'text' => $u->name,
            ]);

        return response()->json(['results' => $rows]);
    }

    protected function validateProject(Request $request, ?int $id = null): array
    {
        return Validator::make($request->all(), [
            'project_code'       => ['nullable', 'string', 'max:50', 'unique:projects,project_code,' . ($id ?: 'NULL') . ',id,deleted_at,NULL'],
            'project_name'       => ['required', 'string', 'max:255'],
            'client_id'          => ['nullable', 'integer', 'exists:customers,id'],
            'project_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'status'             => ['required', 'in:draft,planned,active,on_hold,completed,cancelled'],
            'priority'           => ['required', 'in:low,medium,high,critical'],
            'start_date'         => ['nullable', 'date'],
            'end_date'           => ['nullable', 'date'],
            'budget'             => ['nullable', 'numeric', 'min:0'],
            'actual_cost'        => ['nullable', 'numeric', 'min:0'],
            'description'        => ['nullable', 'string'],
            'notes'              => ['nullable', 'string'],
        ])->validate();
    }

    protected function generateProjectCode(): string
    {
        $nextId = (Project::max('id') ?? 0) + 1;
        return 'PRJ-' . now()->format('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}