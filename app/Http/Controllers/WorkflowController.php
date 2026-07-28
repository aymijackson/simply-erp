<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkflowController extends Controller
{
    public function index()
    {
        return view('workflows.index');
    }

    public function datatable(Request $request)
    {
        $q = Workflow::query();

        if ($request->filled('module')) {
            $q->where('module', $request->module);
        }

        if ($request->filled('trigger_event')) {
            $q->where('trigger_event', 'like', '%' . trim($request->trigger_event) . '%');
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $q->where('is_active', 1);
            } elseif ($request->status === 'inactive') {
                $q->where('is_active', 0);
            }
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $q->where(function ($x) use ($term) {
                $x->where('name', 'like', "%{$term}%")
                  ->orWhere('module', 'like', "%{$term}%")
                  ->orWhere('trigger_event', 'like', "%{$term}%");
            });
        }

        $draw = (int) ($request->draw ?? 1);
        $start = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);

        $recordsTotal = (clone $q)->count();

        $rows = $q->withCount('steps')
            ->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($w) {
            $statusBadge = $w->is_active
                ? '<span class="badge bg-success">ACTIVE</span>'
                : '<span class="badge bg-secondary">INACTIVE</span>';

            $json = [
                'id' => $w->id,
                'name' => $w->name,
                'module' => $w->module,
                'trigger_event' => $w->trigger_event,
                'is_active' => (int) $w->is_active,
                'steps_count' => (int) ($w->steps_count ?? 0),
                'created_at' => optional($w->created_at)->format('Y-m-d H:i:s'),
            ];

            $actions = view('workflows.partials.actions', [
                'w' => $w,
                'json' => $json,
            ])->render();

            return [
                'check' => '<input type="checkbox" class="row-check" value="'.$w->id.'">',
                'id' => $w->id,
                'name' => e($w->name),
                'module' => e($w->module),
                'trigger_event' => e($w->trigger_event),
                'steps_count' => (int) ($w->steps_count ?? 0),
                'status' => $statusBadge,
                'created_at' => optional($w->created_at)->format('Y-m-d H:i'),
                'actions' => $actions,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $workflow = Workflow::with(['steps' => function ($q) {
            $q->orderBy('step_order');
        }])->findOrFail((int) $id);

        return response()->json([
            'id' => $workflow->id,
            'name' => $workflow->name,
            'module' => $workflow->module,
            'trigger_event' => $workflow->trigger_event,
            'is_active' => (int) $workflow->is_active,
            'created_at' => optional($workflow->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($workflow->updated_at)->format('Y-m-d H:i:s'),
            'steps' => $workflow->steps->map(function ($s) {
                return [
                    'id' => $s->id,
                    'step_order' => $s->step_order,
                    'action_type' => $s->action_type,
                    'action_target' => $s->action_target,
                    'action_value' => $s->action_value,
                    'delay_minutes' => $s->delay_minutes,
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateWorkflow($request);

        return DB::transaction(function () use ($data) {
            $workflow = Workflow::create([
                'name' => $data['name'],
                'module' => $data['module'],
                'trigger_event' => $data['trigger_event'],
                'is_active' => (int) ($data['is_active'] ?? 1),
            ]);

            $this->syncSteps($workflow->id, $data['steps'] ?? []);

            return response()->json([
                'message' => 'Workflow created successfully.',
                'id' => $workflow->id,
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $workflow = Workflow::findOrFail((int) $id);
        $data = $this->validateWorkflow($request);

        return DB::transaction(function () use ($workflow, $data) {
            $workflow->update([
                'name' => $data['name'],
                'module' => $data['module'],
                'trigger_event' => $data['trigger_event'],
                'is_active' => (int) ($data['is_active'] ?? 1),
            ]);

            $this->syncSteps($workflow->id, $data['steps'] ?? []);

            return response()->json(['message' => 'Workflow updated successfully.']);
        });
    }

    public function destroy($id)
    {
        $workflow = Workflow::findOrFail((int) $id);

        DB::transaction(function () use ($workflow) {
            WorkflowStep::where('workflow_id', $workflow->id)->delete();
            WorkflowLog::where('workflow_id', $workflow->id)->delete();
            $workflow->delete();
        });

        return response()->json(['message' => 'Workflow deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $data = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])->validate();

        DB::transaction(function () use ($data) {
            WorkflowStep::whereIn('workflow_id', $data['ids'])->delete();
            WorkflowLog::whereIn('workflow_id', $data['ids'])->delete();
            Workflow::whereIn('id', $data['ids'])->delete();
        });

        return response()->json(['message' => 'Selected workflows deleted successfully.']);
    }

    public function toggle($id)
    {
        $workflow = Workflow::findOrFail((int) $id);

        $workflow->update([
            'is_active' => $workflow->is_active ? 0 : 1,
        ]);

        return response()->json([
            'message' => $workflow->is_active ? 'Workflow activated.' : 'Workflow deactivated.'
        ]);
    }

    public function logs(Request $request, $id)
    {
        $workflow = Workflow::findOrFail((int) $id);

        $logs = WorkflowLog::where('workflow_id', $workflow->id)
            ->orderByDesc('id')
            ->limit((int)($request->limit ?? 50))
            ->get();

        return response()->json([
            'workflow' => [
                'id' => $workflow->id,
                'name' => $workflow->name,
            ],
            'logs' => $logs->map(function ($l) {
                return [
                    'id' => $l->id,
                    'reference_type' => $l->reference_type,
                    'reference_id' => $l->reference_id,
                    'status' => $l->status,
                    'message' => $l->message,
                    'created_at' => optional($l->created_at)->format('Y-m-d H:i:s'),
                ];
            })->values(),
        ]);
    }

    protected function validateWorkflow(Request $request): array
    {
        return Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'module' => ['required', 'string', 'max:100'],
            'trigger_event' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'in:0,1'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
            'steps.*.action_type' => ['required', 'string', 'max:50'],
            'steps.*.action_target' => ['nullable', 'string', 'max:100'],
            'steps.*.action_value' => ['nullable', 'string'],
            'steps.*.delay_minutes' => ['nullable', 'integer', 'min:0'],
        ])->validate();
    }

    protected function syncSteps(int $workflowId, array $steps): void
    {
        WorkflowStep::where('workflow_id', $workflowId)->delete();

        $rows = [];
        foreach ($steps as $step) {
            $rows[] = [
                'workflow_id' => $workflowId,
                'step_order' => (int) $step['step_order'],
                'action_type' => $step['action_type'],
                'action_target' => $step['action_target'] ?? null,
                'action_value' => $step['action_value'] ?? null,
                'delay_minutes' => (int) ($step['delay_minutes'] ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            WorkflowStep::insert($rows);
        }
    }
}